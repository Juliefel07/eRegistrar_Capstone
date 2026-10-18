<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$admin_id = $_SESSION['user_id'] ?? 1;

$admin_query = mysqli_query($conn, "SELECT fullname, profile_pic FROM users WHERE user_id = '$admin_id' LIMIT 1");
$admin_data = mysqli_fetch_assoc($admin_query);
$sidebar_fullname = $admin_data['fullname'] ?? ($_SESSION['fullname'] ?? 'Administrator');
$sidebar_profile_pic = !empty($admin_data['profile_pic']) ? "../" . $admin_data['profile_pic'] : "../assets/images/default-avatar.png";

$notifResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id='$admin_id' AND (is_read = 0 OR is_read IS NULL)");
$notifCount = ($notifResult) ? (int)mysqli_fetch_assoc($notifResult)['total'] : 0;

$msgResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM messages WHERE receiver_id='$admin_id' AND status='Unread'");
$unreadMsgCount = ($msgResult) ? (int)mysqli_fetch_assoc($msgResult)['total'] : 0;
?>

<style>
:root {
    --deep-blue: #1F5CAB;
    --bright-blue: #3982DC;
    --sky-blue: #75AAE7;
    --light-sky: #B9D4F3;
    --navbar-dark: #0f2d59;
    --radius-md: 12px;
}

.sidebar {
    width: 260px;
    height: 100vh;
    background: var(--navbar-dark);
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    position: fixed;
    top: 0;
    left: 0;
    display: flex;
    flex-direction: column;
    z-index: 10001;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-sizing: border-box;
    overflow: hidden;
    font-family: 'Inter', sans-serif;
    box-shadow: 4px 0 20px rgba(15, 45, 89, 0.2);
}
.sidebar-brand {
    padding: 16px 12px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sidebar-brand .brand-content {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
}

.portal-logo {
    width: 40px !important;
    height: 40px !important;
    object-fit: contain;
    flex-shrink: 0;
}

.brand-text {
    display: flex;
    flex-direction: column;
    min-width: 0; /* Prevents text overflow */
}

.brand-text h2 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.02em;
    display: inline-flex !important; /* Forces 'e' and 'Registrar' to stay locked on one line */
    align-items: center;
    white-space: nowrap;
}

.brand-e {
    color: #3982DC !important;
}

.brand-registrar {
    color: #ffffff !important;
    margin-left: -8px;
}

.brand-text span.sub-title {
    font-size: 0.68rem;
    color: var(--light-sky);
    font-weight: 600;
    text-transform: none;
    letter-spacing: 0.2px;
    display: block;
    margin-top: 2px;
    
}
.sidebar-profile-dropdown {
    padding: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    position: relative;
    background: rgba(0, 0, 0, 0.15);
}

.user-profile-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: var(--radius-md);
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
    box-sizing: border-box;
}

.user-profile-card:hover {
    background-color: rgba(255, 255, 255, 0.15);
    border-color: var(--light-sky);
}

.user-profile-card .avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid var(--light-sky);
}

.user-profile-card .user-details {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    flex-grow: 1;
}

.user-profile-card .user-details strong {
    font-size: 0.85rem;
    color: #ffffff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 700;
}

.user-profile-card .user-details small {
    font-size: 0.7rem;
    color: var(--light-sky);
}

.user-profile-card .dropdown-arrow {
    font-size: 0.7rem;
    color: var(--light-sky);
    margin-left: auto;
    transition: transform 0.2s ease;
}

.sidebar-dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 6px);
    left: 12px;
    right: 12px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: var(--radius-md);
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2);
    padding: 6px;
    z-index: 100;
}

.sidebar-dropdown-menu.show {
    display: block;
}

.sidebar-dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    color: #0f172a;
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 600;
    border-radius: 8px;
    transition: background 0.2s ease;
}

.sidebar-dropdown-menu a:hover {
    background-color: #DBE9F9;
    color: #1F5CAB;
}

.sidebar-dropdown-menu a.logout-link {
    color: #ef4444;
}

.sidebar-dropdown-menu a.logout-link:hover {
    background-color: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    padding: 12px 10px;
    gap: 4px;
    flex-grow: 1;
    overflow: hidden;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    color: #cbd5e1;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    border-radius: var(--radius-md);
    transition: all 0.2s ease;
}

.sidebar-nav a .nav-link-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.sidebar-nav a i {
    font-size: 1rem;
    width: 20px;
    text-align: center;
    color: var(--sky-blue);
}

.sidebar-nav a:hover {
    background-color: rgba(255, 255, 255, 0.08);
    color: #ffffff;
}

.sidebar-nav a:hover i {
    color: #ffffff;
}

.sidebar-nav a.active {
    background-color: var(--deep-blue);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(31, 92, 171, 0.35);
    font-weight: 700;
}

.sidebar-nav a.active i {
    color: #ffffff;
}

.notif-badge {
    background-color: #ef4444;
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 10px;
    line-height: 1.1;
}

.mobile-header-toggle { display: none; }
.mobile-actions { display: flex; align-items: center; gap: 12px; }
.toggle-btn { background: var(--navbar-dark); border: 1px solid rgba(255, 255, 255, 0.2); font-size: 1.2rem; color: #ffffff; cursor: pointer; padding: 6px 12px; border-radius: 8px; outline: none; }
.sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px); z-index: 10000; }
.sidebar-overlay.show { display: block !important; }

