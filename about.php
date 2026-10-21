<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About eRegistrar | Online Document Request System</title>

<!-- Favicon & External Fonts/Icons -->
<link rel="icon" type="image/png" href="assets/images/logooo.png?v=3">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* =========================================================
   CLEAN ACADEMIC BLUE PALETTE & STYLING (MATCHING INDEX)
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
   STYLED NAVBAR (EXACT MATCH TO INDEX)
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

/* HERO SECTION WITH PHOTO OVERLAY */
.about-hero {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 90px 48px;
    background: linear-gradient(rgba(31, 92, 171, 0.88), rgba(15, 23, 42, 0.92)), url('assets/images/about.png') center/cover no-repeat;
    color: #ffffff;
    text-align: center;
    position: relative;
    box-shadow: 0 10px 30px rgba(31, 92, 171, 0.25);
}

.about-hero h1 {
    font-size: 3rem;
    font-weight: 800;
    margin: 0 0 16px 0;
    letter-spacing: -0.03em;
}

.about-hero p {
    font-size: 1.15rem;
    color: var(--pale-bg);
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

/* CONTAINER & SECTIONS */
.about-container {
    max-width: 1200px;
    margin: 50px auto;
    padding: 0 24px;
    box-sizing: border-box;
}

/* INTRO SECTION */
.about-intro {
    display: flex;
    align-items: center;
    gap: 48px;
    background: #ffffff;
    padding: 48px;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    margin-bottom: 60px;
}

.about-intro img {
    width: 100%;
    max-width: 420px;
    border-radius: var(--radius-md);
    object-fit: cover;
}

.about-intro h2 {
    font-size: 2rem;
    font-weight: 800;
    color: var(--heading-color);
    margin: 0 0 16px 0;
}

.about-intro p {
    font-size: 1.05rem;
    color: var(--text-muted);
    line-height: 1.7;
    margin: 0;
}

/* SECTION TITLES */
.section-title {
    text-align: center;
    margin-bottom: 40px;
}

.section-title h2 {
    font-size: 2.1rem;
    font-weight: 800;
    color: var(--heading-color);
    margin: 0 0 10px 0;
}

.section-title p {
    color: var(--text-muted);
    font-size: 1.05rem;
    margin: 0;
}

.team-description {
    text-align: center;
    color: var(--text-muted);
    font-size: 1.05rem;
    max-width: 700px;
    margin: -20px auto 40px auto;
    line-height: 1.6;
}

/* CARDS GRID (FEATURES) */
.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 28px;
    margin-bottom: 60px;
}

.info-card {
    background: #ffffff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    padding: 36px 28px;
    text-align: center;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.info-card:hover {
    border-color: var(--bright-blue);
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.info-card img.feature-image {
    width: 64px;
    height: 64px;
    object-fit: contain;
    margin-bottom: 20px;
}

.info-card h3 {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--heading-color);
    margin: 0 0 10px 0;
}

.info-card p {
    color: var(--text-muted);
    font-size: 0.94rem;
    line-height: 1.6;
    margin: 0;
}

/* MISSION VISION GRID */
.mission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 32px;
    margin-bottom: 60px;
}

.mission-card {
    background: linear-gradient(135deg, #0f2748 0%, #163660 100%);
    color: #ffffff;
    padding: 40px;
    border-radius: var(--radius-lg);
    border: 1px solid rgba(57, 130, 220, 0.3);
    box-shadow: var(--shadow-md);
}

.mission-card h2 {
    font-size: 1.6rem;
    font-weight: 800;
    margin: 0 0 16px 0;
    color: #ffffff;
}

.mission-card p {
    color: var(--pale-bg);
    font-size: 1.05rem;
    line-height: 1.7;
    margin: 0;
}

/* DEVELOPER GRID */
.developer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 28px;
    margin-bottom: 60px;
}

.developer-card {
    background: #ffffff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    padding: 32px 24px;
    text-align: center;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.developer-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
    border-color: var(--bright-blue);
}

.developer-card img {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 16px;
    border: 3px solid var(--pale-bg);
}

.developer-card h3 {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--heading-color);
    margin: 0 0 4px 0;
}

.developer-card .role {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--deep-blue);
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.developer-card p {
    font-size: 0.9rem;
    color: var(--text-muted);
    line-height: 1.5;
    margin: 0;
}

/* COMMITMENT BOX */
.box {
    background: white;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    padding: 40px;
    text-align: center;
    box-shadow: var(--shadow-sm);
    max-width: 800px;
    margin: 0 auto;
}

