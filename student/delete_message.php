<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}



$user_id = $_SESSION['user_id'];



if(isset($_GET['id'])){


    $message_id = $_GET['id'];



    mysqli_query($conn,

    "
    DELETE FROM messages

    WHERE message_id='$message_id'

    AND sender_id='$user_id'

    "

    );


}



header("Location: messages.php");

exit();


?>