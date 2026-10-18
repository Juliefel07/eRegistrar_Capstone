<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<title>eRegistrar Login</title>
<link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* =========================================================
   ACADEMIC BLUE DARK THEME LOGIN
   ========================================================= */
:root {
    --deep-blue: #1F5CAB;
    --bright-blue: #3982DC;
    --sky-blue: #75AAE7;
    --light-sky: #B9D4F3;
    --pale-bg: #DBE9F9;
    --navbar-dark: #0f2d59;
    --card-dark: #0a192f;
    --text-light: #f8fafc;
    --text-muted: #94a3b8;
    --border-dark: rgba(255, 255, 255, 0.12);
    --radius-md: 12px;
    --radius-lg: 20px;
    --shadow-lg: 0 25px 50px -12px rgba(15, 23, 42, 0.4);
}

*, *::before, *::after {
    box-sizing: border-box !important;
}

body {
    font-family: 'Inter', sans-serif;
    color: var(--text-light);
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    margin: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 16px;
}

/* BACK BUTTON */
.back-button {
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 100;
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    border: 1px solid var(--border-dark);
    backdrop-filter: blur(8px);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.back-button:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateX(-3px);
}

/* MAIN WRAPPER */
.login-wrapper {
    display: flex;
    width: 100%;
    max-width: 950px;
    min-height: 560px;
    background: var(--card-dark);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-dark);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    margin: auto;
}

/* LEFT SIDE ILLUSTRATION */
.login-image {
    flex: 1;
    background: linear-gradient(135deg, #0f2d59 0%, #1F5CAB 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    border-right: 1px solid var(--border-dark);
}

.login-image img {
    max-width: 85%;
    height: auto;
    object-fit: contain;
    filter: drop-shadow(0 15px 25px rgba(0, 0, 0, 0.4));
}

/* RIGHT SIDE CARD */
.login-card {
    flex: 1;
    padding: 40px 36px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    background-color: var(--card-dark);
}

/* LOGO & BRANDING */
.login-card .logo {
    width: 75px;
    height: 75px;
    object-fit: contain;
    margin: 0 auto 12px auto;
    display: block;
    filter: drop-shadow(0 4px 12px rgba(57, 130, 220, 0.3));
}

.login-card h2 {
    font-size: 2.2rem;
    font-weight: 800;
    text-align: center;
    margin: 0 0 16px 0;
    letter-spacing: -0.02em;
}

.login-card h2 .brand-e {
    color: #3982DC;
}

.login-card h2 .brand-registrar {
    color: #ffffff !important;
}

/* FORM CONTROLS */
.login-card form {
    display: flex;
    flex-direction: column;
    gap: 16px;
    width: 100%;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    width: 100%;
}

.login-card label {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--text-light);
}

/* PERFECT INPUT & ICON POSITIONING */
.input-icon-wrapper {
    position: relative !important;
    width: 100% !important;
    display: flex !important;
    align-items: center !important;
}

.input-icon-wrapper svg {
    position: absolute !important;
    left: 14px !important;
    width: 18px !important;
    height: 18px !important;
    fill: none !important;
    stroke: #75AAE7 !important;
    stroke-width: 2 !important;
    stroke-linecap: round !important;
    stroke-linejoin: round !important;
    pointer-events: none !important;
    z-index: 10 !important;
}

.login-card input {
    width: 100% !important;
    padding: 12px 16px 12px 44px !important;
    border-radius: var(--radius-md) !important;
    border: 1px solid var(--border-dark) !important;
    font-family: 'Inter', sans-serif !important;
    font-size: 0.95rem !important;
    background-color: rgba(255, 255, 255, 0.06) !important;
    color: #ffffff !important;
    transition: all 0.2s ease !important;
    box-sizing: border-box !important;
}

.login-card input::placeholder {
    color: #64748b;
}

.login-card input:focus {
    outline: none !important;
    border-color: var(--bright-blue) !important;
    background-color: rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 0 0 4px rgba(57, 130, 220, 0.25) !important;
}

.login-card button[type="submit"] {
    background-color: var(--bright-blue) !important;
    color: #ffffff !important;
    padding: 14px !important;
    border: none !important;
    border-radius: var(--radius-md) !important;
    font-weight: 800 !important;
    font-size: 1rem !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 4px 14px rgba(57, 130, 220, 0.3) !important;
    margin-top: 8px !important;
    width: 100% !important;
}

.login-card button[type="submit"]:hover {
    background-color: #2563eb !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 20px rgba(57, 130, 220, 0.4) !important;
}

