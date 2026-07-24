<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

exit();

}



$user_id=$_SESSION['user_id'];



if(isset($_GET['id'])){


$id=$_GET['id'];



mysqli_query($conn,

"
UPDATE notifications

SET status='Read'

WHERE notification_id='$id'

AND user_id='$user_id'

"

);


}

?>