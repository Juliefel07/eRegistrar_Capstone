<?php
session_start();
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$status_type = ''; // 'success' or 'error'
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    $current = trim($_POST['current_password'] ?? '');
    $new     = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if (empty($current) || empty($new) || empty($confirm)) {
        $status_type = 'error';
        $message = "Please fill in all required password fields.";
    } elseif ($new !== $confirm) {
        $status_type = 'error';
        $message = "New passwords do not match.";
    } elseif (strlen($new) < 8) {
        $status_type = 'error';
        $message = "New password must be at least 8 characters long.";
    } elseif ($current === $new) {
        $status_type = 'error';
        $message = "New password cannot be the same as your current password.";
    } else {
        // Fetch current password securely using prepared statement
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            if (password_verify($current, $user['password'])) {
                
                // Hash and update new password
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $update_stmt->bind_param("si", $hashed, $user_id);

                if ($update_stmt->execute()) {
                    $status_type = 'success';
                    $message = "Password updated successfully!";
                } else {
                    $status_type = 'error';
                    $message = "Failed to update password. Please try again.";
                }

            } else {
                $status_type = 'error';
                $message = "Current password is incorrect.";
            }
        } else {
            $status_type = 'error';
            $message = "Account not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eRegistrar | Change Password</title>
    <link rel="icon" type="image/png" href="../assets/images/logooo.png?v=3">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        :root {
            --primary: #0056b3;
            --primary-hover: #004494;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .student-main {
            flex: 1;
            max-width: 520px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px 40px;
        }

        .password-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        .card-header {
            margin-bottom: 24px;
            text-align: center;
        }

        .card-header .icon-circle {
            width: 54px;
            height: 54px;
            background-color: #eff6ff;
            color: var(--primary);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 12px;
        }

        .card-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text);
        }

        .card-header p {
            font-size: 13.5px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13.5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 42px 12px 14px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            font-size: 14px;
            outline: none;
            background: #f8fafc;
            transition: all 0.2s ease;
        }

        .input-wrapper input:focus {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 86, 179, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            color: #94a3b8;
            cursor: pointer;
            font-size: 15px;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .btn-submit {
            width: 100%;
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
        }

        /* Mobile Optimization */
        @media (max-width: 576px) {
            .student-main {
                margin: 20px auto;
                padding: 0 16px 80px; /* Added extra bottom padding for mobile navbar */
            }

            .password-card {
                padding: 24px 18px;
            }

            .card-header h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

    <!-- Include Navbar Header -->
<!-- TO THIS: -->
 <?php require_once __DIR__ . "/navbar.php"; ?>
    <div class="student-main">
        <div class="password-card">
            
            <div class="card-header">
                <div class="icon-circle">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h2>Change Password</h2>
                <p>Update your account password to stay secure.</p>
            </div>

            <!-- Notification Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $status_type === 'success' ? 'success' : 'error'; ?>">
                    <i class="fa-solid <?= $status_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                    <span><?= htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="change_password.php">
                
                <div class="form-group">
                    <label>Current Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="current_password" id="current_password" placeholder="Enter current password" required>
                        <i class="fa-regular fa-eye toggle-password" onclick="togglePass('current_password', this)"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="new_password" id="new_password" placeholder="At least 8 characters" required>
                        <i class="fa-regular fa-eye toggle-password" onclick="togglePass('new_password', this)"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-type new password" required>
                        <i class="fa-regular fa-eye toggle-password" onclick="togglePass('confirm_password', this)"></i>
                    </div>
                </div>

                <button type="submit" name="change_password" class="btn-submit">
                    <i class="fa-solid fa-lock"></i> Update Password
                </button>

            </form>

        </div>
    </div>

    <!-- Toggle Password Visibility JS -->
    <script>
        function togglePass(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

</body>
</html>