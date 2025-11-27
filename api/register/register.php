<?php
header("Content-Type: application/json");
session_start();

$input = file_get_contents("php://input");
$data = json_decode($input, true);

$username = $data['username'] ?? null;
$email    = $data['email'] ?? null;
$password = $data['password'] ?? null;

require_once("../../db/config.php");

// validate input
if (!$username || !$email || !$password) {
    http_response_code(400);
    echo json_encode(["message" => "Username, email and password are required."]);
    exit();
}

// check if user already exists by username OR email
$stmt = $connection->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
$stmt->bind_param("ss", $email, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["message" => "Username or email already exists."]);
    exit();
}

// hash and insert
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$stmt = $connection->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $hashedPassword);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["message" => "Error creating user."]);
    exit();
}

$user_id = $connection->insert_id;

// Store session
$_SESSION['user'] = [
    "id"       => $user_id,
    "username" => $username,
    "email"    => $email,
    "loggedin" => true,
    "isAdmin"  => false,
];
$_SESSION['user_id'] = $user_id;

// Success response
http_response_code(201);
echo json_encode([
    "message"  => "Registration successful. Welcome, " . $username . "!",
    "token"    => session_id(),
    "username" => $username,
    "user_id"  => $user_id,
    "isAdmin"  => false
]);
