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
<link rel="stylesheet" href="assets/css/public.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* =========================================================
   ENHANCED ANNOUNCEMENT & HIGHLIGHT STYLES
   ========================================================= */
:root {
    --primary-blue: #2563eb;
    --primary-dark: #1d4ed8;
    --heading-color: #0f172a;
    --text-muted: #64748b;
    --card-bg: #ffffff;
}

/* SECTION HEADER STYLING */
.section-title {
    text-align: center;
    margin-bottom: 32px;
}

.section-title h2 {
    font-size: 1.85rem;
    font-weight: 800;
    color: var(--heading-color);
    letter-spacing: -0.02em;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.section-title p {
    color: var(--text-muted);
    font-size: 1rem;
    margin: 0;
}

/* ANNOUNCEMENTS GRID & CARDS (NO BLUE SIDEBAR LINE + HIGHLIGHTED LOOK) */
.announcements-section {
    max-width: 1150px;
    margin: 48px auto;
    padding: 0 24px;
}

.announcements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 24px;
}

.announcement-card {
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    border-radius: 16px;
    border: 1px solid #cbd5e1;
    padding: 26px;
    position: relative;
    box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.announcement-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 30px -10px rgba(37, 99, 235, 0.18), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    border-color: #93c5fd;
    background: linear-gradient(180deg, #f0f6ff 0%, #ffffff 100%);
}

.announcement-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.announcement-title {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--heading-color);
    line-height: 1.35;
    margin: 0;
}

.announcement-badge {
    background: #dbeafe;
    color: #1e40af;
    font-size: 0.72rem;
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

.announcement-content {
    color: #334155;
    font-size: 0.96rem;
    line-height: 1.65;
    margin-bottom: 16px;
    flex-grow: 1;
}

.announcement-image {
    width: 100%;
    max-height: 240px;
    border-radius: 12px;
    object-fit: cover;
    margin-bottom: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
}

.announcement-footer {
    font-size: 0.82rem;
    color: var(--text-muted);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    padding-top: 14px;
    border-top: 1px solid #f1f5f9;
}

/* PROCESS GRID (HOW IT WORKS STEP CARDS) */
.process-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    max-width: 1150px;
    margin: 0 auto;
    padding: 0 24px;
}

.process-grid > div {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: 24px;
    box-sizing: border-box;
    transition: all 0.2s ease;
    position: relative;
}

.process-grid > div:hover {
    border-color: #2563eb;
    transform: translateY(-3px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
}

.process-grid span {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, #2563eb, #60a5fa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: block;
    margin-bottom: 8px;
}

.process-grid h3 {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--heading-color);
    margin: 0 0 6px 0;
}

.process-grid p {
    font-size: 0.88rem;
    color: var(--text-muted);
    margin: 0;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .announcements-grid {
        grid-template-columns: 1fr;
    }
}
</style>

</head>

<body>

<?php include "navbar.php"; ?>

<!-- HERO SECTION -->
<section class="hero">

<div class="hero-content">

<span class="badge">
<i class="fa-solid fa-sparkles"></i> Welcome to eRegistrar
</span>

<h1>
Your Digital Solution<br>for Academic Document Requests
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

<img src="assets/images/logosss.png" alt="eRegistrar Logo">

</div>

</section>


<!-- ANNOUNCEMENTS SECTION -->
<?php if ($announcements_query && mysqli_num_rows($announcements_query) > 0): ?>
<section class="announcements-section">
    <div class="section-title">
        <h2><i class="fa-solid fa-bullhorn" style="color: #2563eb;"></i> Announcements</h2>
        <p>Official updates and announcements from the Registrar's Office</p>
    </div>

    <div class="announcements-grid">
        <?php while ($announcement = mysqli_fetch_assoc($announcements_query)): ?>
            <div class="announcement-card">
                <div>
                    <div class="announcement-header">
                        <h3 class="announcement-title">
                            <?= htmlspecialchars($announcement['title']); ?>
                        </h3>
                        <span class="announcement-badge"><i class="fa-solid fa-circle-exclamation"></i> Official Notice</span>
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
                    <span>Posted on <?= date("F j, Y - h:i A", strtotime($announcement['created_at'])); ?></span>
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


<!-- FEATURES -->
<section class="home-section light">

<div class="section-title">
<h2>Why Choose eRegistrar?</h2>
<p>Everything you need for a smoother document request experience.</p>
</div>

<div class="feature-grid">

<div class="feature-card">
<div class="icon">📄</div>
<h3>Online Requests</h3>
<p>Submit your document requests anywhere without visiting the office.</p>
</div>

<div class="feature-card">
<div class="icon">📌</div>
<h3>Request Tracking</h3>
<p>Monitor your request status from submission until completion.</p>
</div>

<div class="feature-card">
<div class="icon">🔐</div>
<h3>Secure Records</h3>
<p>Your academic information is managed safely and efficiently.</p>
</div>

</div>

</section>


<!-- HOW IT WORKS PREVIEW -->
<section class="home-section">

<div class="section-title">
<h2>How It Works</h2>
<p>Four simple steps to request your documents.</p>
</div>

<div class="process-grid">

<div>
<span>01</span>
<h3>Create Account</h3>
<p>Register your student information.</p>
</div>

<div>
<span>02</span>
<h3>Submit Request</h3>
<p>Choose the document you need.</p>
</div>

<div>
<span>03</span>
<h3>Track Status</h3>
<p>Follow your request progress.</p>
</div>

<div>
<span>04</span>
<h3>Claim Document</h3>
<p>Receive your completed document.</p>
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