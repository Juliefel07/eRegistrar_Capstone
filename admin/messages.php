<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = (int)$_SESSION['user_id'];
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;

// AJAX ENDPOINT FOR CHECKING UNREAD MESSAGES / NOTIFICATIONS
if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_unread') {
    header('Content-Type: application/json');
    $unread_query = mysqli_query($conn, "
        SELECT COUNT(*) AS unread_count 
        FROM messages 
        WHERE receiver_id = '$admin_id' AND status = 'Unread'
    ");
    $data = mysqli_fetch_assoc($unread_query);
    echo json_encode(['unread' => (int)$data['unread_count']]);
    exit();
}

// SEND REPLY
if (isset($_POST['send']) && $student_id) {
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));

    if (!empty($message)) {
        mysqli_query($conn, "
            INSERT INTO messages (sender_id, receiver_id, message, status, created_at)
            VALUES ('$admin_id', '$student_id', '$message', 'Unread', NOW())
        ");
    }

    header("Location: messages.php?student_id=" . $student_id);
    exit();
}

// MARK MESSAGES AS READ WHEN OPENING A CONVERSATION
if ($student_id) {
    mysqli_query($conn, "
        UPDATE messages 
        SET status = 'Read' 
        WHERE sender_id = '$student_id' AND receiver_id = '$admin_id' AND status = 'Unread'
    ");
}

// GET ALL STUDENTS WHO MESSAGED ADMIN (WITH UNREAD COUNT & LAST MESSAGE TIMESTAMP)
$students = mysqli_query($conn, "
    SELECT 
        u.user_id,
        u.fullname,
        u.profile_image,
        (SELECT COUNT(*) FROM messages m1 WHERE m1.sender_id = u.user_id AND m1.receiver_id = '$admin_id' AND m1.status = 'Unread') AS unread_count,
        (SELECT MAX(created_at) FROM messages m2 WHERE (m2.sender_id = u.user_id AND m2.receiver_id = '$admin_id') OR (m2.sender_id = '$admin_id' AND m2.receiver_id = u.user_id)) AS last_activity
    FROM users u
    WHERE u.role = 'Student' AND EXISTS (
        SELECT 1 FROM messages m3 
        WHERE (m3.sender_id = u.user_id AND m3.receiver_id = '$admin_id') 
           OR (m3.sender_id = '$admin_id' AND m3.receiver_id = u.user_id)
    )
    ORDER BY unread_count DESC, last_activity DESC
");

// GET CONVERSATION CHAT HISTORY
$chat = null;
$student_name = "";
$name_data = null;

if ($student_id) {
    $name_query = mysqli_query($conn, "SELECT fullname, profile_image FROM users WHERE user_id = '$student_id'");
    $name_data = mysqli_fetch_assoc($name_query);
    if ($name_data) {
        $student_name = $name_data['fullname'];
        $chat = mysqli_query($conn, "
            SELECT * FROM messages
            WHERE (sender_id = '$student_id' AND receiver_id = '$admin_id')
               OR (sender_id = '$admin_id' AND receiver_id = '$student_id')
            ORDER BY created_at ASC
        ");
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
            max-width: 1280px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        /* Split-Pane Layout */
        .chat-container {
            display: grid;
            grid-template-columns: 320px 1fr;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            height: 75vh;
            min-height: 550px;
            overflow: hidden;
        }

        /* Sidebar: Student List */
        .student-list {
            border-right: 1px solid #e2e8f0;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .list-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: #f8fafc;
        }

        .student-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            text-decoration: none;
            color: #1e293b;
            gap: 12px;
            transition: background-color 0.15s ease;
            position: relative;
        }

        .student-item:hover {
            background-color: #f8fafc;
        }

        .student-item.active {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
        }

        .student-avatar {
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
            border: 1px solid #cbd5e1;
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
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .student-info small {
            font-size: 0.78rem;
            color: #64748b;
        }

        /* Unread Notification Pill */
        .unread-badge {
            background-color: #ef4444;
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(239, 68, 68, 0.4);
        }

        /* Main Chat Window */
        .admin-chat {
    display: flex;
    flex-direction: column;
    background-color: #f8fafc;
    height: 100%; /* Ensure it takes full height of the grid cell */
    overflow: hidden;
}

        .admin-chat-header {
            padding: 14px 20px;
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-chat-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .admin-chat-body {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* Message Bubbles */
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
            max-width: 65%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 0.9rem;
            line-height: 1.45;
            position: relative;
            word-wrap: break-word;
        }

        .admin-bubble.me {
            background-color: #2563eb;
            color: #ffffff;
            border-bottom-right-radius: 2px;
        }

        .admin-bubble.student {
            background-color: #ffffff;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 2px;
        }

        .admin-bubble small {
            display: block;
            font-size: 0.68rem;
            margin-top: 4px;
            opacity: 0.75;
            text-align: right;
        }

        /* Form Input */
        .admin-chat-input {
            display: flex;
            padding: 14px;
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            gap: 10px;
        }

        .admin-chat-input textarea {
            flex-grow: 1;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            resize: none;
            height: 40px;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .admin-chat-input textarea:focus {
            border-color: #2563eb;
        }

        .admin-chat-input button {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            width: 42px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
        }

        .admin-chat-input button:hover {
            background-color: #1d4ed8;
        }

        /* Empty Selection State */
        .empty-admin-chat {
            margin: auto;
            text-align: center;
            color: #64748b;
        }

        .empty-admin-chat i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 12px;
        }

        /* Pop-up Notification Toast */
        #notification-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background-color: #0f172a;
            color: #ffffff;
            padding: 14px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: none;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-content {
                padding: 12px;
            }

            .chat-container {
                grid-template-columns: 1fr;
                height: 80vh;
            }

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

        <!-- STUDENT LIST SIDEBAR -->
        <div class="student-list">
            <div class="list-header">
                <i class="fa-solid fa-comments" style="color: #2563eb;"></i> Messages
            </div>

            <?php if (mysqli_num_rows($students) > 0): ?>
                <?php while ($s = mysqli_fetch_assoc($students)): ?>
                    <a href="messages.php?student_id=<?php echo $s['user_id']; ?>"
                       class="student-item <?php echo ($student_id == $s['user_id']) ? 'active' : ''; ?>">

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
                <div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 0.88rem;">
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

            <?php else: ?>
                <div class="empty-admin-chat">
                    <i class="fa-regular fa-paper-plane"></i>
                    <h2>Select a Student</h2>
                    <p>Choose a student conversation from the left sidebar to start messaging.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<!-- REAL-TIME UNREAD NOTIFICATION TOAST -->
<div id="notification-toast">
    <i class="fa-solid fa-bell" style="color: #38bdf8;"></i>
    <span>You have new unread messages!</span>
</div>

<script>
    // Auto-scroll chat to the latest message
    const chat = document.getElementById("chatBox");
    if (chat) {
        chat.scrollTop = chat.scrollHeight;
    }

    // Keydown Listener: Submit message on "Enter" without Shift
    const box = document.getElementById("messageBox");
    if (box) {
        box.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                document.getElementById("sendButton").click();
            }
        });
    }

    // REAL-TIME UNREAD MESSAGE POLLER
    let previousUnreadCount = null;

    function checkUnreadMessages() {
        fetch('messages.php?ajax=check_unread')
            .then(response => response.json())
            .then(data => {
                if (previousUnreadCount !== null && data.unread > previousUnreadCount) {
                    showNotificationToast();
                }
                previousUnreadCount = data.unread;
            })
            .catch(error => console.error('Error polling messages:', error));
    }

    function showNotificationToast() {
        const toast = document.getElementById('notification-toast');
        toast.style.display = 'flex';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 4000);
    }

    // Poll server every 5 seconds for new message notifications
    setInterval(checkUnreadMessages, 5000);
</script>

</body>
</html>