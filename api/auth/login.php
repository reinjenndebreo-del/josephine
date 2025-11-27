<?php
// Allow CORS and credentials so session cookie can be set when frontend uses credentials: 'include'
if (isset($_SERVER['HTTP_ORIGIN'])) {
  // Allow the requesting origin (do not use '*' when credentials are needed)
  header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
  header('Access-Control-Allow-Credentials: true');
}
header("Content-Type: application/json");

$input = file_get_contents("php://input");
$data = json_decode($input, true);

$username = $data['username'] ?? null;
$password = $data['password'] ?? null;

// connect to database
require_once("../../db/config.php");

if (!$username || !$password) {
  http_response_code(400);
  echo json_encode(["message" => "Username and password are required."]);
  exit();
}


// check if the user is admin 
if ($username === "admin" && $password === "admin123") {
    session_start();
    $_SESSION['user'] = [
        "id"       => 0,
        "username" => $username,
        "loggedin" => true,
        "isAdmin"  => true
    ];
    http_response_code(200);
    echo json_encode([
        "message"  => "Admin login successful",
        "token"    => session_id(),
        "username" => $username,
        "isAdmin"  => true
    ]);
    exit();
}

// Find user
$stmt = $connection->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();




if ($result->num_rows === 0) {
  http_response_code(404);
  echo json_encode(["message" => "User not found."]);
  exit();
}
$user = $result->fetch_assoc();

// Verify password using compare hash
if (!password_verify($password, $user['password'])) {
  http_response_code(401);
  echo json_encode(["message" => "Invalid credentials."]);
  exit();
}

// Start session
session_start();

$_SESSION['user_id'] = $stmt->insert_id; 
// Store user info in session
$_SESSION['user'] = [
  "id"       => $user['user_id'],
  "username" => $user['username'],
  "loggedin" => true,
  "isAdmin"  => false,
];

// Also store user_id directly for easy access later
$_SESSION['user_id'] = $user['user_id'];

http_response_code(200);
echo json_encode([
  "message"  => "Login successful. Welcome back, " . $user['username'] . "!",
  "token"    => session_id(),        // PHP session ID
  "username" => $user['username'],   // return username
]);

$stmt->close();
$connection->close();
