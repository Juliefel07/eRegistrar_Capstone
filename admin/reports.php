<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if(!isset($_SESSION['user_id'])){
    die("Access denied.");
}


// Total requests
$total = mysqli_query($conn,
"SELECT COUNT(*) AS total FROM requests");

$total_requests = mysqli_fetch_assoc($total)['total'];


// Pending
$pending = mysqli_query($conn,
"SELECT COUNT(*) AS total FROM requests WHERE status='Pending'");

$pending_requests = mysqli_fetch_assoc($pending)['total'];


// Approved
$approved = mysqli_query($conn,
"SELECT COUNT(*) AS total FROM requests WHERE status='Approved'");

$approved_requests = mysqli_fetch_assoc($approved)['total'];


// Rejected
$rejected = mysqli_query($conn,
"SELECT COUNT(*) AS total FROM requests WHERE status='Rejected'");

$rejected_requests = mysqli_fetch_assoc($rejected)['total'];


// Claimed
$claimed = mysqli_query($conn,
"SELECT COUNT(*) AS total FROM requests WHERE status='Claimed'");

$claimed_requests = mysqli_fetch_assoc($claimed)['total'];


// Recent requests
$recent = mysqli_query($conn,"
SELECT
r.tracking_no,
u.fullname,
d.document_name,
r.status,
r.request_date

FROM requests r

JOIN users u 
ON r.user_id=u.user_id

JOIN documents d
ON r.document_id=d.document_id

ORDER BY r.request_date DESC

LIMIT 10
");


?>


<!DOCTYPE html>
<html>

<head>

<title>Reports</title>

<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/admin.css">

</head>


<body>


<?php include("sidebar.php"); ?>


<div class="admin-content">

<?php include("header.php"); ?>
<div class="container">


<h2>Request Reports</h2>


<div class="dashboard-cards">


<div class="card blue">

<h3>Total</h3>

<p>
<?php echo $total_requests; ?>
</p>

</div>



<div class="card yellow">

<h3>Pending</h3>

<p>
<?php echo $pending_requests; ?>
</p>

</div>



<div class="card green">

<h3>Approved</h3>

<p>
<?php echo $approved_requests; ?>
</p>

</div>



<div class="card red">

<h3>Rejected</h3>

<p>
<?php echo $rejected_requests; ?>
</p>

</div>



<div class="card dark">

<h3>Claimed</h3>

<p>
<?php echo $claimed_requests; ?>
</p>

</div>


</div>



<br>


<h2>Recent Requests</h2>


<table>


<tr>

<th>Tracking No</th>
<th>Student</th>
<th>Document</th>
<th>Status</th>
<th>Date</th>

</tr>


<?php while($row=mysqli_fetch_assoc($recent)){ ?>


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
<?php echo $row['status']; ?>
</td>


<td>
<?php echo date("M d, Y",strtotime($row['request_date'])); ?>
</td>


</tr>


<?php } ?>


</table>


</div>


</div>


</body>

</html>