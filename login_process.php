<?php

session_start();

include "includes/db.php";


$email = mysqli_real_escape_string($conn, $_POST['email']);

$password = $_POST['password'];



$sql = "SELECT * FROM users WHERE email='$email' LIMIT 1";

$result = mysqli_query($conn, $sql);



if(mysqli_num_rows($result) == 1){


    $user = mysqli_fetch_assoc($result);



    if(password_verify($password, $user['password'])){


$_SESSION['user_id'] = $user['user_id'];

$_SESSION['fullname'] = $user['fullname'];

$_SESSION['profile_image'] = $user['profile_image'];

$_SESSION['role'] = $user['role'];



        if($user['role'] == "Admin"){

            header("Location: admin/dashboard.php");

        }else{

            header("Location: student/dashboard.php");

        }


        exit();



    }else{


        $_SESSION['error'] = "Incorrect password.";


        header("Location: login.php");

        exit();


    }



}else{


    $_SESSION['error'] = "Account not found.";


    header("Location: login.php");

    exit();


}


?>