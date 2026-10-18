<?php

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification.php";
require_once __DIR__ . "/../includes/send_verification.php"; // Include Brevo email functions


if(!isset($_GET['id'])){
    die("Invalid request.");
}


$id = intval($_GET['id']);


$get = mysqli_query($conn, "
    SELECT r.user_id, r.tracking_no, u.email, u.fullname, d.document_name 
    FROM requests r
    JOIN users u ON r.user_id = u.user_id
    JOIN documents d ON r.document_id = d.document_id
    WHERE r.request_id = '$id'
");


$data = mysqli_fetch_assoc($get);



if(!$data){
    die("Request not found.");
}



$sql = "
    UPDATE requests 
    SET status = 'Ready for Claim' 
    WHERE request_id = '$id'
";



if(mysqli_query($conn, $sql)){

    // 1. System Dashboard Notification
    createNotification(
        $conn,
        $data['user_id'],
        "Your request " . $data['tracking_no'] . " is ready for claim."
    );

    // 2. Brevo API Email Notification
    sendStatusUpdateEmail(
        $data['email'],
        $data['fullname'],
        $data['tracking_no'],
        'Ready for Claim',
        $data['document_name']
    );

    header("Location: requests.php");
    exit();

} else {
    echo mysqli_error($conn);
}

?>