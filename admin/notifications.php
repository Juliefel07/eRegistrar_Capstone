<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = (int)$_SESSION['user_id'];

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

    $pk_check = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'id'");
    $pk_col = (mysqli_num_rows($pk_check) > 0) ? 'id' : 'notification_id';

    if ($action === 'mark_read') {
        mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE `$pk_col` = '$notif_id' AND user_id = '$admin_id'");
    } elseif ($action === 'mark_unread') {
        mysqli_query($conn, "UPDATE notifications SET is_read = 0 WHERE `$pk_col` = '$notif_id' AND user_id = '$admin_id'");
    } elseif ($action === 'mark_all_read') {
        mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = '$admin_id'");
    }

    // Get recalculated unread count
    $cnt_res = mysqli_query($conn, "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = '$admin_id' AND (is_read = 0 OR is_read IS NULL)");
    $new_unread = mysqli_fetch_assoc($cnt_res)['unread_count'] ?? 0;
    $_SESSION['unread_notifications'] =$new_unread;

    echo json_encode(['success' => true, 'unread_count' => $new_unread]);
    exit();
}

// FILTER SETUP
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';$where_condition = "WHERE user_id = '$admin_id'";

if ($filter === 'unread') {$where_condition .= " AND (is_read = 0 OR is_read IS NULL)";
} elseif ($filter === 'read') {$where_condition .= " AND is_read = 1";
}

