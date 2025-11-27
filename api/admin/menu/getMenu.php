<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once("../../../db/config.php");

$sql = "SELECT * FROM menu_items ORDER BY category, name";
$result = $connection->query($sql);

if ($result) {
    $items = array();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode(["status" => "success", "items" => $items]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to retrieve menu items"]);
}

$connection->close();