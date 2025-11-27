<?php
// Include autoloader and DB config
require_once("../../vendor/autoload.php");
require_once("../../db/config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Allow JSON responses and CORS (adjust for production)
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Get JSON data from frontend
$input = json_decode(file_get_contents("php://input"), true);

$username = $input['username'] ?? null;
$type = $input['type'] ?? null;
$status = $input['status'] ?? null;

// Validate
if (!$username || !$type || !$status) {
  echo json_encode(["error" => "Missing required parameters"]);
  exit;
}

// Fetch email from database
try {
  $stmt = $connection->prepare("SELECT email FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->bind_result($email);
  $stmt->fetch();
  $stmt->close();

  if (!$email) {
    echo json_encode(["error" => "Email not found for user"]);
    exit;
  }
} catch (Exception $dbErr) {
  echo json_encode(["error" => "Database query failed"]);
  exit;
}

// Initialize PHPMailer
$mail = new PHPMailer(true);

try {
  // SMTP setup
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = 'forklore740@gmail.com';
  $mail->Password = 'tcuu fvyv idqm xsas';
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = 587;

  // Email headers
  $mail->setFrom('albinoaloya@gmail.com', 'Notification Service for store');
  $mail->addAddress($email);

  // Content
  $mail->isHTML(true);
  $mail->Subject = ucfirst($type) . " Status Update";
  $mail->Body = "
        <div style='font-family: Arial, sans-serif; padding: 10px;'>
            <h3 style='color: #333;'>Status Update</h3>
            <p>Hi <strong>$username</strong>,</p>
            <p>Your <strong>$type</strong> status has changed to: 
               <span style='color: #007BFF; font-weight: bold;'>$status</span>.
            </p>
            <p>Stay tuned for further updates!</p>
            <hr>
            <p style='font-size: 12px; color: #888;'>This is an automated notification from Your App.</p>
        </div>
    ";

  $mail->send();
  echo json_encode(["success" => "Email sent successfully to $email"]);
} catch (Exception $e) {
  echo json_encode(["error" => "Mailer Error: " . $mail->ErrorInfo]);
}
