<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <title>eRegistrar | Registration</title>
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="assets/css/register.css?v=999">

    <!-- Fonts & Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* =========================================================
   DARK ACADEMIC BLUE THEME & EXACT LOGIN CONTAINER FIT
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
    --shadow-lg: 0 25px 50px -12px rgba(15, 23, 42, 0.45);
}

* {
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

/* BACK BUTTON MATCHING LOGIN.PHP */
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
/* ==========================================
   MOBILE-FRIENDLY FLOATING ERROR MODAL STYLES
   ========================================== */
.modal-error {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(5px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 16px; /* Prevents modal from touching screen edges on mobile */
}

.modal-box {
    background: var(--card-dark, #0a192f);
    border: 1px solid var(--border-dark, rgba(255, 255, 255, 0.12));
    padding: 24px 20px;
    border-radius: var(--radius-lg, 20px);
    text-align: center;
    width: 100%;
    max-width: 360px; /* Keeps it compact on desktop */
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    animation: modalPop 0.3s ease;
}

.modal-box .error-icon {
    font-size: 2.5rem; /* Slightly smaller for mobile layout */
    color: #ef4444;
    margin-bottom: 12px;
}

.modal-box h3 {
    margin: 0 0 8px 0;
    font-size: 1.15rem;
    color: #ffffff;
}

.modal-box p {
    color: var(--text-muted, #94a3b8);
    font-size: 0.85rem;
    margin-bottom: 20px;
    line-height: 1.4;
}

.modal-box button {
    background: var(--bright-blue, #3982DC);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    width: 100%; /* Makes the button full-width and easier to tap on phones */
    cursor: pointer;
    transition: background 0.2s ease;
}

.modal-box button:hover {
    background: var(--deep-blue, #1F5CAB);
}

@keyframes modalPop {
    0% { transform: scale(0.9); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

/* Extra safety for very small mobile screens */
@media (max-width: 400px) {
    .modal-box {
        padding: 20px 16px;
    }
}
/* MAIN CONTAINER - EXACT MATCH TO LOGIN DIMENSIONS */
.register-container {
    display: flex;
    width: 100%;
    max-width: 950px;
    height: 560px;
    background: var(--card-dark);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-dark);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    margin: auto;
}

/* LEFT PANEL */
.left-panel {
    flex: 0.9;
    background: linear-gradient(135deg, #0f2d59 0%, #1F5CAB 100%);
    padding: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    border-right: 1px solid var(--border-dark);
}

.left-panel .logo {
    width: 55px;
    height: 55px;
    object-fit: contain;
    margin-bottom: 8px;
    filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
}

.left-panel h1 {
    font-size: 1.45rem;
    font-weight: 800;
    margin: 0 0 4px 0;
    letter-spacing: -0.02em;
    color: #ffffff;
}

.left-panel p {
    color: var(--light-sky);
    font-size: 0.82rem;
    line-height: 1.35;
    margin: 0 0 12px 0;
    max-width: 250px;
}

.left-panel .illustration {
    max-width: 60%;
    height: auto;
    object-fit: contain;
    filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.35));
}

/* RIGHT PANEL */
.right-panel {
    flex: 1.1;
    padding: 20px 28px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background-color: var(--card-dark);
    overflow: hidden;
}

/* MOBILE HERO HEADER (HIDDEN ON DESKTOP) */
.mobile-hero {
    display: none;
    text-align: center;
    margin-bottom: 12px;
}

.mobile-hero .mobile-logo {
    width: 50px;
    height: 50px;
    object-fit: contain;
    margin-bottom: 4px;
}

.mobile-hero h2 {
    font-size: 1.3rem;
    font-weight: 800;
    margin: 0 0 2px 0;
    color: #ffffff;
}

.mobile-hero p {
    color: var(--text-muted);
    font-size: 0.78rem;
    margin: 0;
}

/* COMPACT STEPPER PROGRESS BAR */
.progress {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 2px;
    margin-bottom: 12px;
    padding: 0;
}

.progress .step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    position: relative;
    z-index: 2;
}

.progress .step .circle {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid var(--border-dark);
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.progress .step span {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.progress .step.active .circle {
    background: var(--bright-blue);
    border-color: #60a5fa;
    color: #ffffff;
    box-shadow: 0 0 12px rgba(57, 130, 220, 0.5);
}

.progress .step.active span {
    color: #ffffff;
}

.progress .line {
    flex: 1;
    height: 2px;
    background: rgba(255, 255, 255, 0.1);
    margin: 0 6px;
    margin-bottom: 14px;
    border-radius: 2px;
}

/* FORM STEP DISPLAY LOGIC (PREVENTS OVERLAPPING) */
.form-step {
    display: none !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    height: 100% !important;
}

.form-step.active {
    display: flex !important;
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

    .register-container {
        flex-direction: column;
        max-width: 100%;
        height: auto;
    }

    .left-panel {
        display: none;
    }

    .mobile-hero {
        display: block;
    }

    .right-panel {
        padding: 24px 16px;
        overflow-y: auto;
    }
}
</style>

</head>

<body>

<?php
if(isset($_SESSION['error'])){
?>

<div class="modal-error" id="errorModal">

    <div class="modal-box">

        <i class="fa-solid fa-circle-xmark error-icon"></i>

        <h3>Registration Failed</h3>

        <p><?php echo htmlspecialchars($_SESSION['error']); ?></p>

        <button onclick="closeModal()">
            OK
        </button>

    </div>

</div>

<?php
unset($_SESSION['error']);
}
?>

<a href="index.php" class="back-button">
    <i class="fa-solid fa-arrow-left"></i> Back to Home
</a>

<div class="register-container">

    <!-- LEFT PANEL -->

    <div class="left-panel">

        <img src="assets/images/logo1.png" class="logo" alt="eRegistrar Logo">

        <h1>Welcome to <span style="color: #3982DC;">e</span>Registrar</h1>

        <p>
            Register your account to request school documents online.
        </p>

        <img src="assets/images/register.png" class="illustration" alt="Register Illustration">

    </div>


    <!-- RIGHT PANEL -->

    <div class="right-panel">
        <div class="mobile-header"></div>

        <div class="mobile-hero">

            <img src="assets/images/logo1.png" class="mobile-logo" alt="eRegistrar Logo">

            <h2>Welcome to <span style="color: #3982DC;">e</span>Registrar</h2>

            <p>
                Register your account to request school documents online.
            </p>

        </div>

        <form
            action="register_process.php"
            method="POST"
            id="registerForm">

            <!-- Progress -->

            <div class="progress">

                <div class="step active">

                    <div class="circle">1</div>

                    <span>Account</span>

                </div>

                <div class="line"></div>

                <div class="step">

                    <div class="circle">2</div>

                    <span>Level</span>

                </div>

                <div class="line"></div>

                <div class="step">

                    <div class="circle">3</div>

                    <span>Details</span>

                </div>

                <div class="line"></div>

                <div class="step">

                    <div class="circle">4</div>

                    <span>Review</span>

                </div>

            </div>


            <!-- ===========================
                STEP 1
            =========================== -->

            <div
                class="form-step active"
                id="step1">

                <?php include "includes/register/step1_account.php"; ?>

            </div>


            <!-- ===========================
                STEP 2
            =========================== -->

            <div
                class="form-step"
                id="step2">

                <?php include "includes/register/step2_level.php"; ?>

            </div>


            <!-- ===========================
                STEP 3
            =========================== -->

            <div
                class="form-step"
                id="step3">

                <?php include "includes/register/step3_student.php"; ?>

                <?php include "includes/register/step3_parent.php"; ?>

            </div>


            <!-- ===========================
                STEP 4
            =========================== -->

            <div
                class="form-step"
                id="step4">

                <?php include "includes/register/step4_review.php"; ?>

            </div>

        </form>

    </div>

</div>

<script src="assets/js/register.js?v=<?php echo time(); ?>"></script>

<script>

function closeModal(){

    const modal = document.getElementById("errorModal");
    if (modal) {
        modal.style.display = "none";
    }

}

</script>

</body>
</html>