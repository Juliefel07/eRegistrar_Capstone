<?php
session_start();
require_once __DIR__ . "/includes/db.php";

// Ensure user passed the code verification step
if (!isset($_SESSION['verified_reset']) || !$_SESSION['verified_reset'] || !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Password</title>
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="assets/css/style.css">
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

<div class="login-wrapper">
    <div class="login-image">
        <img src="assets/images/reset-illustration.png" alt="New Password Illustration">
    </div>
    <div class="login-card">
        <img src="assets/images/logosss.png" alt="eRegistrar Logo" class="logo">
        <h2>New Password</h2>
        <p class="description">Create your new password.</p>

        <form action="reset_password_process.php" method="POST">
            <label>Enter New Password</label>
            <input type="password" name="password" placeholder="At least 8 characters" required>

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm password" required>

            <button type="submit" style="margin-top: 20px;">Send</button>
        </form>
    </div>
</div>

<script>
function closeModal(id) { document.getElementById(id).style.display = "none"; }
</script>
</body>
</html>