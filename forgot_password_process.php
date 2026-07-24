<?php

session_start();

require_once __DIR__ . "/includes/db.php";


$email = $_POST['email'];


// Check email
$sql = "SELECT * FROM users WHERE email='$email'";

$result = mysqli_query($conn, $sql);



if(mysqli_num_rows($result) == 0){

    $_SESSION['error'] = "Email address is not registered.";

    header("Location: forgot_password.php");
    exit();

}


// Email exists

$user = mysqli_fetch_assoc($result);

$_SESSION['reset_user'] = $user['user_id'];
header("Location: reset_password.php");
exit();

?>