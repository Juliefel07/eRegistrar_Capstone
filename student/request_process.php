<?php

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification.php";

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

    $_SESSION['request_error'] = "Please fill in all required fields.";

    header("Location: request.php");

    exit();

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

$file = NULL;


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
$request_id = mysqli_insert_id($conn);
if(isset($_FILES['requirements'])){

    $uploadDir = __DIR__ . "/../assets/uploads/requirements/";

    if(!is_dir($uploadDir)){
        mkdir($uploadDir,0777,true);
    }

    foreach($_FILES['requirements']['name'] as $requirement_id => $originalName){

        if($_FILES['requirements']['error'][$requirement_id] != 0){
            continue;
        }

        $ext = strtolower(pathinfo($originalName,PATHINFO_EXTENSION));

        $allowed = [
            "pdf",
            "jpg",
            "jpeg",
            "png",
            "doc",
            "docx"
        ];

        if(!in_array($ext,$allowed)){
            continue;
        }

        $newName = time()."_".$requirement_id."_".basename($originalName);

        move_uploaded_file(

            $_FILES['requirements']['tmp_name'][$requirement_id],

            $uploadDir.$newName

        );

        mysqli_query($conn,"
            INSERT INTO request_requirement_files
            (
                request_id,
                requirement_id,
                file_name,
                file_path
            )
            VALUES
            (
                '$request_id',
                '$requirement_id',
                '$newName',
                'assets/uploads/requirements/$newName'
            )
        ");

    }

}
// Notify the student
createNotification(
    $conn,
    $user_id,
    "Your request $tracking has been submitted successfully."
);

// Notify the administrator
createNotification(
    $conn,
    1,
    "$fullname submitted a new document request ($tracking)."
);

$_SESSION['request_success'] = true;

header("Location: request.php");
exit();


}
else{


    die(mysqli_error($conn));


}



?>