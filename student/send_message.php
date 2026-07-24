<?php

session_start();

include("../config/db.php");


if(isset($_POST['message'])){


$user = $_SESSION['user_id'];

$message = $_POST['message'];


// Registrar ID example

$registrar = 1;



$sql = "INSERT INTO messages
(sender_id,receiver_id,message)
VALUES(?,?,?)";


$stmt=$conn->prepare($sql);


$stmt->bind_param(
"iis",
$user,
$registrar,
$message
);



$stmt->execute();



header("Location: messages.php");


}

?>