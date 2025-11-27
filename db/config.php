<?php
// Disable error display in output
error_reporting(0);
ini_set('display_errors', 0);

$servername = "127.0.0.1";  // or "127.0.0.1"
$connection  = new mysqli($servername, "root", "", "josephine");

//check connection
if ($connection->connect_error) {
  header('Content-Type: application/json');
  http_response_code(500);
  echo json_encode([
    "status" => "error",
    "message" => "Cannot connect to database: " . $connection->connect_error
  ]);
  exit();
}