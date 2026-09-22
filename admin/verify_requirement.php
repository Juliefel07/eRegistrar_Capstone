<?php

session_start();
require_once __DIR__ . "/../includes/db.php";

if(!isset($_SESSION['user_id'])){
    die("Access denied.");
}

if(!isset($_GET['id']) || !isset($_GET['request'])){
    die("Invalid request.");
}

$id = intval($_GET['id']);
$request = intval($_GET['request']);

mysqli_query($conn,"
UPDATE request_requirement_files
SET status='Verified'
WHERE id='$id'
");

header("Location: view_request.php?id=".$request);
exit();