<?php
session_start();
include "includes/db.php";
require_once "includes/send_verification.php";

$account_type = $_POST['account_type'] ?? '';

// ===============================
// STUDENT
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

    // If student is graduated, override year_level to "Graduated"
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

    $hash  = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));

    // UPDATED: is_verified set to 1 directly upon insertion
    $sql = "INSERT INTO users 
            (account_type, student_level, student_status, student_no, lrn, fullname, course, grade_level, section, year_level, school_year, contact_no, email, password, role, verification_token, is_verified) 
            VALUES 
            ('Student', '$student_level', '$student_status', '$student_no', '$lrn', '$fullname', '$course', '$grade_level', '$section', '$year_level', '$school_year', '$contact_no', '$email', '$hash', 'Student', '$token', 1)";

    if (mysqli_query($conn, $sql)) {
        // Optional email sending (does not block login anymore)
        sendVerificationEmail($email, $fullname, $token);
    } else {
        die("Student Registration Error: " . mysqli_error($conn));
    }
}

// ===============================
// PARENT
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

    $hash  = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));

    // UPDATED: is_verified set to 1 directly upon insertion
    $insertParent = mysqli_query($conn, "INSERT INTO users (account_type, fullname, contact_no, email, password, role, verification_token, is_verified) VALUES ('Parent', '$fullname', '$contact_no', '$email', '$hash', 'Parent', '$token', 1)");

    if (!$insertParent) {
        die("Parent Insert Error: " . mysqli_error($conn));
    }

    $parent_id = mysqli_insert_id($conn);
    sendVerificationEmail($email, $fullname, $token);

    if (isset($_POST['student_names']) && isset($_POST['student_numbers'])) {
        foreach ($_POST['student_names'] as $key => $name) {
            $name   = mysqli_real_escape_string($conn, trim($name));
            $number = mysqli_real_escape_string($conn, trim($_POST['student_numbers'][$key]));

            if ($name == "" && $number == "") continue;

            mysqli_query($conn, "INSERT INTO parent_students (parent_id, student_number, student_name) VALUES ('$parent_id', '$number', '$name')");
        }
    }
}

$_SESSION['success'] = "Registration successful! You can now log in.";
header("Location: login.php");
exit();
?>