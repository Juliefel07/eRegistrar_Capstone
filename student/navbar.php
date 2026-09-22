<?php
require_once __DIR__ . "/../includes/db.php";

$current = basename($_SERVER['PHP_SELF']);

$notification_count = 0;
$user_id = $_SESSION['user_id'];

$countQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM notifications
WHERE user_id='$user_id'
AND status='Unread'
");

if($countQuery){
    $countRow = mysqli_fetch_assoc($countQuery);
    $notification_count = $countRow['total'];
}
?>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<nav class="top-navbar">

    <div class="nav-left">

        <a href="dashboard.php" class="logo">

            <img src="../assets/images/logosss.png">

            <div>
                <h2>eRegistrar</h2>
                <span>Student Portal</span>
            </div>

        </a>

        <ul class="nav-menu">

            <li>
                <a href="dashboard.php"
                class="<?= $current=="dashboard.php" ? "active" : "" ?>">
                    <i class="fa-solid fa-house"></i>
                    Dashboard
                </a>
            </li>

            <li>
                <a href="request.php"
                class="<?= $current=="request.php" ? "active" : "" ?>">
                    <i class="fa-solid fa-file-circle-plus"></i>
                    Request
                </a>
            </li>

            <li>
                <a href="history.php"
                class="<?= $current=="history.php" ? "active" : "" ?>">
                    <i class="fa-solid fa-folder-open"></i>
                    History
                </a>
            </li>

            <li>
                <a href="track.php"
                class="<?= $current=="track.php" ? "active" : "" ?>">
                    <i class="fa-solid fa-location-dot"></i>
                    Track
                </a>
            </li>

            <li>
                <a href="payments.php"
                class="<?= $current=="payments.php" ? "active" : "" ?>">
                    <i class="fa-solid fa-credit-card"></i>
                    Payments
                </a>
            </li>

            <li>
                <a href="messages.php"
                class="<?= $current=="messages.php" ? "active" : "" ?>">
                    <i class="fa-solid fa-envelope"></i>
                    Messages
                </a>
            </li>

        </ul>

    </div>

    <div class="nav-right">

        <div class="search-box">

            <i class="fa-solid fa-search"></i>

            <input
            type="text"
            placeholder="Search request...">

        </div>

        <a href="notifications.php" class="notification">

            <i class="fa-solid fa-bell"></i>

            <?php if($notification_count>0){ ?>

                <span><?= $notification_count ?></span>

            <?php } ?>

        </a>

        <div class="profile-menu">

            <div class="profile-trigger">

                <div class="avatar">

                    <?php if(!empty($_SESSION['profile_image'])){ ?>

                        <img src="uploads/<?=
                        htmlspecialchars($_SESSION['profile_image']); ?>">

                    <?php } else { ?>

                        <?= strtoupper(substr($_SESSION['fullname'],0,1)); ?>

                    <?php } ?>

                </div>

                <div class="profile-name">

                    <strong>
                        <?= $_SESSION['fullname']; ?>
                    </strong>

                    <small>
                        Student
                    </small>

                </div>

                <i class="fa-solid fa-chevron-down"></i>

            </div>

            <div class="profile-dropdown">

                <a href="profile.php">

                    <i class="fa-solid fa-user"></i>

                    My Profile

                </a>

                <a href="settings.php">

                    <i class="fa-solid fa-gear"></i>

                    Settings

                </a>

                <hr>

                <a href="../logout.php" class="logout">

                    <i class="fa-solid fa-right-from-bracket"></i>

                    Logout

                </a>

            </div>

        </div>

    </div>

</nav>