<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:8000");
header("Access-Control-Allow-Credentials: true");

require_once("../../../db/config.php");

try {
    if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Admins only"]);
        exit;
    }

    // JOIN reservations with users
    $sql = "
        SELECT 
            r.reservation_id,
            u.username AS user_name,
            u.email AS user_email,
            r.reservation_date,
            r.reservation_time,
            r.number_of_people,
            r.status,
            r.created_at
        FROM reservationDetails r
        JOIN users u ON r.user_id = u.user_id
        ORDER BY r.created_at DESC
    ";

    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $reservations
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
