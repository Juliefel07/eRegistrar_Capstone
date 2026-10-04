<?php
session_start();
include "includes/db.php";

// PHPMailer Includes & Namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Helper function to send OTP email via PHPMailer
function sendOTPEmail($recipientEmail, $recipientName, $otpCode) {
    $mail = new PHPMailer(true);

    try {
        // Disable debug output for live redirect flow
        $mail->SMTPDebug = 2; 

        // SMTP Server Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';             
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('MAIL_USER') ?: 'eregistrarcctc@gmail.com';    
        $mail->Password   = getenv('MAIL_PASS') ?: 'hfxanjszjhzpcarv';        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 587;

        // Disable SSL Certificate Verification for XAMPP
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            )
        );

        // Sender & Recipient Setup
        $mail->setFrom('eregistrarcctc@gmail.com', 'eRegistrar System');
        $mail->addAddress($recipientEmail, $recipientName);

        // Content Setup
        $mail->isHTML(true);
        $mail->Subject = 'eRegistrar - Account Verification Code';
        $mail->Body    = "
        <!DOCTYPE html>
        <html>
        <head>
        
            <meta charset='UTF-8'>
            
        </head>
        <body style='margin:0; padding:0; background-color: #f8fafc; font-family: \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='padding: 40px 10px;'>
                <tr>
                    <td align='center'>
                        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 520px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;'>
                            
                            <!-- Header Bar -->
                            <tr>
                                <td style='background-color: #1e3a8a; padding: 24px; text-align: center;'>
                                    <h1 style='color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.5px;'>eRegistrar System</h1>
                                </td>
                            </tr>

                            <!-- Body Content -->
                            <tr>
                                <td style='padding: 32px 28px;'>
                                    <h2 style='color: #0f172a; margin-top: 0; margin-bottom: 12px; font-size: 20px;'>Verify Your Email</h2>
                                    <p style='color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 24px;'>
                                        Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,<br>
                                        Thank you for signing up with eRegistrar. Please use the verification code below to complete your registration.
                                    </p>

                                    <!-- OTP Box -->
                                    <div style='background-color: #f1f5f9; border-radius: 10px; border: 1px solid #cbd5e1; padding: 20px; text-align: center; margin-bottom: 24px;'>
                                        <div style='font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 1px; margin-bottom: 8px;'>Verification Code</div>
                                        <div style='font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #1e3a8a; font-family: monospace;'>" . $otpCode . "</div>
                                    </div>

                                    <p style='color: #64748b; font-size: 13px; line-height: 1.5; margin: 0;'>
                                        ⏱️ <strong>This code expires in 10 minutes.</strong><br>
                                        If you did not request this account registration, you can safely ignore this email.
                                    </p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style='background-color: #f8fafc; padding: 16px 28px; border-top: 1px solid #e2e8f0; text-align: center;'>
                                    <p style='color: #94a3b8; font-size: 12px; margin: 0;'>
                                        &copy; " . date('Y') . " eRegistrar. All rights reserved.
                                    </p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
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
        sendOTPEmail($email, $fullname, $otp);
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