<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../includes/db.php";

$current_page = basename($_SERVER['PHP_SELF']);

$notification_count = 0;
$unread_msg_count = 0;
$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id) {
    // 1. Fetch Notification Count
    $countQuery = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM notifications
        WHERE user_id='$user_id'
        AND (is_read = 0 OR is_read IS NULL)
    ");

    if ($countQuery) {
        $countRow = mysqli_fetch_assoc($countQuery);
        $notification_count = (int)$countRow['total'];
    }

    // 2. Fetch Unread Message Count
    $msgCountQuery = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM messages
        WHERE receiver_id = '$user_id'
        AND status = 'Unread'
    ");

    if ($msgCountQuery) {
        $msgCountRow = mysqli_fetch_assoc($msgCountQuery);
        $unread_msg_count = (int)$msgCountRow['total'];
    }
}

// Fetch the latest profile image directly from the database
$nav_profile_img = "";
if ($user_id && isset($conn)) {
    $n_res = mysqli_query($conn, "SELECT profile_image, fullname FROM users WHERE user_id = $user_id");
    if ($n_res && mysqli_num_rows($n_res) > 0) {
        $n_row = mysqli_fetch_assoc($n_res);
        if (!empty($n_row['profile_image']) && file_exists("uploads/" . $n_row['profile_image'])) {
            $nav_profile_img = "uploads/" . $n_row['profile_image']; 
        }
        if (!empty($n_row['fullname'])) {
            $_SESSION['fullname'] = $n_row['fullname'];
        }
    }
}

// Fallback to UI-Avatars if no profile picture is found or uploaded
if (empty($nav_profile_img)) {
    $nav_profile_img = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['fullname'] ?? 'User') . "&background=0056b3&color=fff";
}
?>