.box h2 {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--heading-color);
    margin: 0 0 16px 0;
}

.box p {
    font-size: 1.05rem;
    color: var(--text-muted);
    line-height: 1.7;
    margin: 0;
}

/* Responsive layout adjustments */
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

    .about-intro {
        flex-direction: column;
        padding: 28px;
        text-align: center;
    }
    .about-hero h1 {
        font-size: 2.2rem;
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

<!-- HERO -->
<section class="about-hero">
    <div class="hero-overlay">
        <h1>About eRegistrar</h1>
        <p>A modern digital registrar system designed to simplify academic document services.</p>
    </div>
</section>

<section class="about-container">

    <!-- INTRO -->
    <div class="about-intro">
        <img src="assets/images/students.png" alt="Students">
        <div>
            <h2>Welcome to eRegistrar</h2>
            <p>
                eRegistrar is an online academic document request system developed to provide students with a faster, easier, and more convenient way of accessing registrar services.
                The system allows students to submit requests, monitor progress, and receive updates digitally while helping administrators efficiently manage student records.
            </p>
        </div>
    </div>

    <!-- FEATURES -->
    <div class="section-title">
        <h2>System Features</h2>
        <p>Built with efficiency and reliability in mind.</p>
    </div>

    <div class="card-grid">
        <div class="info-card">
            <img src="assets/images/online-request.png" alt="Online Requests" class="feature-image">
            <h3>Online Requests</h3>
            <p>Students can submit document requests anytime without manual processing.</p>
        </div>

        <div class="info-card">
            <img src="assets/images/faster.png" alt="Fast Processing" class="feature-image">
            <h3>Fast Processing</h3>
            <p>Improves transaction speed through digital workflows.</p>
        </div>

        <div class="info-card">
            <img src="assets/images/secure.png" alt="Secure System" class="feature-image">
            <h3>Secure System</h3>
            <p>Protects student information through secure authentication.</p>
        </div>

        <div class="info-card">
            <img src="assets/images/track.png" alt="Request Tracking" class="feature-image">
            <h3>Request Tracking</h3>
            <p>Students can monitor their request status easily.</p>
        </div>
    </div>

    <!-- MISSION VISION -->
    <div class="mission-grid">
        <div class="mission-card">
            <h2>Our Mission</h2>
            <p>To provide a reliable and efficient digital platform that improves registrar services and creates a better experience for students and administrators.</p>
        </div>

        <div class="mission-card">
            <h2>Our Vision</h2>
            <p>To create a connected digital campus environment where academic services are accessible, organized, and efficient.</p>
        </div>
    </div>

    <!-- DEVELOPMENT TEAM -->
    <div class="section-title">
        <h2>Meet the Developers</h2>
    </div>
    <p class="team-description">
        eRegistrar was designed and developed by a team of dedicated student developers who collaborated in planning, designing, programming, testing, and improving the system.
    </p>

    <div class="developer-grid">
        <div class="developer-card">
            <img src="assets/images/developer1.png" alt="Juliefel Malusay">
            <h3>Juliefel Malusay</h3>
            <p class="role">Project Manager / Lead Developer</p>
            <p>Responsible for system planning, database integration, and overall project coordination.</p>
        </div>

        <div class="developer-card">
            <img src="assets/images/developer2.png" alt="Ira Mariz Mabunay">
            <h3>Ira Mariz Mabunay</h3>
            <p class="role">Asst. Developer</p>
            <p>Supports system development, feature implementation, debugging, and integration of system components.</p>
        </div>

        <div class="developer-card">
            <img src="assets/images/developer3.png" alt="Anjenneth Alcontin">
            <h3>Anjenneth Alcontin</h3>
            <p class="role">UI/UX Designer</p>
            <p>Designs user interfaces, improves usability, and creates a smooth experience for students and administrators.</p>
        </div>

        <div class="developer-card">
            <img src="assets/images/developer4.png" alt="Jemar Jardin">
            <h3>Jemar Jardin</h3>
            <p class="role">Tester</p>
            <p>Performs system testing, identifies issues, and ensures the system functions correctly and reliably.</p>
        </div>
    </div>

    <!-- COMMITMENT -->
    <div class="box">
        <h2>Our Commitment</h2>
        <p>The eRegistrar development team continues to improve the system by applying technology solutions that make academic services more efficient, accessible, and student-friendly.</p>
    </div>

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