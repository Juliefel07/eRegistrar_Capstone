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
        
        // Check if profile_image contains Base64 data or a physical file
        if (!empty($n_row['profile_image'])) {
            if (strpos($n_row['profile_image'], 'data:image') === 0) {
                // Base64 string stored directly from the database
                $nav_profile_img = $n_row['profile_image'];
            } else {
                // Fallback for physical files
                $local_path = "uploads/" . $n_row['profile_image'];
                $parent_path = "../student/uploads/" . $n_row['profile_image'];
                
                if (file_exists($local_path)) {
                    $nav_profile_img = $local_path;
                } elseif (file_exists($parent_path)) {
                    $nav_profile_img = $parent_path;
                }
            }
        }
        
        if (!empty($n_row['fullname'])) {
            $_SESSION['fullname'] = $n_row['fullname'];
        }
    }
}

// Fallback to UI-Avatars if no profile picture is found
if (empty($nav_profile_img)) {
    $nav_profile_img = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['fullname'] ?? 'User') . "&background=1F5CAB&color=fff";
}
?>

<!-- Google Fonts & FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* =========================================================
       DARK ACADEMIC BLUE NAVBAR (MATCHED TO INDEX.PHP)
       ========================================================= */
    :root {
        --deep-blue: #1F5CAB;
        --bright-blue: #3982DC;
        --sky-blue: #75AAE7;
        --light-sky: #B9D4F3;
        --pale-bg: #DBE9F9;
        --navbar-dark: #0f2d59;
        --heading-color: #0f172a;
        --text-muted: #475569;
        --border-color: #cbd5e1;
        --radius-md: 12px;
    }

    body {
        font-family: 'Inter', sans-serif;
    }

    .navbar {
        background-color: var(--navbar-dark) !important;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 32px;
        box-shadow: 0 4px 20px rgba(15, 45, 89, 0.35);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        position: sticky;
        top: 0;
        z-index: 1000;
        box-sizing: border-box;
    }

    .nav-brand {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        text-decoration: none !important;
    }

    .brand-logo-img {
        width: 42px !important;
        height: 42px !important;
        object-fit: contain !important;
        flex-shrink: 0 !important;
    }

    .brand-text h2 {
        font-size: 1.25rem !important;
        font-weight: 800 !important;
        color: #ffffff !important;
        line-height: 1.2 !important;
        margin: 0 !important;
        padding: 0 !important;
        letter-spacing: -0.02em;
    }

    .brand-text span {
        font-size: 0.78rem !important;
        font-weight: 600 !important;
        color: var(--light-sky) !important;
        display: block !important;
        line-height: 1 !important;
        margin-top: 2px !important;
        letter-spacing: 0.02em;
    }

    .nav-links {
        display: flex;
        list-style: none;
        gap: 6px;
        margin: 0;
        padding: 0;
        align-items: center;
    }

    .nav-links a {
        text-decoration: none;
        color: #e2e8f0;
        padding: 8px 14px;
        border-radius: var(--radius-md);
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }

    .nav-links a:hover {
        color: #ffffff;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .nav-links a.active {
        color: #ffffff !important;
        background-color: var(--deep-blue) !important;
        box-shadow: 0 4px 12px rgba(31, 92, 171, 0.35);
    }

    .nav-user {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .desktop-search-form {
        display: flex !important;
        align-items: center !important;
        flex-shrink: 0 !important;
    }

    .search-pill {
        width: 240px !important;
        display: flex !important;
        align-items: center !important;
        background: rgba(255, 255, 255, 0.08) !important;
        padding: 8px 14px !important;
        border-radius: 20px !important;
        gap: 8px !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        flex-shrink: 0 !important;
        transition: all 0.2s ease;
    }

    .search-pill:focus-within {
        background: rgba(255, 255, 255, 0.15) !important;
        border-color: var(--light-sky) !important;
    }

    .search-pill input {
        border: none;
        background: transparent;
        outline: none;
        font-size: 0.88rem;
        color: #ffffff;
        width: 100%;
    }

    .search-pill input::placeholder {
        color: #94a3b8;
    }

    .icon-badge {
        position: relative;
        cursor: pointer;
        color: #e2e8f0;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        padding: 8px 10px;
        border-radius: var(--radius-md);
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        transition: all 0.2s;
    }

    .icon-badge:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
    }

    .icon-badge .badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #ef4444;
        color: #fff;
        font-size: 9px;
        padding: 2px 6px;
        border-radius: 10px;
        font-weight: 800;
        border: 2px solid var(--navbar-dark);
    }

    .link-badge {
        background: #ef4444;
        color: #fff;
        font-size: 10px;
        font-weight: 800;
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
        gap: 10px;
        background: rgba(255, 255, 255, 0.08);
        padding: 6px 14px 6px 6px;
        border-radius: 30px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        transition: background 0.2s;
    }

    .user-profile:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .user-avatar {
    width: 38px !important;
    height: 38px !important;
    min-width: 38px !important;
    min-height: 38px !important;
    border-radius: 50% !important;
    object-fit: cover !important;
    border: 2px solid var(--light-sky) !important;
}
    .user-name-text {
        font-size: 0.9rem;
        font-weight: 600;
        color: #ffffff;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        margin-top: 10px;
        background: #ffffff;
        min-width: 200px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.25);
        border-radius: var(--radius-md);
        border: 1px solid var(--border-color);
        padding: 6px;
        z-index: 1001;
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
        gap: 10px;
        padding: 10px 14px;
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--heading-color);
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .dropdown-menu a:hover {
        background: var(--pale-bg);
        color: var(--deep-blue);
    }

    .dropdown-menu a.logout-link:hover {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    .dropdown-divider {
        height: 1px;
        background: var(--border-color);
        margin: 4px 0;
    }

    .user-dropdown:hover .dropdown-menu {
        display: block;
    }

    /* Mobile Drawer & Mobile Bottom Bar */
    .mobile-hamburger {
        display: none;
        background: none;
        border: none;
        font-size: 1.4rem;
        color: #ffffff;
        cursor: pointer;
    }

    .mobile-drawer {
        position: fixed;
        top: 0;
        left: -300px;
        width: 300px;
        height: 100%;
        background: var(--navbar-dark);
        z-index: 2000;
        transition: left 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 5px 0 25px rgba(0, 0, 0, 0.4);
        padding: 24px;
        box-sizing: border-box;
        overflow-y: auto;
    }

    .mobile-drawer.open { left: 0; }

    .drawer-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(2px);
        z-index: 1999;
    }

    .drawer-overlay.open { display: block; }

    .drawer-menu {
        list-style: none;
        margin-top: 15px;
        padding: 0;
    }

    .drawer-menu a {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 14px;
        color: #cbd5e1;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 600;
        border-radius: var(--radius-md);
        transition: all 0.2s;
    }

    .drawer-menu a:hover, .drawer-menu a.active {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }

    .mobile-bottom-nav {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: var(--navbar-dark);
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        padding: 8px 0;
        justify-content: space-around;
        z-index: 1000;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.25);
    }

    .mobile-bottom-nav a {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: #94a3b8;
        font-size: 0.72rem;
        font-weight: 600;
        gap: 4px;
        position: relative;
    }

    .mobile-bottom-nav a.active, .mobile-bottom-nav a:hover {
        color: var(--light-sky);
    }

    .mobile-bottom-nav i { font-size: 1.2rem; }

    .mobile-bottom-nav .badge {
        position: absolute;
        top: -2px;
        right: calc(50% - 14px);
        background: #ef4444;
        color: #fff;
        font-size: 8px;
        padding: 1px 4px;
        border-radius: 8px;
        font-weight: bold;
    }

    /* Dark Mode Overrides */
    body.dark-mode {
        --bg-color: #0f172a !important;
        --card-bg: #1e293b !important;
        --border: #334155 !important;
        --text: #f8fafc !important;
        --text-muted: #94a3b8 !important;
        background-color: var(--bg-color) !important;
        color: var(--text) !important;
    }

    body.dark-mode .dropdown-menu {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.4) !important;
    }
    .brand-e {
    color: #3982DC !important;
}

