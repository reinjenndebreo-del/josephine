<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require_once("../../../db/config.php");

$id = intval($_GET['id'] ?? 0);

$sql = "DELETE FROM menu_items WHERE id = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Menu item deleted successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to delete menu item: " . $connection->error]);
}

$stmt->close();
$connection->close();