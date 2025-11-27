<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:8000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once("../../../db/config.php");

try {
    if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "message" => "Access denied. Admins only."
        ]);
        exit;
    }

    $stmt = $connection->prepare("SELECT * FROM users");
    if (!$stmt) {
        throw new Exception("DB prepare failed: " . $connection->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $orders
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
