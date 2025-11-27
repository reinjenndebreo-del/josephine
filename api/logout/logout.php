
<?php 
session_start();
// Destroy the session
session_unset();
session_destroy();

http_response_code(200);
echo json_encode([
    "message" => "Logout successful."
]);





