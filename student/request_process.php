<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}


$user_id = $_SESSION['user_id'];


// REQUIRED FIELDS

if(
    empty($_POST['document_id']) ||
    empty($_POST['purpose']) ||
    empty($_POST['quantity']) ||
    empty($_POST['payment_method'])
){

    die("Required fields missing.");

}



$document_id = intval($_POST['document_id']);

$purpose = mysqli_real_escape_string(
    $conn,
    $_POST['purpose']
);


$quantity = intval($_POST['quantity']);


$remarks = mysqli_real_escape_string(
    $conn,
    $_POST['remarks'] ?? ''
);



$payment_method = $_POST['payment_method'];



// STUDENT INFO

$fullname = $_POST['fullname'] ?? '';

$student_no = $_POST['student_no'] ?? '';

$course = $_POST['course'] ?? '';

$year_level = $_POST['year_level'] ?? '';

$email = $_POST['email'] ?? '';




// TRACKING NUMBER

$tracking = "REQ" . date("YmdHis");



// FILE UPLOAD
// FILE UPLOAD

$file = NULL;


if(
    isset($_FILES['requirements']) &&
    $_FILES['requirements']['error'] == 0
){


    $allowed = [
        "pdf",
        "jpg",
        "jpeg",
        "png",
        "doc",
        "docx"
    ];


    $originalName = $_FILES['requirements']['name'];


    $ext = strtolower(
        pathinfo($originalName, PATHINFO_EXTENSION)
    );


    if(!in_array($ext,$allowed)){

        die("Invalid file type.");

    }



    $file = time() . "_" . basename($originalName);



    $uploadDir = __DIR__ . "/../assets/uploads/";



    if(!is_dir($uploadDir)){

        mkdir($uploadDir,0777,true);

    }



    $uploadPath = $uploadDir . $file;



    if(move_uploaded_file(

        $_FILES['requirements']['tmp_name'],

        $uploadPath

    )){


       


    }
    else{


        die("Upload failed.");

    }


}



// INSERT REQUEST


$stmt = mysqli_prepare(

$conn,

"INSERT INTO requests

(
tracking_no,
user_id,
fullname,
student_no,
course,
year_level,
email,
document_id,
purpose,
quantity,
remarks,
uploaded_file,
payment_method
)

VALUES

(?,?,?,?,?,?,?,?,?,?,?,?,?)

"

);



mysqli_stmt_bind_param(

$stmt,

"sisssssisisis",

$tracking,
$user_id,
$fullname,
$student_no,
$course,
$year_level,
$email,
$document_id,
$purpose,
$quantity,
$remarks,
$file,
$payment_method

);




if(mysqli_stmt_execute($stmt)){


    $_SESSION['request_success'] = true;


    header("Location: request.php");

    exit();


}
else{


    die(mysqli_error($conn));


}



?>