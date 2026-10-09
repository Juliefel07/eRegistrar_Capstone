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

// HANDLE AJAX REQUESTS (MARK READ/UNREAD & CLEAR ALL)
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action =$_POST['ajax_action'];

    // Identify primary key column name (id or notification_id)
    $pk_check = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'id'");
    $pk_col = (mysqli_num_rows($pk_check) > 0) ? 'id' : 'notification_id';

    if ($action === 'mark_read') {
        $notif_id = (int)$_POST['notification_id'];
        mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE `$pk_col` = '$notif_id' AND user_id = '$user_id'");
    } elseif ($action === 'mark_unread') {
        $notif_id = (int)$_POST['notification_id'];
        mysqli_query($conn, "UPDATE notifications SET is_read = 0 WHERE `$pk_col` = '$notif_id' AND user_id = '$user_id'");
    } elseif ($action === 'clear_all') {
        mysqli_query($conn, "DELETE FROM notifications WHERE user_id = '$user_id'");
    }

    // Get recalculated unread count for current user
    $cnt_res = mysqli_query($conn, "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = '$user_id' AND (is_read = 0 OR is_read IS NULL)");
    $new_unread = mysqli_fetch_assoc($cnt_res)['unread_count'] ?? 0;
    $_SESSION['unread_notifications'] =$new_unread;

    echo json_encode(['success' => true, 'unread_count' => $new_unread]);
    exit();
}

// Fetch all notifications for current user
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .student-main {
            padding: 24px 16px;
            max-width: 750px;
            margin: 0 auto;
            padding-bottom: 90px;
        }

        .form-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .form-card h2 {
            font-size: 1.25rem;
            color: #0f172a;
            margin: 0;
            font-weight: 700;
        }

        .form-card > p {
            font-size: 0.88rem;
            color: #64748b;
            margin-bottom: 20px;
        }

        .btn-clear-all {
            background: #fee2e2;
            color: #991b1b;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-clear-all:hover {
            background: #fecaca;
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
        }

        .notification-card.unread .notif-msg {
            font-weight: 600;
            color: #0f172a;
        }

        .notification-card.read {
            background-color: #ffffff !important;
            opacity: 0.85 !important;
            display: block !important;
        }

        .notif-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: #2563eb;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notif-msg {
            font-size: 0.85rem;
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
            font-size: 0.75rem;
            color: #64748b;
        }

        .notification-status-badge {
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
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
            color: #64748b;
            padding: 40px 20px;
            background: #f8fafc;
            border-radius: 8px;
            border: 2px dashed #e2e8f0;
        }

        /* Generic Modal Styling */
        .notification-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
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
            max-width: 440px;
            border-radius: 12px;
            padding: 24px;
            position: relative;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: modalPop 0.2s ease;
        }

        @keyframes modalPop {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .notification-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: #f1f5f9;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            cursor: pointer;
            color: #64748b;
            transition: background 0.2s;
        }
        .notification-close:hover { background: #fee2e2; color: #ef4444; }

        .notification-modal-box h2 {
            font-size: 1.1rem;
            margin-top: 0;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #0f172a;
        }

        .notification-modal-box p {
            font-size: 0.9rem;
            color: #334155;
            line-height: 1.5;
            margin-bottom: 20px;
            word-break: break-word;
        }

        .notification-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 14px;
            font-size: 0.78rem;
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

        /* Confirm Modal Buttons */
        .confirm-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-modal-secondary {
            flex: 1;
            background: #64748b;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            text-align: center;
        }
        .btn-modal-secondary:hover { background: #475569; }

        .btn-modal-danger {
            flex: 1;
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            text-align: center;
        }
        .btn-modal-danger:hover { background: #dc2626; }

        @media (max-width: 576px) {
            .student-main { padding: 12px 10px; }
            .form-card { padding: 16px 14px; }
        }
    </style>
</head>

<body>

    <?php include("navbar.php"); ?>

    <div class="student-main">
        <div class="form-card">
            <div class="header-row">
                <h2>Notifications</h2>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <button type="button" class="btn-clear-all" onclick="openConfirmModal()">
                        <i class="fa-solid fa-trash-can"></i> Clear All
                    </button>
                <?php endif; ?>
            </div>
            <p>Updates about your document requests and announcements.</p>

            <div id="notificationsContainer">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php 
                            $is_read = isset($row['is_read']) &&$row['is_read'] == 1;
                            $notif_id = $row['id'] ?? $row['notification_id'];$formatted_time = date("M d, Y • h:i A", strtotime($row['created_at']));
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
                    <div class="empty-notifications" id="emptyState">
                        <i class="fa-regular fa-bell-slash" style="font-size: 28px; margin-bottom: 8px; display: block; color: #cbd5e1;"></i> 
                        No notifications available.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Notification Details Modal -->
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

    <!-- Clear All Confirmation Modal -->
    <div class="notification-modal" id="confirmModal">
        <div class="notification-modal-box" style="max-width: 380px; text-align: center;">
            <div style="font-size: 2.5rem; color: #ef4444; margin-bottom: 10px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h2 style="justify-content: center;">Clear All Notifications?</h2>
            <p>Are you sure you want to clear all notifications? This action cannot be undone.</p>
            <div class="confirm-actions">
                <button type="button" class="btn-modal-secondary" onclick="closeConfirmModal()">Cancel</button>
                <button type="button" class="btn-modal-danger" onclick="clearAllNotifications()">Yes, Clear All</button>
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

        document.getElementById("notificationModal").classList.add("show");

        if (!currentActiveIsRead) {
            updateNotificationStatus(id, 'mark_read');
        } else {
            updateModalButtonText();
        }
    }

    function closeNotification() {
        document.getElementById("notificationModal").classList.remove("show");
    }

    function openConfirmModal() {
        document.getElementById("confirmModal").classList.add("show");
    }

    function closeConfirmModal() {
        document.getElementById("confirmModal").classList.remove("show");
    }

    function clearAllNotifications() {
        const formData = new FormData();
        formData.append('ajax_action', 'clear_all');

        fetch('notifications.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeConfirmModal();
                // Clear the list and show empty state
                document.getElementById('notificationsContainer').innerHTML = `
                    <div class="empty-notifications">
                        <i class="fa-regular fa-bell-slash" style="font-size: 28px; margin-bottom: 8px; display: block; color: #cbd5e1;"></i> 
                        No notifications available.
                    </div>
                `;
                // Hide clear all button
                const clearBtn = document.querySelector('.btn-clear-all');
                if (clearBtn) clearBtn.style.display = 'none';

                updateNavbarBadges(data.unread_count);
            }
        })
        .catch(err => console.error("Error clearing notifications:", err));
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

    // Close modals when clicking outside box area
    window.onclick = function(event) {
        let notifModal = document.getElementById("notificationModal");
        let confirmModal = document.getElementById("confirmModal");
        if (event.target === notifModal) {
            closeNotification();
        }
        if (event.target === confirmModal) {
            closeConfirmModal();
        }
    }
    </script>
</body>
</html>