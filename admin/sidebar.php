<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<?php
// Safely initiate session without throwing "Ignoring session_start()" notice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$admin_id = $_SESSION['user_id'] ?? 1;

// Fetch notification count
$notifResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id='$admin_id' AND (is_read = 0 OR is_read IS NULL)");
$notifCount = ($notifResult) ? (int)mysqli_fetch_assoc($notifResult)['total'] : 0;

// Fetch TOTAL unread message count for badge
$msgResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM messages WHERE receiver_id='$admin_id' AND status='Unread'");
$unreadMsgCount = ($msgResult) ? (int)mysqli_fetch_assoc($msgResult)['total'] : 0;

$user_initial = !empty($_SESSION['fullname']) ? strtoupper(substr($_SESSION['fullname'], 0, 1)) : 'A';
?>

<style>
/* ==========================================
   SIDEBAR & LAYOUT STYLES
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
}

.sidebar-brand {
    padding: 20px 20px 16px 20px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
}

.sidebar-brand .brand-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.portal-logo {
    width: 36px;
    height: 36px;
    object-fit: contain;
}

.brand-text h2 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

.brand-text span {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    padding: 16px 12px;
    gap: 4px;
    flex-grow: 1;
    overflow-y: auto;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    color: #64748b;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.92rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.sidebar-nav a .nav-link-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.sidebar-nav a i {
    font-size: 1.1rem;
    width: 20px;
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
    font-size: 0.75rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
    line-height: 1.2;
}

.sidebar-footer {
    padding: 16px 12px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #fafafa;
}

.user-profile-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 10px;
    border-radius: 8px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
}

.user-profile-card .avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #2563eb;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.user-profile-card .user-details {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.user-profile-card .user-details strong {
    font-size: 0.88rem;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-profile-card .user-details small {
    font-size: 0.75rem;
    color: #64748b;
}.sidebar-logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 11px;
    background-color: #dc2626; /* Solid bright red background */
    color: #ffffff;            /* White text */
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 700;
    text-decoration: none;
    box-sizing: border-box;
    transition: all 0.2s ease;
    border: none;
    box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3);
}

.sidebar-logout-btn:hover {
    background-color: #b91c1c; /* Darker red on hover */
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 6px 15px rgba(220, 38, 38, 0.45);
}

.sidebar-logout-btn i {
    font-size: 1.05rem;
}

.mobile-header-toggle { display: none; }
.mobile-actions { display: flex; align-items: center; gap: 12px; }
.mobile-bell { position: relative; color: #64748b; font-size: 1.2rem; text-decoration: none; }
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
            <!-- Red Badge displaying the active number of unread messages -->
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

        <!-- TRANSACTION HISTORY ADDED HERE -->
        <a href="transaction_history.php" class="<?= ($current_page == 'transaction_history.php') ? 'active' : ''; ?>">
            <div class="nav-link-content">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Transaction History</span>
            </div>
        </a>

         <a href="announcements.php" class="<?= ($current_page == 'announcements.php') ? 'active' : ''; ?>">
            <div class="nav-link-content">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Annoucements</span>
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
    </nav>

    <div class="sidebar-footer">
        <a href="../logout.php" class="logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
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
});
</script>