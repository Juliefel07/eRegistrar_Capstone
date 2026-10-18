<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Flexible DB connection include
if (file_exists(__DIR__ . "/includes/db.php")) {
    require_once __DIR__ . "/includes/db.php";
} elseif (file_exists(__DIR__ . "/../includes/db.php")) {
    require_once __DIR__ . "/../includes/db.php";
}

// Fetch active announcements aimed at Everyone or Public
$announcements_query = false;
if (isset($conn) && $conn) {
    $announcements_query = mysqli_query($conn, "
        SELECT * FROM announcements 
        WHERE status = 'active' AND target_audience IN ('all', 'public') 
        ORDER BY created_at DESC 
        LIMIT 6
    ");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>eRegistrar | Online Document Request System</title>

<link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* =========================================================
   CLEAN ACADEMIC BLUE PALETTE & MOBILE-FRIENDLY STYLING
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
    --card-bg: #ffffff;
    --border-color: #cbd5e1;
    --radius-md: 12px;
    --radius-lg: 16px;
    --shadow-sm: 0 1px 3px rgba(31, 92, 171, 0.08);
    --shadow-md: 0 10px 25px -3px rgba(57, 130, 220, 0.12);
    --shadow-lg: 0 20px 30px -10px rgba(31, 92, 171, 0.18);
}

body {
    font-family: 'Inter', sans-serif;
    color: var(--heading-color);
    background-color: #f8fafc;
    margin: 0;
}

/* =========================================================
   STYLED NAVBAR
   ========================================================= */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 48px;
    background-color: var(--navbar-dark);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 4px 20px rgba(15, 45, 89, 0.35);
}

.logo-area {
    display: flex;
    align-items: center;
    gap: 14px;
}

.logo-area img {
    width: 42px;
    height: 42px;
    object-fit: contain;
}

.logo-area h2 {
    font-size: 1.25rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.logo-area small {
    font-size: 0.78rem;
    color: var(--light-sky);
    font-weight: 600;
    letter-spacing: 0.02em;
}

#nav-menu {
    display: flex;
    align-items: center;
    gap: 32px;
}

#nav-menu a {
    color: #e2e8f0;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    transition: color 0.2s ease;
}

#nav-menu a:hover {
    color: var(--light-sky);
}

#nav-menu a.login-btn {
    background-color: #ffffff !important;
    color: #1F5CAB !important;
    padding: 10px 24px;
    border-radius: var(--radius-md);
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

#nav-menu a.login-btn:hover {
    background-color: var(--pale-bg);
    transform: translateY(-1px);
}

.menu-toggle {
    display: none;
    background: transparent;
    border: none;
    color: #ffffff;
    font-size: 1.5rem;
    cursor: pointer;
}

/* SECTION HEADER STYLING */
.section-title {
    text-align: center;
    margin-bottom: 40px;
    padding: 0 16px;
}

.section-title h2 {
    font-size: 2.1rem;
    font-weight: 800;
    color: var(--heading-color);
    letter-spacing: -0.025em;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.section-title p {
    color: var(--text-muted);
    font-size: 1.05rem;
    margin: 0;
}

/* =========================================================
   FEATURES SECTION (ACADEMIC BLUE THEME)
   ========================================================= */
.home-section.features-section {
    background: #ffffff;
    border-radius: var(--radius-lg, 20px);
    border: 1px solid var(--border-color, #cbd5e1);
    box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05));
    padding: 60px 32px;
    margin: 50px auto;
    max-width: 1200px;
    box-sizing: border-box;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 28px;
    margin-top: 32px;
}

.feature-card {
    background: #f8fafc;
    border-radius: var(--radius-md, 14px);
    border: 1px solid #e2e8f0;
    padding: 32px 24px;
    text-align: center;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    box-sizing: border-box;
}

