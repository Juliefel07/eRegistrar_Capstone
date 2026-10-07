<?php
session_start();
include "includes/db.php";

// Redirect if there is no pending verification session
if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['pending_email'];
$error = '';
$success = '';

// Handle OTP Form Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    // Combine the 6 individual inputs into one string
    $entered_otp = implode('', $_POST['otp_digits'] ?? []);

    if (strlen($entered_otp) < 6) {
        $error = "Please enter the complete 6-digit verification code.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, otp_code, otp_expires_at FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            $now = date("Y-m-d H:i:s");

            if ($user['otp_code'] !== $entered_otp) {
                $error = "Invalid verification code. Please check and try again.";
            } elseif ($now > $user['otp_expires_at']) {
                $error = "The verification code has expired. Please click 'Resend Code'.";
            } else {
                // Mark user as verified
                $update = $conn->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE user_id = ?");
                $update->bind_param("i", $user['user_id']);
                $update->execute();

                // Clear temporary session and set success login state
                unset($_SESSION['pending_email']);
                $_SESSION['success'] = "Account verified successfully! You can now log in.";
                header("Location: login.php");
                exit();
            }
        } else {
            $error = "User account not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eRegistrar | Verify OTP</title>
    <!-- FontAwesome & Google Fonts -->
     <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .otp-card {
            background: #ffffff;
            width: 100%;
            max-width: 440px;
            padding: 40px 32px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid #e2e8f0;
            text-align: center;
        }

        .icon-box {
            width: 60px;
            height: 60px;
            background-color: #eff6ff;
            color: #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 20px;
        }

        .otp-card h2 {
            color: #0f172a;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .otp-card p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .user-email {
            color: #1e3a8a;
            font-weight: 600;
            word-break: break-all;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-align: left;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .otp-inputs {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 28px;
        }

        .otp-digit {
            width: 48px;
            height: 56px;
            border-radius: 10px;
            border: 1.5px solid #cbd5e1;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            outline: none;
            transition: all 0.2s ease;
            background: #f8fafc;
        }

        .otp-digit:focus {
            border-color: #2563eb;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .btn-submit {
            width: 100%;
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-submit:hover {
            background-color: #1d4ed8;
        }

        .resend-box {
            margin-top: 24px;
            font-size: 14px;
            color: #64748b;
        }

        .resend-btn {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }

        .resend-btn:disabled {
            color: #94a3b8;
            cursor: not-allowed;
            text-decoration: none;
        }

        .timer-text {
            color: #64748b;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="otp-card">
    <div class="icon-box">
        <i class="fa-solid fa-envelope-circle-check"></i>
    </div>

    <h2>Verify Your Email</h2>
    <p>We sent a 6-digit verification code to<br><span class="user-email"><?php echo htmlspecialchars($email); ?></span></p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo $success; ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="verify_otp.php" id="otpForm">
        <div class="otp-inputs">
            <input type="text" name="otp_digits[]" class="otp-digit" maxlength="1" pattern="\d*" inputmode="numeric" required autofocus>
            <input type="text" name="otp_digits[]" class="otp-digit" maxlength="1" pattern="\d*" inputmode="numeric" required>
            <input type="text" name="otp_digits[]" class="otp-digit" maxlength="1" pattern="\d*" inputmode="numeric" required>
            <input type="text" name="otp_digits[]" class="otp-digit" maxlength="1" pattern="\d*" inputmode="numeric" required>
            <input type="text" name="otp_digits[]" class="otp-digit" maxlength="1" pattern="\d*" inputmode="numeric" required>
            <input type="text" name="otp_digits[]" class="otp-digit" maxlength="1" pattern="\d*" inputmode="numeric" required>
        </div>

        <button type="submit" name="verify_otp" class="btn-submit">Verify & Continue</button>
    </form>

    <div class="resend-box">
        Didn't receive the code? 
        <button id="resendBtn" class="resend-btn" onclick="resendOTP()" disabled>Resend Code</button>
        <span id="timerContainer" class="timer-text">(<span id="timer">60</span>s)</span>
    </div>
</div>
<script>
    // 1. Request Notification Permission on Page Load
    document.addEventListener('DOMContentLoaded', () => {
        if ("Notification" in window && Notification.permission !== "granted" && Notification.permission !== "denied") {
            Notification.requestPermission();
        }
    });

    // Helper function to show Push Notification
    function showPushNotification(title, message, iconUrl = '') {
        if ("Notification" in window && Notification.permission === "granted") {
            new Notification(title, {
                body: message,
                icon: iconUrl || 'https://cdn-icons-png.flaticon.com/512/732/732200.png' // Default email icon
            });
        } else {
            // Fallback to standard alert if notifications are blocked or unsupported
            alert(message);
        }
    }

    // 2. Auto-Focus Logic for 6 Single-Digit Inputs
    const inputs = document.querySelectorAll('.otp-digit');

    inputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            input.value = input.value.replace(/[^0-9]/g, '');

            if (input.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && index > 0) {
                inputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = e.clipboardData.getData('text').trim().slice(0, 6);
            if (/^\d+$/.test(pasteData)) {
                pasteData.split('').forEach((char, i) => {
                    if (inputs[i]) inputs[i].value = char;
                });
                if (inputs[Math.min(pasteData.length, inputs.length - 1)]) {
                    inputs[Math.min(pasteData.length, inputs.length - 1)].focus();
                }
            }
        });
    });

    // 3. Countdown Timer Logic for Resend Link
    let timeLeft = 60;
    const timerElement = document.getElementById('timer');
    const timerContainer = document.getElementById('timerContainer');
    const resendBtn = document.getElementById('resendBtn');

    function startTimer() {
        resendBtn.disabled = true;
        timerContainer.style.display = 'inline';
        timeLeft = 60;

        const countdown = setInterval(() => {
            timeLeft--;
            timerElement.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(countdown);
                resendBtn.disabled = false;
                timerContainer.style.display = 'none';
            }
        }, 1000);
    }

    // Start timer on load
    startTimer();

    // 4. Resend OTP Action with Push Notification
    function resendOTP() {
        if (resendBtn.disabled) return;

        resendBtn.disabled = true;
        resendBtn.innerText = "Sending...";

        fetch('resend_otp_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=resend'
        })
        .then(async response => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (err) {
                console.error('Server Output:', text);
                throw new Error('Server returned non-JSON content.');
            }
        })
        .then(data => {
            if (data.status === 'success') {
                // Trigger Native Push Notification
                showPushNotification(
                    'eRegistrar Verification',
                    'A new 6-digit OTP verification code has been sent to your email.'
                );

                resendBtn.innerText = "Resend Code";
                startTimer();
            } else {
                showPushNotification(
                    'eRegistrar Error',
                    data.message || 'Failed to resend code.'
                );
                resendBtn.innerText = "Resend Code";
                resendBtn.disabled = false;
            }
        })
        .catch(err => {
            showPushNotification(
                'eRegistrar Error',
                err.message || 'An error occurred while resending the code.'
            );
            resendBtn.innerText = "Resend Code";
            resendBtn.disabled = false;
        });
    }
</script>

</body>
</html>