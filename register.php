<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>eRegistrar | Registration</title>
    <link rel="icon" type="image/png" href="assets/images/logooo.png">
    <link rel="stylesheet" href="assets/css/register.css?v=999">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>

<body>

<?php
if(isset($_SESSION['error'])){
?>

<div class="modal-error" id="errorModal">

    <div class="modal-box">

        <i class="fa-solid fa-circle-xmark error-icon"></i>

        <h3>Registration Failed</h3>

        <p><?php echo $_SESSION['error']; ?></p>

        <button onclick="closeModal()">
            OK
        </button>

    </div>

</div>

<?php
unset($_SESSION['error']);
}
?>

<div class="register-container">

    <!-- LEFT PANEL -->

    <div class="left-panel">

        <img src="assets/images/logosss.png" class="logo">

        <h1>Welcome to eRegistrar</h1>

        <p>
            Register your account to request school documents online.
        </p>

        <img src="assets/images/register.png"
             class="illustration">

    </div>


    <!-- RIGHT PANEL -->

    <div class="right-panel">
        <div class="mobile-header">



</div>
<div class="mobile-hero">

    <img src="assets/images/logosss.png" class="mobile-logo">

    <h2>Welcome to eRegistrar</h2>

    <p>
        Register your account to request school documents online.
    </p>

    <img src="assets/images/register.png"
         class="mobile-illustration">

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
            ============================ -->

            <div
                class="form-step active"
                id="step1">

                <?php include "includes/register/step1_account.php"; ?>

            </div>


            <!-- ===========================
                 STEP 2
            ============================ -->

            <div
                class="form-step"
                id="step2">

                <?php include "includes/register/step2_level.php"; ?>

            </div>


            <!-- ===========================
                 STEP 3
            ============================ -->

            <div
                class="form-step"
                id="step3">

                <?php include "includes/register/step3_student.php"; ?>

                <?php include "includes/register/step3_parent.php"; ?>

            </div>


            <!-- ===========================
                 STEP 4
            ============================ -->

            <div
                class="form-step"
                id="step4">

                <?php include "includes/register/step4_review.php"; ?>

            </div>

        </form>

    </div>

</div>

<script src="assets/js/register.js"></script>

<script>

function closeModal(){

    document.getElementById("errorModal").style.display="none";
    

}

</script>

</body>
</html>