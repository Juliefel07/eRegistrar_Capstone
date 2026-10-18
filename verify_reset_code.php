<?php
session_start();
require_once __DIR__ . "/includes/db.php";

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$email_escaped = mysqli_real_escape_string($conn, $email);

// Fetch current active code/number from DB
$sql = "SELECT * FROM users WHERE email = '$email_escaped' AND token_expiry > NOW() LIMIT 1";
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Session expired. Please request a new verification.";
    header("Location: forgot_password.php");
    exit();
}
$user = mysqli_fetch_assoc($result);
$correct_number = $user['reset_token'];

// Handle Resend Request (Generates a new number)
if (isset($_GET['resend'])) {
    $new_number = rand(10, 99);
    if ($new_number == $correct_number) { $new_number = ($correct_number + 1) % 90 + 10; }
    
    $expiry = date("Y-m-d H:i:s", strtotime('+15 minutes'));
    mysqli_query($conn, "UPDATE users SET reset_token = '$new_number', token_expiry = '$expiry' WHERE email = '$email_escaped'");

    // Resend email via Brevo
    $apiKey = getenv('BREVO_API_KEY'); // Or your literal API key string
    $email_data = [
        "sender" => ["name" => "eRegistrar", "email" => "eregistrarcctc@gmail.com"],
        "to" => [["email" => $email]],
        "subject" => "New Verification Number - eRegistrar",
        "htmlContent" => "
            <div style='font-family: Arial, sans-serif; padding: 25px; color: #333; max-width: 500px; margin: auto; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #3982DC;'>New Verification Number</h2>
                <p>Your new verification number is:</p>
                <div style='background-color: #f8f9fa; padding: 15px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                    <span style='font-size: 38px; font-weight: bold; letter-spacing: 5px; color: #3982DC;'>$new_number</span>
                </div>
            </div>
        "
    ];
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['accept: application/json', 'api-key: ' . $apiKey, 'content-type: application/json']);
    curl_exec($ch);
    curl_close($ch);

    $_SESSION['success'] = "A new verification number has been sent to your email.";
    header("Location: verify_reset_code.php");
    exit();
}

// Handle Choice Selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_number = $_POST['selected_number'] ?? '';

    if ($selected_number == $correct_number) {
        $_SESSION['verified_reset'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $fallback_number = rand(10, 99);
        mysqli_query($conn, "UPDATE users SET reset_token = '$fallback_number' WHERE email = '$email_escaped'");
        
        $_SESSION['error'] = "Incorrect number selected! A new verification number has been generated. Please check your email.";
        header("Location: verify_reset_code.php");
        exit();
    }
}

// Build 4 choice buttons (1 correct, 3 random wrong numbers)
$choices = [$correct_number];
while (count($choices) < 4) {
    $rand_wrong = rand(10, 99);
    if (!in_array($rand_wrong, $choices)) {
        $choices[] = $rand_wrong;
    }
}
shuffle($choices);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <title>Verification - eRegistrar</title>
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
    /* =========================================================
       ACADEMIC BLUE DARK THEME VERIFICATION
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
        margin: 0 0 8px 0;
        letter-spacing: -0.02em;
    }

    .login-card h2 .brand-e {
        color: #3982DC;
    }

    .login-card h2 .brand-registrar {
        color: #ffffff !important;
    }

    /* DESCRIPTION SUBTITLE */
    .login-card .description {
        text-align: center;
        color: var(--text-muted);
        font-size: 0.95rem;
        margin: 0 0 20px 0;
        line-height: 1.5;
    }

    /* CIRCLE GRID FOR CODES */
    .circle-grid {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        gap: 12px !important;
        margin: 20px 0 !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }

    .circle-btn {
        width: 60px !important;
        min-width: 60px !important;
        max-width: 60px !important;
        height: 60px !important;
        min-height: 60px !important;
        max-height: 60px !important;
        flex: 0 0 60px !important;
        border-radius: 50% !important;
        background: rgba(255, 255, 255, 0.06) !important;
        border: 1px solid var(--border-dark) !important;
        font-family: 'Inter', sans-serif !important;
        font-size: 1.15rem !important;
        font-weight: 700 !important;
        color: var(--text-light) !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        margin: 0 !important;
        line-height: 1 !important;
        outline: none !important;
        box-shadow: none !important;
        box-sizing: border-box !important;
        transition: all 0.2s ease !important;
    }

    .circle-btn:hover {
        border-color: var(--bright-blue) !important;
        color: #ffffff !important;
        background: rgba(57, 130, 220, 0.2) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 14px rgba(57, 130, 220, 0.3) !important;
    }

    /* LINKS */
    .text-center {
        text-align: center;
        font-size: 0.9rem;
        color: var(--text-muted);
        margin: 16px 0 0 0;
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

    @media (max-width: 400px) {
        .circle-grid {
            gap: 8px !important;
        }
        .circle-btn {
            width: 50px !important;
            min-width: 50px !important;
            max-width: 50px !important;
            height: 50px !important;
            min-height: 50px !important;
            max-height: 50px !important;
            flex: 0 0 50px !important;
            font-size: 1rem !important;
        }
    }
    </style>
</head>

<body>

<a href="forgot_password.php" class="back-button">
    Back
</a>

<?php if (isset($_SESSION['error'])): ?>
<div class="modal-error" id="errorModal">
    <div class="modal-box">
        <h3>Error</h3>
        <p><?php echo $_SESSION['error']; ?></p>
        <button onclick="closeModal('errorModal')">OK</button>
    </div>
</div>
<?php unset($_SESSION['error']); endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<div class="success-modal" id="successModal">
    <div class="success-box">
        <h3>Success</h3>
        <p><?php echo $_SESSION['success']; ?></p>
        <button onclick="closeModal('successModal')">OK</button>
    </div>
</div>
<?php unset($_SESSION['success']); endif; ?>

<div class="login-wrapper">

    <!-- LEFT SIDE IMAGE -->
    <div class="login-image">
        <img src="assets/images/login-illustration.png" alt="Verification Illustration">
    </div>

    <!-- RIGHT SIDE CARD -->
    <div class="login-card">

        <img src="assets/images/logo1.png" alt="eRegistrar Logo" class="logo">

        <h2><span class="brand-e">e</span><span class="brand-registrar">Registrar</span></h2>
        
        <p class="description">
            Enter Verification Code
        </p>

        <form action="verify_reset_code.php" method="POST">
            <div class="circle-grid">
                <?php foreach ($choices as $num): ?>
                    <button type="submit" name="selected_number" value="<?php echo $num; ?>" class="circle-btn">
                        <?php echo sprintf('%02d', $num); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <p class="text-center">
                Didn't receive a code? <a href="verify_reset_code.php?resend=true">Resend</a>
            </p>
        </form>

    </div>

</div>

<script>
function closeModal(id) { 
    document.getElementById(id).style.display = "none"; 
}
</script>

</body>
</html>