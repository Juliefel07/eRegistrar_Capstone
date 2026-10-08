<?php
session_start();
date_default_timezone_set('Asia/Manila');
include "includes/db.php";

// PHPMailer Includes & Namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Helper function to send OTP email via PHPMailer
function sendOTPEmail($recipientEmail, $recipientName, $otpCode) {
    $apiKey = getenv('BREVO_API_KEY');

    $url = 'https://api.brevo.com/v3/smtp/email';

    $data = [
        'sender' => [
            'name'  => 'eRegistrar System',
            'email' => 'eregistrarcctc@gmail.com'
        ],
        'to' => [
            [
                'email' => $recipientEmail,
                'name'  => $recipientName
            ]
        ],
        'subject' => 'eRegistrar - Account Verification Code',
        'htmlContent' => "
        <!DOCTYPE html>
        <html>
        <body style='margin:0; padding:0; background-color: #f8fafc; font-family: Arial, sans-serif;'>
            <div style='max-width: 520px; margin: 20px auto; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;'>
                <div style='background-color: #1e3a8a; padding: 24px; text-align: center;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 22px;'>eRegistrar System</h1>
                </div>
                <div style='padding: 32px 28px;'>
                    <h2 style='color: #0f172a;'>Verify Your Email</h2>
                    <p style='color: #475569;'>Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,<br>Your verification code is below:</p>
                    <div style='background-color: #f1f5f9; border-radius: 10px; padding: 20px; text-align: center;'>
                        <div style='font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #1e3a8a;'>" . $otpCode . "</div>
                    </div>
                    <p style='color: #64748b; font-size: 13px; margin-top: 16px;'>⏱️ Code expires in 10 minutes.</p>
                </div>
            </div>
        </body>
        </html>"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 201 || $httpCode === 200) {
        return true;
    } else {
        error_log("Brevo API Error Code [$httpCode]: " . $response);
        return false;
    }
}
$account_type = $_POST['account_type'] ?? '';

// Generate 6-digit OTP & 10-minute expiry
$otp = sprintf("%06d", mt_rand(100000, 999999));
$expires_at = date("Y-m-d H:i:s", strtotime("+10 minutes"));

// ===============================
// STUDENT REGISTRATION
// ===============================
if ($account_type == "Student") {

    $student_level  = mysqli_real_escape_string($conn, $_POST['student_level'] ?? '');
    $student_status = mysqli_real_escape_string($conn, $_POST['student_status'] ?? 'Enrolled');
    $student_no     = mysqli_real_escape_string($conn, $_POST['student_no'] ?? '');
    $lrn            = mysqli_real_escape_string($conn, $_POST['lrn'] ?? '');
    $fullname       = mysqli_real_escape_string($conn, $_POST['fullname'] ?? '');
    $course         = mysqli_real_escape_string($conn, $_POST['course'] ?? '');
    $grade_level    = mysqli_real_escape_string($conn, $_POST['grade_level'] ?? '');
    $section        = mysqli_real_escape_string($conn, $_POST['section'] ?? '');

    if ($student_status === 'Graduated') {
        $year_level = 'Graduated';
    } else {
        $year_level = mysqli_real_escape_string($conn, $_POST['year_level'] ?? '');
    }

    $school_year   = mysqli_real_escape_string($conn, $_POST['school_year'] ?? '');
    $contact_no    = mysqli_real_escape_string($conn, $_POST['contact_no'] ?? '');
    $email         = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $password      = $_POST['password'] ?? '';
    $confirm_pass  = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_pass) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: register.php");
        exit();
    }

    $check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error'] = "Email already exists.";
        header("Location: register.php");
        exit();
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users 
            (account_type, student_level, student_status, student_no, lrn, fullname, course, grade_level, section, year_level, school_year, contact_no, email, password, role, otp_code, otp_expires_at, is_verified) 
            VALUES 
            ('Student', '$student_level', '$student_status', '$student_no', '$lrn', '$fullname', '$course', '$grade_level', '$section', '$year_level', '$school_year', '$contact_no', '$email', '$hash', 'Student', '$otp', '$expires_at', 0)";

    if (mysqli_query($conn, $sql)) {
        // Force the script to stop and show the error if email fails
        // Inside register_process.php (Parent section around line 160)
$mail_sent = sendOTPEmail($email, $fullname, $otp);
if (!$mail_sent) {
    die("Email failed to send! Please check your Render application logs for details.");
}

$_SESSION['pending_email'] = $email;
header("Location: verify_otp.php");
exit();
    } else {
        die("Student Registration Error: " . mysqli_error($conn));
    }
}

// ===============================
// PARENT REGISTRATION
// ===============================
if ($account_type == "Parent") {

    $fullname   = mysqli_real_escape_string($conn, trim($_POST['parent_name'] ?? ''));
    $contact_no = mysqli_real_escape_string($conn, trim($_POST['parent_contact'] ?? ''));
    $email      = mysqli_real_escape_string($conn, trim($_POST['parent_email'] ?? ''));
    $password   = $_POST['parent_password'] ?? '';
    $confirm    = $_POST['parent_confirm_password'] ?? '';

    if (empty($fullname) || empty($contact_no) || empty($email) || empty($password) || empty($confirm)) {
        $_SESSION['error'] = "Please complete all required fields.";
        header("Location: register.php");
        exit();
    }

    if ($password !== $confirm) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: register.php");
        exit();
    }

    $check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error'] = "Email already exists.";
        header("Location: register.php");
        exit();
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $insertParent = mysqli_query($conn, "INSERT INTO users (account_type, fullname, contact_no, email, password, role, otp_code, otp_expires_at, is_verified) VALUES ('Parent', '$fullname', '$contact_no', '$email', '$hash', 'Parent', '$otp', '$expires_at', 0)");

    if (!$insertParent) {
        die("Parent Insert Error: " . mysqli_error($conn));
    }

    $parent_id = mysqli_insert_id($conn);

    if (isset($_POST['student_names']) && isset($_POST['student_numbers'])) {
        foreach ($_POST['student_names'] as $key => $name) {
            $name   = mysqli_real_escape_string($conn, trim($name));
            $number = mysqli_real_escape_string($conn, trim($_POST['student_numbers'][$key]));

            if ($name == "" && $number == "") continue;

            mysqli_query($conn, "INSERT INTO parent_students (parent_id, student_number, student_name) VALUES ('$parent_id', '$number', '$name')");
        }
    }

    sendOTPEmail($email, $fullname, $otp);
    $_SESSION['pending_email'] = $email;
    header("Location: verify_otp.php");
    exit();
}
?>