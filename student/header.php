<?php
require_once __DIR__ . "/../includes/db.php";

$notification_count = 0;
$user_id = $_SESSION['user_id'];

$countQuery = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id='$user_id'
    AND status='Unread'
");

if ($countQuery) {
    $countRow = mysqli_fetch_assoc($countQuery);
    $notification_count = $countRow['total'];
}
?>

<div class="student-header">
    <div class="brand">
        
        <div class="brand-text">
            
           
        </div>
    </div>


    <div class="header-right">
        
        <div class="student-info">
<div class="avatar">
    <?php if (!empty($_SESSION['profile_image'])) { ?>
        <img
            src="uploads/<?php echo htmlspecialchars($_SESSION['profile_image']); ?>"
            alt="Profile Picture"
            class="avatar-img">
    <?php } else { ?>
        <?php echo strtoupper(substr($_SESSION['fullname'], 0, 1)); ?>
    <?php } ?>
</div>
            <div class="student-name">
                
                <strong>
                <?php echo $_SESSION['fullname']; ?>
                </strong>
                <small>
                Student Account
                </small>
            </div>
        </div>
        <div class="notification-icon">
    <a href="notifications.php">
        <i class="fa-solid fa-bell"></i>

        <?php if ($notification_count > 0) { ?>
            <span class="notification-badge">
                <?php echo $notification_count; ?>
            </span>
        <?php } ?>
    </a>
</div>

        <a href="../logout.php" class="logout-btn">
            Logout
        </a>
    </div>
</div>

