<?php


function createNotification($conn, $user_id, $message)
{

    $message = mysqli_real_escape_string($conn, $message);


    $sql = "

    INSERT INTO notifications
    (
        user_id,
        message
    )

    VALUES
    (
        '$user_id',
        '$message'
    )

    ";


    mysqli_query($conn,$sql);


}

?>