<?php

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification.php";



if(!isset($_GET['id'])){

    die("Invalid request.");

}



$id = intval($_GET['id']);




// Get request owner information

$get = mysqli_query($conn,

"
SELECT 

user_id,
tracking_no

FROM requests

WHERE request_id='$id'

"

);



if(!$get){

    die(mysqli_error($conn));

}



$data = mysqli_fetch_assoc($get);



if(!$data){

    die("Request not found.");

}





// Update request status

$sql = "

UPDATE requests

SET status='Approved'

WHERE request_id='$id'

";




if(mysqli_query($conn,$sql)){



    // Create student notification

    createNotification(

        $conn,

        $data['user_id'],

        "Your request ".$data['tracking_no']." has been approved."

    );



    header("Location: requests.php");

    exit();



}else{


    echo "Error: " . mysqli_error($conn);


}



?>