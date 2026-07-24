<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . "/../includes/db.php";


// ==========================
// CHECK LOGIN
// ==========================

if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}


$student_id = $_SESSION['user_id'];


// ==========================
// MESSAGE VARIABLES
// ==========================

$message = "";
$messageType = "";


// ==========================
// GET AVAILABLE DOCUMENTS
// ==========================

$documents = mysqli_query(
    $conn,

    "
    SELECT *
    FROM documents
    WHERE status='Available'
    ORDER BY document_name ASC
    "

);



// ==========================
// HANDLE UPLOAD
// ==========================

if(isset($_POST['upload'])){


    $document_id = $_POST['document_id'];



    // Get selected document name

    $docQuery = mysqli_query(

        $conn,

        "
        SELECT document_name
        FROM documents
        WHERE document_id='$document_id'
        "

    );


    $doc = mysqli_fetch_assoc($docQuery);




    if(!$doc){


        $message = "Invalid document selected.";
        $messageType = "danger";


    }

    else{



        // ==========================
        // CHECK FILE
        // ==========================


        if(isset($_FILES['file']) && $_FILES['file']['error']==0){



            $file = $_FILES['file'];



            $fileName = $file['name'];

            $fileTmp = $file['tmp_name'];

            $fileSize = $file['size'];

            $fileExt = strtolower(
                pathinfo($fileName, PATHINFO_EXTENSION)
            );



            // Allowed files

            $allowed = [
                "pdf",
                "jpg",
                "jpeg",
                "png"
            ];



            if(!in_array($fileExt,$allowed)){


                $message = "Only PDF, JPG, JPEG and PNG files are allowed.";
                $messageType="danger";


            }



            elseif($fileSize > 5000000){


                $message="File size must be below 5MB.";
                $messageType="danger";


            }



            else{



                // ==========================
                // CHECK DUPLICATE UPLOAD
                // ==========================


                $check = mysqli_query(

                    $conn,

                    "
                    SELECT *
                    FROM student_documents

                    WHERE

                    student_id='$student_id'

                    AND

                    document_id='$document_id'

                    AND

                    status!='Rejected'

                    "

                );



                if(mysqli_num_rows($check)>0){


                    $message="You already uploaded this document.";
                    $messageType="warning";


                }



                else{



                    // ==========================
                    // CREATE UPLOAD FOLDER
                    // ==========================


                    $uploadDir = "../uploads/documents/";



                    if(!is_dir($uploadDir)){


                        mkdir(
                            $uploadDir,
                            0777,
                            true
                        );


                    }





                    // ==========================
                    // UNIQUE FILE NAME
                    // ==========================


                    $newFileName =

                    time()
                    .
                    "_"
                    .
                    $student_id
                    .
                    "_"
                    .
                    basename($fileName);



                    $filePath = $uploadDir . $newFileName;






                    // ==========================
                    // MOVE FILE
                    // ==========================


                    if(move_uploaded_file(
                        $fileTmp,
                        $filePath
                    )){



                        $databasePath =
                        "uploads/documents/"
                        .
                        $newFileName;




                        // ==========================
                        // SAVE TO DATABASE
                        // ==========================


                        $insert = mysqli_query(

                            $conn,

                            "

                            INSERT INTO student_documents

                            (

                            student_id,

                            request_id,

                            document_id,

                            file_name,

                            file_path,

                            status,

                            remarks

                            )


                            VALUES

                            (

                            '$student_id',

                            '0',

                            '$document_id',

                            '$newFileName',

                            '$databasePath',

                            'Pending',

                            'Waiting for admin approval'

                            )


                            "

                        );




                        if($insert){


                            $message =
                            "Document uploaded successfully!";

                            $messageType="success";


                        }

                        else{


                            $message =
                            "Database error: "
                            .
                            mysqli_error($conn);

                            $messageType="danger";


                        }



                    }

                    else{


                        $message =
                        "Failed to upload file.";

                        $messageType="danger";


                    }


                }


            }



        }

        else{


            $message =
            "Please select a file.";

            $messageType="danger";


        }



    }



}



?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>
Upload Document
</title>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">


<link rel="stylesheet" href="../assets/css/student.css">


</head>


<body class="bg-light">



<?php include("sidebar.php"); ?>


<?php include("header.php"); ?>



<div class="student-main">



<div class="container-fluid py-4">



<div class="row justify-content-center">


<div class="col-lg-7">





<div class="card shadow border-0 upload-card">



<div class="card-header bg-primary text-white">


<h4 class="mb-0">

<i class="fas fa-cloud-arrow-up"></i>

Upload Document

</h4>


</div>





<div class="card-body p-4">





<?php if($message!=""){ ?>


<div class="alert alert-<?= $messageType ?> alert-dismissible fade show">


<i class="fas fa-info-circle"></i>


<?= $message ?>


<button 
type="button"
class="btn-close"
data-bs-dismiss="alert">

</button>


</div>



<?php } ?>







