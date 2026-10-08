<?php
session_start();
include "includes/db.php";

// Grab the latest user registered
$result = mysqli_query($conn, "SELECT email FROM users ORDER BY user_id DESC LIMIT 1");
if ($row = mysqli_fetch_assoc($result)) {
    $email = $row['email'];
    $otp = '123456';
    $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    // Set them as unverified with a fixed OTP
    $stmt = $conn->prepare("UPDATE users SET is_verified = 0, otp_code = ?, otp_expires_at = ? WHERE email = ?");
    $stmt->bind_param("sss", $otp, $expires, $email);
    $stmt->execute();

    $_SESSION['pending_email'] = $email;
    echo "Success! Test user configured for: " . htmlspecialchars($email) . "<br>";
    echo "OTP code is: <strong>123456</strong><br>";
    echo "<a href='verify_otp.php'>Proceed to Verify OTP Page</a>";
} else {
    echo "No users found in the database yet. Register a user first.";
}
?>