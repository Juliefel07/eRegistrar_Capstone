<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eRegistrar | Settings</title>

    <link rel="icon" type="image/png" href="../assets/images/logooo.png?v=3">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">

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
        }

        .student-main {
            max-width: 800px;
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

        .settings-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .settings-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 20px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .settings-item:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            transform: translateY(-1px);
        }

        .settings-item > i {
            width: 44px;
            height: 44px;
            background-color: #eff6ff;
            color: var(--primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .settings-item-content {
            flex: 1;
        }

        .settings-item-content h3 {
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 2px;
        }

        .settings-item-content p {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .settings-btn {
            background-color: #f1f5f9;
            color: var(--primary);
            text-decoration: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .settings-btn:hover {
            background-color: var(--primary);
            color: #ffffff;
        }

        /* Mobile Responsive Layout */
        @media (max-width: 600px) {
            .student-main {
                margin: 15px auto;
                padding: 0 14px 80px; /* Space for mobile bottom bar */
            }

            .settings-card {
                padding: 22px 16px;
            }

            .settings-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                padding: 16px;
            }

            .settings-item-header {
                display: flex;
                align-items: center;
                gap: 12px;
                width: 100%;
            }

            .settings-btn {
                width: 100%;
                padding: 11px;
                margin-top: 4px;
            }
        }
    </style>
</head>
<body>

    <!-- Include Navbar -->
    <?php require_once __DIR__ . "/navbar.php"; ?>

    <div class="student-main">
        <div class="settings-card">
            
            <h2>
                <i class="fa-solid fa-gear"></i> Settings
            </h2>
            <p class="settings-description">
                Manage your account, preferences, and portal settings.
            </p>

            <div class="settings-section">

                <!-- Profile Item -->
                <div class="settings-item">
                    <i class="fa-solid fa-user"></i>
                    <div class="settings-item-content">
                        <h3>Profile Information</h3>
                        <p>Update your personal information and profile picture.</p>
                    </div>
                    <a href="profile.php" class="settings-btn">Manage</a>
                </div>

                <!-- Password Item -->
                <div class="settings-item">
                    <i class="fa-solid fa-lock"></i>
                    <div class="settings-item-content">
                        <h3>Password & Security</h3>
                        <p>Change your password to keep your account secure.</p>
                    </div>
                    <a href="change_password.php" class="settings-btn">Change</a>
                </div>

                <!-- Notifications Item -->
                <div class="settings-item">
                    <i class="fa-solid fa-bell"></i>
                    <div class="settings-item-content">
                        <h3>Notification Settings</h3>
                        <p>Manage how you receive updates about document requests and messages.</p>
                    </div>
                    <a href="notification_settings.php" class="settings-btn">Manage</a>
                </div>

                <!-- Messages Item -->
                <div class="settings-item">
                    <i class="fa-solid fa-envelope"></i>
                    <div class="settings-item-content">
                        <h3>Messages</h3>
                        <p>View conversations and contact the Registrar Office.</p>
                    </div>
                    <a href="messages.php" class="settings-btn">Open</a>
                </div>

                <!-- Help Item -->
                <div class="settings-item">
                    <i class="fa-solid fa-circle-question"></i>
                    <div class="settings-item-content">
                        <h3>Help & Support</h3>
                        <p>Find answers or send concerns to the Registrar.</p>
                    </div>
                    <a href="support.php" class="settings-btn">Visit</a>
                </div>

            </div>

        </div>
    </div>

    <script>
        // Dark Mode Handler
        const toggle = document.getElementById("darkModeToggle");

        if (toggle) {
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
    </script>

</body>
</html>