<?php
session_start();
include "includes/db.php";

// Process Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $sql = "SELECT * FROM users WHERE email='$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        // Check password against hashed password in database
        // Check password against hashed password in database
        if (password_verify($password, $user['password'])) {

            // Check email verification for non-admin accounts
            if ($user['role'] !== "Admin" && (int)$user['is_verified'] === 0) {
                $_SESSION['pending_email'] = $user['email'];
                header("Location: verify_otp.php");
                exit();
            }

            // Set Session Data
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['profile_image'] = $user['profile_image'];
            $_SESSION['role'] = $user['role'];

            // Role-based Redirection
            if ($user['role'] === "Admin") {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: student/dashboard.php");
            }
            exit();

        } else {
            $_SESSION['error'] = "Incorrect password.";
            header("Location: login.php");
            exit();
        }

    } else {
        $_SESSION['error'] = "Account not found.";
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eRegistrar Login</title>
    <link rel="icon" type="image/png" href="assets/images/logooo.png">
    <link rel="stylesheet" href="assets/css/style.css?v=999">
</head>

<body>

<?php
// ERROR MODAL - LOGIN FAILED
if (isset($_SESSION['error'])) {
?>
<div class="modal-error" id="errorModal">
    <div class="modal-box">
        <h3>Login Failed</h3>
        <p><?php echo htmlspecialchars($_SESSION['error']); ?></p>
        <button onclick="closeModal()">OK</button>
    </div>
</div>
<?php
unset($_SESSION['error']);
}

// SUCCESS MODAL - EMAIL VERIFICATION
if (isset($_SESSION['success'])) {
?>
<div class="success-modal" id="successModal">
    <div class="success-box">
        <h3>Success</h3>
        <p><?php echo htmlspecialchars($_SESSION['success']); ?></p>
        <button onclick="closeSuccessModal()">OK</button>
    </div>
</div>
<?php
unset($_SESSION['success']);
}

// SUCCESS MODAL - REGISTRATION
if (isset($_GET['success'])) {
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
if (isset($_GET['reset'])) {
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

    <a href="index.php" class="back-button">Back</a>

    <!-- LEFT SIDE IMAGE -->
    <div class="login-image">
        <img src="assets/images/login-illustration.png" alt="Login Illustration">
    </div>

    <!-- LOGIN CARD -->
    <div class="login-card">

        <img src="assets/images/logosss.png" alt="eRegistrar Logo" class="logo">

        <h2>eRegistrar</h2>

        <form action="login.php" method="POST">

            <label>Email Address</label>
            <input 
                type="email"
                name="email"
                placeholder="Enter your email"
                required>

            <label>Password</label>
            <input 
                type="password"
                name="password"
                placeholder="Enter your password"
                required>

            <button type="submit">Login</button>

        </form>

        <p class="text-center">
            <a href="forgot_password.php">Forgot Password?</a>
        </p>

        <p class="text-center">
            Don't have an account?
            <a href="register.php">Register Here</a>
        </p>

    </div>

</div>

<script>
function closeModal(){
    var modal = document.getElementById("errorModal");
    if(modal) modal.style.display="none";
}

function closeSuccessModal(){
    var modal = document.getElementById("successModal");
    if(modal) modal.style.display="none";
}
</script>

</body>
</html>