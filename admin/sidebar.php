<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<?php
// Safely initiate session without throwing "Ignoring session_start()" notice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$admin_id = $_SESSION['user_id'] ?? 1;

// Fetch the admin's latest details directly from the database for instant syncing
$admin_query = mysqli_query($conn, "SELECT fullname, profile_pic FROM users WHERE user_id = '$admin_id' LIMIT 1");
$admin_data = mysqli_fetch_assoc($admin_query);
$sidebar_fullname = $admin_data['fullname'] ?? ($_SESSION['fullname'] ?? 'Administrator');
$sidebar_profile_pic = !empty($admin_data['profile_pic']) ? "../" . $admin_data['profile_pic'] : "../assets/images/default-avatar.png";

// Fetch notification count
$notifResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id='$admin_id' AND (is_read = 0 OR is_read IS NULL)");
$notifCount = ($notifResult) ? (int)mysqli_fetch_assoc($notifResult)['total'] : 0;

// Fetch TOTAL unread message count for badge
$msgResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM messages WHERE receiver_id='$admin_id' AND status='Unread'");
$unreadMsgCount = ($msgResult) ? (int)mysqli_fetch_assoc($msgResult)['total'] : 0;

$user_initial = !empty($sidebar_fullname) ? strtoupper(substr($sidebar_fullname, 0, 1)) : 'A';
?>

<style>
/* ==========================================
   SIDEBAR & LAYOUT STYLES (COMPACT FIT)
   ========================================== */
.sidebar {
    width: 260px;
    height: 100vh;
    background: #ffffff;
    border-right: 1px solid #e2e8f0;
    position: fixed;
    top: 0;
    left: 0;
    display: flex;
    flex-direction: column;
    z-index: 10001;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-sizing: border-box;
    overflow: hidden; /* Prevents outer scrollbar */
}

.sidebar-brand {
    padding: 12px 16px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
}

.sidebar-brand .brand-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.portal-logo {
    width: 32px;
    height: 32px;
    object-fit: contain;
}

.brand-text h2 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.1;
}

.brand-text span {
    font-size: 0.7rem;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Profile Dropdown Container Below Brand */
.sidebar-profile-dropdown {
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
    position: relative;
    background: #fafafa;
}

.user-profile-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 8px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
    box-sizing: border-box;
}

.user-profile-card:hover {
    background-color: #f8fafc;
    border-color: #cbd5e1;
}

.user-profile-card .avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 1px solid #cbd5e1;
}

.user-profile-card .user-details {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    flex-grow: 1;
}

.user-profile-card .user-details strong {
    font-size: 0.8rem;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-profile-card .user-details small {
    font-size: 0.68rem;
    color: #64748b;
}

.user-profile-card .dropdown-arrow {
    font-size: 0.65rem;
    color: #64748b;
    margin-left: auto;
    transition: transform 0.2s ease;
}

/* Downward Popup Dropdown Menu */
/* Downward Popup Dropdown Menu */
.sidebar-dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 8px;
    right: 8px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    
    
}

.sidebar-dropdown-menu.show {
    display: block;
}

.sidebar-dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 10px;
    color: #333333;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 400;
    border-radius: 6px;
    transition: background 0.2s ease;
}

.sidebar-dropdown-menu a:hover {
    background-color: #f8fafc;
    color: #0f172a;
}

.sidebar-dropdown-menu a.logout-link {
    color: #dc2626;
    font-weight: 600;
}

.sidebar-dropdown-menu a.logout-link:hover {
    background-color: #ffeeec;
    color: #dc2626;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    padding: 8px 10px;
    gap: 2px;
    flex-grow: 1;
    overflow: hidden; /* Removed scrolling */
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 12px;
    color: #64748b;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.86rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.sidebar-nav a .nav-link-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sidebar-nav a i {
    font-size: 1rem;
    width: 18px;
    text-align: center;
}

.sidebar-nav a:hover {
    background-color: #f8fafc;
    color: #0f172a;
}

.sidebar-nav a.active {
    background-color: #eff6ff;
    color: #2563eb;
    font-weight: 600;
}

.sidebar-nav a.active i {
    color: #2563eb;
}

.notif-badge {
    background-color: #ef4444;
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    line-height: 1.1;
}

.mobile-header-toggle { display: none; }
.mobile-actions { display: flex; align-items: center; gap: 12px; }
.toggle-btn { background: #ffffff; border: 1px solid #cbd5e1; font-size: 1.2rem; color: #0f172a; cursor: pointer; padding: 6px 12px; border-radius: 6px; outline: none; }
.sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(2px); z-index: 10000; }
.sidebar-overlay.show { display: block !important; }

@media (max-width: 768px) {
    .mobile-header-toggle {
        display: flex !important;
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 60px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        padding: 0 16px;
        align-items: center;
        justify-content: space-between;
        z-index: 9999;
    }
    .mobile-brand { display: flex; align-items: center; gap: 10px; font-weight: 700; color: #0f172a; font-size: 1.05rem; }
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0) !important; box-shadow: 10px 0 25px -5px rgba(0, 0, 0, 0.1); }
    body { padding-top: 60px !important; }
    .admin-content { margin-left: 0 !important; }
}

@media (min-width: 769px) {
    .admin-content { margin-left: 260px; }
}
</style>

<!-- Mobile Top Bar Header -->
<div class="mobile-header-toggle">
    <div class="mobile-brand">
        <img src="../assets/images/logosss.png" class="portal-logo" alt="Logo">
        <span>eRegistrar Admin</span>
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
            <img src="../assets/images/logosss.png" class="portal-logo" alt="eRegistrar Logo">
            <div class="brand-text">
                <h2>eRegistrar</h2>
                <span>Admin Panel</span>
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
            <a href="../logout.php">
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

    // Profile Dropdown Toggle
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

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!profileToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                dropdownArrow.style.transform = 'rotate(0deg)';
            }
        });
    }
});
</script>