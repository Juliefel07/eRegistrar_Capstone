<?php
session_start();
require_once __DIR__ . "/includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));

    $sql = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 0) {
        $_SESSION['error'] = "Email address is not registered.";
        header("Location: forgot_password.php");
        exit();
    }

    $user = mysqli_fetch_assoc($result);
    $user_id = $user['user_id'];

    // Generate a 2-digit target number (e.g., 72)
    $correct_number = rand(10, 99);
    $expiry = date("Y-m-d H:i:s", strtotime('+15 minutes'));

    $update_sql = "UPDATE users SET reset_token = '$correct_number', token_expiry = '$expiry' WHERE user_id = '$user_id'";
    
    if (mysqli_query($conn, $update_sql)) {
        $_SESSION['reset_email'] = $email;

        // Send via Brevo API
        $api_key = getenv('BREVO_API_KEY'); // Replace with your actual Brevo API Key
        $email_data = [
            "sender" => ["name" => "eRegistrar", "email" => "eregistrarcctc@gmail.com"],
            "to" => [["email" => $email]],
            "subject" => "Password Reset Number Verification - eRegistrar",
            "htmlContent" => "
                <div style='font-family: Arial, sans-serif; padding: 25px; color: #333; max-width: 500px; margin: auto; border: 1px solid #eee; border-radius: 10px;'>
                    <h2 style='color: #ff5722;'>Verification Number</h2>
                    <p>Hi,</p>
                    <p>We received a request to reset your password. Your verification number is:</p>
                    <div style='background-color: #f8f9fa; padding: 15px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                        <span style='font-size: 38px; font-weight: bold; letter-spacing: 5px; color: #ff5722;'>$correct_number</span>
                    </div>
                    <p style='color: #666; font-size: 13px;'>Select this matching number on your screen to proceed. This expires in 15 minutes.</p>
                </div>
            "
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $api_key,
            'content-type: application/json'
        ]);
        curl_exec($ch);
        curl_close($ch);

        header("Location: verify_reset_code.php");
        exit();
    } else {
        $_SESSION['error'] = "Something went wrong. Please try again.";
        header("Location: forgot_password.php");
        exit();
    }
} else {
    header("Location: forgot_password.php");
    exit();
}
?>