.feature-card:hover {
    background: #ffffff;
    border-color: var(--bright-blue, #3982DC);
    transform: translateY(-5px);
    box-shadow: 0 12px 25px -5px rgba(31, 92, 171, 0.12);
}

.feature-card .icon-wrapper {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px auto;
    background: var(--pale-bg, #DBE9F9);
    color: var(--deep-blue, #1F5CAB);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    transition: all 0.3s ease;
}

.feature-card:hover .icon-wrapper {
    background: var(--deep-blue, #1F5CAB);
    color: #ffffff;
    transform: scale(1.08);
}

.feature-card h3 {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--heading-color, #0f172a);
    margin: 0 0 10px 0;
}

.feature-card p {
    color: var(--text-muted, #475569);
    font-size: 0.94rem;
    line-height: 1.6;
    margin: 0;
}

/* Mobile Screen Adjustments */
@media (max-width: 768px) {
    .home-section.features-section {
        padding: 40px 20px;
        margin: 30px 16px;
        border-radius: 16px;
    }
    
    .feature-grid {
        gap: 18px;
        margin-top: 24px;
    }
    
    .feature-card {
        padding: 24px 18px;
    }
}

/* HERO SECTION WITH PHOTO OVERLAY */
.hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 90px 48px;
    background: linear-gradient(rgba(31, 92, 171, 0.88), rgba(15, 23, 42, 0.92)), url('assets/images/index.png') center/cover no-repeat;
    color: #ffffff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(31, 92, 171, 0.25);
}

.hero-content {
    flex: 1.2;
    z-index: 2;
}

.hero .badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 8px 16px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 0.85rem;
    margin-bottom: 24px;
}

.hero h1 {
    font-size: 3.2rem;
    font-weight: 800;
    line-height: 1.15;
    color: #ffffff;
    margin: 0 0 20px 0;
    letter-spacing: -0.03em;
}

.hero p {
    font-size: 1.12rem;
    color: var(--pale-bg);
    line-height: 1.6;
    margin-bottom: 36px;
    max-width: 580px;
}

.hero-buttons {
    display: flex;
    gap: 16px;
}

.primary-btn, .secondary-btn, .outline-btn {
    padding: 14px 28px;
    border-radius: var(--radius-md);
    font-weight: 700;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.primary-btn {
    background-color: #ffffff;
    color: var(--deep-blue);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
}

.primary-btn:hover {
    background-color: var(--pale-bg);
    transform: translateY(-2px);
}

.secondary-btn {
    background-color: rgba(255, 255, 255, 0.12);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.25);
}

.secondary-btn:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.hero-logo {
    flex: 0.8;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2;
}

.hero-logo img {
    max-width: 280px;
    height: auto;
    filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.4));
    animation: floatAnim 4s ease-in-out infinite alternate;
}

@keyframes floatAnim {
    0% { transform: translateY(0); }
    100% { transform: translateY(-12px); }
}

/* ANNOUNCEMENTS GRID & CARDS */
/* =========================================================
   NEW UPGRADED ANNOUNCEMENTS STYLING
   ========================================================= */
/* =========================================================
   DARK THEME COMPACT ANNOUNCEMENTS SECTION
 /* =========================================================
   DARK THEME COMPACT ANNOUNCEMENTS SECTION (MOBILE-FRIENDLY)
   ========================================================= */
.announcements-section {
    max-width: 850px;
    margin: 40px auto;
    padding: 32px 24px;
    background-color: #0d172a;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.3);
    box-sizing: border-box;
}

.announcements-section .section-title h2 {
    color: #ffffff;
    font-size: 1.6rem;
}

.announcements-section .section-title p {
    color: #94a3b8;
    font-size: 0.92rem;
}

.announcements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Flexible grid adapting to smaller widths */
    gap: 20px;
}

.announcement-card {
    background: linear-gradient(135deg, #0f2748 0%, #163660 100%);
    border-radius: 14px;
    border: 1px solid rgba(57, 130, 220, 0.25);
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
    transition: all 0.25s ease;
    position: relative;
    box-sizing: border-box;
    word-break: break-word; /* Prevents long words/links from breaking layout on mobile */
}

.announcement-card:hover {
    transform: translateY(-3px);
    border-color: var(--bright-blue, #3982DC);
    box-shadow: 0 10px 25px rgba(57, 130, 220, 0.25);
}

.announcement-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
    flex-wrap: wrap; /* Allows header badges to wrap gracefully on very small screens */
}

.announcement-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.35;
    margin: 0;
}

.announcement-badge {
    background: rgba(57, 130, 220, 0.25);
    color: #93c5fd;
    border: 1px solid rgba(147, 197, 253, 0.3);
    font-size: 0.68rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.announcement-badge i {
    color: #fbbf24;
}

.announcement-content {
    color: #cbd5e1;
    font-size: 0.9rem;
    line-height: 1.55;
    margin-bottom: 16px;
    flex-grow: 1;
}

.announcement-image {
    width: 100%;
    max-height: 180px;
    border-radius: 8px;
    object-fit: cover;
    margin-bottom: 14px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.announcement-footer {
    font-size: 0.78rem;
    color: #94a3b8;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    padding-top: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.announcement-footer i {
    color: var(--sky-blue, #75AAE7);
}

/* Mobile Screen Adjustments */
@media (max-width: 768px) {
    .announcements-section {
        padding: 20px 16px;
        margin: 24px 16px;
        border-radius: 16px;
    }
    
    .announcements-section .section-title h2 {
        font-size: 1.35rem;
    }

    .announcement-card {
        padding: 16px 18px;
    }
}
/* STATISTICS */
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
    max-width: 1200px;
    margin: 60px auto;
    padding: 0 24px;
}

.stat-card {
    background: white;
    padding: 32px 24px;
    border-radius: var(--radius-lg);
    text-align: center;
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

.stat-card h2 {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--deep-blue);
    margin: 0 0 6px 0;
}

.stat-card p {
    color: var(--text-muted);
    font-weight: 600;
    margin: 0;
}

/* ABOUT BOX */
.about-box {
    background: white;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    padding: 40px;
    box-shadow: var(--shadow-sm);
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
}

.about-box p {
    font-size: 1.1rem;
    color: var(--text-muted);
    line-height: 1.7;
    margin-bottom: 24px;
}

.outline-btn {
    border: 2px solid var(--deep-blue);
    color: var(--deep-blue);
    background: transparent;
}

.outline-btn:hover {
    background: var(--deep-blue);
    color: white;
}

/* FEATURES */
.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 28px;
}

.feature-card {
    background: white;
    padding: 36px 30px;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    transition: all 0.2s ease;
}

.feature-card:hover {
    border-color: var(--bright-blue);
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

.feature-card .icon {
    font-size: 2.2rem;
    margin-bottom: 16px;
}

.feature-card h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--heading-color);
    margin: 0 0 10px 0;
}

