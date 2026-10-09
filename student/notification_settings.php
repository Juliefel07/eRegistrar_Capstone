<?php
session_start();
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$status_type = '';
$message = '';

// Check and create notification preference columns if they don't exist yet
$check_cols = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'notify_documents'");
if (mysqli_num_rows($check_cols) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN notify_documents TINYINT(1) DEFAULT 1, ADD COLUMN notify_messages TINYINT(1) DEFAULT 1");
}

// Handle Settings Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $notify_documents = isset($_POST['notify_documents']) ? 1 : 0;
    $notify_messages  = isset($_POST['notify_messages']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE users SET notify_documents = ?, notify_messages = ? WHERE user_id = ?");
    $stmt->bind_param("iii", $notify_documents, $notify_messages, $user_id);

    if ($stmt->execute()) {
        $status_type = 'success';
        $message = "Notification preferences saved successfully!";
    } else {
        $status_type = 'error';
        $message = "Failed to update preferences. Please try again.";
    }
}

// Fetch current user notification preferences
$stmt = $conn->prepare("SELECT notify_documents, notify_messages FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_prefs = $stmt->get_result()->fetch_assoc();

$notify_docs_checked = ($user_prefs['notify_documents'] ?? 1) == 1 ? 'checked' : '';
$notify_msg_checked  = ($user_prefs['notify_messages'] ?? 1) == 1 ? 'checked' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eRegistrar | Notification Settings</title>

    <link rel="icon" type="image/png" href="../assets/images/logooo.png?v=3">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">

    <!-- Anti-Flicker Script: Load dark mode before page render -->
    <script>
        if (localStorage.getItem("darkMode") === "enabled") {
            document.documentElement.classList.add("dark-mode");
            document.addEventListener("DOMContentLoaded", () => {
                document.body.classList.add("dark-mode");
            });
        }
    </script>

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

        /* DARK MODE CSS VARIABLE OVERRIDES */
        body.dark-mode {
            --primary: #38bdf8;
            --primary-hover: #0284c7;
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --border: #334155;
            --text: #f8fafc;
            --text-muted: #94a3b8;
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
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .student-main {
            max-width: 700px;
            width: 100%;
            margin: 30px auto;
            padding: 0 20px 60px;
        }

        .settings-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            transition: background-color 0.2s, border-color 0.2s;
        }

        .settings-card h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
        }

        .settings-description {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 16px;
        }

        .section-subtitle {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin: 20px 0 12px;
        }

        .settings-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 14px;
            transition: all 0.2s ease;
        }

        .settings-item:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        body.dark-mode .settings-item:hover {
            border-color: #475569;
        }

        .settings-item-info h3 {
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 3px;
        }

        .settings-item-info p {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* Toggle Switch UI */
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 26px;
            flex-shrink: 0;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .3s;
            border-radius: 34px;
        }

        body.dark-mode .slider {
            background-color: #475569;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        input:checked + .slider {
            background-color: #0056b3;
        }

        body.dark-mode input:checked + .slider {
            background-color: #38bdf8;
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .btn-save {
            width: 100%;
            background-color: #0056b3;
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
            margin-top: 20px;
        }

        body.dark-mode .btn-save {
            background-color: #0284c7;
        }

        .btn-save:hover {
            background-color: var(--primary-hover);
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

        body.dark-mode .alert-success {
            background-color: #064e3b;
            color: #6ee7b7;
            border-color: #047857;
        }

        body.dark-mode .alert-error {
            background-color: #7f1d1d;
            color: #fca5a5;
            border-color: #b91c1c;
        }

        /* Mobile Layout */
        @media (max-width: 600px) {
            .student-main {
                margin: 15px auto;
                padding: 0 14px 80px;
            }

            .settings-card {
                padding: 22px 16px;
            }

            .settings-item {
                padding: 16px;
            }
        }
    </style>
</head>
<body>

    <!-- Include Navbar -->
    <?php require_once "navbar.php"; ?>

    <div class="student-main">
        <div class="settings-card">
            
            <h2>
                <i class="fa-solid fa-bell"></i> Notification Settings
            </h2>
            <p class="settings-description">
                Manage how you receive alerts and updates from the Registrar.
            </p>

            <!-- Alerts -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $status_type === 'success' ? 'success' : 'error'; ?>">
                    <i class="fa-solid <?= $status_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                    <span><?= htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Appearance Section -->
            <div class="section-subtitle">Appearance</div>
            <div class="settings-item">
                <div class="settings-item-info">
                    <h3><i class="fa-solid fa-moon" style="color: #6366f1;"></i> Dark Mode</h3>
                    <p>Switch between light and dark portal appearance.</p>
                </div>
                <label class="switch">
                    <input type="checkbox" id="darkModeToggle">
                    <span class="slider"></span>
                </label>
            </div>

            <!-- Email & System Alerts Section -->
            <div class="section-subtitle">Alert Preferences</div>
            <form method="POST" action="notification_settings.php">

                <div class="settings-item">
                    <div class="settings-item-info">
                        <h3><i class="fa-solid fa-file-signature" style="color: var(--primary);"></i> Document Updates</h3>
                        <p>Receive notifications when your request status changes.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="notify_documents" value="1" <?= $notify_docs_checked; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="settings-item">
                    <div class="settings-item-info">
                        <h3><i class="fa-solid fa-envelope" style="color: #10b981;"></i> Direct Messages</h3>
                        <p>Receive alerts when the Registrar sends a message.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="notify_messages" value="1" <?= $notify_msg_checked; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <button type="submit" name="save_settings" class="btn-save">
                    <i class="fa-solid fa-floppy-disk"></i> Save Preferences
                </button>

            </form>

        </div>
    </div>

    <!-- Dark Mode Synchronizing JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById("darkModeToggle");

            if (toggle) {
                // Initialize toggle state based on localStorage
                if (localStorage.getItem("darkMode") === "enabled") {
                    document.body.classList.add("dark-mode");
                    toggle.checked = true;
                }

                toggle.addEventListener("change", function() {
                    if (this.checked) {
                        document.body.classList.add("dark-mode");
                        localStorage.setItem("darkMode", "enabled");
                    } else {
                        document.body.classList.remove("dark-mode");
                        localStorage.setItem("darkMode", "disabled");
                    }
                });
            }
        });
    </script>

</body>
</html>