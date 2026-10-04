<?php
session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// VALIDATE MULTI-DOCUMENT & REQUIRED FIELDS
if (
    empty($_POST['documents']) || 
    !is_array($_POST['documents']) ||
    empty($_POST['purpose']) ||
    empty($_POST['payment_method'])
) {
    $_SESSION['request_error'] = "Please fill in all required fields.";
    header("Location: request.php");
    exit();
}

$purpose        = mysqli_real_escape_string($conn, $_POST['purpose']);
$remarks        = mysqli_real_escape_string($conn, $_POST['remarks'] ?? '');
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);

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

// Fallbacks
$student_no = $_POST['student_no'] ?? '';
$course     = $course_year; 
$year_level = ''; 

// HANDLE E-PAYMENT PROOF UPLOAD (IF E-PAYMENT SELECTED)
$proof_file_path = NULL;
if ($payment_method === 'E-Payment' && isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === 0) {
    $proofDir = __DIR__ . "/../assets/uploads/payments/";
    if (!is_dir($proofDir)) {
        mkdir($proofDir, 0777, true);
    }

    $proofExt = strtolower(pathinfo($_FILES['proof_of_payment']['name'], PATHINFO_EXTENSION));
    $allowedProof = ["pdf", "jpg", "jpeg", "png"];

    if (in_array($proofExt, $allowedProof)) {
        $proofName = time() . "_proof_" . basename($_FILES['proof_of_payment']['name']);
        if (move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $proofDir . $proofName)) {
            $proof_file_path = "assets/uploads/payments/" . $proofName;
        }
    }
}

// SHARED TRACKING NUMBER OR GROUP TRACKING ID
$tracking = "REQ" . date("YmdHis");
$submittedRequests = [];

// PREPARE STATEMENT FOR INSERTING REQUESTS
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

$reqUploadDir = __DIR__ . "/../assets/uploads/requirements/";
if (!is_dir($reqUploadDir)) {
    mkdir($reqUploadDir, 0777, true);
}

// LOOP THROUGH EACH SUBMITTED DOCUMENT ITEM
foreach ($_POST['documents'] as $index => $docItem) {
    if (empty($docItem['id'])) {
        continue; // Skip unfilled document blocks
    }

    $document_id = intval($docItem['id']);
    $quantity    = isset($docItem['quantity']) ? intval($docItem['quantity']) : 1;
    $itemTracking = $tracking . (count($_POST['documents']) > 1 ? "-" . ($index + 1) : "");

    mysqli_stmt_bind_param(
        $stmt,
        "sisssssisisis",
        $itemTracking,
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
        $proof_file_path,
        $payment_method
    );

    if (mysqli_stmt_execute($stmt)) {
        $request_id = mysqli_insert_id($conn);
        $submittedRequests[] = $itemTracking;

        // HANDLE REQUIREMENT FILES FOR THIS DOCUMENT ITEM
        if (
            isset($_FILES['documents']['name'][$index]['requirements']) &&
            is_array($_FILES['documents']['name'][$index]['requirements'])
        ) {
            $fileArray = $_FILES['documents']['name'][$index]['requirements'];
            $tmpArray  = $_FILES['documents']['tmp_name'][$index]['requirements'];
            $errorArray= $_FILES['documents']['error'][$index]['requirements'];

            foreach ($fileArray as $requirement_id => $originalName) {
                if ($errorArray[$requirement_id] !== 0 || empty($originalName)) {
                    continue;
                }

                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowed = ["pdf", "jpg", "jpeg", "png", "doc", "docx"];

                if (!in_array($ext, $allowed)) {
                    continue;
                }

                $newName = time() . "_" . $index . "_" . $requirement_id . "_" . basename($originalName);

                if (move_uploaded_file($tmpArray[$requirement_id], $reqUploadDir . $newName)) {
                    $filePath = "assets/uploads/requirements/" . $newName;

                    $reqStmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO request_requirement_files (request_id, requirement_id, file_name, file_path) VALUES (?, ?, ?, ?)"
                    );
                    mysqli_stmt_bind_param($reqStmt, "iiss", $request_id, $requirement_id, $newName, $filePath);
                    mysqli_stmt_execute($reqStmt);
                    mysqli_stmt_close($reqStmt);
                }
            }
        }
    } else {
        die(mysqli_error($conn));
    }
}

mysqli_stmt_close($stmt);

// SEND NOTIFICATIONS & REDIRECT
if (!empty($submittedRequests)) {
    createNotification(
        $conn,
        $user_id,
        "Your request ($tracking) has been submitted successfully."
    );

    createNotification(
        $conn,
        1, // Admin / Registrar user ID
        "$fullname submitted a new document request ($tracking)."
    );

    $_SESSION['request_success']  = true;
    $_SESSION['last_tracking_no'] = $tracking;

    header("Location: request.php");
    exit();
} else {
    $_SESSION['request_error'] = "Failed to submit request. Please try again.";
    header("Location: request.php");
    exit();
}
?>