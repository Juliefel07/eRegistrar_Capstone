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

    <style>
        body { background-color: #f8fafc; font-family: 'Segoe UI', Roboto, sans-serif; margin: 0; }
        .admin-content { padding: 24px; max-width: 1100px; margin: 0 auto; }
        .page-header h2 { font-size: 1.4rem; color: #0f172a; margin: 0 0 6px 0; }
        
        .card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .card-header-title { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px; }

        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; box-sizing: border-box; }

        .btn { padding: 8px 16px; border-radius: 6px; font-weight: 600; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; }
        .btn-primary { background: #2563eb; color: #ffffff; }
        .btn-danger { background: #dc2626; color: #ffffff; }
        .btn-secondary { background: #64748b; color: #ffffff; }
        .btn-warning { background: #f59e0b; color: #ffffff; }

        .alert-success { background: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }

        .announcement-item { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 12px; border-left: 4px solid #2563eb; }
        .announcement-item.inactive { border-left-color: #94a3b8; opacity: 0.7; }
        .announcement-meta { font-size: 0.78rem; color: #64748b; margin-top: 10px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }

        .badge { padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-active { background: #d1fae5; color: #047857; }
        .badge-inactive { background: #f1f5f9; color: #64748b; }
        .badge-audience { background: #e0f2fe; color: #0369a1; }

        .image-preview { max-width: 150px; max-height: 100px; border-radius: 6px; object-fit: cover; margin-top: 8px; border: 1px solid #e2e8f0; }

        /* EDIT MODAL */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 16px; }
        .modal-container { background: #ffffff; width: 100%; max-width: 550px; border-radius: 12px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); position: relative; }
        .modal-close { position: absolute; right: 16px; top: 16px; background: none; border: none; font-size: 1.25rem; color: #64748b; cursor: pointer; }
    </style>
</head>
<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">
    <div class="page-header">
        <h2><i class="fa-solid fa-bullhorn" style="color: #2563eb;"></i> Manage Announcements</h2>
        <p style="color: #64748b; margin: 0 0 20px 0; font-size: 0.9rem;">Post, edit, or attach banners to system announcements.</p>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <!-- CREATE ANNOUNCEMENT FORM -->
    <div class="card">
        <h3 class="card-header-title"><i class="fa-solid fa-pen-to-square" style="color: #2563eb;"></i> Post New Announcement</h3>
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

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-paper-plane"></i> Publish Announcement
            </button>
        </form>
    </div>

    <!-- ANNOUNCEMENTS LIST -->
    <h3 style="font-size: 1.1rem; color: #0f172a; margin-bottom: 12px;">Existing Announcements</h3>

    <?php if (mysqli_num_rows($announcements) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($announcements)): ?>
            <div class="announcement-item <?= $row['status'] === 'inactive' ? 'inactive' : ''; ?>">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 6px 0; color: #0f172a; font-size: 1rem;"><?= htmlspecialchars($row['title']); ?></h4>
                        <p style="margin: 0 0 8px 0; font-size: 0.9rem; color: #334155; line-height: 1.5;"><?= nl2br(htmlspecialchars($row['content'])); ?></p>
                        
                        <?php if (!empty($row['image_path'])): ?>
                            <div>
                                <img src="../<?= htmlspecialchars($row['image_path']); ?>" alt="Banner" class="image-preview">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; gap: 6px; flex-shrink: 0;">
                        <!-- EDIT BUTTON -->
                        <button type="button" class="btn btn-warning" style="padding: 5px 10px; font-size: 0.78rem;" 
                                onclick='openEditModal(<?= json_encode($row); ?>)'>
                            <i class="fa-solid fa-pen"></i> Edit
                        </button>

                        <!-- TOGGLE STATUS -->
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="current_status" value="<?= $row['status']; ?>">
                            <button type="submit" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.78rem;">
                                <i class="fa-solid <?= $row['status'] === 'active' ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                <?= $row['status'] === 'active' ? 'Hide' : 'Show'; ?>
                            </button>
                        </form>

                        <!-- DELETE -->
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.78rem;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="announcement-meta">
                    <span><i class="fa-regular fa-clock"></i> <?= date("M d, Y h:i A", strtotime($row['created_at'])); ?></span>
                    <span><i class="fa-regular fa-user"></i> <?= htmlspecialchars($row['fullname'] ?? 'Admin'); ?></span>
                    <span class="badge badge-audience">Audience: <?= ucfirst($row['target_audience']); ?></span>
                    <span class="badge <?= $row['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                        <?= ucfirst($row['status']); ?>
                    </span>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card" style="text-align: center; color: #64748b; font-style: italic;">
            No announcements posted yet.
        </div>
    <?php endif; ?>
</div>

<!-- EDIT ANNOUNCEMENT MODAL -->
<div id="editModal" class="modal-overlay">
    <div class="modal-container">
        <button class="modal-close" onclick="closeEditModal()">&times;</button>
        <h3 style="margin-top: 0; color: #0f172a;"><i class="fa-solid fa-pen" style="color: #f59e0b;"></i> Edit Announcement</h3>
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

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<script>
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

window.onclick = function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        closeEditModal();
    }
}
</script>

</body>
</html>