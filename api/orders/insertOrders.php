<?php
header("Content-Type: application/json");
session_start();

require_once("../../db/config.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["message" => "Unauthorized. Please login first."]);
  exit();
}

$userId = $_SESSION['user_id'];

  // Verify the user exists in the database to avoid foreign key constraint failures
  $stmt_check_user = $connection->prepare("SELECT user_id FROM users WHERE user_id = ?");
  $stmt_check_user->bind_param("i", $userId);
  $stmt_check_user->execute();
  $result_check_user = $stmt_check_user->get_result();
  $stmt_check_user->close();

  if ($result_check_user->num_rows === 0) {
    throw new Exception("Invalid user session. Please log in again before placing an order.");
  }
try {
  // Start transaction
  $connection->begin_transaction();

  // Get payment method
  $mop = isset($_POST['mop']) ? $_POST['mop'] : 'Payonline';
  
  // Validate based on payment method
  if ($mop === 'Payonline') {
    // GCash validation
    if (!isset($_POST['reference_number']) || empty(trim($_POST['reference_number']))) {
      throw new Exception("Reference number is required");
    }

    if (!isset($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
      throw new Exception("Payment screenshot is required");
    }
  }

  if (!isset($_POST['cart']) || empty($_POST['cart'])) {
    throw new Exception("Cart is empty");
  }

  if (!isset($_POST['total_amount']) || empty($_POST['total_amount'])) {
    throw new Exception("Total amount is required");
  }

  // Get form data
  $reference_number = isset($_POST['reference_number']) ? trim($_POST['reference_number']) : '';
  $total_amount = floatval($_POST['total_amount']);
  
  $delivery_type = isset($_POST['delivery_type']) && !empty($_POST['delivery_type']) ? $_POST['delivery_type'] : 'pickup';
  $delivery_address = null;
  
  // Validate and set delivery address only if delivery type is 'delivery'
  if ($delivery_type === 'delivery') {
    if (!isset($_POST['delivery_address']) || empty(trim($_POST['delivery_address']))) {
      throw new Exception("Delivery address is required for delivery orders");
    }
    $delivery_address = trim($_POST['delivery_address']);
  }
  
  $cart = json_decode($_POST['cart'], true);

  if (!$cart || !is_array($cart)) {
    throw new Exception("Invalid cart data");
  }

  // Handle file upload (only for GCash)
  $screenshot_db_path = null;
  
  if ($mop === 'Payonline') {
    $screenshot = $_FILES['screenshot'];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Validate file type by extension
    $file_extension = strtolower(pathinfo($screenshot['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
      throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
    }

    // Validate file size
    if ($screenshot['size'] > $max_size) {
      throw new Exception("File size too large. Maximum 5MB allowed.");
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = "../../uploads/payments/";
    if (!file_exists($upload_dir)) {
      mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $filename = 'payment_' . $userId . '_' . time() . '_' . uniqid() . '.' . $file_extension;
    $screenshot_path = $upload_dir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($screenshot['tmp_name'], $screenshot_path)) {
      throw new Exception("Failed to upload screenshot");
    }

    // Store relative path in database
    $screenshot_db_path = "uploads/payments/" . $filename;
  } else {
    // For COD, use a placeholder or empty string
    $screenshot_db_path = "COD";
  }

  // Insert payment record
  $stmt_payment = $connection->prepare("
    INSERT INTO payments (user_id, reference_number, screenshot_path, total_amount, delivery_type, delivery_address, payment_status, mop) 
    VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)
  ");
  $stmt_payment->bind_param("issdsss", $userId, $reference_number, $screenshot_db_path, $total_amount, $delivery_type, $delivery_address, $mop);
  
  if (!$stmt_payment->execute()) {
    throw new Exception("Failed to insert payment record: " . $stmt_payment->error);
  }

  $payment_id = $connection->insert_id;
  $stmt_payment->close();

  // Insert order details
  $stmt_order = $connection->prepare("
    INSERT INTO orderDetails (payment_id, user_id, product_name, quantity, price, customize) 
    VALUES (?, ?, ?, ?, ?, ?)
  ");

  foreach ($cart as $item) {
    $name = $item['name'];
    $price = (float)$item['price'];
    $quantity = (int)$item['quantity'];
    $customize = isset($item['customize']) ? $item['customize'] : null;

    $stmt_order->bind_param("iisids", $payment_id, $userId, $name, $quantity, $price, $customize);
    
    if (!$stmt_order->execute()) {
      throw new Exception("Failed to insert order item: " . $stmt_order->error);
    }
  }
  $stmt_order->close();

  // Commit transaction
  $connection->commit();

  $message = $mop === 'COD' 
    ? "Your COD order has been placed successfully! We will contact you shortly to confirm your order."
    : "Order successfully placed! Your payment is being verified.";

  echo json_encode([
    "success" => true,
    "message" => $message,
    "payment_id" => $payment_id,
    "delivery_type" => $delivery_type,
    "payment_method" => $mop,
    "items_count" => count($cart)
  ]);

} catch (Exception $e) {
  // Rollback transaction on error
  $connection->rollback();

  // Delete uploaded file if exists (only for GCash)
  if (isset($screenshot_path) && file_exists($screenshot_path)) {
    unlink($screenshot_path);
  }

  http_response_code(400);
  echo json_encode([
    "success" => false,
    "message" => $e->getMessage()
  ]);
}

$connection->close();
?>