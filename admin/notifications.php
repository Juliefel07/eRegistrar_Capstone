<?php

session_start();

require_once __DIR__ . "/../includes/db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit();
}

$notifications = mysqli_query($conn,"
SELECT *
FROM notifications
WHERE user_id='1'
ORDER BY created_at DESC
");

?>

<!DOCTYPE html>
<html>

<head>

<title>Admin Notifications</title>

<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/admin.css">

<style>

.notification-card{

background:#fff;

border-left:5px solid #2563eb;

padding:15px;

margin-bottom:15px;

border-radius:8px;

box-shadow:0 2px 6px rgba(0,0,0,.08);

}

.notification-time{

font-size:12px;

color:#666;

margin-top:8px;

}

</style>

</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

<?php include("header.php"); ?>

<div class="container">

<h2>Notifications</h2>

<br>

<?php if(mysqli_num_rows($notifications)==0){ ?>

<p>No notifications found.</p>

<?php } ?>

<?php while($row=mysqli_fetch_assoc($notifications)){ ?>

<div class="notification-card">

<?= htmlspecialchars($row['message']); ?>

<div class="notification-time">

<?= date("M d, Y h:i A",strtotime($row['created_at'])); ?>

</div>

</div>

<?php } ?>

</div>

</div>

</body>

</html>