<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$contact_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;

// Dynamically check primary key column
$pk_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'user_id'");
$id_col = ($pk_check && mysqli_num_rows($pk_check) > 0) ? 'user_id' : 'id';

// 1. AJAX ENDPOINT FOR REAL-TIME UNREAD CHECK
if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_unread') {
    header('Content-Type: application/json');
    $unread_query = mysqli_query($conn, "
        SELECT COUNT(*) AS unread_count 
        FROM messages 
        WHERE receiver_id = '$user_id' 
          AND status = 'Unread'
    ");
    $data = mysqli_fetch_assoc($unread_query);
    echo json_encode(['unread' => (int)($data['unread_count'] ?? 0)]);
    exit();
}

// Fetch all Admin/Registrar accounts
$admins_query = mysqli_query($conn, "
    SELECT 
        u.{$id_col} AS admin_id,
        u.fullname,
        u.profile_image,
        (SELECT COUNT(*) FROM messages m1 
         WHERE m1.sender_id = u.{$id_col} 
           AND m1.receiver_id = '$user_id' 
           AND m1.status = 'Unread') AS unread_count
    FROM users u
    WHERE LOWER(u.role) IN ('admin', 'registrar') AND u.{$id_col} != '$user_id'
    ORDER BY unread_count DESC, u.fullname ASC
");

// Mobile view mode checking
$view_mode = $_GET['view'] ?? '';
$is_mobile_contacts_view = ($view_mode === 'contacts');

if (!$contact_id && !$is_mobile_contacts_view && $admins_query && mysqli_num_rows($admins_query) > 0) {
    $first_admin = mysqli_fetch_assoc($admins_query);
    $contact_id = (int)$first_admin['admin_id'];
    mysqli_data_seek($admins_query, 0); 
}

// 2. SEND MESSAGE
if (isset($_POST['send']) && $contact_id) {
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));

    if (!empty($message)) {
        mysqli_query($conn, "
            INSERT INTO messages (sender_id, receiver_id, message, status, created_at)
            VALUES ('$user_id', '$contact_id', '$message', 'Unread', NOW())
        ");

        $user_res = mysqli_query($conn, "SELECT fullname FROM users WHERE {$id_col} = '$user_id'");
        $user_row = mysqli_fetch_assoc($user_res);
        $student_name = $user_row['fullname'] ?? "A student";

        $admin_query = mysqli_query($conn, "SELECT {$id_col} AS admin_id FROM users WHERE LOWER(role) = 'admin'");
        if ($admin_query && mysqli_num_rows($admin_query) > 0) {
            $notif_msg = mysqli_real_escape_string($conn, "New message received from $student_name.");
            while ($admin = mysqli_fetch_assoc($admin_query)) {
                $target_admin_id = (int)$admin['admin_id'];
                mysqli_query($conn, "
                    INSERT INTO notifications (user_id, message, is_read, created_at)
                    VALUES ('$target_admin_id', '$notif_msg', 0, NOW())
                ");
            }
        }
    }

    header("Location: messages.php?student_id=" . $contact_id);
    exit();
}

// 3. MARK MESSAGES AS READ
if ($contact_id) {
    mysqli_query($conn, "
        UPDATE messages 
        SET status = 'Read' 
        WHERE sender_id = '$contact_id' 
          AND receiver_id = '$user_id' 
          AND status = 'Unread'
    ");
}

// Fetch Active Conversation
$chat = null;
$contact_name = "Registrar Administrator";
$contact_data = null;

if ($contact_id) {
    $name_query = mysqli_query($conn, "SELECT fullname, profile_image FROM users WHERE {$id_col} = '$contact_id'");
    if ($name_query && mysqli_num_rows($name_query) > 0) {
        $contact_data = mysqli_fetch_assoc($name_query);
        $contact_name = $contact_data['fullname'];
    }

    $chat = mysqli_query($conn, "
        SELECT * FROM messages
        WHERE (sender_id = '$user_id' AND receiver_id = '$contact_id')
           OR (sender_id = '$contact_id' AND receiver_id = '$user_id')
        ORDER BY created_at ASC
    ");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Messages - CCTC eRegistrar</title>
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        .student-content {
            padding: 20px 24px;
            max-width: 1400px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .chat-container {
            display: grid;
            grid-template-columns: 320px 1fr;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            height: calc(100vh - 120px);
            min-height: 550px;
            overflow: hidden;
        }

        /* Contacts List Sidebar */
        .contact-list {
            border-right: 1px solid #e2e8f0;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .list-header {
            padding: 18px 20px;
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
        }

        .contact-item {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            text-decoration: none;
            color: #1e293b;
            gap: 12px;
            transition: background-color 0.15s ease;
            position: relative;
            border-bottom: 1px solid #f8fafc;
        }

        .contact-item:hover {
            background-color: #f8fafc;
        }

        .contact-item.active {
            background-color: #eff6ff;
        }

        .contact-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background-color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #475569;
            flex-shrink: 0;
            overflow: hidden;
            font-size: 1rem;
        }

        .contact-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .contact-info {
            flex-grow: 1;
            overflow: hidden;
        }

        .contact-info strong {
            display: block;
            font-size: 0.92rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #0f172a;
            font-weight: 700;
        }

        .contact-info small {
            font-size: 0.76rem;
            color: #94a3b8;
            display: block;
            margin-top: 2px;
        }

        .unread-badge {
            background-color: #ef4444;
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 12px;
        }

        /* Active Chat Main View */
        .chat-main {
            display: flex;
            flex-direction: column;
            background-color: #ffffff;
            height: 100%;
            overflow: hidden;
        }

        .chat-header {
            padding: 14px 20px;
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            z-index: 10;
        }

        .mobile-back-btn {
            display: none;
            text-decoration: none;
            color: #0056b3;
            font-size: 0.95rem;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 8px;
            background-color: #eff6ff;
            align-items: center;
            gap: 6px;
        }

        .chat-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .chat-body {
            flex-grow: 1;
            padding: 18px 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background-color: #f8fafc;
        }

        .message-row {
            display: flex;
            width: 100%;
        }

        .message-row.right {
            justify-content: flex-end;
        }

        .message-row.left {
            justify-content: flex-start;
        }

        .bubble {
            max-width: 65%;
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 0.92rem;
            line-height: 1.45;
            position: relative;
            word-wrap: break-word;
        }

        .bubble.me {
            background-color: #0056b3;
            color: #ffffff;
            border-bottom-right-radius: 4px;
        }

        .bubble.admin {
            background-color: #ffffff;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .bubble small {
            display: block;
            font-size: 0.7rem;
            margin-top: 5px;
            opacity: 0.8;
            text-align: left;
        }

        .bubble.me small {
            color: #e0e7ff;
            text-align: right;
        }

        /* HIGH-VISIBILITY INPUT CONTAINER */
        .chat-input-container {
            padding: 12px 16px 16px;
            background-color: #ffffff;
            border-top: 1px solid #cbd5e1;
            flex-shrink: 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.04);
            z-index: 10;
        }

        .chat-input {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            background-color: #ffffff;
            border: 2px solid #0056b3;
            border-radius: 16px;
            padding: 8px 8px 8px 14px;
            box-shadow: 0 2px 6px rgba(0, 86, 179, 0.08);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .chat-input:focus-within {
            border-color: #004494;
            box-shadow: 0 0 0 4px rgba(0, 86, 179, 0.15);
        }

        .chat-input textarea {
            flex-grow: 1;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 15px; /* 15px+ prevents auto-zoom on iOS */
            line-height: 1.4;
            resize: none;
            height: 38px;
            max-height: 100px;
            outline: none;
            color: #0f172a;
            padding: 6px 0;
        }

        .chat-input textarea::placeholder {
            color: #64748b;
        }

        .chat-input button {
            background-color: #0056b3;
            color: #ffffff;
            border: none;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background-color 0.2s ease;
        }

        .chat-input button:hover {
            background-color: #004494;
        }

        .empty-chat {
            margin: auto;
            text-align: center;
            color: #64748b;
            padding: 20px;
        }

        .empty-chat i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 12px;
        }

        #notification-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background-color: #0f172a;
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: none;
            align-items: center;
            gap: 10px;
            z-index: 2000;
            font-size: 0.88rem;
        }

        /* MOBILE RESPONSIVE DESIGN */
        @media (max-width: 768px) {
            .student-content {
                padding: 0;
            }

            .chat-container {
                grid-template-columns: 1fr;
                height: calc(100dvh - 65px);
                border-radius: 0;
                border: none;
                min-height: auto;
            }

            .mobile-back-btn {
                display: inline-flex;
            }

            .bubble {
                max-width: 82%;
            }

            /* Extra bottom padding on mobile so input isn't blocked by bottom bars */
            .chat-input-container {
                padding: 10px 12px 80px; 
            }

            .chat-input {
                border-width: 1.5px;
            }

            /* Mobile view toggle */
            <?php if ($contact_id && !$is_mobile_contacts_view): ?>
                .contact-list { display: none !important; }
                .chat-main { display: flex !important; }
            <?php else: ?>
                .contact-list { display: flex !important; width: 100%; }
                .chat-main { display: none !important; }
            <?php endif; ?>
        }
    </style>
</head>

<body>

<?php require_once __DIR__ . "/navbar.php"; ?>

<div class="student-content">

    <div class="chat-container">

        <!-- LEFT SIDEBAR: CONTACTS LIST -->
        <div class="contact-list">
            <div class="list-header">
                <i class="fa-solid fa-comments" style="color: #0056b3;"></i> Registrar Messages
            </div>

            <?php if ($admins_query && mysqli_num_rows($admins_query) > 0): ?>
                <?php while ($a = mysqli_fetch_assoc($admins_query)): ?>
                    <a href="messages.php?student_id=<?php echo $a['admin_id']; ?>"
                       class="contact-item <?php echo ($contact_id == $a['admin_id'] && !$is_mobile_contacts_view) ? 'active' : ''; ?>">

                        <div class="contact-avatar">
                            <?php if (!empty($a['profile_image'])): ?>
                                <img src="../student/uploads/<?php echo htmlspecialchars($a['profile_image']); ?>" alt="Profile">
                            <?php else: ?>
                                <?php echo strtoupper(substr($a['fullname'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>

                        <div class="contact-info">
                            <strong><?php echo htmlspecialchars($a['fullname']); ?></strong>
                            <small>Tap to open chat</small>
                        </div>

                        <?php if ($a['unread_count'] > 0): ?>
                            <span class="unread-badge"><?php echo $a['unread_count']; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding: 24px; text-align: center; color: #94a3b8; font-size: 0.88rem;">
                    No registrar accounts available.
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT PANE: CHAT MESSAGES -->
        <div class="chat-main">
            <?php if ($contact_id && $chat): ?>
                <div class="chat-header">
                    <a href="messages.php?view=contacts" class="mobile-back-btn">
                        <i class="fa-solid fa-chevron-left"></i> 
                    </a>
                    <div class="contact-avatar">
                        <?php if (!empty($contact_data['profile_image'])): ?>
                            <img src="../student/uploads/<?php echo htmlspecialchars($contact_data['profile_image']); ?>" alt="Profile">
                        <?php else: ?>
                            <?php echo strtoupper(substr($contact_name, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($contact_name); ?></h3>
                </div>

                <div class="chat-body" id="chatBox">
                    <?php if (mysqli_num_rows($chat) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($chat)): ?>
                            <?php if ($row['sender_id'] == $user_id): ?>
                                <div class="message-row right">
                                    <div class="bubble me">
                                        <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                                        <small><?php echo date("M d, g:i a", strtotime($row['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="message-row left">
                                    <div class="bubble admin">
                                        <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                                        <small><?php echo date("M d, g:i a", strtotime($row['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-chat">
                            <p>No messages yet. Send a message to start conversing with the Registrar.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="chat-input-container">
                    <form method="POST" class="chat-input">
                        <textarea
                            id="messageBox"
                            name="message"
                            placeholder="Type a message..."
                            required></textarea>
                        <button type="submit" name="send" id="sendButton">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <div class="empty-chat">
                    <i class="fa-regular fa-comments"></i>
                    <h2 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Select a Contact</h2>
                    <p style="font-size: 0.88rem; margin: 0;">Tap an administrator from the list to begin chatting.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<!-- REAL-TIME UNREAD TOAST NOTIFICATION -->
<div id="notification-toast">
    <i class="fa-solid fa-bell" style="color: #38bdf8;"></i>
    <span>New message received!</span>
</div>

<script>
    const chat = document.getElementById("chatBox");
    if (chat) { chat.scrollTop = chat.scrollHeight; }

    const box = document.getElementById("messageBox");
    if (box) {
        // Auto-expand textarea as user types
        box.addEventListener("input", function() {
            this.style.height = "auto";
            this.style.height = (this.scrollHeight < 100 ? this.scrollHeight : 100) + "px";
        });

        // Submit on Enter key (desktop)
        box.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey && window.innerWidth > 768) {
                e.preventDefault();
                document.getElementById("sendButton").click();
            }
        });
    }

    let previousUnreadCount = null;

    function checkUnreadMessages() {
        fetch('messages.php?ajax=check_unread')
            .then(response => response.json())
            .then(data => {
                if (previousUnreadCount !== null && data.unread > previousUnreadCount) {
                    showNotificationToast();
                    if (typeof chat !== 'undefined' && chat) {
                        location.reload();
                    }
                }
                previousUnreadCount = data.unread;
            })
            .catch(error => console.error('Error polling messages:', error));
    }

    function showNotificationToast() {
        const toast = document.getElementById('notification-toast');
        if (toast) {
            toast.style.display = 'flex';
            setTimeout(() => { toast.style.display = 'none'; }, 4000);
        }
    }

    setInterval(checkUnreadMessages, 4000);
</script>

</body>
</html>