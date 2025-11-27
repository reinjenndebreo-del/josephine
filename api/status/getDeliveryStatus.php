<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:8000");
header("Access-Control-Allow-Credentials: true");

require_once("../../db/config.php");

try {
  $data = json_decode(file_get_contents("php://input"), true);
  $username = $data['username'] ?? null;

  if (!$username) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Username required"]);
    exit;
  }

  $stmt = $connection->prepare("SELECT user_id FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();

  if (!$user) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
  }

  $user_id = $user['user_id'];

  $sql = "SELECT delivery_status AS status FROM payments WHERE user_id = ? ORDER BY payment_id DESC LIMIT 1";
  $stmt = $connection->prepare($sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();

  $status = $result['status'] ?? 'pending';

  echo json_encode(["status" => $status]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
