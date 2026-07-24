<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}



$student_id = $_SESSION['user_id'];



$request_id = $_POST['request_id'];

$payment_method = $_POST['payment_method'];

$reference_number = $_POST['reference_number'];





// Upload receipt

$file_name = $_FILES['payment_proof']['name'];

$file_tmp = $_FILES['payment_proof']['tmp_name'];





$upload_folder = "../assets/uploads/payments/";



// create folder if missing

if(!is_dir($upload_folder)){

    mkdir($upload_folder,0777,true);

}




$new_file_name = time() . "_" . $file_name;


$target = $upload_folder . $new_file_name;



move_uploaded_file($file_tmp,$target);





// Save payment record


$sql = "

INSERT INTO payments

(

student_id,

request_id,

payment_method,

reference_number,

proof_file,

status

)

VALUES

(

'$student_id',

'$request_id',

'$payment_method',

'$reference_number',

'$new_file_name',

'Pending'

)

";





if(mysqli_query($conn,$sql)){



    // add notification


   $notification = mysqli_query($conn,

"

INSERT INTO notifications

(user_id,message,status)

VALUES

('$student_id','$message','Unread')

"

);


if(!$notification){

    echo mysqli_error($conn);
    exit();

}





    header("Location: payments.php?success=1");

    exit();



}

else{


    echo "Payment submission failed.";

}


?>