<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$contact_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;

// Dynamically check primary key column (id vs user_id)
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

// Fetch all Admin/Registrar accounts for the left sidebar
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

// Default to the first admin in the list if no contact selected
if (!$contact_id && $admins_query && mysqli_num_rows($admins_query) > 0) {
    $first_admin = mysqli_fetch_assoc($admins_query);
    $contact_id = (int)$first_admin['admin_id'];
    mysqli_data_seek($admins_query, 0); 
}

// 2. SEND MESSAGE FROM STUDENT TO ADMIN + CREATE SYSTEM NOTIFICATION FOR ALL ADMINS
if (isset($_POST['send']) && $contact_id) {
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));

    if (!empty($message)) {
        // Save message
        mysqli_query($conn, "
            INSERT INTO messages (sender_id, receiver_id, message, status, created_at)
            VALUES ('$user_id', '$contact_id', '$message', 'Unread', NOW())
        ");

        // Fetch Student Name for notification
        $user_res = mysqli_query($conn, "SELECT fullname FROM users WHERE {$id_col} = '$user_id'");
        $user_row = mysqli_fetch_assoc($user_res);
        $student_name = $user_row['fullname'] ?? "A student";

        // Notify ALL Active Admins
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

// Get Active Contact Info & Chat History
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            grid-template-columns: 300px 1fr;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            height: calc(100vh - 170px);
            min-height: 580px;
            overflow: hidden;
        }

        .contact-list {
            border-right: 1px solid #e2e8f0;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .list-header {
            padding: 20px 24px;
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: #ffffff;
        }

        .contact-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
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
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #475569;
            flex-shrink: 0;
            overflow: hidden;
            font-size: 1.1rem;
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

        .chat-main {
            display: flex;
            flex-direction: column;
            background-color: #ffffff;
            height: 100%;
            overflow: hidden;
        }

        .chat-header {
            padding: 16px 24px;
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .chat-header h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
        }

        .chat-body {
            flex-grow: 1;
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background-color: #ffffff;
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
            max-width: 55%;
            padding: 12px 18px;
            border-radius: 16px;
            font-size: 0.92rem;
            line-height: 1.5;
            position: relative;
            word-wrap: break-word;
        }

        .bubble.me {
            background-color: #2563eb;
            color: #ffffff;
            border-bottom-right-radius: 4px;
        }

        .bubble.admin {
            background-color: #f8fafc;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
        }

        .bubble small {
            display: block;
            font-size: 0.72rem;
            margin-top: 6px;
            opacity: 0.8;
            text-align: left;
        }

        .bubble.me small {
            color: #e0e7ff;
            text-align: right;
        }

        .chat-input-container {
            padding: 18px 24px;
            background-color: #ffffff;
        }

        .chat-input {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 10px 14px;
        }

        .chat-input textarea {
            flex-grow: 1;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 0.95rem;
            resize: none;
            height: 55px;
            outline: none;
            color: #0f172a;
            padding: 4px 0;
        }

        .chat-input textarea::placeholder {
            color: #94a3b8;
        }

        .chat-input button {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
            flex-shrink: 0;
        }

        .chat-input button:hover {
            background-color: #1d4ed8;
        }

        .empty-chat {
            margin: auto;
            text-align: center;
            color: #64748b;
        }

        .empty-chat i {
            font-size: 3.5rem;
            color: #cbd5e1;
            margin-bottom: 16px;
        }

        #notification-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background-color: #0f172a;
            color: #ffffff;
            padding: 14px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: none;
            align-items: center;
            gap: 12px;
            z-index: 2000;
        }

        @media (max-width: 768px) {
            .student-content { padding: 12px; }
            .chat-container { grid-template-columns: 1fr; height: 80vh; }
            <?php if ($contact_id): ?>
            .contact-list { display: none; }
            <?php else: ?>
            .chat-main { display: none; }
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
                <i class="fa-solid fa-comments" style="color: #2563eb;"></i> Messages
            </div>

            <?php if ($admins_query && mysqli_num_rows($admins_query) > 0): ?>
                <?php while ($a = mysqli_fetch_assoc($admins_query)): ?>
                    <a href="messages.php?student_id=<?php echo $a['admin_id']; ?>"
                       class="contact-item <?php echo ($contact_id == $a['admin_id']) ? 'active' : ''; ?>">

                        <div class="contact-avatar">
                            <?php if (!empty($a['profile_image'])): ?>
                                <img src="../student/uploads/<?php echo htmlspecialchars($a['profile_image']); ?>" alt="Profile">
                            <?php else: ?>
                                <?php echo strtoupper(substr($a['fullname'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>

                        <div class="contact-info">
                            <strong><?php echo htmlspecialchars($a['fullname']); ?></strong>
                            <small>Click to view conversation</small>
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
                        <div style="margin: auto; text-align: center; color: #94a3b8; font-size: 0.9rem;">
                            No messages yet. Start a conversation with the Registrar.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="chat-input-container">
                    <form method="POST" class="chat-input">
                        <textarea
                            id="messageBox"
                            name="message"
                            placeholder="Type your reply here... (Press Enter to send)"
                            required></textarea>
                        <button type="submit" name="send" id="sendButton">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <div class="empty-chat">
                    <i class="fa-regular fa-comments"></i>
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Select Registrar</h2>
                    <p style="font-size: 0.9rem; margin: 0;">Choose a contact from the left sidebar to send a message.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<!-- REAL-TIME UNREAD TOAST NOTIFICATION -->
<div id="notification-toast">
    <i class="fa-solid fa-bell" style="color: #38bdf8;"></i>
    <span>New message from Registrar Office!</span>
</div>

<script>
    const chat = document.getElementById("chatBox");
    if (chat) { chat.scrollTop = chat.scrollHeight; }

    const box = document.getElementById("messageBox");
    if (box) {
        box.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey) {
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
        toast.style.display = 'flex';
        setTimeout(() => { toast.style.display = 'none'; }, 4000);
    }

    // Poll every 4 seconds for new messages
    setInterval(checkUnreadMessages, 4000);
</script>

</body>
</html>