.brand-registrar {
    color: #ffffff !important;
}

    body.dark-mode .dropdown-menu a {
        color: #e2e8f0 !important;
    }

    body.dark-mode .dropdown-menu a:hover {
        background: rgba(57, 130, 220, 0.2) !important;
        color: var(--light-sky) !important;
    }

    @media (max-width: 1200px) {
        .user-name-text { display: none; }
    }

    @media (max-width: 992px) {
        .navbar {
            padding: 0 20px;
            height: 64px;
        }
        .nav-links, .desktop-search-form { display: none !important; }
        .mobile-hamburger { display: block; }
        .mobile-bottom-nav { display: flex; }
    }
</style>

<!-- TOP NAVIGATION BAR -->
<nav class="navbar">
    <div style="display: flex; align-items: center; gap: 14px;">
        <button class="mobile-hamburger" id="drawerOpenBtn">
            <i class="fa-solid fa-bars"></i>
        </button>

        <a href="dashboard.php" class="nav-brand">
    <img src="../assets/images/logo1.png" alt="eRegistrar Logo" class="brand-logo-img">
    <div class="brand-text" style="min-width: 130px !important;">
        <h2 style="margin: 0; font-size: 50px !important; font-weight: 800; line-height: 1.1; display: flex !important; align-items: center !important; white-space: nowrap !important;">
            <span style="color: #3982DC !important;">e</span><span style="color: #ffffff !important;">Registrar</span>
        </h2>
        <span style="font-size: 1rem; color: #B9D4F3; font-weight: 600; display: block; margin-top: 2px; white-space: nowrap;">Student Portal</span>
    </div>