.feature-card p {
    color: var(--text-muted);
    line-height: 1.6;
    margin: 0;
}

/* HOW IT WORKS */
.process-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 24px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

.process-grid > div {
    background: #ffffff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    padding: 32px 24px;
    box-sizing: border-box;
    transition: all 0.2s ease;
    position: relative;
    box-shadow: var(--shadow-sm);
}

.process-grid > div:hover {
    border-color: var(--bright-blue);
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

.process-grid span {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--bright-blue);
    display: block;
    margin-bottom: 12px;
    opacity: 0.9;
}

.process-grid h3 {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--heading-color);
    margin: 0 0 8px 0;
}

.process-grid p {
    font-size: 0.92rem;
    color: var(--text-muted);
    margin: 0;
    line-height: 1.6;
}

/* CALL TO ACTION */
.cta {
    background: linear-gradient(135deg, var(--deep-blue) 0%, var(--bright-blue) 100%);
    color: white;
    text-align: center;
    padding: 80px 24px;
    margin-top: 80px;
    border-radius: 24px 24px 0 0;
    box-shadow: 0 -10px 30px rgba(31, 92, 171, 0.15);
}

.cta h2 {
    font-size: 2.3rem;
    font-weight: 800;
    margin: 0 0 12px 0;
}

.cta p {
    color: var(--pale-bg);
    font-size: 1.1rem;
    margin-bottom: 32px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.cta a {
    background-color: #ffffff;
    color: var(--deep-blue);
    padding: 16px 36px;
    border-radius: var(--radius-md);
    font-weight: 800;
    text-decoration: none;
    display: inline-block;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    transition: all 0.2s;
}

.cta a:hover {
    background-color: var(--pale-bg);
    transform: translateY(-2px);
}

/* =========================================================
   MOBILE RESPONSIVE MEDIA QUERY (MATCHING REFERENCE LAYOUT)
   ========================================================= */
@media (max-width: 968px) {
    .navbar {
        padding: 16px 20px;
    }
    .menu-toggle {
        display: block;
    }
    #nav-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background-color: var(--navbar-dark);
        flex-direction: column;
        padding: 24px;
        gap: 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow: 0 10px 20px rgba(0,0,0,0.4);
        box-sizing: border-box;
    }
    #nav-menu.show {
        display: flex;
    }

    /* Puts logo/emblem at the top on mobile screens */
    .hero {
        flex-direction: column-reverse;
        text-align: center;
        padding: 50px 20px;
        gap: 32px;
    }
    
    .hero-content {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .hero h1 {
        font-size: 2.2rem;
    }

    .hero p {
        margin-left: auto;
        margin-right: auto;
        font-size: 1rem;
    }

    .hero-buttons {
        flex-direction: column;
        width: 100%;
        max-width: 320px;
        gap: 12px;
    }

    .primary-btn, .secondary-btn {
        width: 100%;
        justify-content: center;
        box-sizing: border-box;
    }

    .hero-logo img {
        max-width: 170px;
    }
}
</style>

</head>

<body>

<!-- STYLED DARK NAVBAR -->
<header class="navbar">
    <div class="logo-area">
        <img src="assets/images/logo1.png" alt="eRegistrar Logo">
        <div>
            <h2><span style="color: #3982DC !important; display: inline;">e</span>Registrar</h2>
            <small>Document Request System</small>
        </div>
    </div>

    <button class="menu-toggle" id="menu-toggle">
        ☰
    </button>

    <nav id="nav-menu">
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="login.php" class="login-btn">Login</a>
    </nav>
</header>

