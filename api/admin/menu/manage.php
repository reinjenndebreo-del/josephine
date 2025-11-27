<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require_once("../../db/config.php");

// Get all menu items
function getMenuItems() {
    global $connection;
    $sql = "SELECT * FROM menu_items";
    $result = $connection->query($sql);
    
    if ($result) {
        $items = array();
        while($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return ["status" => "success", "data" => $items];
    }
    return ["status" => "error", "message" => "Failed to fetch menu items"];
}

// Add new menu item
function addMenuItem($data) {
    global $connection;
    $name = $connection->real_escape_string($data['name']);
    $description = $connection->real_escape_string($data['description'] ?? '');
    $price = floatval($data['price']);
    $category = $connection->real_escape_string($data['category']);
    
    $sql = "INSERT INTO menu_items (name, description, price, category) VALUES ('$name', '$description', $price, '$category')";
    
    if ($connection->query($sql)) {
        return ["status" => "success", "message" => "Menu item added successfully"];
    }
    return ["status" => "error", "message" => "Failed to add menu item: " . $connection->error];
}

// Update menu item
function updateMenuItem($id, $data) {
    global $connection;
    $id = intval($id);
    $name = $connection->real_escape_string($data['name']);
    $description = $connection->real_escape_string($data['description'] ?? '');
    $price = floatval($data['price']);
    $category = $connection->real_escape_string($data['category']);
    
    $sql = "UPDATE menu_items SET name='$name', description='$description', price=$price, category='$category' WHERE id=$id";
    
    if ($connection->query($sql)) {
        return ["status" => "success", "message" => "Menu item updated successfully"];
    }
    return ["status" => "error", "message" => "Failed to update menu item: " . $connection->error];
}

// Delete menu item
function deleteMenuItem($id) {
    global $connection;
    $id = intval($id);
    
    $sql = "DELETE FROM menu_items WHERE id=$id";
    
    if ($connection->query($sql)) {
        return ["status" => "success", "message" => "Menu item deleted successfully"];
    }
    return ["status" => "error", "message" => "Failed to delete menu item: " . $connection->error];
}

// Handle requests
$method = $_SERVER['REQUEST_METHOD'];
$response = [];

switch ($method) {
    case 'GET':
        $response = getMenuItems();
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $response = addMenuItem($data);
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $_GET['id'] ?? 0;
        $response = updateMenuItem($id, $data);
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $response = deleteMenuItem($id);
        break;
        
    default:
        $response = ["status" => "error", "message" => "Invalid request method"];
}

echo json_encode($response);