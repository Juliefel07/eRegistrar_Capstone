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
    empty($_POST['purpose'])
) {
    $_SESSION['request_error'] = "Please fill in all required fields.";
    header("Location: request.php");
    exit();
}

$purpose        = mysqli_real_escape_string($conn, $_POST['purpose']);
$remarks        = mysqli_real_escape_string($conn, $_POST['remarks'] ?? '');
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'On the Counter');

// STUDENT INFORMATION FROM FORM
$last_name   = $_POST['last_name'] ?? '';
$first_name  = $_POST['first_name'] ?? '';
$middle_name = $_POST['middle_name'] ?? '';
$fullname    = trim("$last_name, $first_name $middle_name");

$address          = $_POST['address'] ?? '';
$course_year      = $_POST['course_year'] ?? '';
$contact_no       = $_POST['contact_no'] ?? '';
$email            = $_POST['email'] ?? '';
$is_graduate      = $_POST['is_graduate'] ?? 'No';
$last_sy_attended = $_POST['last_school_year'] ?? '';

// Fallbacks
$student_no = $_POST['student_no'] ?? '';
$course     = $course_year; 
$year_level = ''; 

$proof_file_path = NULL;

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
        "sisssssisssss",
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

        // FETCH A VALID REQUIREMENT ID FOR THIS DOCUMENT TO SATISFY NOT NULL FOREIGN KEY
        $default_req_id = 0;
        $reqCheckQuery = mysqli_query($conn, "SELECT requirement_id FROM document_requirements WHERE document_id = {$document_id} LIMIT 1");
        if ($reqCheckQuery && $reqRow = mysqli_fetch_assoc($reqCheckQuery)) {
            $default_req_id = intval($reqRow['requirement_id']);
        } else {
            $anyReqQuery = mysqli_query($conn, "SELECT requirement_id FROM document_requirements LIMIT 1");
            if ($anyReqQuery && $anyRow = mysqli_fetch_assoc($anyReqQuery)) {
                $default_req_id = intval($anyRow['requirement_id']);
            }
        }

        // HANDLE FRONT OF ID UPLOAD
        if (isset($_FILES['valid_id_front']) && $_FILES['valid_id_front']['error'] === UPLOAD_ERR_OK) {
            $frontName = $_FILES['valid_id_front']['name'];
            $frontExt  = strtolower(pathinfo($frontName, PATHINFO_EXTENSION));
            $allowed   = ["pdf", "jpg", "jpeg", "png"];

            if (in_array($frontExt, $allowed)) {
                $newFrontName = time() . "_id_front_" . preg_replace("/[^a-zA-Z0-9\._-]/", "_", $frontName);
                if (move_uploaded_file($_FILES['valid_id_front']['tmp_name'], $reqUploadDir . $newFrontName)) {
                    $frontPath = "assets/uploads/requirements/" . $newFrontName;
                    $idStmt = mysqli_prepare($conn, "INSERT INTO request_requirement_files (request_id, requirement_id, file_name, file_path, status) VALUES (?, ?, 'Valid ID (Front)', ?, 'Pending')");
                    mysqli_stmt_bind_param($idStmt, "iis", $request_id, $default_req_id, $frontPath);
                    mysqli_stmt_execute($idStmt);
                    mysqli_stmt_close($idStmt);
                }
            }
        }

        // HANDLE BACK OF ID UPLOAD
        if (isset($_FILES['valid_id_back']) && $_FILES['valid_id_back']['error'] === UPLOAD_ERR_OK) {
            $backName = $_FILES['valid_id_back']['name'];
            $backExt  = strtolower(pathinfo($backName, PATHINFO_EXTENSION));
            $allowed  = ["pdf", "jpg", "jpeg", "png"];

            if (in_array($backExt, $allowed)) {
                $newBackName = time() . "_id_back_" . preg_replace("/[^a-zA-Z0-9\._-]/", "_", $backName);
                if (move_uploaded_file($_FILES['valid_id_back']['tmp_name'], $reqUploadDir . $newBackName)) {
                    $backPath = "assets/uploads/requirements/" . $newBackName;
                    $idStmt = mysqli_prepare($conn, "INSERT INTO request_requirement_files (request_id, requirement_id, file_name, file_path, status) VALUES (?, ?, 'Valid ID (Back)', ?, 'Pending')");
                    mysqli_stmt_bind_param($idStmt, "iis", $request_id, $default_req_id, $backPath);
                    mysqli_stmt_execute($idStmt);
                    mysqli_stmt_close($idStmt);
                }
            }
        }

        // HANDLE ADDITIONAL DYNAMIC REQUIREMENT FILES FOR THIS DOCUMENT ITEM
        if (
            isset($_FILES['documents']['name'][$index]['requirements']) &&
            is_array($_FILES['documents']['name'][$index]['requirements'])
        ) {
            $fileArray  = $_FILES['documents']['name'][$index]['requirements'];
            $tmpArray   = $_FILES['documents']['tmp_name'][$index]['requirements'];
            $errorArray = $_FILES['documents']['error'][$index]['requirements'];

            foreach ($fileArray as $reqKey => $originalName) {
                if ($errorArray[$reqKey] !== 0 || empty($originalName)) {
                    continue;
                }

                $ext     = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowed = ["pdf", "jpg", "jpeg", "png", "doc", "docx"];

                if (!in_array($ext, $allowed)) {
                    continue;
                }

                $requirement_id = intval($reqKey);
                $newName = time() . "_" . $index . "_" . $requirement_id . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "_", $originalName);

                if (move_uploaded_file($tmpArray[$reqKey], $reqUploadDir . $newName)) {
                    $filePath = "assets/uploads/requirements/" . $newName;

                    $reqStmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO request_requirement_files (request_id, requirement_id, file_name, file_path, status) VALUES (?, ?, ?, ?, 'Pending')"
                    );
                    mysqli_stmt_bind_param($reqStmt, "iiss", $request_id, $requirement_id, $originalName, $filePath);
                    mysqli_stmt_execute($reqStmt);
                    mysqli_stmt_close($reqStmt);
                }
            }
        }
    } else {
        die("Database Execution Error: " . mysqli_error($conn));
    }
}

mysqli_stmt_close($stmt);

// SEND NOTIFICATIONS & REDIRECT
if (!empty($submittedRequests)) {
    // 1. Notify the Student
    createNotification(
        $conn,
        $user_id,
        "Your request ($tracking) has been submitted successfully."
    );

    // 2. Dynamic Primary Key Check (id vs user_id)
    $pk_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'user_id'");
    $id_col = ($pk_check && mysqli_num_rows($pk_check) > 0) ? 'user_id' : 'id';

    // 3. FETCH AND NOTIFY ALL ADMINS
    $admin_query = mysqli_query($conn, "SELECT {$id_col} AS admin_id FROM users WHERE LOWER(role) = 'admin'");

    if ($admin_query && mysqli_num_rows($admin_query) > 0) {
        $admin_message = "$fullname submitted a new document request ($tracking).";
        
        while ($admin = mysqli_fetch_assoc($admin_query)) {
            $target_admin_id = (int)$admin['admin_id'];
            createNotification($conn, $target_admin_id, $admin_message);
        }
    }

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