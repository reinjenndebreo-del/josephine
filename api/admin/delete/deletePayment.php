<?php
session_start();
header("Content-Type: application/json");

require_once("../../../db/config.php");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['payment_id'])) {
    echo json_encode(["status" => "error", "message" => "No payment ID provided"]);
    exit;
}

$paymentId = intval($data['payment_id']);

if (!isset($_SESSION['user']) || empty($_SESSION['user']['isAdmin'])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Admins only"]);
    exit;
}

try {
    $stmt = $connection->prepare("DELETE FROM payments WHERE payment_id = ?");
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Payment deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Payment not found"]);
    }

    $stmt->close();
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