@media (max-width: 768px) {
    .mobile-header-toggle {
        display: flex !important;
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 64px;
        background: var(--navbar-dark);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 0 20px;
        align-items: center;
        justify-content: space-between;
        z-index: 9999;
    }
    .mobile-brand { display: flex; align-items: center; gap: 10px; font-weight: 800; color: #ffffff; font-size: 1.1rem; }
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0) !important; box-shadow: 10px 0 25px -5px rgba(0, 0, 0, 0.3); }
    body { padding-top: 64px !important; }
    .admin-content { margin-left: 0 !important; }
}

@media (min-width: 769px) {
    .admin-content { margin-left: 260px; }
}
</style>

<!-- Mobile Top Bar Header -->
<div class="mobile-header-toggle">
    <div class="mobile-brand">
        <img src="../assets/images/logo1.png" class="portal-logo" alt="Logo">
        <span><span class="brand-e">e</span><span class="brand-registrar">Registrar</span> Admin</span>
    </div>
    <div class="mobile-actions">
        <button type="button" class="toggle-btn" id="sidebarToggle" aria-label="Toggle Navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Component -->
<aside class="sidebar" id="adminSidebar">
    <div class="sidebar-brand">
    <div class="brand-content">
        <img src="../assets/images/logo1.png" class="portal-logo" alt="eRegistrar Logo">
        <div class="brand-text">
            <h2><span class="brand-e">e</span><span class="brand-registrar">Registrar</span></h2>
            <span class="sub-title">Admin Panel</span>
        </div>
    </div>
</div>

    <!-- Admin Profile Section Directly Below Brand Header -->
    <div class="sidebar-profile-dropdown" id="profileDropdownContainer">
        <div class="user-profile-card" id="profileCardToggle">
            <img src="<?= htmlspecialchars($sidebar_profile_pic); ?>" alt="Admin Avatar" class="avatar" onerror="this.src='../assets/images/default-avatar.png';">
            <div class="user-details">
                <strong><?= htmlspecialchars($sidebar_fullname); ?></strong>
                <small>Administrator</small>
            </div>
            <i class="fa-solid fa-chevron-down dropdown-arrow" id="dropdownArrow"></i>
        </div>

        <div class="sidebar-dropdown-menu" id="sidebarDropdownMenu">
            <a href="../logout.php" class="logout-link">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <div class="nav-link-content">
                <i class="fa-solid fa-gauge"></i>
                <span>Dashboard</span>
            </div>
        </a>

        <a href="requests.php" class="<?= ($current_page == 'requests.php') ? 'active' : ''; ?>">
            <div class="nav-link-content">
                <i class="fa-solid fa-file-circle-check"></i>
                <span>Requests</span>
            </div>
        </a>

        <a href="documents.php" class="<?= ($current_page == 'documents.php') ? 'active' : ''; ?>">
            <div class="nav-link-content">
                <i class="fa-solid fa-folder-open"></i>
                <span>Documents</span>
            </div>
        </a>

        <a href="messages.php" class="<?= ($current_page == 'messages.php') ? 'active' : ''; ?>">
            <div class="nav-link-content">
                <i class="fa-solid fa-comments"></i>
                <span>Messages</span>
            </div>
            <span class="notif-badge" id="sidebar-msg-badge" style="<?= ($unreadMsgCount > 0) ? '' : 'display: none;'; ?>">
                <?= $unreadMsgCount; ?>
            </span>
        </a>

        <a href="notifications.php" class="<?= ($current_page == 'notifications.php') ? 'active' : ''; ?>">
            <div class="nav-link-content">
                <i class="fa-solid fa-bell"></i>
                <span>Notifications</span>
            </div>
            <span class="notif-badge" id="sidebarNotifBadge" style="<?= ($notifCount > 0) ? '' : 'display: none;'; ?>">
                <?= $notifCount; ?>
            </span>
        </a>

        <a href="transaction_history.php" class="<?= ($current_page == 'transaction_history.php') ? 'active' : ''; ?>">
            <div class="nav-link-content">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Transaction History</span>
            </div>
        </a>

        <a href="announcements.php" class="<?= ($current_page == 'announcements.php') ? 'active' : ''; ?>">
            <div class="nav-link-content">
                <i class="fa-solid fa-bullhorn"></i>
                <span>Announcements</span>
            </div>
        </a>

        <a href="students.php" class="<?= ($current_page == 'students.php') ? 'active' : ''; ?>">
            <div class="nav-link-content">
                <i class="fa-solid fa-user-graduate"></i>
                <span>Students</span>
            </div>
        </a>

        <a href="reports.php" class="<?= ($current_page == 'reports.php') ? 'active' : ''; ?>">
            <div class="nav-link-content">
                <i class="fa-solid fa-chart-line"></i>
                <span>Reports</span>
            </div>
        </a>

        <a href="admin_settings.php" class="<?= ($current_page == 'admin_settings.php') ? 'active' : ''; ?>">
            <div class="nav-link-content">
                <i class="fa-solid fa-gear"></i>
                <span>Settings</span>
            </div>
        </a>
    </nav>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');

    function toggleMenu() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    }

    if (toggleBtn) toggleBtn.addEventListener('click', toggleMenu);
    if (overlay) overlay.addEventListener('click', toggleMenu);

    const profileToggle = document.getElementById('profileCardToggle');
    const dropdownMenu = document.getElementById('sidebarDropdownMenu');
    const dropdownArrow = document.getElementById('dropdownArrow');

    if (profileToggle && dropdownMenu) {
        profileToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
            if (dropdownMenu.classList.contains('show')) {
                dropdownArrow.style.transform = 'rotate(180deg)';
            } else {
                dropdownArrow.style.transform = 'rotate(0deg)';
            }
        });

        document.addEventListener('click', function (e) {
            if (!profileToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                dropdownArrow.style.transform = 'rotate(0deg)';
            }
        });
    }
});
</script>