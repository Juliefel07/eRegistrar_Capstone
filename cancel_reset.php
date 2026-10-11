<?php
session_start();
require_once "includes/db.php";

$token = $_GET['token'] ?? '';

if (!empty($token)) {
    $token_escaped = mysqli_real_escape_string($conn, $token);
    // Invalidate the reset token in the database
    mysqli_query($conn, "UPDATE users SET reset_token = NULL, token_expiry = NULL WHERE reset_token = '$token_escaped'");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Cancelled</title>
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f6f9; margin: 0;">

    <div style="background: white; padding: 40px; border-radius: 10px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 450px; width: 100%;">
        <h3 style="color: #dc3545; margin-bottom: 15px;">Password Reset Cancelled</h3>
        <p style="color: #555; line-height: 1.5; margin-bottom: 25px;">
            You indicated that you did not request a password reset. This request has been successfully cancelled and your account remains secure.
        </p>
        <a href="login.php" style="display: inline-block; padding: 10px 25px; background-color: #004080; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
            Back to Login
        </a>
    </div>

</body>
</html>