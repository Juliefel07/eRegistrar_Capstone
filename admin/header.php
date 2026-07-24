<div class="admin-header">

    <div class="brand">
        <img src="../assets/images/logosss.png" alt="eRegistrar Logo" class="portal-logo">

        <div class="brand-text">
            <h2>eRegistrar</h2>
            <span>Admin Panel</span>
        </div>
    </div>

    <div class="header-right">

        <div class="student-info">

            <div class="avatar">
                <?= strtoupper(substr($_SESSION['fullname'], 0, 1)); ?>
            </div>

            <div class="student-name">
                <strong><?= htmlspecialchars($_SESSION['fullname']); ?></strong>
                <small>Administrator</small>
            </div>

        </div>

    </div>

</div>