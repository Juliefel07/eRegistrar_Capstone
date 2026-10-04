<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id =$_SESSION['user_id'];

// Get Admin (Registrar) Account
$admin_stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE role = 'Admin' LIMIT 1");
mysqli_stmt_execute($admin_stmt);
$admin_res = mysqli_stmt_get_result($admin_stmt);
$admin_data = mysqli_fetch_assoc($admin_res);
mysqli_stmt_close($admin_stmt);

if (!$admin_data) {
    die("Registrar account not found.");
}

$admin_id =$admin_data['user_id'];

// Send New Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $send_stmt = mysqli_prepare($conn, "
            INSERT INTO messages (sender_id, receiver_id, message, status, created_at)
            VALUES (?, ?, ?, 'Unread', NOW())
        ");
        if ($send_stmt) {
            mysqli_stmt_bind_param($send_stmt, "iis", $user_id, $admin_id,$message);
            mysqli_stmt_execute($send_stmt);
            mysqli_stmt_close($send_stmt);
        }
    }

    header("Location: messages.php");
    exit();
}

// Automatically Mark Incoming Admin Messages as 'Read'
$read_stmt = mysqli_prepare($conn, "
    UPDATE messages 
    SET status = 'Read' 
    WHERE sender_id = ? AND receiver_id = ? AND status = 'Unread'
");
if ($read_stmt) {
    mysqli_stmt_bind_param($read_stmt, "ii", $admin_id,$user_id);
    mysqli_stmt_execute($read_stmt);
    mysqli_stmt_close($read_stmt);
}

// Retrieve Chat History
$chat_stmt = mysqli_prepare($conn, "
    SELECT message_id, sender_id, receiver_id, message, status, created_at
    FROM messages
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
mysqli_stmt_bind_param($chat_stmt, "iiii", $user_id,$admin_id, $admin_id,$user_id);
mysqli_stmt_execute($chat_stmt);
$chat_result = mysqli_stmt_get_result($chat_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <title>Messages - eRegistrar</title>

    <style>
        /* RESET & SYSTEM STYLING */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #0056b3;
            --primary-dark: #002d62;
            --bg-body: #f4f6f9;
            --card-bg: #ffffff;
            --text-dark: #333333;
            --text-muted: #6c757d;
            --border: #e9ecef;
            --radius-lg: 16px;
            --radius-md: 10px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            padding-bottom: 80px;
        }

        @media (min-width: 992px) {
            body { padding-bottom: 0; }
        }

        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 0 16px;
        }

        /* CHAT CONTAINER */
        .chat-container {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 130px);
            min-height: 500px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }

        /* CHAT HEADER */
        .chat-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
            background: #ffffff;
        }

        .chat-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #e0eeff;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .chat-header-info h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .chat-header-info span {
            font-size: 12px;
            color: #198754;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .chat-header-info span::before {
            content: '';
            display: inline-block;
            width: 7px;
            height: 7px;
            background: #198754;
            border-radius: 50%;
        }

        /* CHAT MESSAGES BODY */
        .chat-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background: #f8f9fa;
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
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 14px;
            font-size: 14px;
            line-height: 1.4;
            position: relative;
            word-wrap: break-word;
        }

        .bubble.student {
            background: var(--primary);
            color: #ffffff;
            border-bottom-right-radius: 2px;
        }

        .bubble.admin {
            background: #ffffff;
            color: var(--text-dark);
            border: 1px solid var(--border);
            border-bottom-left-radius: 2px;
        }

        .bubble-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 6px;
            font-size: 11px;
            opacity: 0.8;
        }

        .bubble.student .bubble-meta { color: #e0e0e0; }
        .bubble.admin .bubble-meta { color: var(--text-muted); }

        .message-actions {
            display: flex;
            gap: 8px;
        }

        .message-actions a {
            color: inherit;
            text-decoration: none;
            font-size: 11px;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .message-actions a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        /* CHAT INPUT AREA */
        .chat-input-form {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            background: #ffffff;
            border-top: 1px solid var(--border);
        }

        .chat-input-form textarea {
            flex: 1;
            border: 1px solid #ced4da;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            font-family: inherit;
            resize: none;
            height: 42px;
            outline: none;
            transition: border-color 0.2s;
        }

        .chat-input-form textarea:focus {
            border-color: var(--primary);
        }

        .send-btn {
            background: var(--primary);
            color: #ffffff;
            border: none;
            width: 42px;
            height: 42px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: background 0.2s;
        }

        .send-btn:hover {
            background: var(--primary-dark);
        }

        /* EDIT MODAL */
        .edit-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .edit-modal.active {
            display: flex;
        }

        .edit-box {
            background: #ffffff;
            border-radius: var(--radius-md);
            width: 100%;
            max-width: 450px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }

        .edit-header h3 {
            font-size: 16px;
            color: var(--text-dark);
        }

        .close-modal-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-muted);
        }

        .edit-box textarea {
            width: 100%;
            height: 100px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            padding: 10px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            margin-bottom: 14px;
        }

        .edit-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .cancel-btn, .save-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }

        .cancel-btn { background: #e9ecef; color: #333; }
        .save-btn { background: var(--primary); color: #fff; }

        @media (max-width: 576px) {
            .bubble { max-width: 85%; }
            .chat-container { height: calc(100vh - 160px); }
        }
    </style>
</head>
<body>

    <!-- INCLUDE SHARED NAVIGATION BAR -->
    <?php require_once __DIR__ . "/navbar.php"; ?>

    <main class="container">
        <div class="chat-container">
            
            <!-- Chat Header -->
            <div class="chat-header">
                <div class="chat-avatar">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div class="chat-header-info">
                    <h3>Registrar Office</h3>
                    <span>Usually replies within office hours</span>
                </div>
            </div>

            <!-- Chat Messages Area -->
            <div class="chat-body" id="chatBox">
                <?php if (mysqli_num_rows($chat_result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($chat_result)): ?>
                        <?php if ($row['sender_id'] ==$user_id): ?>
                            <!-- Student Message (Right) -->
                            <div class="message-row right">
                                <div class="bubble student">
                                    <p><?= nl2br(htmlspecialchars($row['message'])); ?></p>
                                    <div class="bubble-meta">
                                        <span><?= date("h:i A", strtotime($row['created_at'])); ?></span>
                                        <div class="message-actions">
                                            <a href="#" onclick="openEditModal('<?= $row['message_id']; ?>', `<?= htmlspecialchars($row['message'], ENT_QUOTES); ?>`); return false;">
                                                <i class="fa-solid fa-pen"></i> Edit
                                            </a>
                                            <a href="delete_message.php?id=<?= $row['message_id']; ?>" onclick="return confirm('Delete this message?');">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Admin Message (Left) -->
                            <div class="message-row left">
                                <div class="bubble admin">
                                    <strong style="display:block; margin-bottom:4px; font-size:12px; color:var(--primary);"><i class="fa-solid fa-user-tie"></i> Registrar Office</strong>
                                    <p><?= nl2br(htmlspecialchars($row['message'])); ?></p>
                                    <div class="bubble-meta">
                                        <span><?= date("h:i A | M j", strtotime($row['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; color: var(--text-muted); margin: auto; font-size: 13px;">
                        No conversation yet. Send a message to contact the Registrar.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Chat Input Form -->
            <form method="POST" class="chat-input-form">
                <textarea id="messageBox" name="message" placeholder="Type your message..." required></textarea>
                <button type="submit" name="send" id="sendButton" class="send-btn">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>

        </div>
    </main>

    <!-- EDIT MESSAGE MODAL -->
    <div class="edit-modal" id="editModal">
        <div class="edit-box">
            <div class="edit-header">
                <h3>Edit Message</h3>
                <button type="button" class="close-modal-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" action="edit_message.php">
                <input type="hidden" name="message_id" id="editMessageId">
                <textarea name="message" id="editMessageText" required></textarea>
                <div class="edit-actions">
                    <button type="button" onclick="closeEditModal()" class="cancel-btn">Cancel</button>
                    <button type="submit" name="update" class="save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT FOR CHAT & MODAL -->
    <script>
        // Scroll to bottom of chat
        const chatBox = document.getElementById("chatBox");
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Enter key to send message (Shift + Enter for new line)
        const messageInput = document.getElementById("messageBox");
        const sendButton = document.getElementById("sendButton");

        if (messageInput) {
            messageInput.addEventListener("keydown", function(e) {
                if (e.key === "Enter" && !e.shiftKey) {
                    e.preventDefault();
                    sendButton.click();
                }
            });
        }

        // Edit Modal Handlers
        function openEditModal(id, text) {
            document.getElementById('editMessageId').value = id;
            document.getElementById('editMessageText').value = text;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
    </script>
</body>
</html>