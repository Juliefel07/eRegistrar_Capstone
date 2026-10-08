<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = (int)$_SESSION['user_id'];
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;

// Dynamically detect user primary key column name (id vs user_id)
$pk_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'user_id'");
$id_col = ($pk_check && mysqli_num_rows($pk_check) > 0) ? 'user_id' : 'id';

// 1. AJAX ENDPOINT FOR REAL-TIME UNREAD CHECK
if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_unread') {
    header('Content-Type: application/json');
    $unread_query = mysqli_query($conn, "
        SELECT COUNT(*) AS unread_count 
        FROM messages 
        WHERE (receiver_id = '$admin_id' OR receiver_id IS NULL OR receiver_id = 0) 
          AND status = 'Unread'
    ");
    $data = mysqli_fetch_assoc($unread_query);
    echo json_encode(['unread' => (int)($data['unread_count'] ?? 0)]);
    exit();
}

// 2. SEND REPLY FROM ADMIN TO STUDENT + CREATE STUDENT NOTIFICATION
if (isset($_POST['send']) && $student_id) {
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));

    if (!empty($message)) {
        // Save message to database
        mysqli_query($conn, "
            INSERT INTO messages (sender_id, receiver_id, message, status, created_at)
            VALUES ('$admin_id', '$student_id', '$message', 'Unread', NOW())
        ");

        // Create System Notification for the Student
        $notif_msg = mysqli_real_escape_string($conn, "You have received a new message reply from the Registrar Office.");
        mysqli_query($conn, "
            INSERT INTO notifications (user_id, message, is_read, created_at)
            VALUES ('$student_id', '$notif_msg', 0, NOW())
        ");
    }

    header("Location: messages.php?student_id=" . $student_id);
    exit();
}

// 3. MARK MESSAGES AS READ WHEN OPENING A CONVERSATION
if ($student_id) {
    mysqli_query($conn, "
        UPDATE messages 
        SET status = 'Read' 
        WHERE sender_id = '$student_id' 
          AND status = 'Unread'
    ");
}

// 4. FETCH ALL USERS/STUDENTS WHO MESSAGED THE SYSTEM
$students = mysqli_query($conn, "
    SELECT 
        u.{$id_col} AS student_id,
        u.fullname,
        u.profile_image,
        (SELECT COUNT(*) FROM messages m1 
         WHERE m1.sender_id = u.{$id_col} 
           AND m1.status = 'Unread') AS unread_count,
        (SELECT MAX(created_at) FROM messages m2 
         WHERE m2.sender_id = u.{$id_col} OR m2.receiver_id = u.{$id_col}) AS last_activity
    FROM users u
    WHERE EXISTS (
        SELECT 1 FROM messages m3 
        WHERE m3.sender_id = u.{$id_col} OR m3.receiver_id = u.{$id_col}
    ) AND u.{$id_col} != '$admin_id'
    ORDER BY unread_count DESC, last_activity DESC
");

// 5. GET CONVERSATION CHAT HISTORY
$chat = null;
$student_name = "";
$name_data = null;

if ($student_id) {
    $name_query = mysqli_query($conn, "SELECT fullname, profile_image FROM users WHERE {$id_col} = '$student_id'");
    if ($name_query) {
        $name_data = mysqli_fetch_assoc($name_query);
        if ($name_data) {
            $student_name = $name_data['fullname'];
            $chat = mysqli_query($conn, "
                SELECT * FROM messages
                WHERE (sender_id = '$student_id' AND (receiver_id = '$admin_id' OR receiver_id IS NULL OR receiver_id = 0))
                   OR (sender_id = '$admin_id' AND receiver_id = '$student_id')
                   OR (sender_id = '$student_id')
                ORDER BY created_at ASC
            ");
        }
    }
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
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .chat-container {
            display: grid;
            grid-template-columns: 320px 1fr;
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            height: calc(100vh - 120px);
            min-height: 600px;
            overflow: hidden;
        }

        .student-list {
            border-right: 1px solid #e2e8f0;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .list-header {
            padding: 20px 24px;
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 12px;
            background-color: #ffffff;
        }

        .student-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            text-decoration: none;
            color: #1e293b;
            gap: 14px;
            transition: background-color 0.15s ease;
            position: relative;
            border-bottom: 1px solid #f8fafc;
        }

        .student-item:hover {
            background-color: #f8fafc;
        }

        .student-item.active {
            background-color: #eff6ff;
        }

        .student-avatar {
            width: 46px;
            height: 46px;
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

        .student-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .student-info {
            flex-grow: 1;
            overflow: hidden;
        }

        .student-info strong {
            display: block;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #0f172a;
            font-weight: 700;
        }

        .student-info small {
            font-size: 0.78rem;
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
            box-shadow: 0 1px 2px rgba(239, 68, 68, 0.4);
        }

        .admin-chat {
            display: flex;
            flex-direction: column;
            background-color: #ffffff;
            height: 100%;
            overflow: hidden;
        }

        .admin-chat-header {
            padding: 16px 24px;
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .admin-chat-header h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
        }

        .admin-chat-body {
            flex-grow: 1;
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background-color: #ffffff;
        }

        .admin-row {
            display: flex;
            width: 100%;
        }

        .admin-row.right {
            justify-content: flex-end;
        }

        .admin-row.left {
            justify-content: flex-start;
        }

        .admin-bubble {
            max-width: 60%;
            padding: 12px 18px;
            border-radius: 16px;
            font-size: 0.92rem;
            line-height: 1.5;
            position: relative;
            word-wrap: break-word;
        }

        .admin-bubble.me {
            background-color: #2563eb;
            color: #ffffff;
            border-bottom-right-radius: 4px;
        }

        .admin-bubble.student {
            background-color: #f8fafc;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
        }

        .admin-bubble small {
            display: block;
            font-size: 0.72rem;
            margin-top: 6px;
            opacity: 0.8;
            text-align: left;
        }

        .admin-bubble.me small {
            color: #e0e7ff;
            text-align: right;
        }

        .admin-chat-input-container {
            padding: 20px 24px;
            background-color: #ffffff;
        }

        .admin-chat-input {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 10px 14px;
        }

        .admin-chat-input textarea {
            flex-grow: 1;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 0.95rem;
            resize: none;
            height: 60px;
            outline: none;
            color: #0f172a;
            padding: 4px 0;
        }

        .admin-chat-input textarea::placeholder {
            color: #94a3b8;
        }

        .admin-chat-input button {
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

        .admin-chat-input button:hover {
            background-color: #1d4ed8;
        }

        .empty-admin-chat {
            margin: auto;
            text-align: center;
            color: #64748b;
        }

        .empty-admin-chat i {
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
            z-index: 1000;
        }

        @media (max-width: 768px) {
            .admin-content { padding: 12px; }
            .chat-container { grid-template-columns: 1fr; height: 85vh; }
            <?php if ($student_id): ?>
            .student-list { display: none; }
            <?php else: ?>
            .admin-chat { display: none; }
            <?php endif; ?>
        }
    </style>
</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

    <div class="chat-container">

        <!-- SIDEBAR: STUDENT CONVERSATIONS LIST -->
        <div class="student-list">
            <div class="list-header">
                <i class="fa-solid fa-comments" style="color: #2563eb;"></i> Messages
            </div>

            <?php if ($students && mysqli_num_rows($students) > 0): ?>
                <?php while ($s = mysqli_fetch_assoc($students)): ?>
                    <a href="messages.php?student_id=<?php echo $s['student_id']; ?>"
                       class="student-item <?php echo ($student_id == $s['student_id']) ? 'active' : ''; ?>">

                        <div class="student-avatar">
                            <?php if (!empty($s['profile_image'])): ?>
                                <img src="../student/uploads/<?php echo htmlspecialchars($s['profile_image']); ?>" alt="Profile">
                            <?php else: ?>
                                <?php echo strtoupper(substr($s['fullname'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>

                        <div class="student-info">
                            <strong><?php echo htmlspecialchars($s['fullname']); ?></strong>
                            <small>Click to view conversation</small>
                        </div>

                        <?php if ($s['unread_count'] > 0): ?>
                            <span class="unread-badge"><?php echo $s['unread_count']; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding: 24px; text-align: center; color: #94a3b8; font-size: 0.9rem;">
                    No student messages found.
                </div>
            <?php endif; ?>
        </div>

        <!-- MAIN CHAT PANEL -->
        <div class="admin-chat">
            <?php if ($chat): ?>
                <div class="admin-chat-header">
                    <div class="student-avatar">
                        <?php if (!empty($name_data['profile_image'])): ?>
                            <img src="../student/uploads/<?php echo htmlspecialchars($name_data['profile_image']); ?>" alt="Profile">
                        <?php else: ?>
                            <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($student_name); ?></h3>
                </div>

                <div class="admin-chat-body" id="chatBox">
                    <?php while ($row = mysqli_fetch_assoc($chat)): ?>
                        <?php if ($row['sender_id'] == $admin_id): ?>
                            <div class="admin-row right">
                                <div class="admin-bubble me">
                                    <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                                    <small><?php echo date("M d, g:i a", strtotime($row['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="admin-row left">
                                <div class="admin-bubble student">
                                    <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                                    <small><?php echo date("M d, g:i a", strtotime($row['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </div>

                <div class="admin-chat-input-container">
                    <form method="POST" class="admin-chat-input">
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
                <div class="empty-admin-chat">
                    <i class="fa-regular fa-comments"></i>
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Select a Student</h2>
                    <p style="font-size: 0.9rem; margin: 0;">Choose a conversation from the left sidebar to start messaging.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<!-- REAL-TIME TOAST NOTIFICATION -->
<div id="notification-toast">
    <i class="fa-solid fa-bell" style="color: #38bdf8;"></i>
    <span>You have new unread messages!</span>
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
                    // Auto reload chat if open
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

    // Check for new messages every 4 seconds
    setInterval(checkUnreadMessages, 4000);
</script>

</body>
</html>