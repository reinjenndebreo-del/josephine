<?php
session_start();
header("Content-Type: application/json");

require_once("../../../db/config.php"); // adjust path if needed

$data = json_decode(file_get_contents("php://input"), true);

$reservation_id = intval($data['reservation_id'] ?? 0);
$status = $data['status'] ?? '';

$status = strtolower($status);
$allowed = ['pending', 'approved', 'cancelled', 'completed'];

if (!in_array($status, $allowed)) {
    echo json_encode(["status" => "error", "message" => "Invalid reservation status"]);
    exit;
}

$stmt = $connection->prepare("UPDATE reservationDetails SET status = ? WHERE reservation_id = ?");
$stmt->bind_param("si", $status, $reservation_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Reservation status updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => $connection->error]);
}

$stmt->close();
$connection->close();
?>