</a>

        <!-- Desktop Navigation Links -->
        <ul class="nav-links">
            <li><a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="request.php" class="<?= ($current_page == 'request.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-signature"></i> Request</a></li>
            <li><a href="history.php" class="<?= ($current_page == 'history.php') ? 'active' : ''; ?>"><i class="fa-solid fa-clock-rotate-left"></i> History</a></li>
            <li><a href="track.php" class="<?= ($current_page == 'track.php') ? 'active' : ''; ?>"><i class="fa-solid fa-location-crosshairs"></i> Track</a></li>
            <li><a href="payments.php" class="<?= ($current_page == 'payments.php') ? 'active' : ''; ?>"><i class="fa-solid fa-wallet"></i> Payments</a></li>
            <li><a href="claim_stub.php" class="<?= ($current_page == 'claim_stub.php') ? 'active' : ''; ?>"><i class="fa-solid fa-receipt"></i> Receipts</a></li>
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
                <i class="fa-solid fa-magnifying-glass" style="color: #94a3b8; font-size: 12px;"></i>
                <input type="text" name="q" placeholder="Search request..." value="<?= htmlspecialchars($_GET['q'] ?? ''); ?>" required>
            </div>
        </form>

        <!-- Notification Bell -->
        <a href="notifications.php" class="icon-badge" style="text-decoration: none;" title="Notifications">
            <i class="fa-regular fa-bell"></i>
            <span class="badge" id="navNotificationBadge" style="<?= $notification_count > 0 ? '' : 'display: none;'; ?>">
                <?= $notification_count; ?>
            </span>
        </a>

        <!-- Desktop User Profile Dropdown -->
        <div class="user-dropdown">
            <div class="user-profile">
                <img src="<?= htmlspecialchars($nav_profile_img); ?>" class="user-avatar" alt="User">
                <span class="user-name-text"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Student'); ?> <i class="fa-solid fa-chevron-down" style="font-size:10px; color: var(--light-sky);"></i></span>
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
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <img src="../assets/images/logo1.png" alt="Logo" style="width: 40px; height: 40px; object-fit: contain;">
        <div style="display: flex; flex-direction: column;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #ffffff; line-height: 1.2;">
                <span style="color: #3982DC;">e</span>Registrar
            </h3>
            <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 600; margin-top: 2px; text-transform: uppercase;">Student Portal</span>
        </div>
    </div>
    <button id="drawerCloseBtn" style="border:none; background:rgba(255,255,255,0.1); color:#ffffff; width: 32px; height: 32px; border-radius: 50%; font-size:1rem; cursor:pointer; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-xmark"></i></button>
</div>

    <!-- Mobile Search Bar -->
    <form action="search.php" method="GET" style="margin-bottom: 16px;">
        <div style="display: flex; align-items: center; background: rgba(255,255,255,0.08); padding: 8px 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.15); gap: 8px;">
            <i class="fa-solid fa-magnifying-glass" style="color: #94a3b8; font-size: 12px;"></i>
            <input type="text" name="q" placeholder="Search requests..." value="<?= htmlspecialchars($_GET['q'] ?? ''); ?>" style="border:none; background:transparent; outline:none; color:#fff; font-size:0.88rem; width:100%;" required>
        </div>
    </form>

    <ul class="drawer-menu">
        <li><a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-house"></i> Dashboard</a></li>
        <li><a href="request.php" class="<?= ($current_page == 'request.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-signature"></i> Request Document</a></li>
        <li><a href="history.php" class="<?= ($current_page == 'history.php') ? 'active' : ''; ?>"><i class="fa-solid fa-clock-rotate-left"></i> Request History</a></li>
        <li><a href="track.php" class="<?= ($current_page == 'track.php') ? 'active' : ''; ?>"><i class="fa-solid fa-location-crosshairs"></i> Track Status</a></li>
        <li><a href="payments.php" class="<?= ($current_page == 'payments.php') ? 'active' : ''; ?>"><i class="fa-solid fa-wallet"></i> Payments</a></li>
        <li><a href="claim_stub.php" class="<?= ($current_page == 'claim_stub.php') ? 'active' : ''; ?>"><i class="fa-solid fa-receipt"></i> Receipts</a></li>
        <li>
            <a href="messages.php" class="<?= ($current_page == 'messages.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-envelope"></i> Messages
                <span class="link-badge" id="drawerMsgBadge" style="<?= $unread_msg_count > 0 ? '' : 'display: none;'; ?>">
                    <?= $unread_msg_count; ?>
                </span>
            </a>
        </li>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 12px 0;"></div>
        <li><a href="profile.php" class="<?= ($current_page == 'profile.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-pen"></i> Edit Profile</a></li>
        <li><a href="settings.php" class="<?= ($current_page == 'settings.php') ? 'active' : ''; ?>"><i class="fa-solid fa-gear"></i> Settings</a></li>
        <li><a href="../logout.php" style="color:#fca5a5;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
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