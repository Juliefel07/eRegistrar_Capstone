<?php

function sendVerificationEmail($email, $fullname, $token)
{
    $apiKey = getenv('BREVO_API_KEY') ?: 'xsmtpsib-ff23e20c9aa0cec6b3eec780b6cad83e041cb50b7a4cc1c5ea24cc8c9257382a-sSiJdjf4pJqJQWuE';
    $url = 'https://api.brevo.com/v3/smtp/email';

    // Dynamic base URL (uses deployed domain on Render or falls back to localhost locally)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'] ?? 'eregistrar-consolatrix.onrender.com';
    $verifyLink = $protocol . $domain . "/verify.php?token=" . urlencode($token);

    $data = [
        'sender' => [
            'name'  => 'eRegistrar System',
            'email' => 'eregistrarcctc@gmail.com'
        ],
        'to' => [
            [
                'email' => $email,
                'name'  => $fullname
            ]
        ],
        'subject' => 'Verify your eRegistrar Account',
        'htmlContent' => "
            <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                <h2 style='color: #1e3a8a; text-align: center;'>Welcome to eRegistrar</h2>
                <p>Hello <b>" . htmlspecialchars($fullname) . "</b>,</p>
                <p>Thank you for registering. Please click the button below to verify your account:</p>
                <div style='text-align: center; margin: 25px 0;'>
                    <a href='$verifyLink' style='background:#2563eb; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; font-weight:bold; display:inline-block;'>Verify My Account</a>
                </div>
                <p style='color: #64748b; font-size: 13px;'>If the button doesn't work, copy and paste this link into your browser:</p>
                <p style='color: #2563eb; word-break: break-all; font-size: 13px;'>$verifyLink</p>
                <br>
                <p style='color: #475569;'>Regards,<br><strong>eRegistrar Team</strong></p>
            </div>
        "
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 201 || $httpCode === 200) {
        return true;
    } else {
        error_log("Brevo API Verification Mail Error [$httpCode]: " . $response);
        return false;
    }
}