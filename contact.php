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
<title>Contact Us | eRegistrar</title>

<!-- Favicon, Fonts & Font Awesome Icons -->
<link rel="icon" type="image/png" href="assets/images/logooo.png?v=3">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* =========================================================
   MATCHING ACADEMIC BLUE THEME & CONTACT PAGE STYLING
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
}

body {
    font-family: 'Inter', sans-serif;
    color: var(--heading-color);
    background-color: #f8fafc;
    margin: 0;
}

/* =========================================================
   STYLED NAVBAR (EXACT MATCH TO INDEX & ABOUT)
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

/* HERO HEADER */
.contact-hero {
    background: linear-gradient(rgba(31, 92, 171, 0.88), rgba(15, 23, 42, 0.92)), url('assets/images/contact.png') center/cover no-repeat;
    color: #ffffff;
    padding: 70px 24px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(31, 92, 171, 0.25);
}

.contact-hero h1 {
    font-size: 2.8rem;
    font-weight: 800;
    margin: 0 0 12px 0;
    letter-spacing: -0.02em;
}

.contact-hero p {
    font-size: 1.1rem;
    color: var(--pale-bg);
    margin: 0;
}

/* CONTACT CONTAINER */
.contact-container {
    max-width: 1100px;
    margin: 50px auto;
    padding: 0 24px;
    box-sizing: border-box;
}

/* FORM SECTION */
.contact-card {
    background: #ffffff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    padding: 40px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 60px;
}

.contact-form {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 700;
    font-size: 0.92rem;
    color: var(--heading-color);
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 14px 16px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    background-color: #f8fafc;
    color: var(--heading-color);
    box-sizing: border-box;
    transition: all 0.2s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--bright-blue);
    background-color: #ffffff;
    box-shadow: 0 0 0 4px rgba(57, 130, 220, 0.15);
}

.form-group textarea {
    resize: vertical;
    min-height: 140px;
}

.contact-form button[type="submit"] {
    align-self: flex-start;
    background-color: var(--deep-blue);
    color: #ffffff;
    padding: 14px 36px;
    border: none;
    border-radius: var(--radius-md);
    font-weight: 800;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 14px rgba(31, 92, 171, 0.25);
}

.contact-form button[type="submit"]:hover {
    background-color: #174888;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(31, 92, 171, 0.35);
}

/* INFO CARDS GRID */
.info-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

.info-card .icon-wrapper {
    width: 60px;
    height: 60px;
    background-color: var(--pale-bg);
    color: var(--deep-blue);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 20px auto;
    transition: all 0.3s ease;
}

.info-card:hover .icon-wrapper {
    background-color: var(--deep-blue);
    color: #ffffff;
}

.info-card h3 {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--heading-color);
    margin: 0 0 12px 0;
    letter-spacing: 0.5px;
}

.info-card p {
    color: var(--text-muted);
    font-size: 0.94rem;
    line-height: 1.6;
    margin: 0 0 8px 0;
}

.info-card p:last-child {
    margin-bottom: 0;
}

/* Responsive adjustments */
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

    .contact-hero h1 {
        font-size: 2.2rem;
    }
    .contact-card {
        padding: 24px;
    }
    .contact-form button[type="submit"] {
        width: 100%;
        justify-content: center;
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
<section class="contact-hero">
    <h1>Contact Us</h1>
    <p>Have questions or need assistance? Write us a message below!</p>
</section>

<div class="contact-container">

    <!-- FORM SECTION -->
    <div class="contact-card">
        <form class="contact-form" action="#" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="e.g. student@consolatrix.edu" required>
                </div>

                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required>
                </div>
            </div>

            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" placeholder="Write your message or inquiry here..." required></textarea>
            </div>

            <button type="submit">
                <i class="fa-solid fa-paper-plane"></i> Send Message
            </button>
        </form>
    </div>

    <!-- INFO CARDS SECTION -->
    <div class="info-container">

        <div class="info-card">
            <div class="icon-wrapper">
                <i class="fa-solid fa-building-columns"></i>
            </div>
            <h3>REGISTRAR OFFICE</h3>
            <p><strong>eRegistrar Student Services</strong></p>
            <p>Academic Records & Document Processing</p>
        </div>

        <div class="info-card">
            <div class="icon-wrapper">
                <i class="fa-solid fa-phone"></i>
            </div>
            <h3>CONTACT INFO</h3>
            <p>(032) 322-5644</p>
            <p>admin@consolatrix.edu</p>
            <p style="margin-top: 8px; font-size: 0.85rem; color: var(--deep-blue); font-weight: 600;">
                <i class="fa-regular fa-clock"></i> Mon - Fri • 8:00 AM - 5:00 PM
            </p>
        </div>

        <div class="info-card">
            <div class="icon-wrapper">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            <h3>OFFICE LOCATION</h3>
            <p><strong>Consolatrix College of Toledo City, Inc.</strong></p>
            <p>645 Hinulawan Street, Magsaysay Hills, Poblacion, Toledo City, Cebu 6038</p>
        </div>

    </div>

</div>

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