// Fetch notifications
$notifications = mysqli_query($conn, "
    SELECT *
    FROM notifications
    $where_condition
    ORDER BY created_at DESC
");

// Fetch Unread Count
$unread_query = mysqli_query($conn, "
    SELECT COUNT(*) AS unread_count 
    FROM notifications 
    WHERE user_id = '$admin_id' AND (is_read = 0 OR is_read IS NULL)
");
$unread_count = mysqli_fetch_assoc($unread_query)['unread_count'] ?? 0;
$_SESSION['unread_notifications'] =$unread_count;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications - CCTC eRegistrar</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        .admin-content {
            padding: 20px;
            max-width: 950px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .page-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge-count {
            background-color: #2563eb;
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .filter-tabs {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }

        .tab-btn {
            padding: 6px 14px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .tab-btn:hover { background-color: #f1f5f9; color: #0f172a; }
        .tab-btn.active { background-color: #e0e7ff; color: #1d4ed8; }

        .btn-mark-all {
            margin-left: auto;
            background: none;
            border: none;
            color: #2563eb;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-mark-all:hover { text-decoration: underline; }

        .notification-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
        }

        .notification-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }

        .notification-card.unread {
            background-color: #f0f9ff;
            
        }

        .notification-card.unread .notification-text {
            font-weight: 600;
            color: #0f172a;
        }

        .notification-card.read {
            
            opacity: 0.8;
        }

        .notification-text {
            font-size: 0.92rem;
            line-height: 1.45;
            color: #334155;
            word-break: break-word;
        }

        .notification-time {
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-dot {
            height: 8px;
            width: 8px;
            background-color: #2563eb;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .no-notifications {
            background: #ffffff;
            padding: 36px 20px;
            text-align: center;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            color: #64748b;
            margin-top: 12px;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(3px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            padding: 20px;
            box-sizing: border-box;
        }

        .modal-container {
            background: #ffffff;
            width: 100%;
            max-width: 720px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: modalFadeIn 0.2s ease-out;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8fafc;
        }

        .modal-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .modal-close-btn {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: #64748b;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
        }

        .modal-close-btn:hover { color: #0f172a; background: #e2e8f0; }

        .modal-body {
            padding: 24px;
            font-size: 1rem;
            line-height: 1.6;
            color: #1e293b;
            max-height: 60vh;
            overflow-y: auto;
            word-wrap: break-word;
        }

        .modal-footer {
            padding: 14px 24px;
            border-top: 1px solid #e2e8f0;
            background-color: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-time {
            font-size: 0.82rem;
            color: #64748b;
        }

        .btn-toggle-status {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-toggle-status:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
    </style>
</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

    <div class="page-header">
        <h2 class="page-title">
            <i class="fa-solid fa-bell" style="color: #2563eb;"></i> Notifications
            <span class="badge-count" id="headerBadge" style="<?= $unread_count == 0 ? 'display:none;' : ''; ?>">
                <?= $unread_count; ?> new
            </span>
        </h2>
    </div>

    <!-- FILTER TABS -->
    <div class="filter-tabs">
        <a href="notifications.php?filter=all" class="tab-btn <?= $filter === 'all' ? 'active' : ''; ?>">All</a>
        <a href="notifications.php?filter=unread" class="tab-btn <?= $filter === 'unread' ? 'active' : ''; ?>">
            Unread (<span id="unreadTabCount"><?= $unread_count; ?></span>)
        </a>
        <a href="notifications.php?filter=read" class="tab-btn <?= $filter === 'read' ? 'active' : ''; ?>">Read</a>

        <?php if ($unread_count > 0): ?>
            <button class="btn-mark-all" onclick="markAllAsRead()">
                <i class="fa-solid fa-check-double"></i> Mark all as read
            </button>
        <?php endif; ?>
    </div>

    <!-- NOTIFICATION LIST -->
    <div class="container">
        <?php if (mysqli_num_rows($notifications) == 0): ?>
            <div class="no-notifications">
                <i class="fa-regular fa-bell-slash"></i>
                <p>No notifications found in this view.</p>
            </div>
        <?php else: ?>
            <?php while ($row = mysqli_fetch_assoc($notifications)): ?>
                <?php 
                    $is_read = isset($row['is_read']) &&$row['is_read'] == 1; 
                    $notif_id = $row['id'] ?? $row['notification_id'];$formatted_time = date("M d, Y h:i A", strtotime($row['created_at']));
                ?>
                <div class="notification-card <?= $is_read ? 'read' : 'unread'; ?>" 
                     id="notif-card-<?= $notif_id; ?>"
                     onclick="openNotificationModal(<?= $notif_id; ?>, <?= $is_read ? 'true' : 'false'; ?>)"
                     data-read="<?= $is_read ? 'true' : 'false'; ?>"
                     data-message="<?= htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8'); ?>"
                     data-time="<?= $formatted_time; ?>">
                    
                    <div class="notification-text" id="notif-text-<?= $notif_id; ?>">
                        <?php if (!$is_read): ?>
                            <span class="status-dot" id="dot-<?= $notif_id; ?>"></span>
                        <?php endif; ?>
                        <?= htmlspecialchars($row['message']); ?>
                    </div>
                    <div class="notification-time">
                        <i class="fa-regular fa-clock"></i>
                        <?= $formatted_time; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

</div>

<!-- WIDE FLOATING MODAL -->
<div class="modal-overlay" id="notificationModal" onclick="closeModalOnOverlay(event)">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fa-solid fa-bell" style="color: #2563eb;"></i> Notification Details
            </h3>
            <button class="modal-close-btn" onclick="closeNotificationModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body" id="modalMessage">
        </div>
        <div class="modal-footer">
            <div class="modal-time" id="modalTime"></div>
            <button class="btn-toggle-status" id="btnToggleStatus" onclick="toggleModalReadStatus()">
                Mark as Unread
            </button>
        </div>
    </div>
</div>

<script>
let currentActiveNotifId = null;
let currentActiveIsRead = false;

function openNotificationModal(id, isRead) {
    currentActiveNotifId = id;
    const card = document.getElementById('notif-card-' + id);
    if (!card) return;

    currentActiveIsRead = (card.getAttribute('data-read') === 'true');
    const message = card.getAttribute('data-message');
    const time = card.getAttribute('data-time');

    document.getElementById('modalMessage').innerText = message;
    document.getElementById('modalTime').innerHTML = '<i class="fa-regular fa-clock"></i> ' + time;

    updateModalButtonText();

    // Show Floating Modal
    document.getElementById('notificationModal').style.display = 'flex';

    // AUTOMATICALLY MARK AS READ WHEN OPENED IF UNREAD
    if (!currentActiveIsRead) {
        updateNotificationStatus(id, 'mark_read');
    }
}

function closeNotificationModal() {
    document.getElementById('notificationModal').style.display = 'none';
}

function closeModalOnOverlay(e) {
    if (e.target.classList.contains('modal-overlay')) {
        closeNotificationModal();
    }
}

function updateModalButtonText() {
    const btn = document.getElementById('btnToggleStatus');
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
            let dot = document.getElementById('dot-' + id);

            if (action === 'mark_read') {
                currentActiveIsRead = true;
                if (card) {
                    card.classList.remove('unread');
                    card.classList.add('read');
                    card.setAttribute('data-read', 'true');
                }
                if (dot) dot.style.display = 'none';
            } else {
                currentActiveIsRead = false;
                if (card) {
                    card.classList.remove('read');
                    card.classList.add('unread');
                    card.setAttribute('data-read', 'false');
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
            updateSidebarAndHeaderBadges(data.unread_count);
        }
    })
    .catch(err => console.error("Error updating status:", err));
}

function markAllAsRead() {
    const formData = new FormData();
    formData.append('ajax_action', 'mark_all_read');
    formData.append('notification_id', 0);

    fetch('notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}

function updateSidebarAndHeaderBadges(count) {
    const headerBadge = document.getElementById('headerBadge');
    const tabCount = document.getElementById('unreadTabCount');

    if (tabCount) tabCount.innerText = count;

    if (headerBadge) {
        if (count > 0) {
            headerBadge.innerText = count + ' new';
            headerBadge.style.display = 'inline-block';
        } else {
            headerBadge.style.display = 'none';
        }
    }

    const sidebarBadge = document.querySelector('.sidebar-badge');
    if (sidebarBadge) {
        if (count > 0) {
            sidebarBadge.innerText = count;
            sidebarBadge.style.display = 'inline-block';
        } else {
            sidebarBadge.style.display = 'none';
        }
    }
}
</script>

</body>
</html>