<?php
session_start();
header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Make sure user is logged in
if (!isset($_SESSION['user'])) {
    sendResponse(false, "Not logged in", null, 401);
}

$user_id = $_SESSION['user']['id'];

// Connect to the database
require_once("../../db/config.php");

try {
    // Fetch user reservations ordered by date and time
    $stmt = $connection->prepare("
        SELECT 
            reservation_id,
            reservation_date,
            reservation_time,
            number_of_people,
            status,
            created_at
        FROM reservationDetails 
        WHERE user_id = ? 
        ORDER BY reservation_date DESC, reservation_time DESC
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {     
        $formatted_date = date('F j, Y', strtotime($row['reservation_date']));

        // If stored time is a range/slot (contains a dash) show it as-is,
        // otherwise try to format a single time value.
        if (strpos($row['reservation_time'], '-') !== false) {
            $formatted_time = $row['reservation_time'];
        } else {
            $ts = strtotime($row['reservation_time']);
            if ($ts === false) {
                $formatted_time = $row['reservation_time'];
            } else {
                $formatted_time = date('g:i A', $ts);
            }
        }
        
        $reservations[] = [
            'id' => $row['reservation_id'],
            'date' => $row['reservation_date'], 
            'formatted_date' => $formatted_date,
            'time' => $row['reservation_time'],
            'formatted_time' => $formatted_time,
            'guests' => $row['number_of_people'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    
    sendResponse(true, "Reservations retrieved successfully", $reservations);
    
} catch (Exception $e) {
    sendResponse(false, "Database error: " . $e->getMessage(), null, 500);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $connection->close();
}
?>