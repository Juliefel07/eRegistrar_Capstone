<?php
// Prevent any stray output/warnings from corrupting the JSON response
ob_start();
session_start();

// Set JSON header immediately
header('Content-Type: application/json');

include "includes/db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check file paths for PHPMailer
if (!file_exists('PHPMailer/src/Exception.php')) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'PHPMailer path not found.']);
    exit();
}

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (!isset($_SESSION['pending_email'])) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please register again.']);
    exit();
}

$email = $_SESSION['pending_email'];

// Generate new 6-digit OTP & new 10-minute expiry
$new_otp = sprintf("%06d", mt_rand(100000, 999999));
$expires_at = date("Y-m-d H:i:s", strtotime("+10 minutes"));

// Update database with new OTP
$stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
$stmt->bind_param("sss", $new_otp, $expires_at, $email);

if ($stmt->execute()) {
    // Get user's name
    $name_query = $conn->prepare("SELECT fullname FROM users WHERE email = ? LIMIT 1");
    $name_query->bind_param("s", $email);
    $name_query->execute();
    $user = $name_query->get_result()->fetch_assoc();
    $fullname = $user['fullname'] ?? 'User';

    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'eregistrarcctc@gmail.com';
        $mail->Password   = 'hfxanjszjhzpcarv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('eregistrarcctc@gmail.com', 'eRegistrar System');
        $mail->addAddress($email, $fullname);

        $mail->isHTML(true);
        $mail->Subject = 'eRegistrar - Your New Verification Code';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                <h2 style='color: #2563eb; text-align: center;'>New Account Verification Code</h2>
                <p>Hello <strong>" . htmlspecialchars($fullname) . "</strong>,</p>
                <p>You requested a new verification code. Here is your new 6-digit OTP:</p>
                <div style='text-align: center; margin: 25px 0;'>
                    <span style='font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #1e293b; background: #f1f5f9; padding: 10px 20px; border-radius: 6px; border: 1px dashed #cbd5e1;'>" . $new_otp . "</span>
                </div>
                <p style='color: #64748b; font-size: 0.85rem;'>This code will expire in 10 minutes.</p>
            </div>";

        $mail->send();
        ob_end_clean();
        echo json_encode(['status' => 'success']);
        exit();
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Mailer error: ' . $mail->ErrorInfo]);
        exit();
    }
} else {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
    exit();
}
?>