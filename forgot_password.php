<?php
session_start();
?>

<!DOCTYPE html>
<html>

<head>

<title>Forgot Password</title>

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



<div class="login-wrapper">


    <div class="login-image">

        <img src="assets/images/login-illustration.png"
             alt="Forgot Password">

    </div>




    <div class="login-card">


        <img src="assets/images/logosss.png"
             alt="eRegistrar Logo"
             class="logo">



        <h2>
            Forgot Password
        </h2>



        <p class="description">
            Enter your registered email address to reset your password.
        </p>




        <form action="forgot_password_process.php" method="POST">


            <label>
                Email Address
            </label>


            <input 
            type="email"
            name="email"
            placeholder="Enter your email"
            required>



            <button type="submit">
                Continue
            </button>


        </form>



        <p class="text-center">

            <a href="login.php">
                Back to Login
            </a>

        </p>


    </div>



</div>




<script>

function closeModal(){

    document.getElementById("errorModal").style.display="none";

}

</script>


</body>

</html>