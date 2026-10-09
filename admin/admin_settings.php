<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');
require_once __DIR__ . "/../includes/db.php";
// Temporary check to ensure Aiven MySQL column fits hashed passwords
mysqli_query($conn, "ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL");
mysqli_query($conn, "SET time_zone = '+08:00'");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = (int)$_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Ensure users table has profile image column
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_pic'");
if (mysqli_num_rows($check_col) == 0) {
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) NULL");
}

// HANDLE FORM SUBMISSIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_type = $_POST['form_type'] ?? '';

    // 1. UPDATE PROFILE PICTURE
    if ($form_type === 'update_profile_pic') {
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . "/../assets/uploads/profiles/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($file_ext, $allowed_exts)) {
                $filename = "admin_" . $admin_id . "_" . time() . "." . $file_ext;
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                    $profile_path = "assets/uploads/profiles/" . $filename;
                    $stmt = mysqli_prepare($conn, "UPDATE users SET profile_pic = ? WHERE user_id = ?");
                    mysqli_stmt_bind_param($stmt, "si", $profile_path, $admin_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $success_msg = "Profile picture updated successfully!";
                    } else {
                        $error_msg = "Failed to update profile picture in database.";
                    }
                }
            } else {
                $error_msg = "Invalid file type. Only JPG, PNG, GIF, and WEBP images are allowed.";
            }
        } else {
            $error_msg = "Please select a valid image file to upload.";
        }
    }

    // 2. CHANGE PASSWORD
    if ($form_type === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (!empty($current_pass) && !empty($new_pass) && !empty($confirm_pass)) {
            if ($new_pass === $confirm_pass) {
                // Strong password validation check
                if (strlen($new_pass) >= 8 && preg_match('/[A-Z]/', $new_pass) && preg_match('/[0-9]/', $new_pass) && preg_match('/[\W]/', $new_pass)) {
                    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ?");
                    mysqli_stmt_bind_param($stmt, "i", $admin_id);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    $user_data = mysqli_fetch_assoc($res);

                    // Check if password matches via secure hash OR legacy plain-text
$is_valid_password = false;
if ($user_data) {
    if (password_verify($current_pass, $user_data['password'])) {
        $is_valid_password = true;
    } elseif ($current_pass === $user_data['password']) {
        // Allows upgrading old plain-text passwords to secure hashes on first change
        $is_valid_password = true;
    }
}

if ($is_valid_password) {
    $hashed_new_pass = password_hash($new_pass, PASSWORD_DEFAULT);
    $update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($update_stmt, "si", $hashed_new_pass, $admin_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $success_msg = "Password changed successfully!";
    } else {
        $error_msg = "Failed to update password.";
    }
} else {
    $error_msg = "Incorrect current password.";
}
                } else {
                    $error_msg = "Password must be at least 8 characters long and include uppercase letters, numbers, and special characters.";
                }
            } else {
                $error_msg = "New passwords do not match.";
            }
        } else {
            $error_msg = "Please fill in all password fields.";
        }
    }
}

