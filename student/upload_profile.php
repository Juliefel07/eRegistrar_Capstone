<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}



$user_id = $_SESSION['user_id'];



if(isset($_FILES['profile_image'])){


$file = $_FILES['profile_image'];



$fileName = $file['name'];

$fileTmp = $file['tmp_name'];



$fileExt = strtolower(
pathinfo($fileName, PATHINFO_EXTENSION)
);



$allowed = [
"jpg",
"jpeg",
"png",
"webp"
];



if(!in_array($fileExt,$allowed)){


die("Only JPG, PNG and WEBP images allowed.");


}




$newName = "profile_".$user_id."_".time().".".$fileExt;



$uploadPath = "uploads/".$newName;



if(move_uploaded_file($fileTmp,$uploadPath)){



$sql = "

UPDATE users

SET profile_image='$newName'

WHERE user_id='$user_id'

";



if(mysqli_query($conn,$sql)){


header("Location: profile.php");

exit();


}else{


echo mysqli_error($conn);


}



}else{


echo "Upload failed";


}



}

?>