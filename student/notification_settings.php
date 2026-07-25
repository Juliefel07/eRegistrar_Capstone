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

<title>Notification Settings</title>

<link rel="stylesheet" href="../assets/css/student.css">

</head>


<body>


<?php include "sidebar.php"; ?>

<?php include "header.php"; ?>


<div class="student-main">


<div class="settings-card">
<div class="settings-item">

<div>

<h3>
<i class="fa-solid fa-moon"></i>
Night Mode
</h3>

<p>
Switch between light and dark appearance.
</p>

</div>


<input type="checkbox" id="darkModeToggle">

</div>

<h2>
Notification Settings
</h2>


<p>
Manage how you receive updates from the Registrar.
</p>



<form method="POST">


<div class="settings-item">

<div>

<h3>
Document Updates
</h3>

<p>
Receive notifications when your request status changes.
</p>

</div>


<input type="checkbox" checked>


</div>




<div class="settings-item">

<div>

<h3>
Messages
</h3>

<p>
Receive alerts when the Registrar sends a message.
</p>

</div>


<input type="checkbox" checked>


</div>




<button class="settings-save">
Save Settings
</button>


</form>



</div>


</div>


</body>

</html>