<?php
// Disable error display but log them
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../../../logs/php_errors.log');

header("Content-Type: application/json");
// When requests include credentials, do not use wildcard '*' for Access-Control-Allow-Origin.
// Echo the request Origin if present and allow credentials.
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once("../../../db/config.php");

// Log the incoming request
$log_file = "../../../logs/menu_add.log";
if (!file_exists("../../../logs")) {
    mkdir("../../../logs", 0777, true);
}

// Get raw POST data
$raw_data = file_get_contents("php://input");
file_put_contents($log_file, date("Y-m-d H:i:s") . " - Raw data received: " . $raw_data . "\n", FILE_APPEND);

try {
    // Determine if request is JSON or form-data (file upload)
    $isJson = false;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $isJson = true;
    }

    if ($isJson) {
        // Parse JSON body
        $data = json_decode($raw_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON parsing error: " . json_last_error_msg());
        }

        file_put_contents($log_file, date("Y-m-d H:i:s") . " - Parsed JSON data: " . print_r($data, true) . "\n", FILE_APPEND);

        $name = $connection->real_escape_string($data['name'] ?? '');
        $description = $connection->real_escape_string($data['description'] ?? '');
        $price = floatval($data['price'] ?? 0);
        $category = $connection->real_escape_string($data['category'] ?? '');
        $image_path = null;
    } else {
        // Expect multipart/form-data (FormData) with possible file upload
        file_put_contents($log_file, date("Y-m-d H:i:s") . " - Using POST/FILES data\n", FILE_APPEND);
        $name = $connection->real_escape_string($_POST['name'] ?? '');
        $description = $connection->real_escape_string($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category = $connection->real_escape_string($_POST['category'] ?? '');

        // Handle file upload if present
        $image_path = null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadsDir = __DIR__ . '/../../../uploads/menu/';
            if (!file_exists($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
            }

            $tmpName = $_FILES['image']['tmp_name'];
            $origName = basename($_FILES['image']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif'];
            if (!in_array($ext, $allowed)) {
                throw new Exception('Invalid image type. Allowed: jpg, jpeg, png, gif');
            }

            $newName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $origName);
            $dest = $uploadsDir . $newName;
            if (!move_uploaded_file($tmpName, $dest)) {
                throw new Exception('Failed to move uploaded file');
            }

            // Store web-accessible relative path
            $image_path = '/uploads/menu/' . $newName;
            file_put_contents($log_file, date("Y-m-d H:i:s") . " - Uploaded image saved: " . $image_path . "\n", FILE_APPEND);
        }
    }

    file_put_contents($log_file, date("Y-m-d H:i:s") . " - Processed data: name={$name}, description={$description}, price={$price}, category={$category}, image={$image_path}\n", FILE_APPEND);

    file_put_contents($log_file, date("Y-m-d H:i:s") . " - Processed data: name=$name, description=$description, price=$price, category=$category\n", FILE_APPEND);

    // Prepare and execute SQL
    // Insert including image column if present
    if ($image_path) {
        $sql = "INSERT INTO menu_items (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($sql);
    } else {
        $sql = "INSERT INTO menu_items (name, description, price, category) VALUES (?, ?, ?, ?)";
        $stmt = $connection->prepare($sql);
    }

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $connection->error);
    }

    if ($image_path) {
        $stmt->bind_param("ssdss", $name, $description, $price, $category, $image_path);
    } else {
        $stmt->bind_param("ssds", $name, $description, $price, $category);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

   
    $new_id = $stmt->insert_id; 

    file_put_contents($log_file, date("Y-m-d H:i:s") . " - Item added successfully with ID: " . $new_id . "\n", FILE_APPEND);

    $stmt->close();
    
    echo json_encode([
        "status" => "success", 
        "message" => "Menu item added successfully",
        "id" => $new_id, 
        "image" => $image_path
    ]);

} catch (Exception $e) {
    file_put_contents($log_file, date("Y-m-d H:i:s") . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to add menu item: " . $e->getMessage()
    ]);
}

$connection->close();