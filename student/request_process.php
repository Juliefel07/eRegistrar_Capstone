<?php
session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// REQUIRED FIELDS VALIDATION
if (
    empty($_POST['document_id']) ||
    empty($_POST['purpose']) ||
    empty($_POST['quantity']) ||
    empty($_POST['payment_method'])
) {
    $_SESSION['request_error'] = "Please fill in all required fields.";
    header("Location: request.php");
    exit();
}

$document_id = intval($_POST['document_id']);
$purpose     = mysqli_real_escape_string($conn, $_POST['purpose']);
$quantity    = intval($_POST['quantity']);
$remarks     = mysqli_real_escape_string($conn, $_POST['remarks'] ?? '');
$payment_method = $_POST['payment_method'];

// STUDENT INFORMATION FROM FORM
$last_name    = $_POST['last_name'] ?? '';
$first_name   = $_POST['first_name'] ?? '';
$middle_name  = $_POST['middle_name'] ?? '';
$fullname     = trim("$last_name, $first_name $middle_name");

$address          = $_POST['address'] ?? '';
$course_year      = $_POST['course_year'] ?? '';
$contact_no       = $_POST['contact_no'] ?? '';
$email            = $_POST['email'] ?? '';
$is_graduate      = $_POST['is_graduate'] ?? 'No';
$last_sy_attended = $_POST['last_sy_attended'] ?? '';

// Fallback for missing student_no, course, year_level if stored separately
$student_no = $_POST['student_no'] ?? '';
$course     = $course_year; 
$year_level = ''; 

// TRACKING NUMBER
$tracking = "REQ" . date("YmdHis");
$file     = NULL;

// INSERT REQUEST INTO DATABASE
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO requests
    (
        tracking_no,
        user_id,
        fullname,
        student_no,
        course,
        year_level,
        email,
        document_id,
        purpose,
        quantity,
        remarks,
        uploaded_file,
        payment_method
    )
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
);

mysqli_stmt_bind_param(
    $stmt,
    "sisssssisisis",
    $tracking,
    $user_id,
    $fullname,
    $student_no,
    $course,
    $year_level,
    $email,
    $document_id,
    $purpose,
    $quantity,
    $remarks,
    $file,
    $payment_method
);

if (mysqli_stmt_execute($stmt)) {
    $request_id = mysqli_insert_id($conn);

    // UPLOAD REQUIREMENTS
    if (isset($_FILES['requirements'])) {
        $uploadDir = __DIR__ . "/../assets/uploads/requirements/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['requirements']['name'] as $requirement_id => $originalName) {
            if ($_FILES['requirements']['error'][$requirement_id] != 0) {
                continue;
            }

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowed = ["pdf", "jpg", "jpeg", "png", "doc", "docx"];

            if (!in_array($ext, $allowed)) {
                continue;
            }

            $newName = time() . "_" . $requirement_id . "_" . basename($originalName);

            if (move_uploaded_file($_FILES['requirements']['tmp_name'][$requirement_id], $uploadDir . $newName)) {
                $filePath = "assets/uploads/requirements/" . $newName;

                $reqStmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO request_requirement_files (request_id, requirement_id, file_name, file_path) VALUES (?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param($reqStmt, "iiss", $request_id, $requirement_id, $newName, $filePath);
                mysqli_stmt_execute($reqStmt);
            }
        }
    }

    // NOTIFICATIONS
    createNotification(
        $conn,
        $user_id,
        "Your request $tracking has been submitted successfully."
    );

    createNotification(
        $conn,
        1,
        "$fullname submitted a new document request ($tracking)."
    );

    // SET SESSION FLAGS FOR CLAIM STUB ACCESS IN REQUEST.PHP
    $_SESSION['request_success']  = true;
    $_SESSION['last_tracking_no'] = $tracking;

    header("Location: request.php");
    exit();
} else {
    die(mysqli_error($conn));
}
?>