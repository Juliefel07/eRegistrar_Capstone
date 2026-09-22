<?php

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] != "POST"){
    die("Invalid request.");
}

$file_id = intval($_POST['file_id']);
$request_id = intval($_POST['request_id']);
$check = mysqli_query($conn,"
SELECT rrf.id
FROM request_requirement_files rrf
JOIN requests r
ON rrf.request_id = r.request_id
WHERE
    rrf.id='$file_id'
AND
    r.request_id='$request_id'
AND
    r.user_id='".$_SESSION['user_id']."'
LIMIT 1
");

if(mysqli_num_rows($check)==0){
    die("Access denied.");
}

if(!isset($_FILES['new_file']) || $_FILES['new_file']['error'] != 0){
    die("Please select a file.");
}

$filename = time() . "_" . basename($_FILES['new_file']['name']);

$upload_dir = "../assets/uploads/requirements/";

if(!is_dir($upload_dir)){
    mkdir($upload_dir,0777,true);
}

$filepath = $upload_dir . $filename;

move_uploaded_file($_FILES['new_file']['tmp_name'],$filepath);

$dbPath = "assets/uploads/requirements/" . $filename;

mysqli_query($conn,"
UPDATE request_requirement_files
SET
    file_name='$filename',
    file_path='$dbPath',
    status='Pending',
    remarks=NULL,
    uploaded_at=NOW()
WHERE id='$file_id'
");

$getInfo = mysqli_query($conn,"
SELECT
    r.tracking_no,
    dr.requirement_name,
    u.fullname
FROM request_requirement_files rrf
JOIN requests r
    ON rrf.request_id = r.request_id
JOIN users u
    ON r.user_id = u.user_id
JOIN document_requirements dr
    ON rrf.requirement_id = dr.requirement_id
WHERE rrf.id='$file_id'
LIMIT 1
");

if (!$getInfo) {
    die("SQL Error: " . mysqli_error($conn));
}

if(mysqli_num_rows($getInfo) == 0){
    die("No rows returned from getInfo.");
}

$info = mysqli_fetch_assoc($getInfo);

createNotification(
    $conn,
    1,
    $info['fullname']." uploaded a new file for '".$info['requirement_name']."' (Request ".$info['tracking_no']."). It is ready for verification."
);

$_SESSION['success'] = "Notification inserted successfully!";
header("Location: request_details.php?id=".$request_id);
exit();

header("Location: request_details.php?id=".$request_id);
exit();

?>