<?php
session_start();
header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, "Invalid request method", null, 405);
}

// Make sure user is logged in
if (!isset($_SESSION['user'])) {
    sendResponse(false, "Not logged in", null, 401);
}

$user_id = $_SESSION['user']['id'];

// Get request data
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['order_id']) || empty($data['order_id'])) {
    sendResponse(false, "Order ID is required", null, 400);
}

$order_id = intval($data['order_id']);

// Connect to the database
require_once("../../db/config.php");

try {
    // First, verify that the order belongs to the logged-in user
    $stmt = $connection->prepare("
        SELECT od.order_id, od.payment_id, p.delivery_status, p.payment_status
        FROM orderdetails od
        LEFT JOIN payments p ON od.payment_id = p.payment_id
        WHERE od.order_id = ? AND od.user_id = ?
    ");
    
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse(false, "Order not found or you don't have permission to cancel it", null, 404);
    }

    $order = $result->fetch_assoc();
    $payment_id = $order['payment_id'];
    $delivery_status = strtolower($order['delivery_status'] ?? '');
    $payment_status = strtolower($order['payment_status'] ?? '');

    // Check if order can be cancelled
    // Orders can't be cancelled if they are already delivered or cancelled
    if ($delivery_status === 'delivered' || $delivery_status === 'completed') {
        sendResponse(false, "Cannot cancel an order that has already been delivered", null, 400);
    }

    if ($delivery_status === 'cancelled') {
        sendResponse(false, "This order is already cancelled", null, 400);
    }

    $stmt->close();

    // Update the delivery status to "cancelled"
    $stmt = $connection->prepare("UPDATE payments SET delivery_status = 'cancelled' WHERE payment_id = ?");
    $stmt->bind_param("i", $payment_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            sendResponse(true, "Order cancelled successfully");
        } else {
            sendResponse(false, "Failed to cancel order", null, 500);
        }
    } else {
        sendResponse(false, "Database error: " . $connection->error, null, 500);
    }

} catch (Exception $e) {
    // Log error internally, don't expose details to client
    error_log("Database error in cancelOrder.php: " . $e->getMessage());
    sendResponse(false, "An error occurred while cancelling the order", null, 500);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $connection->close();
}
?>
