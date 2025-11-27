<?php
header("Content-Type: application/json");

// Helper function to send JSON response
function sendResponse($success, $message, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}
// Start session
session_start();

// Read and decode input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Validate input
if ($data === null) {
    sendResponse(false, "Invalid JSON data", 400);
}

if (!isset($data['date']) ||  !isset($data['time']) || !isset($data['guests'])) {
    sendResponse(false, "Missing required fields: date, time, or guests", 400);
}

$date   = trim($data['date']);
$time   = trim($data['time']);
$guests = $data['guests'];

if ($date === '' || $time === '' || $guests === '' || $guests === null) {
    sendResponse(false, "All fields must be non-empty", 400);
}

// Validate allowed time slots (keep in sync with client)
$allowedTimeSlots = [
    '8:00-10:00 AM',
    '10:30-12:30 PM',
    '1:00-3:00 PM',
    '3:30-5:30 PM',
    '6:30-8:30 PM',
];

if (!in_array($time, $allowedTimeSlots, true)) {
    sendResponse(false, "Invalid time slot selected", 400);
}

// Validate guests: integer between 1 and 150
if (!is_numeric($guests)) {
    sendResponse(false, "Guests must be a number", 400);
}

$guests = (int) $guests;
if ($guests < 1 || $guests > 150) {
    sendResponse(false, "Guests must be between 1 and 150", 400);
}

// Connect to the database
require_once("../../db/config.php");

// Make sure user is logged in
if (!isset($_SESSION['user'])) {
    sendResponse(false, "Not logged in", 401);
}

$user_id = $_SESSION['user']['id']; // we stored this at login

// Insert reservation into ReservationDetails
$stmt = $connection->prepare("INSERT INTO reservationDetails (reservation_date, reservation_time, number_of_people, user_id, status) VALUES (?, ?, ?, ?, 'Pending')");
$stmt->bind_param("ssii", $date, $time, $guests, $user_id);

if ($stmt->execute()) {
    sendResponse(true, "Reservation created successfully");
} else {
    sendResponse(false, "Database error: " . $stmt->error, 500);
}

$stmt->close();
$connection->close();