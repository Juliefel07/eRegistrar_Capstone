<?php

session_start();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Student Registration</title>

<link rel="stylesheet" href="assets/css/style.css">

</head>


<body>


<?php

if(isset($_SESSION['error'])){

?>

<div class="modal-error" id="errorModal">

    <div class="modal-box">

        <h3>Registration Failed</h3>

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
<div class="register-wrapper">
        <a href="index.php" class="back-button">
            Back 
        </a>
    <!-- LEFT IMAGE -->
    <div class="register-image">
        <img src="assets/images/register.png" alt="Student Registration">
    </div>


    <!-- RIGHT FORM -->
    <div class="register-form">
    <img src="assets/images/logosss.png" alt="eRegistrar Logo" class="logo">
        <h2>Student Registration</h2>

        <form action="register_process.php" method="POST">


            <div class="form-row">

                <div>
                    <label>Student Number</label>
                    <input type="text" name="student_no" required>
                </div>


                <div>
                    <label>Full Name</label>
                    <input type="text" name="fullname" required>
                </div>

            </div>



            <div class="form-row">

                <div>
                    <label>Course</label>
                    <input type="text" name="course" required>
                </div>


                <div>
                    <label>Year Level</label>
                    <select name="year_level" required>

                        <option value="">
                            Select Year Level
                        </option>

                        <option>1st Year</option>
                        <option>2nd Year</option>
                        <option>3rd Year</option>
                        <option>4th Year</option>

                    </select>

                </div>

            </div>



            <div class="form-row">

                <div>
                    <label>Contact Number</label>
                    <input type="text" name="contact_no" required>
                </div>


                <div>
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>

            </div>



            <div class="form-row">

                <div>
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>


                <div>
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

            </div>



            <button type="submit">
                Register
            </button>


        </form>


        <p class="text-center">
            Already have an account?
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
</body>

</html>