<?php 
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit();
}

?>


<!DOCTYPE html>
<html>

<head>

<title>Settings</title>

<link rel="stylesheet" href="../assets/css/student.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>


<body>


<?php include "sidebar.php"; ?>

<?php include "header.php"; ?>


<div class="student-main">


<div class="settings-card">


<h2>
<i class="fa-solid fa-gear"></i>
Settings
</h2>
<br></br>

<p class="settings-description">
Manage your account, preferences, and portal settings.
</p>





<div class="settings-section">


<div class="settings-item">

<i class="fa-solid fa-user"></i>

<div>
<h3>
Profile Information
</h3>

<p>
Update your personal information and profile picture.
</p>
</div>

<a href="profile.php">
Manage
</a>

</div>





<div class="settings-item">

<i class="fa-solid fa-lock"></i>

<div>
<h3>
Password & Security
</h3>

<p>
Change your password to keep your account secure.
</p>
</div>

<a href="change_password.php">
Change
</a>

</div>






<div class="settings-item">

<i class="fa-solid fa-bell"></i>

<div>
<h3>
Notification Settings
</h3>

<p>
Manage how you receive updates about document requests and messages.
</p>

</div>


<a href="notification_settings.php">
Manage
</a>

</div>






<div class="settings-item">

<i class="fa-solid fa-envelope"></i>

<div>
<h3>
Messages
</h3>

<p>
View conversations and contact the Registrar Office.
</p>

</div>


<a href="messages.php">
Open
</a>

</div>






<div class="settings-item">

<i class="fa-solid fa-circle-question"></i>

<div>
<h3>
Help & Support
</h3>

<p>
Find answers or send concerns to the Registrar.
</p>

</div>


<a href="support.php">
Visit
</a>

</div>








</div>



</div>


</div>
<script>

const toggle = document.getElementById("darkModeToggle");


if(localStorage.getItem("darkMode") === "enabled"){

    document.body.classList.add("dark-mode");

    toggle.checked = true;

}



toggle.addEventListener("change", function(){


    if(this.checked){


        document.body.classList.add("dark-mode");

        localStorage.setItem("darkMode","enabled");


    }else{


        document.body.classList.remove("dark-mode");

        localStorage.setItem("darkMode","disabled");


    }


});


</script>

</body>

</html>