<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}


$admin_id = $_SESSION['user_id'];



// SELECT STUDENT

$student_id = $_GET['student_id'] ?? null;




// SEND REPLY

if(isset($_POST['send']) && $student_id){


    $message = mysqli_real_escape_string(
        $conn,
        trim($_POST['message'])
    );


    if(!empty($message)){


        mysqli_query($conn,

        "
        INSERT INTO messages
        (
            sender_id,
            receiver_id,
            message,
            status,
            created_at
        )

        VALUES

        (
            '$admin_id',
            '$student_id',
            '$message',
            'Unread',
            NOW()
        )

        "

        );


    }


    header("Location: messages.php?student_id=".$student_id);

    exit();

}





// GET STUDENTS WHO MESSAGED ADMIN


$students = mysqli_query($conn,

"

SELECT DISTINCT

users.user_id,
users.fullname,
users.profile_image

FROM messages


INNER JOIN users

ON messages.sender_id = users.user_id


WHERE

messages.receiver_id='$admin_id'


AND users.role='Student'


"

);






// GET CHAT


$chat = null;


$student_name = "";


if($student_id){



$name_query = mysqli_query($conn,

"
SELECT fullname, profile_image
FROM users
WHERE user_id='$student_id'

"

);


$name_data=mysqli_fetch_assoc($name_query);


$student_name=$name_data['fullname'];





$chat=mysqli_query($conn,

"

SELECT *

FROM messages

WHERE


(sender_id='$student_id'

AND receiver_id='$admin_id')


OR


(sender_id='$admin_id'

AND receiver_id='$student_id')


ORDER BY created_at ASC


"

);



}


?>



<!DOCTYPE html>

<html>


<head>


<title>
Messages
</title>


<link rel="stylesheet" href="../assets/css/admin.css">


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


</head>



<body>



<?php include("sidebar.php"); ?>

<?php include("header.php"); ?>





<div class="admin-content">


<div class="admin-messages">






<!-- STUDENT LIST -->


<div class="student-list">



<div class="list-title">

<i class="fa-solid fa-comments"></i>

Messages

</div>





<?php while($s=mysqli_fetch_assoc($students)){ ?>



<a href="messages.php?student_id=<?php echo $s['user_id']; ?>"

class="student-item 
<?php echo ($student_id==$s['user_id'])?'active':''; ?>">



<div class="student-avatar">

<?php if (!empty($s['profile_image'])) { ?>

    <img src="../student/uploads/<?php echo htmlspecialchars($s['profile_image']); ?>"
         class="student-avatar-img">

<?php } else { ?>

    <?php echo strtoupper(substr($s['fullname'],0,1)); ?>

<?php } ?>

</div>


<div>

<strong>

<?php echo htmlspecialchars($s['fullname']); ?>

</strong>


<small>
Open conversation
</small>


</div>


</a>



<?php } ?>



</div>








<!-- CHAT -->

<div class="admin-chat">



<?php if($chat){ ?>



<div class="admin-chat-header">

<div class="student-avatar">

<?php if (!empty($name_data['profile_image'])) { ?>

    <img src="../student/uploads/<?php echo htmlspecialchars($name_data['profile_image']); ?>"
         class="student-avatar-img">

<?php } else { ?>

    <?php echo strtoupper(substr($student_name,0,1)); ?>

<?php } ?>

</div>

<h3><?php echo htmlspecialchars($student_name); ?></h3>

</div>





<div class="admin-chat-body" id="chatBox">





<?php while($row=mysqli_fetch_assoc($chat)){ ?>



<?php if($row['sender_id']==$admin_id){ ?>



<div class="admin-row right">


<div class="admin-bubble me">


<?php echo nl2br(htmlspecialchars($row['message'])); ?>


<small>

<?php echo $row['created_at']; ?>

</small>


</div>


</div>



<?php }else{ ?>



<div class="admin-row left">


<div class="admin-bubble student">


<?php echo nl2br(htmlspecialchars($row['message'])); ?>


<small>

<?php echo $row['created_at']; ?>

</small>


</div>


</div>



<?php } ?>



<?php } ?>




</div>






<form method="POST" class="admin-chat-input">


<textarea

id="messageBox"

name="message"

placeholder="Type your reply..."

required></textarea>




<button

name="send"

id="sendButton">


<i class="fa-solid fa-paper-plane"></i>


</button>



</form>




<?php }else{ ?>



<div class="empty-admin-chat">


<h2>
Select a student
</h2>


<p>
Choose a conversation from the left.
</p>


</div>



<?php } ?>





</div>




</div>


</div>







<script>


let chat=document.getElementById("chatBox");


if(chat){

chat.scrollTop=chat.scrollHeight;

}



let box=document.getElementById("messageBox");


if(box){


box.addEventListener("keydown",function(e){


if(e.key==="Enter" && !e.shiftKey){


e.preventDefault();


document.getElementById("sendButton").click();


}


});


}



</script>




</body>


</html>