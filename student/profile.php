<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM users WHERE user_id='$user_id'";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die(mysqli_error($conn));
}

$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>

    <link rel="stylesheet" href="../assets/css/student.css">

    <style>
        .profile-avatar{
            width:120px;
            height:120px;
            border-radius:50%;
            overflow:hidden;
            margin:0 auto 20px;
            display:flex;
            justify-content:center;
            align-items:center;
            background:#2563eb;
            color:#fff;
            font-size:45px;
            font-weight:bold;
        }

        .profile-avatar img{
            width:100%;
            height:100%;
            object-fit:cover;
            border-radius:50%;
            display:block;
        }
    </style>

</head>

<body>

<?php include("sidebar.php"); ?>
<?php include("header.php"); ?>

<div class="student-main">

    <div class="profile-card">

        <div class="profile-header">

            <div class="profile-avatar">

                <?php if (!empty($user['profile_image']) && file_exists("uploads/" . $user['profile_image'])) { ?>

                    <img
                        src="uploads/<?php echo htmlspecialchars($user['profile_image']); ?>"
                        alt="Profile">

                <?php } else { ?>

                    <?php echo strtoupper(substr($user['fullname'],0,1)); ?>

                <?php } ?>

            </div>

            <h2><?php echo htmlspecialchars($user['fullname']); ?></h2>

            <p>Student Profile</p>

           

        </div>

        <div class="profile-details">

            <div class="profile-item">
                <label>Student Number</label>
                <p><?php echo htmlspecialchars($user['student_no']); ?></p>
            </div>

            <div class="profile-item">
                <label>Full Name</label>
                <p><?php echo htmlspecialchars($user['fullname']); ?></p>
            </div>

            <div class="profile-item">
                <label>Course</label>
                <p><?php echo htmlspecialchars($user['course']); ?></p>
            </div>

            <div class="profile-item">
                <label>Year Level</label>
                <p><?php echo htmlspecialchars($user['year_level']); ?></p>
            </div>

            <div class="profile-item">
                <label>Contact Number</label>
                <p><?php echo htmlspecialchars($user['contact_no']); ?></p>
            </div>

            <div class="profile-item">
                <label>Email Address</label>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>

        </div>

        <a href="edit_profile.php" class="profile-btn">
            Edit Profile
        </a>

    </div>

</div>

</body>
</html>