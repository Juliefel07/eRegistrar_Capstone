<?php
session_start();
require_once __DIR__ . "/includes/db.php";

if (!isset($_SESSION['verified_reset']) || !$_SESSION['verified_reset'] || !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_SESSION['reset_email'];
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
        header("Location: reset_password.php");
        exit();
    }

    if ($password !== $confirm) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: reset_password.php");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $email_escaped = mysqli_real_escape_string($conn, $email);

    // Update password and clear reset tokens
    $update = mysqli_query($conn, "UPDATE users SET password = '$hashed_password', reset_token = NULL, token_expiry = NULL WHERE email = '$email_escaped'");

    if ($update) {
        // Clear all reset session data
        unset($_SESSION['reset_email']);
        unset($_SESSION['verified_reset']);

        header("Location: login.php?reset=success");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update password. Please try again.";
        header("Location: reset_password.php");
        exit();
    }
} else {
    header("Location: forgot_password.php");
    exit();
}
?>