<form method="POST"
enctype="multipart/form-data">





<div class="mb-4">


<label class="form-label fw-bold">

<i class="fas fa-file"></i>

Select Document

</label>



<select 
name="document_id"
class="form-select form-select-lg"
required>



<option value="">

-- Choose Document --

</option>




<?php while($doc=mysqli_fetch_assoc($documents)){ ?>


<option value="<?= $doc['document_id'] ?>">


<?= htmlspecialchars($doc['document_name']) ?>


</option>


<?php } ?>



</select>



</div>







<div class="mb-4">



<label class="form-label fw-bold">


<i class="fas fa-upload"></i>

Choose File


</label>




<div class="upload-box">



<input

type="file"

name="file"

id="file"

class="form-control"

accept=".pdf,.jpg,.jpeg,.png"

required>


</div>



<small class="text-muted">


Allowed formats:
PDF, JPG, JPEG, PNG

<br>

Maximum size:
5MB


</small>



</div>







<div class="file-preview mt-3 d-none" id="previewBox">


<div class="alert alert-info">


<i class="fas fa-file"></i>


<span id="fileName"></span>


</div>


</div>







<div class="d-flex justify-content-between">


<a href="documents.php"

class="btn btn-secondary">


<i class="fas fa-arrow-left"></i>


Back


</a>





<button

type="submit"

name="upload"

class="btn btn-primary px-4">


<i class="fas fa-cloud-upload-alt"></i>


Upload


</button>



</div>





</form>






</div>



</div>






</div>



</div>





</div>



</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



</body>

</html>

id="m2q6vk"
<style>

/* ==========================
   UPLOAD PAGE STYLE
========================== */


.upload-card{

    border-radius:18px;

    overflow:hidden;

    transition:.3s ease;

}


.upload-card:hover{

    transform:translateY(-5px);

    box-shadow:0 15px 35px rgba(0,0,0,.15)!important;

}



.upload-card .card-header{

    padding:20px 25px;

}




/* ==========================
   FORM STYLE
========================== */


.form-label{

    color:#374151;

    font-size:15px;

}



.form-select,
.form-control{

    border-radius:12px;

    padding:12px;

    border:1px solid #d1d5db;

    transition:.3s;

}



.form-select:focus,
.form-control:focus{

    border-color:#4f46e5;

    box-shadow:0 0 0 .2rem rgba(79,70,229,.15);

}





/* ==========================
   UPLOAD BOX
========================== */


.upload-box{

    background:#f8fafc;

    border:2px dashed #cbd5e1;

    padding:25px;

    border-radius:15px;

    text-align:center;

    transition:.3s;

}



.upload-box:hover{

    border-color:#4f46e5;

    background:#eef2ff;

}



.upload-box input{

    cursor:pointer;

}





/* ==========================
   BUTTONS
========================== */


.btn{

    border-radius:10px;

    font-weight:500;

    transition:.3s;

}



.btn-primary{

    background:#4f46e5;

    border:none;

}



.btn-primary:hover{

    background:#4338ca;

    transform:translateY(-2px);

}



.btn-secondary:hover{

    transform:translateY(-2px);

}





/* ==========================
   FILE PREVIEW
========================== */


#previewBox{

    animation:fade .3s ease;

}



@keyframes fade{


from{

opacity:0;

transform:translateY(10px);

}


to{

opacity:1;

transform:translateY(0);

}


}




/* ==========================
   MOBILE
========================== */


@media(max-width:768px){


.student-main{

    margin-left:0!important;

    padding:20px!important;

}



.upload-card{

    margin-top:20px;

}



}





</style>




<script>


// ==========================
// FILE PREVIEW
// ==========================


const fileInput = document.getElementById("file");

const previewBox = document.getElementById("previewBox");

const fileName = document.getElementById("fileName");



fileInput.addEventListener(
"change",
function(){


let file=this.files[0];



if(file){


fileName.innerHTML =

file.name +

" (" +

(file.size / 1024 / 1024).toFixed(2)

+

" MB)";



previewBox.classList.remove("d-none");



}



}

);





// ==========================
// DRAG AND DROP
// ==========================


const uploadBox =
document.querySelector(".upload-box");



uploadBox.addEventListener(
"dragover",
function(e){

e.preventDefault();

uploadBox.style.background="#e0e7ff";

uploadBox.style.borderColor="#4f46e5";


});




uploadBox.addEventListener(
"dragleave",
function(){


uploadBox.style.background="#f8fafc";

uploadBox.style.borderColor="#cbd5e1";


});




uploadBox.addEventListener(
"drop",
function(e){


e.preventDefault();



let files=e.dataTransfer.files;



if(files.length){


fileInput.files=files;


fileInput.dispatchEvent(
new Event("change")
);


}



});




</script>