<!-- FontAwesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<!-- Navbar Styles -->
<style>
    .navbar {
        background: #ffffff;
        height: 65px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .nav-brand {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        text-decoration: none !important;
    }

    .brand-logo-img {
        width: 38px !important;
        height: 38px !important;
        object-fit: contain !important;
        border-radius: 50% !important;
        flex-shrink: 0 !important;
    }

    .brand-text h2 {
        font-size: 16px !important;
        font-weight: 700 !important;
        color: var(--primary, #0056b3) !important;
        line-height: 1.1 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .brand-text span {
        font-size: 11px !important;
        font-weight: 400 !important;
        color: var(--text-muted, #6c757d) !important;
        display: block !important;
        line-height: 1 !important;
        margin-top: 2px !important;
    }

    .nav-links {
        display: flex;
        list-style: none;
        gap: 8px;
        margin: 0;
        padding: 0;
    }

    .nav-links a {
        text-decoration: none;
        color: #555;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }

    .nav-links a:hover, .nav-links a.active {
        background: #f0f6ff;
        color: var(--primary, #0056b3);
    }

    .nav-user {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .desktop-search-form {
        display: flex;
        align-items: center;
    }

    .search-pill {
        width: 280px;
        display: flex;
        align-items: center;
        background: #f1f3f5;
        padding: 6px 12px;
        border-radius: 20px;
        gap: 8px;
        border: 1px solid #e9ecef;
    }

    .search-pill input {
        border: none;
        background: transparent;
        outline: none;
        font-size: 13px;
        width: 100%;
    }

    .mobile-search-form {
        margin-bottom: 15px;
    }

    .mobile-search-pill {
        display: flex;
        align-items: center;
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 20px;
        gap: 8px;
        border: 1px solid #e0e0e0;
    }

    .mobile-search-pill input {
        border: none;
        background: transparent;
        outline: none;
        font-size: 13px;
        width: 100%;
    }

    .icon-badge {
        position: relative;
        cursor: pointer;
        color: #555;
        font-size: 16px;
        display: flex;
        align-items: center;
    }

    .icon-badge .badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #dc3545;
        color: #fff;
        font-size: 9px;
        padding: 2px 5px;
        border-radius: 10px;
        font-weight: bold;
    }

    /* Inline badge for links */
    .link-badge {
        background: #dc3545;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: auto;
        line-height: 1;
    }

    .user-dropdown {
        position: relative;
        cursor: pointer;
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e0eeff;
    }

    .user-name-text {
        font-size: 13px;
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        margin-top: 0;
        padding-top: 8px;
        background: #ffffff;
        min-width: 180px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-radius: 8px;
        border: 1px solid var(--border, #e9ecef);
        padding-bottom: 6px;
        z-index: 1001;
    }

    /* GLOBAL DARK MODE OVERRIDES FOR NAVBAR & HEADER */
    body.dark-mode {
        --bg-color: #0f172a !important;
        --card-bg: #1e293b !important;
        --border: #334155 !important;
        --text: #f8fafc !important;
        --text-muted: #94a3b8 !important;
        background-color: var(--bg-color) !important;
        color: var(--text) !important;
    }

    body.dark-mode .navbar {
        background: #1e293b !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3) !important;
        border-bottom: 1px solid #334155 !important;
    }

    body.dark-mode .nav-links a {
        color: #cbd5e1 !important;
    }

    body.dark-mode .nav-links a:hover, 
    body.dark-mode .nav-links a.active {
        background: #334155 !important;
        color: #38bdf8 !important;
    }

    body.dark-mode .user-name-text {
        color: #f8fafc !important;
    }

    body.dark-mode .search-pill,
    body.dark-mode .mobile-search-pill {
        background: #0f172a !important;
        border-color: #334155 !important;
    }

    body.dark-mode .search-pill input,
    body.dark-mode .mobile-search-pill input {
        color: #f8fafc !important;
    }

    body.dark-mode .dropdown-menu {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.4) !important;
    }

    body.dark-mode .dropdown-menu a {
        color: #e2e8f0 !important;
    }

    body.dark-mode .dropdown-menu a:hover {
        background: #334155 !important;
        color: #38bdf8 !important;
    }

    body.dark-mode .mobile-drawer {
        background: #1e293b !important;
        border-right: 1px solid #334155 !important;
    }

    body.dark-mode .drawer-menu a {
        color: #e2e8f0 !important;
    }

    body.dark-mode .drawer-menu a:hover {
        background: #334155 !important;
        color: #38bdf8 !important;
    }

    body.dark-mode .mobile-bottom-nav {
        background: #1e293b !important;
        border-top: 1px solid #334155 !important;
    }

    body.dark-mode .mobile-bottom-nav a {
        color: #94a3b8 !important;
    }

    body.dark-mode .mobile-bottom-nav a.active,
    body.dark-mode .mobile-bottom-nav a:hover {
        color: #38bdf8 !important;
    }

    body.dark-mode .mobile-hamburger {
        color: #f8fafc !important;
    }

    .user-dropdown::before {
        content: '';
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        height: 12px;
    }

    .dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        font-size: 13px;
        color: #333;
        text-decoration: none;
        transition: background 0.2s;
    }

    .dropdown-menu a:hover {
        background: #f0f6ff;
        color: var(--primary, #0056b3);
    }

    .dropdown-menu a.logout-link:hover {
        background: #fff5f5;
        color: #dc3545;
    }

    .dropdown-divider {
        height: 1px;
        background: var(--border, #e9ecef);
        margin: 4px 0;
    }

    .user-dropdown:hover .dropdown-menu {
        display: block;
    }

    .mobile-hamburger {
        display: none;
        background: none;
        border: none;
        font-size: 20px;
        color: #333;
        cursor: pointer;
    }

    .mobile-drawer {
        position: fixed;
        top: 0;
        left: -280px;
        width: 280px;
        height: 100%;
        background: #ffffff;
        z-index: 2000;
        transition: left 0.3s ease;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        padding: 20px;
        overflow-y: auto;
    }

    .mobile-drawer.open { left: 0; }

    .drawer-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.4);
        z-index: 1999;
    }

    .drawer-overlay.open { display: block; }

    .drawer-menu {
        list-style: none;
        margin-top: 10px;
        padding: 0;
    }

    .drawer-menu a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 10px;
        color: #333;
        text-decoration: none;
        font-size: 14px;
        border-radius: 8px;
    }

    .drawer-menu a:hover {
        background: #f0f6ff;
        color: var(--primary, #0056b3);
    }

    .mobile-bottom-nav {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #ffffff;
        border-top: 1px solid var(--border, #e9ecef);
        padding: 8px 0;
        justify-content: space-around;
        z-index: 1000;
    }

    .mobile-bottom-nav a {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: #6c757d;
        font-size: 10px;
        gap: 3px;
        position: relative;
    }

    .mobile-bottom-nav a.active, .mobile-bottom-nav a:hover {
        color: var(--primary, #0056b3);
    }

    .mobile-bottom-nav i { font-size: 18px; }

    .mobile-bottom-nav .badge {
        position: absolute;
        top: 0;
        right: calc(50% - 14px);
        background: #dc3545;
        color: #fff;
        font-size: 8px;
        padding: 1px 4px;
        border-radius: 8px;
        font-weight: bold;
    }

    @media (max-width: 1100px) {
        .user-name-text { display: none; }
    }

    @media (max-width: 992px) {
        .nav-links, .desktop-search-form { display: none !important; }
        .mobile-hamburger { display: block; }
        .mobile-bottom-nav { display: flex; }
    }
</style>

<!-- TOP NAVIGATION BAR -->
<nav class="navbar">
    <div style="display: flex; align-items: center; gap: 12px;">
        <button class="mobile-hamburger" id="drawerOpenBtn">
            <i class="fa-solid fa-bars"></i>
        </button>

        <a href="dashboard.php" class="nav-brand">
            <img src="../assets/images/logosss.png" alt="eRegistrar Logo" class="brand-logo-img">
            <div class="brand-text">
                <h2>eRegistrar</h2>
                <span>Student Portal</span>
            </div>
        </a>

        <!-- Desktop Navigation Links -->
        <ul class="nav-links">
            <li><a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="request.php" class="<?= ($current_page == 'request.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-signature"></i> Request</a></li>
            <li><a href="history.php" class="<?= ($current_page == 'history.php') ? 'active' : ''; ?>"><i class="fa-solid fa-clock-rotate-left"></i> History</a></li>
            <li><a href="track.php" class="<?= ($current_page == 'track.php') ? 'active' : ''; ?>"><i class="fa-solid fa-location-crosshairs"></i> Track</a></li>
            <li><a href="payments.php" class="<?= ($current_page == 'payments.php') ? 'active' : ''; ?>"><i class="fa-solid fa-wallet"></i> Payments</a></li>
            <li><a href="claim_stub.php" class="<?= ($current_page == 'claim_stub.php') ? 'active' : ''; ?>"><i class="fa-solid fa-receipt"></i> Claim Stub</a></li>
            <li>
                <a href="messages.php" class="<?= ($current_page == 'messages.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-envelope"></i> Messages
                    <span class="link-badge" id="navMsgBadge" style="<?= $unread_msg_count > 0 ? '' : 'display: none;'; ?>">
                        <?= $unread_msg_count; ?>
                    </span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Right Side Actions & Profile -->
    <div class="nav-user">
        <!-- Desktop Search Form -->
        <form action="search.php" method="GET" class="desktop-search-form">
            <div class="search-pill">
                <i class="fa-solid fa-magnifying-glass" style="color: #aaa; font-size: 12px;"></i>
                <input type="text" name="q" placeholder="Search request..." value="<?= htmlspecialchars($_GET['q'] ?? ''); ?>" required>
            </div>
        </form>

        <!-- Notification Bell with explicit ID for live JS updates -->
        <a href="notifications.php" class="icon-badge" style="text-decoration: none;">
            <i class="fa-regular fa-bell"></i>
            <span class="badge" id="navNotificationBadge" style="<?= $notification_count > 0 ? '' : 'display: none;'; ?>">
                <?= $notification_count; ?>
            </span>
        </a>

        <!-- Desktop User Profile Dropdown -->
        <div class="user-dropdown">
            <div class="user-profile">
                <img src="<?= htmlspecialchars($nav_profile_img); ?>" class="user-avatar" alt="User">
                <span class="user-name-text"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Student'); ?> <i class="fa-solid fa-chevron-down" style="font-size:10px;"></i></span>
            </div>

            <!-- Dropdown Menu -->
            <div class="dropdown-menu">
                <a href="profile.php"><i class="fa-solid fa-user-pen"></i> Edit Profile</a>
                <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
                <div class="dropdown-divider"></div>
                <a href="../logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>

<!-- MOBILE NAVIGATION SIDE DRAWER -->
<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="mobile-drawer" id="mobileDrawer">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
        <h3 style="margin: 0; font-size: 18px; color: var(--primary, #0056b3);">eRegistrar</h3>
        <button id="drawerCloseBtn" style="border:none; background:none; font-size:18px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <!-- Mobile Search Bar (Inside Hamburger Drawer) -->
    <form action="search.php" method="GET" class="mobile-search-form">
        <div class="mobile-search-pill">
            <i class="fa-solid fa-magnifying-glass" style="color: #aaa; font-size: 12px;"></i>
            <input type="text" name="q" placeholder="Search requests..." value="<?= htmlspecialchars($_GET['q'] ?? ''); ?>" required>
        </div>
    </form>

    <ul class="drawer-menu">
        <li><a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
        <li><a href="request.php"><i class="fa-solid fa-file-signature"></i> Request Document</a></li>
        <li><a href="history.php"><i class="fa-solid fa-clock-rotate-left"></i> Request History</a></li>
        <li><a href="track.php"><i class="fa-solid fa-location-crosshairs"></i> Track Status</a></li>
        <li><a href="payments.php"><i class="fa-solid fa-wallet"></i> Payments</a></li>
        <li><a href="claim_stub.php"><i class="fa-solid fa-receipt"></i> Claim Stub</a></li>
        <li>
            <a href="messages.php">
                <i class="fa-solid fa-envelope"></i> Messages
                <span class="link-badge" id="drawerMsgBadge" style="<?= $unread_msg_count > 0 ? '' : 'display: none;'; ?>">
                    <?= $unread_msg_count; ?>
                </span>
            </a>
        </li>
        <li><a href="profile.php"><i class="fa-solid fa-user-pen"></i> Edit Profile</a></li>
        <li><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        <li><a href="../logout.php" style="color:#dc3545;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>

<!-- MOBILE APP BOTTOM NAVIGATION BAR -->
<div class="mobile-bottom-nav">
    <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-house"></i>
        <span>Home</span>
    </a>
    <a href="request.php" class="<?= ($current_page == 'request.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-file-circle-plus"></i>
        <span>Request</span>
    </a>
    <a href="track.php" class="<?= ($current_page == 'track.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-location-crosshairs"></i>
        <span>Track</span>
    </a>
    <a href="messages.php" class="<?= ($current_page == 'messages.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-envelope"></i>
        <span>Messages</span>
        <span class="badge" id="bottomMsgBadge" style="<?= $unread_msg_count > 0 ? '' : 'display: none;'; ?>">
            <?= $unread_msg_count; ?>
        </span>
    </a>
    <a href="profile.php" class="<?= ($current_page == 'profile.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-user"></i>
        <span>Profile</span>
    </a>
</div>

<!-- JAVASCRIPT FOR MOBILE DRAWER -->
<script>
    const openBtn = document.getElementById('drawerOpenBtn');
    const closeBtn = document.getElementById('drawerCloseBtn');
    const drawer = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('drawerOverlay');

    if (openBtn && closeBtn && drawer && overlay) {
        openBtn.addEventListener('click', () => {
            drawer.classList.add('open');
            overlay.classList.add('open');
        });

        const closeDrawer = () => {
            drawer.classList.remove('open');
            overlay.classList.remove('open');
        };

        closeBtn.addEventListener('click', closeDrawer);
        overlay.addEventListener('click', closeDrawer);
    }
</script>