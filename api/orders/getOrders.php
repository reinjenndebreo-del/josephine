<?php
session_start();
header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Make sure user is logged in
if (!isset($_SESSION['user'])) {
    sendResponse(false, "Not logged in", null, 401);
}

$user_id = $_SESSION['user']['id'];

// Connect to the database
require_once("../../db/config.php");

try {
    
    $stmt = $connection->prepare("
        SELECT 
            od.order_id,
            od.product_name,
            od.quantity,
            od.price,
            od.order_date,
            p.payment_status,
            p.delivery_status,
            p.delivery_type,
            p.delivery_address
        FROM orderdetails od
        LEFT JOIN payments p ON od.payment_id = p.payment_id
        WHERE od.user_id = ? 
        ORDER BY od.order_date DESC
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = [
            'order_id' => $row['order_id'],
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'order_date' => $row['order_date'],
            'payment_status' => $row['payment_status'],
            'delivery_status' => $row['delivery_status'],
            'delivery_type' => $row['delivery_type'],
            'delivery_address' => $row['delivery_address']
        ];
    }

    sendResponse(true, "Orders retrieved successfully", $orders);

} catch (Exception $e) {
    // Log error internally, don't expose details to client
    error_log("Database error in get_orders.php: " . $e->getMessage());
    sendResponse(false, "An error occurred while retrieving orders", null, 500);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $connection->close();
}
?>