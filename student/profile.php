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
<?php
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
    <title>My Profile</title>

    <link rel="stylesheet" href="../assets/css/student.css">

    <link rel="stylesheet" href="../assets/css/navbar.css">
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
        .modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    justify-content:center;
    align-items:center;
    z-index:9999;
}

.modal-box{
    width:900px;
    max-width:95%;
    height:90vh;
    background:#fff;
    border-radius:16px;
    overflow:hidden;
    position:relative;
    box-shadow:0 20px 50px rgba(0,0,0,.25);
}

.modal-frame{
    width:100%;
    height:100%;
    border:none;
}

.close-modal{
    position:absolute;
    top:12px;
    right:18px;
    font-size:32px;
    cursor:pointer;
    z-index:10000;
    color:#555;
}

.close-modal:hover{
    color:#000;
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

<?php include("navbar.php"); ?>
<link rel="stylesheet" href="../assets/css/navbar.css">

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
  <div class="profile-bio">
        <?= displayValue($user['bio']); ?>
    </div>
           

        </div>

<h3 class="profile-section-title">Personal Information</h3>

<div class="profile-details">

    <div class="profile-item">
        <label>Full Name</label>
        <p><?= displayValue($user['fullname']); ?></p>
    </div>

    <div class="profile-item">
        <label>Birthdate</label>
        <p><?= displayValue($user['birthdate']); ?></p>
    </div>

    <div class="profile-item">
        <label>Gender</label>
        <p><?= displayValue($user['gender']); ?></p>
    </div>

    <div class="profile-item">
        <label>Address</label>
        <p><?= displayValue($user['address']); ?></p>
    </div>

 

</div>
<h3 class="profile-section-title">Academic Information</h3>

<div class="profile-details">

    <div class="profile-item">
        <label>Account Type</label>
        <p><?= displayValue($user['account_type']); ?></p>
    </div>

    <div class="profile-item">
        <label>Student Level</label>
        <p><?= displayValue($user['student_level']); ?></p>
    </div>

    <div class="profile-item">
        <label>Student Number</label>
        <p><?= displayValue($user['student_no']); ?></p>
    </div>

    <div class="profile-item">
        <label>LRN</label>
        <p><?= displayValue($user['lrn']); ?></p>
    </div>

    <div class="profile-item">
        <label>Course</label>
        <p><?= displayValue($user['course']); ?></p>
    </div>

    <div class="profile-item">
        <label>Year Level</label>
        <p><?= displayValue($user['year_level']); ?></p>
    </div>

    <div class="profile-item">
        <label>Section</label>
        <p><?= displayValue($user['section']); ?></p>
    </div>

    <div class="profile-item">
        <label>Course Graduated</label>
        <p><?= displayValue($user['course_graduated']); ?></p>
    </div>

    <div class="profile-item">
        <label>Graduation Year</label>
        <p><?= displayValue($user['graduation_year']); ?></p>
    </div>

</div>
<h3 class="profile-section-title">Contact Information</h3>

<div class="profile-details">

    <div class="profile-item">
        <label>Email Address</label>
        <p><?= displayValue($user['email']); ?></p>
    </div>

    <div class="profile-item">
        <label>Contact Number</label>
        <p><?= displayValue($user['contact_no']); ?></p>
    </div>

</div>
<h3 class="profile-section-title">Guardian Information</h3>

<div class="profile-details">

    <div class="profile-item">
        <label>Guardian Name</label>
        <p><?= displayValue($user['guardian_name']); ?></p>
    </div>

    <div class="profile-item">
        <label>Emergency Contact</label>
        <p><?= displayValue($user['emergency_contact']); ?></p>
    </div>

</div>

<button type="button" class="profile-btn" id="openEditModal">
    Edit Profile
</button>

    </div>

</div>




<div id="editModal" class="modal">

    <div class="modal-box">

        <span class="close-modal">&times;</span>

        <iframe
            src="edit_profile.php"
            frameborder="0"
            class="modal-frame">
        </iframe>

    </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function () {

    const openBtn = document.getElementById("openEditModal");
    const modal = document.getElementById("editModal");
    const closeBtn = document.querySelector(".close-modal");

    console.log(openBtn);
    console.log(modal);
    console.log(closeBtn);

    openBtn.addEventListener("click", function () {
        modal.style.display = "flex";
    });

    closeBtn.addEventListener("click", function () {
        modal.style.display = "none";
    });

    window.addEventListener("click", function (e) {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    });

});
</script>

</body>
</html>