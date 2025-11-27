<?php
session_start();
header("Content-Type: application/json");

require_once("../../../db/config.php");

$data = json_decode(file_get_contents("php://input"), true);

$payment_id = intval($data['payment_id'] ?? 0);
$status = $data['payment_status'] ?? '';


// print all received data
 error_log(print_r($data, true));


 // convert to lowercase the sattus to make it case insensitive
$status = strtolower($status);
$allowed = ['pending', 'verified', 'rejected'];
if (!in_array($status, $allowed)) {
    echo json_encode(["status" => "error", "message" => "Invalid status"]);
    exit;
}

$stmt = $connection->prepare("UPDATE payments SET payment_status = ? WHERE payment_id = ?");
if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => $connection->error]);
    exit;
}
$stmt->bind_param("si", $status, $payment_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $connection->error]);
}
$stmt->close();
?>
