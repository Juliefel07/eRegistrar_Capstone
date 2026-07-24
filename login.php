<?php

session_start();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>eRegistrar Login</title>

<link rel="stylesheet" href="assets/css/style.css">

</head>


<body>


<?php

// ERROR MODAL

if(isset($_SESSION['error'])){

?>

<div class="modal-error" id="errorModal">

    <div class="modal-box">

        <h3>
            Login Failed
        </h3>


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



// SUCCESS MODAL - REGISTRATION

if(isset($_GET['success'])){

?>

<div class="success-modal" id="successModal">

    <div class="success-box">

        <h3>
            Success
        </h3>


        <p>
            Registration Successful! Please login.
        </p>


        <button onclick="closeSuccessModal()">
            OK
        </button>


    </div>

</div>


<?php

}



// SUCCESS MODAL - RESET PASSWORD

if(isset($_GET['reset'])){

?>

<div class="success-modal" id="successModal">

    <div class="success-box">

        <h3>
            Success
        </h3>


        <p>
            Password changed successfully. Please login.
        </p>


        <button onclick="closeSuccessModal()">
            OK
        </button>


    </div>

</div>


<?php

}

?>



<div class="login-wrapper">
        <a href="index.php" class="back-button">
            Back 
        </a>



    <!-- LEFT SIDE IMAGE -->

    <div class="login-image">

        <img src="assets/images/login-illustration.png"
             alt="Login Illustration">

    </div>




    <!-- LOGIN CARD -->

    <div class="login-card">


        <img src="assets/images/logosss.png"
             alt="eRegistrar Logo"
             class="logo">



        <h2>
            eRegistrar
        </h2>




        <form action="login_process.php" method="POST">


            <label>
                Email Address
            </label>


            <input 
            type="email"
            name="email"
            placeholder="Enter your email"
            required>




            <label>
                Password
            </label>


            <input 
            type="password"
            name="password"
            placeholder="Enter your password"
            required>




            <button type="submit">

                Login

            </button>



        </form>




        <p class="text-center">

            <a href="forgot_password.php">
                Forgot Password?
            </a>

        </p>




        <p class="text-center">

            Don't have an account?

            <a href="register.php">
                Register Here
            </a>

        </p>



    </div>



</div>




<script>


function closeModal(){

    document.getElementById("errorModal").style.display="none";

}



function closeSuccessModal(){

    document.getElementById("successModal").style.display="none";

}


</script>



</body>

</html>