<?php

session_start();

require_once __DIR__ . "/../includes/db.php";

if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}



$user_id=$_SESSION['user_id'];



$result=mysqli_query($conn,

"

SELECT *

FROM notifications

WHERE user_id='$user_id'

ORDER BY created_at DESC

"

);


?>


<!DOCTYPE html>
<html>

<head>

<title>Notifications</title>

<link rel="stylesheet" href="../assets/css/student.css">

</head>


<body>


<?php include("sidebar.php"); ?>

<?php include("header.php"); ?>



<div class="student-main">


<div class="form-card">


<h2>
Notifications 
</h2>


<p>
Updates about your document requests.
</p>


<?php if(mysqli_num_rows($result) > 0){ ?>


<?php while($row=mysqli_fetch_assoc($result)){ ?>


<a href="#"
class="notification-link"
onclick="openNotification(
'<?= $row['notification_id']; ?>',
`<?= htmlspecialchars($row['message'], ENT_QUOTES); ?>`,
'<?= $row['created_at']; ?>'
); return false;">

<div class="notification-box">

<h4>

<i class="fa-solid fa-bell"></i>

Notification

</h4>



<p>

<?php echo $row['message']; ?>

</p>



<div class="notification-footer">


<small>

<?php echo $row['created_at']; ?>

</small>



<span class="notification-status">

<?php echo $row['status']; ?>

</span>


</div>

</a>



</div>


<?php } ?>


<?php } else { ?>


<div class="notification-box empty">


<p>
No notifications available.
</p>


</div>


<?php } ?>


</div>


</div>

<div id="notificationModal" class="notification-modal">

    <div class="notification-content">

        <span class="close-modal" onclick="closeNotification()">
            &times;
        </span>

        <h3>
             Notification
        </h3>

        <p id="modalMessage"></p>

        <small id="modalDate"></small>

    </div>

</div>
<script>

function openNotification(id,message,date){



// MARK AS READ

fetch("mark_notification_read.php?id="+id);





document.getElementById("modalMessage").innerHTML = message;


document.getElementById("modalDate").innerHTML = date;



document.getElementById("notificationModal")
.classList.add("show");




}




function closeNotification(){


document.getElementById("notificationModal")
.classList.remove("show");


}




window.onclick=function(event){


let modal=document.getElementById("notificationModal");


if(event.target===modal){

closeNotification();

}


}
</script>
<div class="notification-modal" id="notificationModal">


<div class="notification-content">


<button class="close-modal"
onclick="closeNotification()">
×
</button>



<h2>

<i class="fa-solid fa-bell"></i>

Notification

</h2>



<p id="modalMessage"></p>



<div class="modal-info">

<span id="modalStatus"></span>

<br>

<small id="modalDate"></small>

</div>



</div>


</div>
<div class="notification-modal" id="notificationModal">


<div class="notification-modal-box">


<button class="notification-close"
onclick="closeNotification()">

×

</button>



<div class="notification-icon">

<i class="fa-solid fa-bell"></i>

</div>




<h2>
Notification
</h2>



<p id="modalMessage">

</p>




<div class="notification-details">


<span class="read-badge">

Read

</span>



<span id="modalDate">

</span>


</div>



</div>


</div>
</body>

</html>