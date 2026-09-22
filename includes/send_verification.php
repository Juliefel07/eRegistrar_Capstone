<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../PHPMailer/src/Exception.php";
require_once __DIR__ . "/../PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/../PHPMailer/src/SMTP.php";

function sendVerificationEmail($email, $fullname, $token)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;

        // Your Gmail account
        $mail->Username = "eregistrar.cctc@gmail.com";

        // Paste your Google App Password here
        $mail->Password = "wsbc rrik uejt gxpz";

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom("eregistrar.cctc@gmail.com", "eRegistrar");
        $mail->addAddress($email, $fullname);

        $mail->isHTML(true);
        $mail->Subject = "Verify your eRegistrar Account";

        // Change this if your project folder has a different name
        $verifyLink = "http://localhost/eRegistrar/verify.php?token=" . $token;

        $mail->Body = "
            <h2>Welcome to eRegistrar</h2>

            <p>Hello <b>$fullname</b>,</p>

            <p>Thank you for registering.</p>

            <p>Please click the button below to verify your account.</p>

            <p>
                <a href='$verifyLink'
                   style='background:#0d6efd;
                          color:white;
                          padding:12px 20px;
                          text-decoration:none;
                          border-radius:6px;'>
                    Verify My Account
                </a>
            </p>

            <p>If the button doesn't work, copy and paste this link into your browser:</p>

            <p>$verifyLink</p>

            <br>

            <p>Regards,<br>eRegistrar Team</p>
        ";

        $mail->send();

        return true;

    } catch (Exception $e) {

        return false;

    }
}