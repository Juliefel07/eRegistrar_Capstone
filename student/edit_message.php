<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}


$user_id = $_SESSION['user_id'];



if(isset($_POST['update'])){


    $message_id = $_POST['message_id'];


    $message = mysqli_real_escape_string(
        $conn,
        trim($_POST['message'])
    );



    mysqli_query($conn,

    "
    UPDATE messages

    SET message='$message',
        updated_at=NOW()

    WHERE message_id='$message_id'

    AND sender_id='$user_id'

    "

    );



    header("Location: messages.php");

    exit();

}



?>