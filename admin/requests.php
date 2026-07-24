
<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


// Check admin login

if (!isset($_SESSION['user_id'])) {

    die("Access denied.");

}



$sql = "

SELECT

r.request_id,
r.tracking_no,
u.fullname,
d.document_name,
r.purpose,
r.quantity,
r.status,
r.request_date

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
<html>

<head>

<title>Manage Requests</title>

<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/admin.css">


</head>


<body>


<?php include("sidebar.php"); ?>



<div class="admin-content">

<?php include("header.php"); ?>
<div class="container">


<h2>Student Document Requests</h2>



<table border="1" cellpadding="10">


<tr>

<th>Tracking No</th>

<th>Student</th>

<th>Document</th>

<th>Purpose</th>

<th>Quantity</th>

<th>Status</th>

<th>Date</th>

<th>Action</th>

</tr>



<?php while($row=mysqli_fetch_assoc($result)){ ?>



<tr>


<td>

<?php echo $row['tracking_no']; ?>

</td>



<td>

<?php echo $row['fullname']; ?>

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

<?php echo $row['status']; ?>

</td>



<td>

<?php echo $row['request_date']; ?>

</td>




<td>



<?php if($row['status']=="Pending"){ ?>



<a class="btn approve"

href="approve.php?id=<?php echo $row['request_id']; ?>">

Approve

</a>



<a class="btn reject"

href="reject.php?id=<?php echo $row['request_id']; ?>">

Reject

</a>



<?php } ?>





<?php if($row['status']=="Approved"){ ?>



<a class="btn approve"

href="processing.php?id=<?php echo $row['request_id']; ?>">

Start Processing

</a>



<?php } ?>





<?php if($row['status']=="Processing"){ ?>



<a class="btn approve"

href="ready.php?id=<?php echo $row['request_id']; ?>">

Ready for Claim

</a>



<?php } ?>





<?php if($row['status']=="Ready for Claim"){ ?>



<a class="btn approve"

href="claimed.php?id=<?php echo $row['request_id']; ?>">

Claimed

</a>



<?php } ?>





<?php if($row['status']=="Claimed"){ ?>



<span class="no-action">

Completed

</span>



<?php } ?>





<?php if($row['status']=="Rejected"){ ?>



<span class="no-action">

Rejected

</span>



<?php } ?>



</td>


</tr>



<?php } ?>



</table>



</div>


</div>



</body>

</html>

