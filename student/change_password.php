<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}


$user_id = $_SESSION['user_id'];



if(isset($_POST['change_password'])){


    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];



    $result = mysqli_query($conn,"
        SELECT password 
        FROM users
        WHERE user_id='$user_id'
    ");


    $user = mysqli_fetch_assoc($result);



    if(password_verify($current,$user['password'])){


        if($new == $confirm){


            $hashed = password_hash($new,PASSWORD_DEFAULT);


            mysqli_query($conn,"
                UPDATE users
                SET password='$hashed'
                WHERE user_id='$user_id'
            ");


            $message = "Password changed successfully.";


        }else{

            $message = "New passwords do not match.";

        }


    }else{

        $message = "Current password is incorrect.";

    }


}


?>


<!DOCTYPE html>
<html>

<head>

<title>Change Password</title>

<link rel="stylesheet" href="../assets/css/student.css">

</head>


<body>


<?php include "sidebar.php"; ?>

<?php include "header.php"; ?>


<div class="student-main">


<div class="password-card">


<h2>
Change Password
</h2>


<?php if(isset($message)){ ?>

<p class="password-message">
    <?php echo $message; ?>
</p>

<?php } ?>



<form method="POST" class="password-form">


<input 
type="password"
name="current_password"
placeholder="Current Password"
required>



<input 
type="password"
name="new_password"
placeholder="New Password"
required>





<input 
type="password"
name="confirm_password"
placeholder="Confirm New Password"
required>


<br>


<button 
type="submit"
name="change_password">

Change Password

</button>


</form>


</div>


</div>


</body>

</html>