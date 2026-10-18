<?php
session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification.php";
require_once __DIR__ . "/../includes/send_verification.php"; // Include Brevo email functions

// Check if user is logged in and has Admin or Staff role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$id = intval($_GET['id']);
$admin_id = intval($_SESSION['user_id']);

$get = mysqli_query($conn, "
    SELECT r.user_id, r.tracking_no, u.email, u.fullname, d.document_name 
    FROM requests r
    JOIN users u ON r.user_id = u.user_id
    JOIN documents d ON r.document_id = d.document_id
    WHERE r.request_id = '$id'
");

$data = mysqli_fetch_assoc($get);

if (!$data) {
    die("Request not found.");
}

// Update the status AND record who processed/grabbed it
$sql = "
    UPDATE requests 
    SET status = 'Processing', processed_by = '$admin_id' 
    WHERE request_id = '$id'
";

if (mysqli_query($conn, $sql)) {
    // 1. System Dashboard Notification
    createNotification(
        $conn,
        $data['user_id'],
        "Your request " . $data['tracking_no'] . " is now being processed."
    );

    // 2. Brevo API Email Notification
    sendStatusUpdateEmail(
        $data['email'],
        $data['fullname'],
        $data['tracking_no'],
        'Processing',
        $data['document_name']
    );

    header("Location: requests.php");
    exit();
} else {
    echo mysqli_error($conn);
}
?>