<!-- HERO SECTION -->
<section class="hero">

    <div class="hero-content">
        <span class="badge">
            <i class="fa-solid fa-sparkles"></i> Welcome to eRegistrar
        </span>

        <h1>
            Your Digital Solution for Academic Document Requests
        </h1>

        <p>
            eRegistrar provides students with a faster, simpler, and more convenient way to request school documents online. Submit requests, monitor progress, and receive updates anytime.
        </p>

        <div class="hero-buttons">
            <a href="register.php" class="primary-btn">
                Get Started <i class="fa-solid fa-arrow-right"></i>
            </a>

            <a href="login.php" class="secondary-btn">
                Student Login
            </a>
        </div>
    </div>

    <div class="hero-logo">
        <img src="assets/images/logo1.png" alt="School Seal">
    </div>

</section>


<!-- ANNOUNCEMENTS SECTION -->
<?php if ($announcements_query && mysqli_num_rows($announcements_query) > 0): ?>
<section class="announcements-section">
    <div class="section-title" style="margin-bottom: 24px;">
        <h2 style="font-size: 1.6rem;"><i class="fa-solid fa-bullhorn" style="color: var(--sky-blue, #75AAE7);"></i> Registrar Announcements</h2>
        <p style="font-size: 0.92rem;">Stay updated with the latest official notices, schedules, and reminders</p>
    </div>

    <div class="announcements-grid">
        <?php while ($announcement = mysqli_fetch_assoc($announcements_query)): ?>
            <div class="announcement-card">
                <div>
                    <div class="announcement-header">
                        <h3 class="announcement-title">
                            <?= htmlspecialchars($announcement['title']); ?>
                        </h3>
                        <span class="announcement-badge"><i class="fa-solid fa-circle-exclamation"></i> Notice</span>
                    </div>

                    <p class="announcement-content">
                        <?= nl2br(htmlspecialchars($announcement['content'])); ?>
                    </p>

                    <?php if (!empty($announcement['image_path'])): ?>
                        <img src="<?= htmlspecialchars($announcement['image_path']); ?>" alt="Announcement Banner" class="announcement-image">
                    <?php endif; ?>
                </div>

                <div class="announcement-footer">
                    <i class="fa-regular fa-clock"></i>
                    <span>Posted on <?= date("F j, Y • h:i A", strtotime($announcement['created_at'])); ?></span>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</section>
<?php endif; ?>


<!-- STATISTICS -->
<section class="stats">

<div class="stat-card">
<h2>500+</h2>
<p>Students Served</p>
</div>

<div class="stat-card">
<h2>24/7</h2>
<p>Online Access</p>
</div>

<div class="stat-card">
<h2>Fast</h2>
<p>Processing</p>
</div>

<div class="stat-card">
<h2>Secure</h2>
<p>Records</p>
</div>

</section>


<!-- ABOUT PREVIEW -->
<section class="home-section">

<div class="section-title">
<h2>About eRegistrar</h2>
<p>A modern document request platform designed to improve student services.</p>
</div>

<div class="about-box">
<p>
Traditional document processing can require long waiting times and manual transactions. eRegistrar transforms this process into a digital experience where students can easily submit requests and track their progress.
</p>

<a href="about.php" class="outline-btn">
Learn More <i class="fa-solid fa-arrow-right"></i>
</a>
</div>

</section>



<!-- FEATURES SECTION -->
<section class="home-section features-section">

    <div class="section-title">
        <h2>Why Choose eRegistrar?</h2>
        <p>Everything you need for a smoother document request experience.</p>
    </div>

    <div class="feature-grid">

        <div class="feature-card">
            <div class="icon-wrapper">
                <i class="fa-solid fa-file-signature"></i>
            </div>
            <h3>Online Requests</h3>
            <p>Submit your document requests anytime, anywhere without having to line up at the office.</p>
        </div>

        <div class="feature-card">
            <div class="icon-wrapper">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <h3>Request Tracking</h3>
            <p>Monitor real-time updates and status changes from submission through completion.</p>
        </div>

        <div class="feature-card">
            <div class="icon-wrapper">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h3>Secure Records</h3>
            <p>Your institutional records and student information are safely encrypted and handled.</p>
        </div>

    </div>

</section>


<!-- CALL TO ACTION -->
<section class="cta">

<h2>Ready to start your request?</h2>
<p>Create your account today and experience a better way to process school documents.</p>

<a href="register.php">
Create Student Account
</a>

</section>


<?php include "footer.php"; ?>

<script>
const toggle = document.getElementById("menu-toggle");
const menu = document.getElementById("nav-menu");

if (toggle && menu) {
    toggle.addEventListener("click", () => {
        menu.classList.toggle("show");
        toggle.innerHTML = menu.classList.contains("show") ? "✕" : "☰";
    });
}
</script>

</body>

</html>