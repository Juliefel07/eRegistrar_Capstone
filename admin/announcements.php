<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// FIX TIMEZONE TO PHILIPPINE STANDARD TIME (PST)
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . "/../includes/db.php";

mysqli_query($conn, "SET time_zone = '+08:00'");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = (int)$_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Ensure announcements table has image column
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM announcements LIKE 'image_path'");
if (mysqli_num_rows($check_col) == 0) {
    @mysqli_query($conn, "ALTER TABLE announcements ADD COLUMN image_path VARCHAR(255) NULL");
}

// HANDLE FORM SUBMISSIONS (CREATE, EDIT, DELETE, TOGGLE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // FILE UPLOAD HELPER FUNCTION
        $uploaded_image = null;
        if (isset($_FILES['announcement_image']) && $_FILES['announcement_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . "/../assets/uploads/announcements/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['announcement_image']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($file_ext, $allowed_exts)) {
                $filename = "announcement_" . time() . "_" . rand(1000, 9999) . "." . $file_ext;
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['announcement_image']['tmp_name'], $target_file)) {
                    $uploaded_image = "assets/uploads/announcements/" . $filename;
                }
            } else {
                $error_msg = "Invalid file type. Only JPG, PNG, GIF, and WEBP images are allowed.";
            }
        }

        // 1. CREATE ANNOUNCEMENT & NOTIFY STUDENTS/LOGGED-IN USERS
        if ($action === 'create' && empty($error_msg)) {
            $title = trim($_POST['title']);
            $content = trim($_POST['content']);
            $audience = $_POST['target_audience'] ?? 'all';

            if (!empty($title) && !empty($content)) {
                $stmt = mysqli_prepare($conn, "INSERT INTO announcements (title, content, target_audience, image_path, created_by) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssi", $title, $content, $audience, $uploaded_image, $admin_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "Announcement published successfully!";

                    // SEND NOTIFICATION TO ALL LOGGED-IN STUDENTS / USERS
                    if ($audience === 'all' || $audience === 'students') {
                        $notif_message = "📢 Announcement: " . $title;

                        // Query all student/user accounts (excluding admins)
                        $students_query = mysqli_query($conn, "SELECT user_id FROM users WHERE role = 'student' OR role = 'user' OR role IS NULL OR role != 'admin'");

                        if ($students_query && mysqli_num_rows($students_query) > 0) {
                            $stmt_notif = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
                            
                            while ($student = mysqli_fetch_assoc($students_query)) {
                                $student_id = (int)$student['user_id'];
                                mysqli_stmt_bind_param($stmt_notif, "is", $student_id, $notif_message);
                                mysqli_stmt_execute($stmt_notif);
                            }
                        }
                    }
                } else {
                    $error_msg = "Failed to create announcement.";
                }
            } else {
                $error_msg = "Please fill in all required fields.";
            }
        }

        // 2. EDIT ANNOUNCEMENT
        if ($action === 'edit' && empty($error_msg)) {
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $content = trim($_POST['content']);
            $audience = $_POST['target_audience'] ?? 'all';

            if ($uploaded_image) {
                // Update with new image
                $stmt = mysqli_prepare($conn, "UPDATE announcements SET title = ?, content = ?, target_audience = ?, image_path = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "ssssi", $title, $content, $audience, $uploaded_image, $id);
            } else {
                // Keep existing image
                $stmt = mysqli_prepare($conn, "UPDATE announcements SET title = ?, content = ?, target_audience = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "sssi", $title, $content, $audience, $id);
            }

            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Announcement updated successfully!";
            }
        }

        // 3. DELETE ANNOUNCEMENT
        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = mysqli_prepare($conn, "DELETE FROM announcements WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Announcement deleted successfully.";
            }
        }

        // 4. TOGGLE VISIBILITY STATUS
        if ($action === 'toggle_status') {
            $id = (int)$_POST['id'];
            $current_status = $_POST['current_status'];
            $new_status = ($current_status === 'active') ? 'inactive' : 'active';
            
            $stmt = mysqli_prepare($conn, "UPDATE announcements SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $new_status, $id);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Visibility updated.";
            }
        }
    }
}

