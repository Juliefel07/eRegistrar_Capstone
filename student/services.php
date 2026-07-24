<?php

session_start();
require_once __DIR__ . "/../includes/db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit();
}

$query = mysqli_query($conn,"
    SELECT *
    FROM documents
    WHERE status='Active'
    ORDER BY document_name ASC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Registrar Services</title>

<link rel="stylesheet" href="../assets/css/student.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>

<body>

<?php include("sidebar.php"); ?>
<?php include("header.php"); ?>

<div class="student-main">

<div class="services-page">

<div class="page-header">

<h1>
<i class="fa-solid fa-file-lines"></i>
Registrar Services
</h1>

<p>
Browse all available registrar documents, processing time, and fees.
</p>

</div>

<div class="services-grid">

<?php if(mysqli_num_rows($query)>0){ ?>

<?php while($row=mysqli_fetch_assoc($query)){ ?>

<div class="service-card">

<div class="service-icon">
<i class="fa-solid fa-file"></i>
</div>

<h3>

<?php echo htmlspecialchars($row['document_name']); ?>

</h3>

<p>

<?php echo htmlspecialchars($row['description']); ?>

</p>

<div class="service-details">

<div>

<strong>Fee</strong>

<span>
₱<?php echo number_format($row['fee'],2); ?>
</span>

</div>

<div>

<strong>Processing</strong>

<span>
<?php echo $row['processing_days']; ?> Day(s)
</span>

</div>

</div>

<div class="service-status">

<span class="status active">

<?php echo htmlspecialchars($row['status']); ?>

</span>

</div>

<a href="request.php" class="request-btn">

Request Document

</a>

</div>

<?php } ?>

<?php }else{ ?>

<div class="empty-services">

<h2>No services available.</h2>

<p>
The Registrar has not added any available services yet.
</p>

</div>

<?php } ?>

</div>

</div>

</div>

</body>

</html>