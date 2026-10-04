<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id =$_SESSION['user_id'];

$result = mysqli_query($conn, "
    SELECT *
    FROM notifications
    WHERE user_id='$user_id'
    ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - eRegistrar</title>
    <!-- FontAwesome Icons -->
      <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    
    <style>
        /* Mobile-First Layout & Responsiveness */
        .student-main {
            padding: 20px 15px;
            max-width: 750px;
            margin: 0 auto;
            padding-bottom: 90px; /* Extra padding for mobile bottom nav */
        }

        .form-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .form-card h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 5px;
        }

        .form-card > p {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .notification-link {
            text-decoration: none;
            color: inherit;
            display: block;
            margin-bottom: 12px;
        }

        .notification-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.2s ease;
        }

        .notification-box:hover {
            background: #f0f6ff;
            border-color: #b8daff;
        }

        .notification-box h4 {
            font-size: 14px;
            margin: 0 0 6px 0;
            color: #0056b3;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notification-box p {
            font-size: 13px;
            color: #495057;
            margin: 0 0 10px 0;
            line-height: 1.4;
            word-break: break-word;
        }

        .notification-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #6c757d;
        }

        .notification-status {
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            background: #e2e8f0;
            color: #4a5568;
            font-size: 10px;
        }

        .notification-box.empty {
            text-align: center;
            color: #6c757d;
            padding: 30px;
        }

        /* Responsive Modal */
        .notification-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .notification-modal.show {
            display: flex;
        }

        .notification-modal-box {
            background: #ffffff;
            width: 100%;
            max-width: 420px;
            border-radius: 12px;
            padding: 20px;
            position: relative;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            animation: modalPop 0.2s ease;
        }

        @keyframes modalPop {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .notification-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }

        .notification-modal-box h2 {
            font-size: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #333;
        }

        .notification-modal-box p {
            font-size: 13px;
            color: #444;
            line-height: 1.5;
            margin-bottom: 15px;
            word-break: break-word;
        }

        .notification-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e9ecef;
            padding-top: 10px;
            font-size: 11px;
            color: #6c757d;
        }

        /* Mobile specific adjustments */
        @media (max-width: 576px) {
            .student-main {
                padding: 10px 8px;
            }
            .form-card {
                padding: 15px 12px;
                border-radius: 8px;
            }
        }
    </style>
</head>

<body>

    <?php include("navbar.php"); ?>

    <div class="student-main">
        <div class="form-card">
            <h2>Notifications</h2>
            <p>Updates about your document requests.</p>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <a href="#" 
                       class="notification-link" 
                       onclick="openNotification(
                           '<?= $row['notification_id']; ?>',
                           `<?= htmlspecialchars($row['message'], ENT_QUOTES); ?>`,
                           '<?= $row['created_at']; ?>',
                           '<?= $row['status']; ?>'
                       ); return false;">
                        
                        <div class="notification-box">
                            <h4><i class="fa-solid fa-bell"></i> Notification</h4>
                            <p><?= htmlspecialchars($row['message']); ?></p>
                            <div class="notification-footer">
                                <small><?= $row['created_at']; ?></small>
                                <span class="notification-status"><?= $row['status']; ?></span>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="notification-box empty">
                    <p><i class="fa-regular fa-bell-slash" style="font-size: 24px; margin-bottom: 8px; display: block;"></i> No notifications available.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Single, Unified Responsive Modal -->
    <div class="notification-modal" id="notificationModal">
        <div class="notification-modal-box">
            <button class="notification-close" onclick="closeNotification()">×</button>
            <h2><i class="fa-solid fa-bell"></i> Notification Details</h2>
            <p id="modalMessage"></p>
            <div class="notification-details">
                <span class="notification-status" id="modalStatus"></span>
                <small id="modalDate"></small>
            </div>
        </div>
    </div>

    <script>
        function openNotification(id, message, date, status) {
            // Mark as read asynchronously
            fetch("mark_notification_read.php?id=" + id)
                .catch(err => console.log('Error marking as read:', err));

            // Populate modal contents
            document.getElementById("modalMessage").innerText = message;
            document.getElementById("modalDate").innerText = date;
            document.getElementById("modalStatus").innerText = status;

            // Show modal
            document.getElementById("notificationModal").classList.add("show");
        }

        function closeNotification() {
            document.getElementById("notificationModal").classList.remove("show");
        }

        // Close modal when clicking outside the box area
        window.onclick = function(event) {
            let modal = document.getElementById("notificationModal");
            if (event.target === modal) {
                closeNotification();
            }
        }
    </script>
</body>
</html>