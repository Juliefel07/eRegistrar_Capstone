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
    $api_key = getenv('BREVO_API_KEY'); // Or your literal API key string
    $email_data = [
        "sender" => ["name" => "eRegistrar", "email" => "eregistrarcctc@gmail.com"],
        "to" => [["email" => $email]],
        "subject" => "New Verification Number - eRegistrar",
        "htmlContent" => "
            <div style='font-family: Arial, sans-serif; padding: 25px; color: #333; max-width: 500px; margin: auto; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #ff5722;'>New Verification Number</h2>
                <p>Your new verification number is:</p>
                <div style='background-color: #f8f9fa; padding: 15px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                    <span style='font-size: 38px; font-weight: bold; letter-spacing: 5px; color: #ff5722;'>$new_number</span>
                </div>
            </div>
        "
    ];
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['accept: application/json', 'api-key: ' . $api_key, 'content-type: application/json']);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification</title>
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .circle-grid {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
            gap: 12px !important;
            margin: 30px 0 !important;
            width: 100% !important;
        }

        .circle-btn {
            width: 60px !important;
            min-width: 60px !important;
            height: 60px !important;
            max-height: 60px !important;
            flex-shrink: 0 !important;
            border-radius: 50% !important;
            background: transparent !important;
            background-color: transparent !important;
            border: 2px solid #555555 !important;
            font-size: 20px !important;
            font-weight: 600 !important;
            color: #333333 !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 !important;
            line-height: 1 !important;
            outline: none !important;
            box-shadow: none !important;
            transition: all 0.25s ease-in-out !important;
        }

        .circle-btn:hover {
            border-color: #2017cb !important;
            color: #1453dc !important;
            background: rgba(32, 23, 203, 0.05) !important;
            transform: translateY(-2px) !important;
        }

        /* Mobile view responsiveness to keep them perfectly round */
        @media (max-width: 480px) {
            .circle-grid {
                gap: 8px !important;
            }
            .circle-btn {
                width: 50px !important;
                min-width: 50px !important;
                height: 50px !important;
                max-height: 50px !important;
                font-size: 17px !important;
                border-width: 1.5px !important;
            }
        }
    </style>
</head>
<body>

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
<div class="modal-error" id="successModal">
    <div class="modal-box">
        <h3>Success</h3>
        <p><?php echo $_SESSION['success']; ?></p>
        <button onclick="closeModal('successModal')">OK</button>
    </div>
</div>
<?php unset($_SESSION['success']); endif; ?>

<div class="login-wrapper">
    <div class="login-image">
        <img src="assets/images/login-illustration.png" alt="Verification">
    </div>
    <div class="login-card">
        <img src="assets/images/logosss.png" alt="eRegistrar Logo" class="logo">
        <h2 style="text-align: center;">Verification</h2>
        <p class="description" style="text-align: center;">Enter Verification Code</p>

        <form action="verify_reset_code.php" method="POST">
            <div class="circle-grid">
                <?php foreach ($choices as $num): ?>
                    <button type="submit" name="selected_number" value="<?php echo $num; ?>" class="circle-btn">
                        <?php echo sprintf('%02d', $num); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <p class="text-center" style="font-size: 13px; margin: 15px 0; color: #777;">
                If you didn't receive a code, <a href="verify_reset_code.php?resend=true" style="color: #1c08b1; font-weight: bold; text-decoration: none;">Resend</a>
            </p>
        </form>

        <p class="text-center" style="margin-top: 20px;">
            <a href="forgot_password.php">Back</a>
        </p>
    </div>
</div>

<script>
function closeModal(id) { document.getElementById(id).style.display = "none"; }
</script>
</body>
</html>