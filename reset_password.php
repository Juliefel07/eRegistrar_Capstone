<?php

session_start();


if(!isset($_SESSION['reset_user'])){

    header("Location: login.php");
    exit();

}


// Generate captcha

$captcha = substr(
    str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"),
    0,
    6
);


$_SESSION['captcha'] = $captcha;


?>


<!DOCTYPE html>
<html lang="en">

<head>

<title>Reset Password</title>

<link rel="stylesheet" href="assets/css/style.css">

</head>


<body>


<?php

if(isset($_SESSION['error'])){

?>

<div class="modal-error" id="errorModal">

    <div class="modal-box">

        <h3>Error</h3>

        <p>
            <?php echo $_SESSION['error']; ?>
        </p>

        <button onclick="closeModal()">
            OK
        </button>

    </div>

</div>


<?php

unset($_SESSION['error']);

}

?>



<div class="reset-wrapper">
        <a href="index.php" class="back-button">
            Back 
        </a>



    <!-- LEFT IMAGE -->

    <div class="login-image">

        <img src="assets/images/reset-illustration.png"
             alt="Reset Password">

    </div>





    <!-- RIGHT FORM -->

    <div class="reset-card">



        <img src="assets/images/logosss.png"
             alt="eRegistrar Logo"
             class="logo">



        <h2>
            Reset Password
        </h2>



        <p class="description">
            Create your new password.
        </p>





        <form action="reset_password_process.php" method="POST">



            <label>
                New Password
            </label>


            <input
            type="password"
            name="password"
            placeholder="Enter new password"
            required>





            <label>
                Confirm Password
            </label>


            <input
            type="password"
            name="confirm_password"
            placeholder="Confirm password"
            required>





            <label>
                Captcha Code
            </label>


<div class="captcha-box">


    <input
    type="text"
    name="captcha"
    placeholder="Enter code"
    required>


    <span class="captcha">

        <?php echo $_SESSION['captcha']; ?>

    </span>


</div>

<div class="human-check">

    <label class="robot-box">

        <input
            type="checkbox"
            name="human_check"
            value="yes"
            >

        <span class="checkmark"></span>

        <span class="robot-text">
            I'm not a robot
        </span>

        <img src="assets/images/recaptcha.png"
             alt="Captcha"
             class="captcha-logo">

    </label>

</div>





            <button type="submit">

                Change Password

            </button>



        </form>





<p class="text-center login-footer">

    Remember your password?

    <a href="login.php">
        Login Here
    </a>

</p>


    </div>



</div>





<script>

function closeModal(){

    document.getElementById("errorModal").style.display="none";

}

</script>


<div id="errorModal" class="modal">
    <div class="modal-content">
        <h3>Error</h3>
        <p id="modalMessage"></p>

        <button type="button" onclick="closeModal()">
            OK
        </button>
    </div>
</div>
</body>

</html>