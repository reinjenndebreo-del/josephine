<?php
session_start();
header("Content-Type: application/json");

require_once("../../../db/config.php");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'])) {
    echo json_encode(["status" => "error", "message" => "No user ID provided"]);
    exit;
}

$userId = intval($data['user_id']);

// Check admin session
if (!isset($_SESSION['user']) || empty($_SESSION['user']['isAdmin'])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Admins only"]);
    exit;
}

try {
    $stmt = $connection->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "User deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }

    $stmt->close();
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
