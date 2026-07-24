<?php

session_start();

include "includes/db.php";


$student_no = mysqli_real_escape_string($conn, $_POST['student_no']);
$fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
$course = mysqli_real_escape_string($conn, $_POST['course']);
$year_level = mysqli_real_escape_string($conn, $_POST['year_level']);
$contact_no = mysqli_real_escape_string($conn, $_POST['contact_no']);
$email = mysqli_real_escape_string($conn, $_POST['email']);

$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];



// Check password match

if($password != $confirm_password){

    $_SESSION['error'] = "Passwords do not match.";

    header("Location: register.php");
    exit();

}




// Check student number

$checkStudent = mysqli_query(
    $conn,
    "SELECT * FROM users WHERE student_no='$student_no'"
);



if(mysqli_num_rows($checkStudent) > 0){

    $_SESSION['error'] = "Student Number already exists.";

    header("Location: register.php");
    exit();

}




// Check email

$checkEmail = mysqli_query(
    $conn,
    "SELECT * FROM users WHERE email='$email'"
);



if(mysqli_num_rows($checkEmail) > 0){

    $_SESSION['error'] = "Email address already exists.";

    header("Location: register.php");
    exit();

}




// Hash password

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);




// Insert user

$sql = "INSERT INTO users
(student_no, fullname, course, year_level, contact_no, email, password)

VALUES

('$student_no',
'$fullname',
'$course',
'$year_level',
'$contact_no',
'$email',
'$hashedPassword')";




if(mysqli_query($conn, $sql)){


    header("Location: login.php?success=registered");

    exit();



}else{


    $_SESSION['error'] = "Registration failed. Please try again.";

    header("Location: register.php");

    exit();

}


?>