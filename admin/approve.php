<?php

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification.php";



if(!isset($_GET['id'])){

    die("Invalid request.");

}



$id = intval($_GET['id']);

$check = mysqli_query($conn,"
SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status='Verified' THEN 1 ELSE 0 END) AS verified
FROM request_requirement_files
WHERE request_id='$id'
");

$counts = mysqli_fetch_assoc($check);

if($counts['total'] != $counts['verified']){

    $_SESSION['error'] = "All requirements must be verified before approval.";

header("Location: view_request.php?id=".$id);
exit();

}


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