// Fetch all announcements
$announcements = mysqli_query($conn, "SELECT a.*, u.fullname FROM announcements a LEFT JOIN users u ON a.created_by = u.user_id ORDER BY a.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - Admin</title>
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #e0e7ff;
            --success: #10b981;
            --success-bg: #d1fae5;
            --warning: #f59e0b;
            --danger: #ef4444;
            --danger-bg: #fee2e2;
            --dark: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --bg-main: #f8fafc;
            --card-bg: #ffffff;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        body { 
            background-color: var(--bg-main); 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            color: var(--text-main);
        }

        .admin-content { 
            padding: 32px; 
            max-width: 1200px; 
            margin: 0 auto; 
        }

        /* HEADER */
        .page-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-header h2 { 
            font-size: 1.6rem; 
            color: var(--dark); 
            margin: 0 0 4px 0; 
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-header p {
            color: var(--text-muted); 
            margin: 0; 
            font-size: 0.92rem;
        }

        /* ALERTS */
        .alert-success { background: var(--success-bg); color: #065f46; padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; box-shadow: var(--shadow-sm); }
        .alert-error { background: var(--danger-bg); color: #991b1b; padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; box-shadow: var(--shadow-sm); }

        /* BUTTONS */
        .btn { 
            padding: 9px 16px; 
            border-radius: var(--radius-sm); 
            font-weight: 600; 
            border: none; 
            cursor: pointer; 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            font-size: 0.85rem; 
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: var(--primary); color: #ffffff; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-danger { background: var(--danger); color: #ffffff; }
        .btn-secondary { background: #64748b; color: #ffffff; }
        .btn-warning { background: var(--warning); color: #ffffff; }

        .btn-glow {
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
        }

        /* CARDS & LIST */
        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark);
            margin: 32px 0 16px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .announcements-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .announcement-item { 
            background: #ffffff; 
            border: 1px solid #cbd5e1; 
            border-radius: var(--radius-md); 
            padding: 20px; 
            box-shadow: var(--shadow-sm);
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .announcement-item:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }
        .announcement-item.inactive { 
            opacity: 0.65; 
            background: #f1f5f9; 
        }

        .announcement-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .announcement-title {
            margin: 0 0 8px 0;
            color: var(--dark);
            font-size: 1.15rem;
            font-weight: 700;
        }

        .announcement-body {
            margin: 0 0 16px 0;
            font-size: 0.95rem;
            color: var(--text-main);
            line-height: 1.6;
            white-space: pre-line;
        }

        .image-preview-container {
            margin-bottom: 16px;
        }
        .image-preview { 
            max-width: 220px; 
            max-height: 140px; 
            border-radius: var(--radius-sm); 
            object-fit: cover; 
            border: 1px solid var(--border); 
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s;
        }
        .image-preview:hover { transform: scale(1.02); }

        .announcement-meta { 
            font-size: 0.82rem; 
            color: var(--text-muted); 
            padding-top: 14px;
            border-top: 1px solid var(--border);
            display: flex; 
            gap: 16px; 
            align-items: center; 
            flex-wrap: wrap; 
        }
        .announcement-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge { 
            padding: 4px 10px; 
            border-radius: 20px; 
            font-size: 0.72rem; 
            font-weight: 700; 
            letter-spacing: 0.3px;
            text-transform: uppercase; 
        }
        .badge-active { background: var(--success-bg); color: #047857; }
        .badge-inactive { background: #e2e8f0; color: var(--text-muted); }
        .badge-audience { background: var(--primary-light); color: var(--primary); }

        /* COMPACT & MOBILE-FRIENDLY ACTION BUTTONS */
        .action-buttons {
            display: flex;
            gap: 4px;
            flex-shrink: 0;
        }

        .action-btn {
            padding: 5px 9px;
            font-size: 0.75rem;
            border-radius: 6px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .admin-content { padding: 16px; }
            .announcement-header-row {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            .action-buttons {
                display: flex;
                gap: 6px;
            }
            .action-btn {
                padding: 6px 10px;
                font-size: 0.78rem;
                flex: 1;
                justify-content: center;
            }
            .page-header-container {
                flex-direction: column;
                align-items: stretch;
            }
            .page-header-container .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* FLOATING MODAL OVERLAYS */
        .modal-overlay { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(15, 23, 42, 0.65); 
            backdrop-filter: blur(6px); 
            z-index: 9999; 
            justify-content: center; 
            align-items: center; 
            padding: 20px; 
            animation: fadeIn 0.25s ease-in-out;
        }
        .modal-container { 
            background: var(--card-bg); 
            width: 100%; 
            max-width: 550px; 
            border-radius: var(--radius-lg); 
            padding: 24px; 
            box-shadow: var(--shadow-lg); 
            position: relative; 
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .modal-close { 
            position: absolute; 
            right: 18px; 
            top: 18px; 
            background: #f1f5f9;
            border: none; 
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem; 
            color: var(--text-muted); 
            cursor: pointer; 
            transition: all 0.2s;
        }
        .modal-close:hover { background: var(--danger-bg); color: var(--danger); }

        .modal-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* FORM CONTROLS */
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.82rem; color: var(--text-main); margin-bottom: 6px; }
        .form-control { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid var(--border); 
            border-radius: var(--radius-sm); 
            font-size: 0.88rem; 
            font-family: 'Inter', sans-serif;
            box-sizing: border-box; 
            transition: all 0.2s;
            background: #fff;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
        textarea.form-control { resize: vertical; }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(15px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Empty state */
        .empty-state {
            background: var(--card-bg);
            border: 2px dashed var(--border);
            border-radius: var(--radius-md);
            padding: 40px 20px;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 2.2rem;
            color: #cbd5e1;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">
    <div class="page-header-container">
        <div class="page-header">
            <h2><i class="fa-solid fa-bullhorn" style="color: var(--primary);"></i> Manage Announcements</h2>
            <p>Post announcements, broadcast alerts, and manage portal banners.</p>
        </div>
        <button type="button" class="btn btn-primary btn-glow" onclick="openCreateModal()">
            <i class="fa-solid fa-plus"></i> Post Announcement
        </button>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert-success"><i class="fa-solid fa-circle-check fa-lg"></i> <?= htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert-error"><i class="fa-solid fa-circle-exclamation fa-lg"></i> <?= htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <!-- EXISTING ANNOUNCEMENTS -->
    <div class="section-title">
        <span>Existing Announcements</span>
        <span style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);"><?= mysqli_num_rows($announcements); ?> total posts</span>
    </div>

    <?php if (mysqli_num_rows($announcements) > 0): ?>
        <div class="announcements-grid">
            <?php while ($row = mysqli_fetch_assoc($announcements)): ?>
                <div class="announcement-item <?= $row['status'] === 'inactive' ? 'inactive' : ''; ?>">
                    <div>
                        <div class="announcement-header-row">
                            <div style="flex: 1;">
                                <h4 class="announcement-title"><?= htmlspecialchars($row['title']); ?></h4>
                                <p class="announcement-body"><?= htmlspecialchars($row['content']); ?></p>
                            </div>

                            <div class="action-buttons">
                                <!-- EDIT BUTTON -->
                                <button type="button" class="btn btn-warning action-btn" onclick='openEditModal(<?= json_encode($row); ?>)' title="Edit">
                                    <i class="fa-solid fa-pen"></i> <span>Edit</span>
                                </button>

                                <!-- TOGGLE STATUS -->
                                <form method="POST" style="display: inline; margin: 0;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?= $row['status']; ?>">
                                    <button type="submit" class="btn btn-secondary action-btn" title="<?= $row['status'] === 'active' ? 'Hide' : 'Show'; ?>">
                                        <i class="fa-solid <?= $row['status'] === 'active' ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                        <span><?= $row['status'] === 'active' ? 'Hide' : 'Show'; ?></span>
                                    </button>
                                </form>

                                <!-- DELETE BUTTON -->
                                <button type="button" class="btn btn-danger action-btn" onclick="openDeleteModal(<?= $row['id']; ?>)" title="Delete">
                                    <i class="fa-solid fa-trash"></i> <span>Delete</span>
                                </button>
                            </div>
                        </div>

                        <?php if (!empty($row['image_path'])): ?>
                            <div class="image-preview-container">
                                <img src="../<?= htmlspecialchars($row['image_path']); ?>" alt="Banner" class="image-preview">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="announcement-meta">
                        <span><i class="fa-regular fa-clock"></i> <?= date("M d, Y • h:i A", strtotime($row['created_at'])); ?></span>
                        <span><i class="fa-regular fa-user"></i> <?= htmlspecialchars($row['fullname'] ?? 'Admin'); ?></span>
                        <span class="badge badge-audience">Audience: <?= ucfirst($row['target_audience']); ?></span>
                        <span class="badge <?= $row['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                            <?= ucfirst($row['status']); ?>
                        </span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-regular fa-folder-open"></i>
            <p style="margin: 0; font-size: 0.9rem;">No announcements posted yet. Click <strong>"Post Announcement"</strong> to create one.</p>
        </div>
    <?php endif; ?>
</div>

<!-- CREATE ANNOUNCEMENT FLOATING MODAL -->
<div id="createModal" class="modal-overlay">
    <div class="modal-container">
        <button class="modal-close" onclick="closeCreateModal()">&times;</button>
        <h3 class="modal-title"><i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i> Post New Announcement</h3>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label>Announcement Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g., Office Hours Schedule Change" required>
            </div>

            <div class="form-group">
                <label>Target Audience</label>
                <select name="target_audience" class="form-control">
                    <option value="all">Everyone (Public & Student Portal)</option>
                    <option value="public">Public Only (Landing Page)</option>
                    <option value="students">Students Only (Logged-in Portal)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Content / Message</label>
                <textarea name="content" rows="4" class="form-control" placeholder="Write announcement details..." required></textarea>
            </div>

            <div class="form-group">
                <label>Attach Optional Banner / Image</label>
                <input type="file" name="announcement_image" class="form-control" accept="image/*">
            </div>

            <div style="display: flex; gap: 8px; margin-top: 18px;">
                <button type="button" class="btn btn-secondary" style="flex: 1; justify-content: center;" onclick="closeCreateModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
                    <i class="fa-solid fa-paper-plane"></i> Publish Now
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT ANNOUNCEMENT MODAL -->
<div id="editModal" class="modal-overlay">
    <div class="modal-container">
        <button class="modal-close" onclick="closeEditModal()">&times;</button>
        <h3 class="modal-title"><i class="fa-solid fa-pen" style="color: var(--warning);"></i> Edit Announcement</h3>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" id="edit_title" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Target Audience</label>
                <select name="target_audience" id="edit_audience" class="form-control">
                    <option value="all">Everyone (Public & Student Portal)</option>
                    <option value="public">Public Only (Landing Page)</option>
                    <option value="students">Students Only (Logged-in Portal)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Content / Message</label>
                <textarea name="content" id="edit_content" rows="4" class="form-control" required></textarea>
            </div>

            <div class="form-group">
                <label>Replace Image (Optional)</label>
                <input type="file" name="announcement_image" class="form-control" accept="image/*">
            </div>

            <div style="display: flex; gap: 8px; margin-top: 18px;">
                <button type="button" class="btn btn-secondary" style="flex: 1; justify-content: center;" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE CONFIRMATION FLOATING MODAL -->
<div id="deleteModal" class="modal-overlay">
    <div class="modal-container" style="max-width: 380px; text-align: center;">
        <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        <div style="font-size: 2.2rem; color: var(--danger); margin-bottom: 8px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="modal-title" style="justify-content: center; margin-bottom: 6px;">Delete Announcement</h3>
        <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 18px;">
            Are you sure you want to delete this announcement? This action cannot be undone.
        </p>
        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete_id">
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-secondary" style="flex: 1; justify-content: center;" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger" style="flex: 1; justify-content: center;">
                    <i class="fa-solid fa-trash"></i> Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Create Modal Functions
function openCreateModal() {
    document.getElementById('createModal').style.display = 'flex';
}
function closeCreateModal() {
    document.getElementById('createModal').style.display = 'none';
}

// Edit Modal Functions
function openEditModal(announcement) {
    document.getElementById('edit_id').value = announcement.id;
    document.getElementById('edit_title').value = announcement.title;
    document.getElementById('edit_audience').value = announcement.target_audience;
    document.getElementById('edit_content').value = announcement.content;
    document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Delete Modal Functions
function openDeleteModal(id) {
    document.getElementById('delete_id').value = id;
    document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modals when clicking outside the container
window.onclick = function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        closeCreateModal();
        closeEditModal();
        closeDeleteModal();
    }
}
</script>

</body>
</html>