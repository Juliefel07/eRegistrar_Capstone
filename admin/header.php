<?php

$notifResult = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM notifications
WHERE user_id='1'
");

$notifCount = mysqli_fetch_assoc($notifResult)['total'];

?>


<div class="admin-header">

    <div class="brand">
        <img src="../assets/images/logosss.png" alt="eRegistrar Logo" class="portal-logo">

        <div class="brand-text">
            <h2>eRegistrar</h2>
            <span>Admin Panel</span>
        </div>
    </div>

  <div class="header-right">

    <a href="notifications.php" class="admin-bell">

        🔔

        <?php if($notifCount > 0){ ?>

        <span class="notif-count">

            <?= $notifCount; ?>

        </span>

        <?php } ?>

    </a>

    <div class="student-info">

        <div class="avatar">
            <?= strtoupper(substr($_SESSION['fullname'],0,1)); ?>
        </div>

        <div class="student-name">
            <strong><?= htmlspecialchars($_SESSION['fullname']); ?></strong>
            <small>Administrator</small>
        </div>

    </div>

</div>

</div>