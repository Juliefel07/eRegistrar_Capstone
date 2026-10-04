<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Secure Parameterized Query
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die(mysqli_error($conn));
}

$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

function displayValue($value)
{
    if (!empty($value)) {
        return htmlspecialchars($value);
    }
    return "<span class='empty-field'>Not yet added</span>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CCTC eRegistrar</title>
    <link rel="icon" type="image/png" href="/eRegistrar/assets/images/logooo.png">
    <!-- Base Stylesheets -->
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">

    <style>
        body {
            background: #f4f7fb;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        .student-main {
            padding: 20px 15px;
            max-width: 900px;
            margin: 0 auto;
            padding-bottom: 130px; /* Increased to completely clear the bottom navbar */
        }

        .profile-card {
            background: #ffffff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #2563eb;
            color: #fff;
            font-size: 36px;
            font-weight: bold;
            border: 3px solid #e0eeff;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
        }

        .profile-header h2 {
            font-size: 20px;
            color: #1e3a8a;
            margin: 0 0 4px 0;
        }

        .profile-header p {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 10px 0;
        }

        .profile-bio {
            font-size: 13px;
            color: #4b5563;
            max-width: 500px;
            margin: 0 auto;
        }

        .profile-section-title {
            font-size: 16px;
            color: #111827;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
            margin: 25px 0 15px 0;
        }

        /* Responsive Grid for Profile Items */
        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .profile-item {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 12px 15px;
            border-radius: 8px;
        }

        .profile-item label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .profile-item p {
            font-size: 13px;
            color: #1f2937;
            margin: 0;
            word-break: break-word;
        }

        .empty-field {
            color: #9ca3af;
            font-style: italic;
        }

        .profile-btn {
            display: block;
            width: 100%;
            margin-top: 30px;
            padding: 12px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: background 0.2s;
            box-shadow: 0 4px 10px rgba(37,99,235,0.2);
        }

        .profile-btn:hover {
            background: #1d4ed8;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            justify-content: center;
            align-items: center;
            z-index: 3000;
            padding: 15px;
        }

        .modal-box {
            width: 100%;
            max-width: 850px;
            height: 85vh;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 20px 50px rgba(0, 0, 0, .25);
        }

        .modal-frame {
            width: 100%;
            height: 100%;
            border: none;
        }

        .close-modal {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            cursor: pointer;
            z-index: 10000;
            color: #555;
            background: none;
            border: none;
        }

        .close-modal:hover {
            color: #000;
        }

        @media (max-width: 576px) {
            .student-main {
                padding: 10px 10px;
                padding-bottom: 130px;
            }
            .profile-card {
                padding: 15px;
            }
            .profile-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

   <?php require_once __DIR__ . "/navbar.php"; ?>

<div class="student-main">
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php if (!empty($user['profile_image']) && file_exists("uploads/" . $user['profile_image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($user['profile_image']); ?>" alt="Profile">
                <?php else: ?>
                    <?= strtoupper(substr($user['fullname'] ?? $user['first_name'] ?? 'U', 0, 1)); ?>
                <?php endif; ?>
            </div>

            <h2><?= htmlspecialchars($user['fullname'] ?? ''); ?></h2>
            <p>Student Profile</p>

            <div class="profile-bio">
                <?= displayValue($user['bio'] ?? ''); ?>
            </div>
        </div>

        <h3 class="profile-section-title">Personal Information</h3>
        <div class="profile-details">
            <div class="profile-item">
                <label>Full Name</label>
                <p><?= displayValue($user['fullname'] ?? ''); ?></p>
            </div>
            <div class="profile-item">
                <label>Birthdate</label>
                <p><?= displayValue($user['birthdate'] ?? ''); ?></p>
            </div>
            <div class="profile-item">
                <label>Gender</label>
                <p><?= displayValue($user['gender'] ?? ''); ?></p>
            </div>
            <div class="profile-item">
                <label>Address</label>
                <p><?= displayValue($user['address'] ?? ''); ?></p>
            </div>
        </div>

        <h3 class="profile-section-title">Academic Information</h3>
        <div class="profile-details">
            <div class="profile-item">
                <label>Account Type</label>
                <p><?= displayValue($user['account_type'] ?? ''); ?></p>
            </div>
            <div class="profile-item">
                <label>Student Level</label>
                <p><?= displayValue($user['student_level'] ?? ''); ?></p>
            </div>
            <div class="profile-item">
                <label>Student Number</label>
                <p><?= displayValue($user['student_no'] ?? ''); ?></p>
            </div>
            <div class="profile-item">
                <label>LRN</label>
                <p><?= displayValue($user['lrn'] ?? ''); ?></p>
            </div>
            <div class="profile-item">
                <label>Course</label>
                <p><?= displayValue($user['course'] ?? ''); ?></p>
            </div>
            <div class="profile-item">
                <label>Year Level</label>
                <p><?= displayValue($user['year_level'] ?? ''); ?></p>
            </div>
            <div class="profile-item">
                <label>Section</label>
                <p><?= displayValue($user['section'] ?? ''); ?></p>
            </div>
            <div class="profile-item">
                <label>Course Graduated</label>
                <p><?= displayValue($user['course_graduated'] ?? ''); ?></p>
            </div>
            <div class="profile-item">
                <label>Graduation Year</label>
                <p><?= displayValue($user['graduation_year'] ?? ''); ?></p>
            </div>
        </div>

        <h3 class="profile-section-title">Contact Information</h3>
        <div class="profile-details">
            <div class="profile-item">
                <label>Email Address</label>
                <p><?= displayValue($user['email'] ?? ''); ?></p>
            </div>
            <div class="profile-item">
                <label>Contact Number</label>
                <p><?= displayValue($user['contact_no'] ?? ''); ?></p>
            </div>
        </div>

        <h3 class="profile-section-title">Guardian Information</h3>
        <div class="profile-details">
            <div class="profile-item">
                <label>Guardian Name</label>
                <p><?= displayValue($user['guardian_name'] ?? ''); ?></p>
            </div>
            <div class="profile-item">
                <label>Emergency Contact</label>
                <p><?= displayValue($user['emergency_contact'] ?? ''); ?></p>
            </div>
        </div>

        <button type="button" class="profile-btn" id="openEditModal">
            <i class="fa-solid fa-user-pen" style="margin-right: 6px;"></i> Edit Profile
        </button>
    </div>
</div>

<!-- EDIT PROFILE MODAL -->
<div id="editModal" class="modal">
    <div class="modal-box">
        <button class="close-modal">&times;</button>
        <iframe src="edit_profile.php" frameborder="0" class="modal-frame"></iframe>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const openBtn = document.getElementById("openEditModal");
    const modal = document.getElementById("editModal");
    const closeBtn = document.querySelector(".close-modal");

    if (openBtn) {
        openBtn.addEventListener("click", function () {
            modal.style.display = "flex";
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener("click", function () {
            modal.style.display = "none";
        });
    }

    window.addEventListener("click", function (e) {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    });
});
</script>

</body>
</html>