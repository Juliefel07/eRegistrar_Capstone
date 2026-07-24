<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}


$user_id = $_SESSION['user_id'];




// GET ADMIN ACCOUNT

$admin_query = mysqli_query($conn,

"
SELECT user_id
FROM users
WHERE role='Admin'
LIMIT 1
"

);


$admin_data=mysqli_fetch_assoc($admin_query);



if(!$admin_data){

    die("Registrar account not found.");

}



$admin_id=$admin_data['user_id'];






// SEND MESSAGE

if(isset($_POST['send'])){


$message=mysqli_real_escape_string(
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
'$user_id',
'$admin_id',
'$message',
'Unread',
NOW()
)

"

);


}



header("Location: messages.php");

exit();


}







// GET CHAT


$result=mysqli_query($conn,

"

SELECT *

FROM messages

WHERE

(sender_id='$user_id' AND receiver_id='$admin_id')

OR

(sender_id='$admin_id' AND receiver_id='$user_id')


ORDER BY created_at ASC

"

);



?>

<!DOCTYPE html>

<html>

<head>

<title>
Messages
</title>


<link rel="stylesheet" href="../assets/css/student.css">


<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


</head>


<body>


<?php include("sidebar.php"); ?>

<?php include("header.php"); ?>





<div class="student-main">


<div class="messages-page">


<div class="msg-chat-container">






<div class="msg-chat-header">


<div class="chat-user">


<div class="avatar">

<i class="fa-solid fa-user-tie"></i>

</div>



<div>

<h3>
Registrar Office
</h3>


<span>
Online
</span>


</div>


</div>


</div>







<div class="msg-chat-body" id="chatBox">



<?php while($row=mysqli_fetch_assoc($result)){ ?>



<?php if($row['sender_id']==$user_id){ ?>



<div class="message-row right">


<div class="bubble student">


<p>

<?= nl2br(htmlspecialchars($row['message'])); ?>

</p>



<small>

<?= $row['created_at']; ?>


<br>



<span class="message-actions">


<span class="message-actions">


<span class="message-actions">


<a href="#"
onclick="openEditModal(
'<?= $row['message_id']; ?>',
`<?= htmlspecialchars($row['message'], ENT_QUOTES); ?>`
); return false;">


<i class="fa-solid fa-pen"></i>

Edit


</a>



<a 
href="delete_message.php?id=<?= $row['message_id']; ?>"
onclick="return confirm('Delete this message?');">

Delete

</a>


</span>


</span>


</span>


</small>



</div>


</div>





<?php }else{ ?>





<div class="message-row left">


<div class="bubble admin">


<p>


<i class="fa-solid fa-user-tie"></i>

Registrar:

<br><br>


<?= nl2br(htmlspecialchars($row['message'])); ?>


</p>



<small>

<?= $row['created_at']; ?>


</small>


</div>


</div>




<?php } ?>



<?php } ?>




</div>







<form method="POST" class="msg-chat-input">


<textarea

id="messageBox"

name="message"

placeholder="Type your message..."

required></textarea>




<button
    type="submit"
    name="send"
    id="sendButton">


<i class="fa-solid fa-paper-plane"></i>


</button>


</form>





</div>


</div>


</div>








<script>


const box = document.getElementById("chatBox");

if (box) {
    box.scrollTop = box.scrollHeight;
}

const input = document.getElementById("messageBox");
const sendButton = document.getElementById("sendButton");

if (input) {
    input.addEventListener("keydown", function(e){

        if(e.key === "Enter" && !e.shiftKey){

            e.preventDefault();
            sendButton.click();

        }

    });
}


</script>


<div class="edit-modal" id="editModal">


<div class="edit-box">


<div class="edit-header">

<h3>
Edit Message
</h3>


<button onclick="closeEditModal()">

×


</button>


</div>





<form method="POST" action="edit_message.php">


<input 
type="hidden"
name="message_id"
id="editMessageId">





<textarea
name="message"
id="editMessageText"
required></textarea>






<div class="edit-actions">


<button 
type="button"
onclick="closeEditModal()"
class="cancel-btn">

Cancel

</button>




<button 
type="submit"
name="update"
class="save-btn">

Save

</button>


</div>



</form>


</div>


</div>
</body>

</html>