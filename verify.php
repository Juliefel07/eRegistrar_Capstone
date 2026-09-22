<?php
session_start();

include "includes/db.php";

if (!isset($_GET['token'])) {
    die("Invalid verification link.");
}

$token = mysqli_real_escape_string($conn, $_GET['token']);

$result = mysqli_query(
    $conn,
    "SELECT user_id, fullname FROM users
     WHERE verification_token='$token'
     LIMIT 1"
);

if (mysqli_num_rows($result) == 0) {
    die("Invalid or expired verification link.");
}

$user = mysqli_fetch_assoc($result);

mysqli_query(
    $conn,
    "UPDATE users
     SET
        is_verified = 1,
        verification_token = NULL
     WHERE user_id = '{$user['user_id']}'"
);

$_SESSION['success'] =
"Your email has been verified successfully. You can now log in.";

header("Location: login.php");
exit();
?>