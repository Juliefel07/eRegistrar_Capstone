<?php

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification.php";


if(!isset($_GET['id'])){

die("Invalid request.");

}


$id=intval($_GET['id']);



$get=mysqli_query($conn,

"SELECT user_id,tracking_no
FROM requests
WHERE request_id='$id'"

);



$data=mysqli_fetch_assoc($get);



if(!$data){

die("Request not found.");

}



$sql="

UPDATE requests

SET status='Claimed',
claim_date=CURDATE()

WHERE request_id='$id'

";



if(mysqli_query($conn,$sql)){


createNotification(

$conn,

$data['user_id'],

"Your request ".$data['tracking_no']." has been successfully claimed."

);



header("Location: requests.php");

exit();


}else{

echo mysqli_error($conn);

}


?>