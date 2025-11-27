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

    // JOIN payments with users
$sql = "
    SELECT 
        p.payment_id,
        u.username AS user_name,
        u.email AS user_email,
        p.reference_number,
        p.screenshot_path,
        p.total_amount,
        p.payment_status,
        p.mop,
        p.delivery_type,
        p.delivery_address,
        p.delivery_status,
        p.payment_date
    FROM payments p
    JOIN users u ON p.user_id = u.user_id
    ORDER BY p.payment_date DESC
";


    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $payments
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
