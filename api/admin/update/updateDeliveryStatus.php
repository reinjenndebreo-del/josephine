<?php
session_start();
header("Content-Type: application/json");

require_once("../../../db/config.php");

$data = json_decode(file_get_contents("php://input"), true);

$payment_id = intval($data['payment_id'] ?? 0);
$status = $data['delivery_status'] ?? '';

// for debugging (optional)
// error_log(print_r($data, true));

$status = strtolower($status);
$allowed = ['pending', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];

if (!in_array($status, $allowed)) {
    echo json_encode(["status" => "error", "message" => "Invalid delivery status"]);
    exit;
}

$stmt = $connection->prepare("UPDATE payments SET delivery_status = ? WHERE payment_id = ?");
$stmt->bind_param("si", $status, $payment_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $connection->error]);
}

$stmt->close();
?>
