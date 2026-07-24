<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}



$user_id = $_SESSION['user_id'];


$sql = "

SELECT

r.request_id,
r.tracking_no,
u.fullname,
d.document_name,
r.purpose,
r.quantity,
r.status,
r.request_date,
r.uploaded_file

FROM requests r

JOIN users u 
ON r.user_id = u.user_id

JOIN documents d 
ON r.document_id = d.document_id

ORDER BY r.request_date DESC

";



$result = mysqli_query($conn,$sql);



if(!$result){

    die(mysqli_error($conn));

}


?>



<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>My Requests</title>


<link rel="stylesheet" href="../assets/css/student.css">


</head>



<body>



<?php include("sidebar.php"); ?>


<?php include("header.php"); ?>





<div class="student-main">





<div class="history-card">



<h2>

My Document Requests

</h2>

<br>

<p>

Track the progress of your submitted requests.

</p>

<br>



<table class="request-table">


<thead>

<tr>

<th>
Tracking No.
</th>


<th>
Document
</th>


<th>
Purpose
</th>


<th>
Quantity
</th>


<th>
Status
</th>


<th>
Date
</th>


<th>
Requirement File
</th>


</tr>


</thead>





<tbody>



<?php while($row=mysqli_fetch_assoc($result)){ ?>



<tr>


<td>

<?php echo $row['tracking_no']; ?>

</td>



<td>

<?php echo $row['document_name']; ?>

</td>




<td>

<?php echo $row['purpose']; ?>

</td>




<td>

<?php echo $row['quantity']; ?>

</td>





<td>


<span class="status <?php echo strtolower(str_replace(' ','-',$row['status'])); ?>">


<?php echo $row['status']; ?>


</span>


</td>





<td>

<?php echo date("M d, Y",strtotime($row['request_date'])); ?>

</td>





<td>

<?php

if(!empty($row['uploaded_file'])){

$file = "../assets/uploads/" . $row['uploaded_file'];

?>

<a class="btn approve"

href="<?php echo $file; ?>"

target="_blank">

View Requirement

</a>


<?php

}else{

?>

<span class="no-action">
No File
</span>


<?php } ?>


</td>



</tr>



<?php } ?>





</tbody>


</table>





</div>





</div>





</body>

</html>