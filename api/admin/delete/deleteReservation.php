<?php
session_start();
header("Content-Type: application/json");

require_once("../../../db/config.php");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['reservation_id'])) {
    echo json_encode(["status" => "error", "message" => "No reservation ID provided"]);
    exit;
}

$reservationId = intval($data['reservation_id']);

// Check admin session
if (!isset($_SESSION['user']) || empty($_SESSION['user']['isAdmin'])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Admins only"]);
    exit;
}

try {
    $stmt = $connection->prepare("DELETE FROM reservationDetails WHERE reservation_id = ?");
    $stmt->bind_param("i", $reservationId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Reservation deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Reservation not found"]);
    }

    $stmt->close();
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
