<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Check if 'is_read' column exists; auto-add if missing
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'is_read'");
if (mysqli_num_rows($check_col) == 0) {
    @mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0");
}

// HANDLE AJAX REQUESTS FOR MARKING AS READ/UNREAD
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $notif_id = (int)$_POST['notification_id'];
    $action =$_POST['ajax_action'];

    // Identify primary key column name (id or notification_id)
    $pk_check = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'id'");
    $pk_col = (mysqli_num_rows($pk_check) > 0) ? 'id' : 'notification_id';

    if ($action === 'mark_read') {
        mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE `$pk_col` = '$notif_id' AND user_id = '$user_id'");
    } elseif ($action === 'mark_unread') {
        mysqli_query($conn, "UPDATE notifications SET is_read = 0 WHERE `$pk_col` = '$notif_id' AND user_id = '$user_id'");
    }

    // Get recalculated unread count for current student
    $cnt_res = mysqli_query($conn, "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = '$user_id' AND (is_read = 0 OR is_read IS NULL)");
    $new_unread = mysqli_fetch_assoc($cnt_res)['unread_count'] ?? 0;
    $_SESSION['unread_notifications'] =$new_unread;

    echo json_encode(['success' => true, 'unread_count' => $new_unread]);
    exit();
}

// Fetch all notifications for current student
$result = mysqli_query($conn, "
    SELECT *
    FROM notifications
    WHERE user_id='$user_id'
    ORDER BY created_at DESC
");

// Fetch Unread Count
$unread_query = mysqli_query($conn, "
    SELECT COUNT(*) AS unread_count 
    FROM notifications 
    WHERE user_id = '$user_id' AND (is_read = 0 OR is_read IS NULL)
");
$unread_count = mysqli_fetch_assoc($unread_query)['unread_count'] ?? 0;
$_SESSION['unread_notifications'] =$unread_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - eRegistrar</title>
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    
    <style>
        .student-main {
            padding: 20px 15px;
            max-width: 750px;
            margin: 0 auto;
            padding-bottom: 90px;
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
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .form-card > p {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .notification-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .notification-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }

        .notification-card.unread {
            background-color: #f0f9ff;
            border-left: 5px solid #2563eb;
        }

        .notification-card.unread .notif-msg {
            font-weight: 600;
            color: #0f172a;
        }

        .notification-card.read {
            border-left: 5px solid #cbd5e1;
            opacity: 0.85;
        }

        .notif-title {
            font-size: 14px;
            font-weight: 700;
            color: #0056b3;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notif-msg {
            font-size: 13px;
            color: #334155;
            margin: 0 0 10px 0;
            line-height: 1.45;
            word-break: break-word;
        }

        .status-dot {
            height: 8px;
            width: 8px;
            background-color: #2563eb;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .notification-footer-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #64748b;
        }

        .notification-status-badge {
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            text-transform: uppercase;
        }

        .notification-status-badge.unread-tag {
            background: #dbeafe;
            color: #1e40af;
        }

        .notification-status-badge.read-tag {
            background: #f1f5f9;
            color: #64748b;
        }

        .empty-notifications {
            text-align: center;
            color: #6c757d;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        /* Responsive Modal */
        .notification-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(3px);
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
            max-width: 460px;
            border-radius: 12px;
            padding: 20px;
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
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
            color: #64748b;
        }

        .notification-modal-box h2 {
            font-size: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #0f172a;
        }

        .notification-modal-box p {
            font-size: 14px;
            color: #334155;
            line-height: 1.55;
            margin-bottom: 20px;
            word-break: break-word;
        }

        .notification-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 14px;
            font-size: 12px;
            color: #64748b;
        }

        .btn-toggle-read {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-toggle-read:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        @media (max-width: 576px) {
            .student-main { padding: 10px 8px; }
            .form-card { padding: 15px 12px; border-radius: 8px; }
        }
    </style>
</head>

<body>

    <?php include("navbar.php"); ?>

    <div class="student-main">
        <div class="form-card">
            <h2>
                <span>Notifications</span>
            </h2>
            <p>Updates about your document requests and announcements.</p>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php 
                        $is_read = isset($row['is_read']) &&$row['is_read'] == 1;
                        $notif_id = $row['id'] ?? $row['notification_id'];$formatted_time = date("M d, Y h:i A", strtotime($row['created_at']));
                    ?>
                    <div class="notification-card <?= $is_read ? 'read' : 'unread'; ?>" 
                         id="notif-card-<?= $notif_id; ?>"
                         onclick="openNotification(<?= $notif_id; ?>)"
                         data-read="<?= $is_read ? 'true' : 'false'; ?>"
                         data-message="<?= htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8'); ?>"
                         data-time="<?= $formatted_time; ?>">
                        
                        <div class="notif-title">
                            <i class="fa-solid fa-bell"></i> Notification
                        </div>
                        <p class="notif-msg" id="notif-text-<?= $notif_id; ?>">
                            <?php if (!$is_read): ?>
                                <span class="status-dot" id="dot-<?= $notif_id; ?>"></span>
                            <?php endif; ?>
                            <?= htmlspecialchars($row['message']); ?>
                        </p>
                        <div class="notification-footer-meta">
                            <small><i class="fa-regular fa-clock"></i> <?= $formatted_time; ?></small>
                            <span class="notification-status-badge <?= $is_read ? 'read-tag' : 'unread-tag'; ?>" id="tag-<?= $notif_id; ?>">
                                <?= $is_read ? 'Read' : 'Unread'; ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-notifications">
                    <i class="fa-regular fa-bell-slash" style="font-size: 24px; margin-bottom: 8px; display: block;"></i> 
                    No notifications available.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Responsive Notification Details Modal -->
    <div class="notification-modal" id="notificationModal">
        <div class="notification-modal-box">
            <button class="notification-close" onclick="closeNotification()">×</button>
            <h2><i class="fa-solid fa-bell" style="color: #2563eb;"></i> Notification Details</h2>
            <p id="modalMessage"></p>
            <div class="notification-details">
                <small id="modalDate"></small>
                <button class="btn-toggle-read" id="btnToggleRead" onclick="toggleModalReadStatus()">
                    <i class="fa-regular fa-envelope"></i> Mark as Unread
                </button>
            </div>
        </div>
    </div>

    <script>
    let currentActiveNotifId = null;
    let currentActiveIsRead = false;

    function openNotification(id) {
        currentActiveNotifId = id;
        const card = document.getElementById('notif-card-' + id);
        if (!card) return;

        currentActiveIsRead = (card.getAttribute('data-read') === 'true');
        const message = card.getAttribute('data-message');
        const time = card.getAttribute('data-time');

        document.getElementById("modalMessage").innerText = message;
        document.getElementById("modalDate").innerHTML = '<i class="fa-regular fa-clock"></i> ' + time;

        // Show Modal
        document.getElementById("notificationModal").classList.add("show");

        // AUTOMATICALLY MARK AS READ IF CURRENTLY UNREAD
        if (!currentActiveIsRead) {
            updateNotificationStatus(id, 'mark_read');
        } else {
            updateModalButtonText();
        }
    }

    function closeNotification() {
        document.getElementById("notificationModal").classList.remove("show");
    }

    function updateModalButtonText() {
        const btn = document.getElementById('btnToggleRead');
        if (currentActiveIsRead) {
            btn.innerHTML = '<i class="fa-regular fa-envelope"></i> Mark as Unread';
        } else {
            btn.innerHTML = '<i class="fa-regular fa-envelope-open"></i> Mark as Read';
        }
    }

    function toggleModalReadStatus() {
        if (!currentActiveNotifId) return;

        const targetAction = currentActiveIsRead ? 'mark_unread' : 'mark_read';
        updateNotificationStatus(currentActiveNotifId, targetAction);
    }

    function updateNotificationStatus(id, action) {
        const formData = new FormData();
        formData.append('ajax_action', action);
        formData.append('notification_id', id);

        fetch('notifications.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const card = document.getElementById('notif-card-' + id);
                const textContainer = document.getElementById('notif-text-' + id);
                const tag = document.getElementById('tag-' + id);
                let dot = document.getElementById('dot-' + id);

                if (action === 'mark_read') {
                    currentActiveIsRead = true;
                    if (card) {
                        card.classList.remove('unread');
                        card.classList.add('read');
                        card.setAttribute('data-read', 'true');
                    }
                    if (dot) dot.style.display = 'none';
                    if (tag) {
                        tag.innerText = 'Read';
                        tag.className = 'notification-status-badge read-tag';
                    }
                } else {
                    currentActiveIsRead = false;
                    if (card) {
                        card.classList.remove('read');
                        card.classList.add('unread');
                        card.setAttribute('data-read', 'false');
                    }
                    if (tag) {
                        tag.innerText = 'Unread';
                        tag.className = 'notification-status-badge unread-tag';
                    }
                    if (!dot && textContainer) {
                        dot = document.createElement('span');
                        dot.className = 'status-dot';
                        dot.id = 'dot-' + id;
                        textContainer.insertBefore(dot, textContainer.firstChild);
                    } else if (dot) {
                        dot.style.display = 'inline-block';
                    }
                }

                updateModalButtonText();
                updateNavbarBadges(data.unread_count);
            }
        })
        .catch(err => console.error("Error updating status:", err));
    }
function updateNavbarBadges(count) {
    const badge = document.getElementById('navNotificationBadge');
    if (badge) {
        if (count > 0) {
            badge.innerText = count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
}

    // Close modal when clicking outside box area
    window.onclick = function(event) {
        let modal = document.getElementById("notificationModal");
        if (event.target === modal) {
            closeNotification();
        }
    }
    </script>
</body>
</html>