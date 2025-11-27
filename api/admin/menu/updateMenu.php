<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once("../../../db/config.php");

$id = intval($_GET['id'] ?? 0);
$data = json_decode(file_get_contents("php://input"), true);

$name = $connection->real_escape_string($data['name']);
$description = $connection->real_escape_string($data['description'] ?? '');
$price = floatval($data['price']);
$category = $connection->real_escape_string($data['category']);

$sql = "UPDATE menu_items SET name = ?, description = ?, price = ?, category = ? WHERE id = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("ssdsi", $name, $description, $price, $category, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Menu item updated successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to update menu item: " . $connection->error]);
}

$stmt->close();
$connection->close();