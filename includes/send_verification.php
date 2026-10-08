<?php

function sendVerificationEmail($email, $fullname, $otpCode)
{
    $apiKey = getenv('BREVO_API_KEY');
    $url = 'https://api.brevo.com/v3/smtp/email';

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
        'subject' => 'eRegistrar - Account Verification Code',
        'htmlContent' => "
            <!DOCTYPE html>
            <html>
            <body style='margin:0; padding:0; background-color: #f8fafc; font-family: Arial, sans-serif;'>
                <div style='max-width: 500px; margin: 20px auto; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; text-align: center;'>
                    <h2 style='color: #1e3a8a; margin-top: 0;'>eRegistrar System</h2>
                    <p style='color: #475569; font-size: 15px;'>Hello <strong>" . htmlspecialchars($fullname) . "</strong>,<br>Your 6-digit verification code is below:</p>
                    <div style='background-color: #f1f5f9; border-radius: 10px; padding: 18px; margin: 20px 0;'>
                        <span style='font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #1e3a8a;'>" . htmlspecialchars($otpCode) . "</span>
                    </div>
                    <p style='color: #64748b; font-size: 13px;'>⏱️ Code expires in 10 minutes.</p>
                    <p style='color: #94a3b8; font-size: 12px; margin-top: 20px;'>If you did not request this code, please ignore this email.</p>
                </div>
            </body>
            </html>
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