/* LINKS */
.text-center {
    text-align: center;
    font-size: 0.9rem;
    color: var(--text-muted);
    margin: 12px 0 0 0;
}

.text-center a {
    color: var(--sky-blue);
    font-weight: 700;
    text-decoration: none;
}

.text-center a:hover {
    color: #ffffff;
    text-decoration: underline;
}

/* MODALS */
.modal-error, .success-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 16px;
}

.modal-box, .success-box {
    background: var(--card-dark);
    border-radius: var(--radius-lg);
    padding: 32px 24px;
    max-width: 400px;
    width: 100%;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    border: 1px solid var(--border-dark);
}

.modal-box h3 { color: #f87171; margin: 0 0 10px 0; font-weight: 800; }
.success-box h3 { color: #34d399; margin: 0 0 10px 0; font-weight: 800; }
.modal-box p, .success-box p { color: var(--text-muted); margin-bottom: 24px; font-size: 0.95rem; }

.modal-box button, .success-box button {
    background-color: var(--bright-blue);
    color: #ffffff;
    padding: 12px 32px;
    border: none;
    border-radius: var(--radius-md);
    font-weight: 700;
    cursor: pointer;
    width: 100%;
    max-width: 200px;
}

/* MOBILE RESPONSIVE ADJUSTMENTS */
@media (max-width: 850px) {
    body {
        justify-content: flex-start;
        padding-top: 12px;
    }

    .back-button {
        position: relative;
        top: 0;
        left: 0;
        align-self: flex-start;
        margin-bottom: 16px;
    }

    .login-wrapper {
        flex-direction: column;
        max-width: 100%;
    }

    .login-image {
        display: none;
    }

    .login-card {
        padding: 32px 20px;
    }
}
</style>

</head>

<body>

<a href="index.php" class="back-button">
    Back
</a>

<?php
// ERROR MODAL - LOGIN FAILED
if(isset($_SESSION['error'])){
?>
<div class="modal-error" id="errorModal">
    <div class="modal-box">
        <h3>Login Failed</h3>
        <p><?php echo $_SESSION['error']; ?></p>
        <button onclick="closeModal()">OK</button>
    </div>
</div>
<?php
unset($_SESSION['error']);
}

// SUCCESS MODAL - EMAIL VERIFICATION
if(isset($_SESSION['success'])){
?>
<div class="success-modal" id="successModal">
    <div class="success-box">
        <h3>Success</h3>
        <p><?php echo $_SESSION['success']; ?></p>
        <button onclick="closeSuccessModal()">OK</button>
    </div>
</div>
<?php
unset($_SESSION['success']);
}

// SUCCESS MODAL - REGISTRATION
if(isset($_GET['success'])){
?>
<div class="success-modal" id="successModal">
    <div class="success-box">
        <h3>Success</h3>
        <p>Registration Successful! Please login.</p>
        <button onclick="closeSuccessModal()">OK</button>
    </div>
</div>
<?php
}

// SUCCESS MODAL - RESET PASSWORD
if(isset($_GET['reset'])){
?>
<div class="success-modal" id="successModal">
    <div class="success-box">
        <h3>Success</h3>
        <p>Password changed successfully. Please login.</p>
        <button onclick="closeSuccessModal()">OK</button>
    </div>
</div>
<?php
}
?>

<div class="login-wrapper">

    <!-- LEFT SIDE IMAGE -->
    <div class="login-image">
        <img src="assets/images/login-illustration.png" alt="Login Illustration">
    </div>

    <!-- LOGIN CARD -->
    <div class="login-card">
        <img src="assets/images/logo1.png" alt="eRegistrar Logo" class="logo">
        
        <h2><span class="brand-e">e</span><span class="brand-registrar">Registrar</span></h2>

        <form action="login_process.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-icon-wrapper">
                    <!-- ENVELOPE SVG ICON -->
                    <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon-wrapper">
                    <!-- LOCK SVG ICON -->
                    <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>

            <button type="submit">Login</button>
        </form>

        <p class="text-center" style="margin-top: 16px;">
            <a href="forgot_password.php">Forgot Password?</a>
        </p>

        <p class="text-center">
            Don't have an account? <a href="register.php">Register Here</a>
        </p>
    </div>

</div>

<script>
function closeModal(){
    document.getElementById("errorModal").style.display = "none";
}

function closeSuccessModal(){
    document.getElementById("successModal").style.display = "none";
}
</script>

</body>

</html>