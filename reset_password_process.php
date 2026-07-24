<?php

session_start();

require_once "includes/db.php";


// Check reset session

if(!isset($_SESSION['reset_user'])){

    header("Location: login.php");
    exit();

}



// Get user id

$user_id = $_SESSION['reset_user'];



// Get form data

$password = $_POST['password'];

$confirm = $_POST['confirm_password'];

$captcha = $_POST['captcha'];

$human_check = $_POST['human_check'] ?? '';





// Verify captcha

if($captcha != $_SESSION['captcha']){


    $_SESSION['error'] = "Invalid captcha code.";


    header("Location: reset_password.php");

    exit();

}





// Verify human checkbox

$human_check = $_POST['human_check'] ?? '';

if ($human_check !== 'yes') {
    $_SESSION['error'] = "Please verify that you are human.";
    header("Location: reset_password.php");
    exit();
}




// Check password match

if($password != $confirm){


    $_SESSION['error'] = "Passwords do not match.";


    header("Location: reset_password.php");

    exit();

}





// Encrypt password

$hashed_password = password_hash(

    $password,

    PASSWORD_DEFAULT

);






// Update password

$update = mysqli_query($conn,


"

UPDATE users

SET password='$hashed_password'

WHERE user_id='$user_id'

"


);







if($update){


    // Clear reset sessions

    unset($_SESSION['reset_user']);

    unset($_SESSION['captcha']);



    header("Location: login.php?reset=success");


    exit();



}else{


    $_SESSION['error'] = "Failed to update password.";


    header("Location: reset_password.php");


    exit();


}



?>