<?php
session_start();
header("Content-Type: application/json");

// Only allow admins
require_once("../../../db/config.php");

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['isAdmin']) || !$_SESSION['user']['isAdmin']) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Admins only"]);
    exit;
}

$month = isset($_GET['month']) ? $_GET['month'] : null; // expected format YYYY-MM
$top = isset($_GET['top']) ? (int) $_GET['top'] : 5;

if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid or missing 'month' parameter. Use YYYY-MM"]);
    exit;
}

try {
    // Accept records where the payment is verified OR the delivery_status indicates completion.
    // Use case-insensitive matching and normalize underscores to spaces.

    // Daily records: group by date and product
    $dailySql = "
        SELECT DATE(o.order_date) AS date, o.product_name, SUM(o.quantity) AS quantity, SUM(o.price * o.quantity) AS total
        FROM orderDetails o
        LEFT JOIN payments p ON o.payment_id = p.payment_id
        WHERE DATE_FORMAT(o.order_date, '%Y-%m') = ?
          AND (
            p.payment_status = 'verified' OR LOWER(REPLACE(p.delivery_status, '_', ' ')) IN ('preparing','out for delivery','delivered')
          )
        GROUP BY DATE(o.order_date), o.product_name
        ORDER BY total DESC, DATE(o.order_date) DESC
    ";

    $stmt = $connection->prepare($dailySql);
    if (!$stmt) throw new Exception($connection->error);
    $stmt->bind_param('s', $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $daily = [];
    while ($row = $result->fetch_assoc()) {
        $daily[] = $row;
    }
    $stmt->close();

    // Monthly aggregated: top N products by total sales
    $monthlySql = "
        SELECT o.product_name, SUM(o.quantity) AS quantity, SUM(o.price * o.quantity) AS total
        FROM orderDetails o
        LEFT JOIN payments p ON o.payment_id = p.payment_id
        WHERE DATE_FORMAT(o.order_date, '%Y-%m') = ?
          AND (
            p.payment_status = 'verified' OR LOWER(REPLACE(p.delivery_status, '_', ' ')) IN ('preparing','out for delivery','delivered')
          )
        GROUP BY o.product_name
        ORDER BY total DESC
        LIMIT ?
    ";

    $stmt2 = $connection->prepare($monthlySql);
    if (!$stmt2) throw new Exception($connection->error);
    $stmt2->bind_param('si', $month, $top);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $monthly = [];
    while ($row = $result2->fetch_assoc()) {
        $monthly[] = $row;
    }
    $stmt2->close();

    // Monthly total sum for the selected month
    $totalSql = "
        SELECT SUM(o.price * o.quantity) AS monthly_total
        FROM orderDetails o
        LEFT JOIN payments p ON o.payment_id = p.payment_id
        WHERE DATE_FORMAT(o.order_date, '%Y-%m') = ?
          AND (
            p.payment_status = 'verified' OR LOWER(REPLACE(p.delivery_status, '_', ' ')) IN ('preparing','out for delivery','delivered')
          )
    ";

    $stmt3 = $connection->prepare($totalSql);
    if ($stmt3) {
        $stmt3->bind_param('s', $month);
        $stmt3->execute();
        $res3 = $stmt3->get_result();
        $monthlyTotal = 0;
        if ($row3 = $res3->fetch_assoc()) {
            $monthlyTotal = (float) $row3['monthly_total'];
        }
        $stmt3->close();
    } else {
        $monthlyTotal = 0;
    }

    echo json_encode(["success" => true, "data" => ["dailyRecords" => $daily, "monthlyRecords" => $monthly, "monthlyTotal" => $monthlyTotal]], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('salesReport error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error"]);
} finally {
    if (isset($connection)) $connection->close();
}

?>