// Fetch current admin info
$admin_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$admin_id'");
$admin = mysqli_fetch_assoc($admin_query);
$profile_pic = !empty($admin['profile_pic']) ? "../" . $admin['profile_pic'] : "../assets/images/default-avatar.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - CCTC eRegistrar</title>
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #eff6ff;
            --success: #16a34a;
            --success-bg: #f0fdf4;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --dark: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            --border: #cbd5e1;
            --bg-main: #f8fafc;
            --card-bg: #ffffff;
            --radius-sm: 8px;
            --radius-md: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.04);
        }

        body {
            background-color: var(--bg-main);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            margin: 0;
        }

        .admin-content {
            padding: 32px;
            max-width: 900px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .page-header {
            margin-bottom: 24px;
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

        .alert-success { background: var(--success-bg); color: #166534; padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; border: 1px solid #bbf7d0; }
        .alert-error { background: var(--danger-bg); color: #991b1b; padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; border: 1px solid #fecaca; }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        .settings-card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            padding: 28px;
            box-shadow: var(--shadow-sm);
        }

        .settings-card h3 {
            font-size: 1.15rem;
            color: var(--dark);
            margin-top: 0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
        }

        .profile-preview-container {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .current-avatar {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
            box-shadow: var(--shadow-sm);
            background: #e2e8f0;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.82rem;
            color: var(--text-main);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Split Pill Input Field Style matching reference */
        .custom-input-group {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 8px; /* Fully rounded capsule pill shape */
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .custom-input-group:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .input-icon-left {
            padding: 0 18px;
            color: var(--text-muted);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-right: 1px solid #f1f5f9;
            background: #fafafa;
            align-self: stretch;
        }

        .custom-input-field {
            flex: 1;
            border: none;
            padding: 12px 16px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            background: transparent;
            color: var(--dark);
            outline: none;
            border-radius: 0px !important;
        }

        .input-action-right {
            padding: 0 18px;
            color: var(--text-muted);
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-left: 1px solid #f1f5f9;
            background: #fafafa;
            align-self: stretch;
            cursor: pointer;
            transition: color 0.2s;
        }

        .input-action-right:hover {
            color: var(--primary);
        }

        .form-control.file-input {
            border: 1px solid var(--border);
            border-radius: none;
            padding: 10px;
            background: #f8fafc;
            width: 100%;
        }

        /* Password Strength Checklist & Meter */
        .password-strength-box {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: none;
            padding: 14px;
            margin-top: 10px;
        }

        .strength-meter {
            height: 6px;
            width: 100%;
            background: #e2e8f0;
            border-radius: 3px;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .requirement-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .requirement-list li {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .requirement-list li.valid {
            color: var(--success);
            font-weight: 600;
        }

        .requirement-list li.valid i {
            color: var(--success);
        }

        .btn {
            padding: 12px 20px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.92rem;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: var(--primary); color: #ffffff; }
        .btn-primary:hover { background: var(--primary-hover); }

        /* Floating Confirmation Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .floating-modal {
            background: #ffffff;
            width: 100%;
            max-width: 420px;
            border-radius: var(--radius-md);
            padding: 28px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            transform: translateY(20px);
            transition: transform 0.25s ease;
            text-align: center;
        }

        .modal-overlay.active .floating-modal {
            transform: translateY(0);
        }

        .modal-icon {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
        }

        .floating-modal h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 8px 0;
            border: none;
            padding: 0;
        }

        .floating-modal p {
            font-size: 0.92rem;
            color: var(--text-muted);
            margin: 0 0 24px 0;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .modal-btn {
            flex: 1;
            padding: 10px 16px;
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .modal-btn-cancel {
            background: #f1f5f9;
            color: var(--text-muted);
        }
        .modal-btn-cancel:hover { background: #e2e8f0; }

        .modal-btn-confirm {
            background: var(--primary);
            color: #ffffff;
        }
        .modal-btn-confirm:hover { background: var(--primary-hover); }
    </style>
</head>
<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">
    <div class="page-header">
        <h2><i class="fa-solid fa-gear" style="color: var(--primary);"></i> Admin Settings</h2>
        <p>Manage your account security and profile information.</p>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert-success"><i class="fa-solid fa-circle-check fa-lg"></i> <?= htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert-error"><i class="fa-solid fa-circle-exclamation fa-lg"></i> <?= htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- PROFILE PICTURE CARD -->
        <div class="settings-card">
            <h3><i class="fa-solid fa-user-pen" style="color: var(--primary);"></i> Profile Picture</h3>
            
            <form id="profilePicForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form_type" value="update_profile_pic">
                
                <div class="profile-preview-container">
                    <img src="<?= htmlspecialchars($profile_pic); ?>" alt="Admin Avatar" class="current-avatar" onerror="this.src='../assets/images/default-avatar.png';">
                    <div>
                        <p style="margin: 0 0 4px 0; font-weight: 600; font-size: 0.95rem;"><?= htmlspecialchars($admin['fullname'] ?? 'Administrator'); ?></p>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.85rem;">Upload a new avatar (JPG, PNG, WEBP)</p>
                    </div>
                </div>

                <div class="form-group">
                    <input type="file" id="profile_pic_input" name="profile_pic" class="form-control file-input" accept="image/*" required>
                </div>

                <button type="button" class="btn btn-primary" onclick="openProfileModal()">
                    <i class="fa-solid fa-upload"></i> Update Profile Picture
                </button>
            </form>
        </div>

        <!-- CHANGE PASSWORD CARD -->
        <div class="settings-card">
            <h3><i class="fa-solid fa-shield-halved" style="color: var(--primary);"></i> Change Password</h3>
            
            <form id="passwordForm" method="POST">
                <input type="hidden" name="form_type" value="change_password">

                <div class="form-group">
                    <label>Current Password</label>
                    <div class="custom-input-group">
                        <div class="input-icon-left"><i class="fa-solid fa-lock"></i></div>
                        <input type="password" id="current_password" name="current_password" class="custom-input-field" placeholder="Enter current password" required>
                        <div class="input-action-right" onclick="togglePassword('current_password', this)">
                            <i class="fa-solid fa-eye"></i>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <div class="custom-input-group">
                        <div class="input-icon-left"><i class="fa-solid fa-key"></i></div>
                        <input type="password" id="new_password" name="new_password" class="custom-input-field" placeholder="Enter new password" required>
                        <div class="input-action-right" onclick="togglePassword('new_password', this)">
                            <i class="fa-solid fa-eye"></i>
                        </div>
                    </div>

                    <!-- Live Password Strength Meter & Checklist -->
                    <div class="password-strength-box">
                        <div class="strength-meter">
                            <div id="strengthBar" class="strength-bar"></div>
                        </div>
                        <ul class="requirement-list">
                            <li id="req-length"><i class="fa-regular fa-circle-check"></i> At least 8 chars</li>
                            <li id="req-upper"><i class="fa-regular fa-circle-check"></i> Uppercase letter</li>
                            <li id="req-number"><i class="fa-regular fa-circle-check"></i> A number</li>
                            <li id="req-special"><i class="fa-regular fa-circle-check"></i> Special character</li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="custom-input-group">
                        <div class="input-icon-left"><i class="fa-solid fa-lock-open"></i></div>
                        <input type="password" id="confirm_password" name="confirm_password" class="custom-input-field" placeholder="Confirm new password" required>
                        <div class="input-action-right" onclick="togglePassword('confirm_password', this)">
                            <i class="fa-solid fa-eye"></i>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-primary" onclick="openPasswordModal()">
                    <i class="fa-solid fa-shield-keyhole"></i> Update Password
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Floating Confirmation Modal for Profile Picture -->
<div id="profileModal" class="modal-overlay">
    <div class="floating-modal">
        <div class="modal-icon">
            <i class="fa-solid fa-user-camera"></i>
        </div>
        <h3>Update Profile Picture</h3>
        <p>Are you sure you want to change your administrative profile picture?</p>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeProfileModal()">Cancel</button>
            <button type="button" class="modal-btn modal-btn-confirm" onclick="submitProfileForm()">Yes, Upload</button>
        </div>
    </div>
</div>

<!-- Floating Confirmation Modal for Password -->
<div id="passwordModal" class="modal-overlay">
    <div class="floating-modal">
        <div class="modal-icon">
            <i class="fa-solid fa-shield-keyhole"></i>
        </div>
        <h3>Confirm Password Change</h3>
        <p>Are you sure you want to update your password? You'll use this new password on your next login.</p>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closePasswordModal()">Cancel</button>
            <button type="button" class="modal-btn modal-btn-confirm" onclick="submitPasswordForm()">Yes, Update</button>
        </div>
    </div>
</div>

<script>
    // Toggle Password Visibility Function
    function togglePassword(fieldId, iconElement) {
        const inputField = document.getElementById(fieldId);
        const icon = iconElement.querySelector('i');
        if (inputField.type === 'password') {
            inputField.type = 'text';
            icon.className = 'fa-solid fa-eye-slash';
        } else {
            inputField.type = 'password';
            icon.className = 'fa-solid fa-eye';
        }
    }

    // Live Password Strength & Criteria Checker
    const newPasswordInput = document.getElementById('new_password');
    const strengthBar = document.getElementById('strengthBar');
    
    const reqLength = document.getElementById('req-length');
    const reqUpper = document.getElementById('req-upper');
    const reqNumber = document.getElementById('req-number');
    const reqSpecial = document.getElementById('req-special');

    newPasswordInput.addEventListener('input', function() {
        const val = newPasswordInput.value;
        let score = 0;

        if (val.length >= 8) {
            reqLength.classList.add('valid');
            reqLength.querySelector('i').className = 'fa-solid fa-circle-check';
            score++;
        } else {
            reqLength.classList.remove('valid');
            reqLength.querySelector('i').className = 'fa-regular fa-circle-check';
        }

        if (/[A-Z]/.test(val)) {
            reqUpper.classList.add('valid');
            reqUpper.querySelector('i').className = 'fa-solid fa-circle-check';
            score++;
        } else {
            reqUpper.classList.remove('valid');
            reqUpper.querySelector('i').className = 'fa-regular fa-circle-check';
        }

        if (/[0-9]/.test(val)) {
            reqNumber.classList.add('valid');
            reqNumber.querySelector('i').className = 'fa-solid fa-circle-check';
            score++;
        } else {
            reqNumber.classList.remove('valid');
            reqNumber.querySelector('i').className = 'fa-regular fa-circle-check';
        }

        if (/[\W]/.test(val)) {
            reqSpecial.classList.add('valid');
            reqSpecial.querySelector('i').className = 'fa-solid fa-circle-check';
            score++;
        } else {
            reqSpecial.classList.remove('valid');
            reqSpecial.querySelector('i').className = 'fa-regular fa-circle-check';
        }

        const percentage = (score / 4) * 100;
        strengthBar.style.width = percentage + '%';
        
        if (score <= 1) {
            strengthBar.style.backgroundColor = '#dc2626'; // Red
        } else if (score <= 3) {
            strengthBar.style.backgroundColor = '#f59e0b'; // Amber
        } else {
            strengthBar.style.backgroundColor = '#16a34a'; // Green
        }
    });

    // Profile Modal Handlers
    function openProfileModal() {
        const fileInput = document.getElementById('profile_pic_input');
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Please select an image file first.');
            return;
        }
        document.getElementById('profileModal').classList.add('active');
    }
    function closeProfileModal() {
        document.getElementById('profileModal').classList.remove('active');
    }
    function submitProfileForm() {
        document.getElementById('profilePicForm').submit();
    }

    // Password Modal Handlers
    function openPasswordModal() {
        const current = document.getElementById('current_password').value;
        const newPass = document.getElementById('new_password').value;
        const confirmPass = document.getElementById('confirm_password').value;

        if (!current || !newPass || !confirmPass) {
            alert('Please fill in all password fields.');
            return;
        }

        if (newPass !== confirmPass) {
            alert('New passwords do not match.');
            return;
        }

        document.getElementById('passwordModal').classList.add('active');
    }
    function closePasswordModal() {
        document.getElementById('passwordModal').classList.remove('active');
    }
    function submitPasswordForm() {
        document.getElementById('passwordForm').submit();
    }
</script>

</body>
</html>