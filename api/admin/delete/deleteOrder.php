<?php
session_start();
header("Content-Type: application/json");

require_once("../../../db/config.php");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['order_id'])) {
    echo json_encode(["status" => "error", "message" => "No order ID provided"]);
    exit;
}

$orderId = intval($data['order_id']);

if (!isset($_SESSION['user']) || empty($_SESSION['user']['isAdmin'])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Admins only"]);
    exit;
}

try {
    $stmt = $connection->prepare("DELETE FROM orderDetails WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Order deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Order not found"]);
    }

    $stmt->close();
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
