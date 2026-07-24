<?php
session_start();

require_once __DIR__ . "/../includes/db.php";


if(isset($_POST['submit'])){


$name = mysqli_real_escape_string($conn,$_POST['document_name']);
$description = mysqli_real_escape_string($conn,$_POST['description']);
$fee = $_POST['fee'];
$days = $_POST['processing_days'];

$requirements = trim($_POST['requirements']);

$sql = "INSERT INTO documents
(
document_name,
description,
fee,
processing_days,
status
)

VALUES
(
'$name',
'$description',
'$fee',
'$days',
'Available'
)";

if(mysqli_query($conn,$sql)){

    // Get the new document ID
    $document_id = mysqli_insert_id($conn);

    // Split each line into a separate requirement
    $lines = explode("\n",$requirements);

    foreach($lines as $req){

        $req = trim($req);

        if($req!=""){

            $req = mysqli_real_escape_string($conn,$req);

            mysqli_query($conn,"
                INSERT INTO document_requirements
                (
                    document_id,
                    requirement_name
                )
                VALUES
                (
                    '$document_id',
                    '$req'
                )
            ");

        }

    }

    header("Location: documents.php");
    exit();

}else{

    echo mysqli_error($conn);

}


}

?>


<!DOCTYPE html>
<html>

<head>

<title>Add Document</title>

<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/admin.css">


</head>


<body>


<?php include("sidebar.php"); ?>


<div class="admin-content">


<?php include("header.php"); ?>



<div class="form-card">


<h2>
Add New Document
</h2>



<form method="POST">



<div class="form-group">

<label>
Document Name
</label>

<input 
type="text" 
name="document_name"
placeholder="Enter document name"
required>

</div>




<div class="form-group">

<label>
Description
</label>

<textarea 
name="description"
placeholder="Enter description"></textarea>

</div>




<div class="form-row">


<div class="form-group">

<label>
Fee
</label>

<input 
type="number"
name="fee"
step="0.01"
placeholder="0.00">

</div>




<div class="form-group">

<label>
Processing Days
</label>

<input 
type="number"
name="processing_days"
placeholder="Number of days">

</div>


</div>





<div class="form-group">

<label>
Requirements
</label>


<textarea
name="requirements"
placeholder="One requirement per line

Example:
Valid School ID
Payment Receipt
Authorization Letter"></textarea>





<button class="save-btn" name="submit">

Save Document

</button>




</form>


</div>


</div>


</body>

</html>