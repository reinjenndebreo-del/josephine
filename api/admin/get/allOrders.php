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

    // JOIN orders with users and payments
    $sql = "
        SELECT 
            o.order_id,
            u.username AS user_name,
            u.email AS user_email,
            o.product_name,
            o.quantity,
            o.price,
            o.customize,
            p.payment_id,
            p.payment_status,
            o.order_date
        FROM orderDetails o
        JOIN users u ON o.user_id = u.user_id
        LEFT JOIN payments p ON o.payment_id = p.payment_id
        ORDER BY o.order_date DESC
    ";

    $stmt = $connection->prepare($sql);
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
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
