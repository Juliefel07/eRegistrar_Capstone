<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

$sql = "
SELECT
    sd.*,
    d.document_name
FROM student_documents sd
INNER JOIN documents d
ON sd.document_id = d.document_id
WHERE sd.student_id = ?
ORDER BY sd.created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$documents = [];

$totalDocuments = 0;
$approved = 0;
$pending = 0;
$rejected = 0;

$latestUpload = "No uploads yet";

while($row = mysqli_fetch_assoc($result)){

    $documents[] = $row;

    $totalDocuments++;

    switch(strtolower($row['status'])){

        case "approved":
            $approved++;
            break;

        case "pending":
            $pending++;
            break;

        case "rejected":
            $rejected++;
            break;
    }

    if($latestUpload=="No uploads yet"){
        $latestUpload=date("F d, Y h:i A",strtotime($row['created_at']));
    }

}

$requiredDocuments=8;

$progress=0;

if($requiredDocuments>0){

    $progress=($totalDocuments/$requiredDocuments)*100;

    if($progress>100){

        $progress=100;

    }

}
?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>My Documents</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/student.css">
</head>

<body class="bg-light">
<?php include("sidebar.php"); ?>
<?php include("header.php"); ?>
<div class="student-main">

    <div class="container-fluid">
<div class="container py-4">

<div class="d-flex justify-content-between align-items-center mb-4">

<div>

<h2 class="fw-bold">

<i class="fas fa-folder-open text-primary"></i>

My Documents

</h2>

<p class="text-muted">

Manage and monitor your uploaded requirements.

</p>

</div>

</div>

<div class="row g-3 mb-4">

<div class="col-lg-3">

<div class="card shadow border-0 bg-primary text-white">

<div class="card-body text-center">

<i class="fas fa-file-alt fa-2x mb-3"></i>

<h2><?= $totalDocuments ?></h2>

<p class="mb-0">

Total Documents

</p>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="card shadow border-0 bg-success text-white">

<div class="card-body text-center">

<i class="fas fa-circle-check fa-2x mb-3"></i>

<h2><?= $approved ?></h2>

<p class="mb-0">

Approved

</p>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="card shadow border-0 bg-warning">

<div class="card-body text-center">

<i class="fas fa-clock fa-2x mb-3"></i>

<h2><?= $pending ?></h2>

<p class="mb-0">

Pending

</p>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="card shadow border-0 bg-danger text-white">

<div class="card-body text-center">

<i class="fas fa-circle-xmark fa-2x mb-3"></i>

<h2><?= $rejected ?></h2>

<p class="mb-0">

Rejected

</p>

</div>

</div>

</div>

</div>

<div class="card shadow border-0 mb-4">

<div class="card-body">

<h5 class="fw-bold">

Submission Progress

</h5>

<div class="progress mb-2" style="height:25px;">

<div
class="progress-bar progress-bar-striped progress-bar-animated bg-success"
style="width:<?= $progress ?>%">

<?= round($progress) ?>%

</div>

</div>

<p class="text-muted mb-0">

<?= $totalDocuments ?>

of

<?= $requiredDocuments ?>

required documents uploaded.

</p>

</div>

</div>

<div class="row mb-4">

<div class="col-md-6">

<input
type="text"
id="search"
class="form-control"
placeholder="Search document...">

</div>

<div class="col-md-3">

<select
id="statusFilter"
class="form-select">

<option value="">All Status</option>

<option value="approved">Approved</option>

<option value="pending">Pending</option>

<option value="rejected">Rejected</option>

</select>

</div>

<div class="col-md-3">

<div class="alert alert-info mb-0">

<b>Latest:</b><br>

<?= $latestUpload ?>

</div>

</div>

</div>

<div class="card shadow border-0">

<div class="card-header bg-primary text-white">

<h5 class="mb-0">

<i class="fas fa-folder-open"></i>

Uploaded Documents

</h5>

</div>

<div class="table-responsive">

<table
class="table table-hover align-middle mb-0"
id="documentsTable">

<thead class="table-light">

<tr>

<th>Document</th>

<th>Status</th>

<th>Uploaded</th>

<th>Remarks</th>

<th width="180">

Actions

</th>

</tr>

</thead>

<tbody>

<?php if(count($documents) > 0): ?>

<?php foreach($documents as $row): ?>

<tr>

<td>

<strong>

<?= htmlspecialchars($row['document_name']) ?>

</strong>

</td>


<td class="status">


<?php

$status = strtolower($row['status']);


if($status == "approved"){

    echo '<span class="badge bg-success">
    <i class="fas fa-check"></i> Approved
    </span>';

}

elseif($status == "pending"){

    echo '<span class="badge bg-warning text-dark">
    <i class="fas fa-clock"></i> Pending
    </span>';

}

elseif($status == "rejected"){

    echo '<span class="badge bg-danger">
    <i class="fas fa-times"></i> Rejected
    </span>';

}

else{

    echo '<span class="badge bg-secondary">
    '.$row['status'].'
    </span>';

}

?>

</td>


<td>

<?= date("M d, Y", strtotime($row['created_at'])) ?>

<br>

<small class="text-muted">

<?= date("h:i A", strtotime($row['created_at'])) ?>

</small>

</td>


<td>

<?= htmlspecialchars($row['remarks'] ?? "No remarks") ?>

</td>


<td>


<?php if(isset($row['file_path'])): ?>


<a href="<?= htmlspecialchars($row['file_path']) ?>"
target="_blank"
class="btn btn-sm btn-primary mb-1">

<i class="fas fa-eye"></i>

View

</a>


<a href="<?= htmlspecialchars($row['file_path']) ?>"
download
class="btn btn-sm btn-success mb-1">

<i class="fas fa-download"></i>

Download

</a>


<?php endif; ?>


<?php if($status == "rejected"): ?>


<a href="upload_document.php?id=<?= $row['document_id'] ?>"
class="btn btn-sm btn-danger">

<i class="fas fa-upload"></i>

Re-upload

</a>


<?php endif; ?>


</td>


</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

<td colspan="5" class="text-center py-5">


<i class="fas fa-folder-open fa-3x text-muted mb-3"></i>


<h5>

No Documents Found

</h5>


<p class="text-muted">

You have not uploaded any documents yet.

</p>


<a href="upload_document.php"
class="btn btn-primary">

<i class="fas fa-upload"></i>

Upload Document

</a>


</td>

</tr>


<?php endif; ?>


</tbody>

</table>

</div>

</div>


<!-- Upload Timeline -->

<div class="card shadow border-0 mt-4">


<div class="card-header bg-dark text-white">

<h5 class="mb-0">

<i class="fas fa-history"></i>

Upload History

</h5>

</div>


<div class="card-body">


<div class="timeline">


<?php if(count($documents)>0): ?>


<?php foreach($documents as $history): ?>


<div class="timeline-item mb-3">


<div class="card border-0 shadow-sm">


<div class="card-body">


<h6 class="fw-bold">


<i class="fas fa-file text-primary"></i>


<?= htmlspecialchars($history['document_name']) ?>


</h6>


<p class="mb-1">


Status:


<?php


if(strtolower($history['status'])=="approved"){

echo '<span class="badge bg-success">
Approved
</span>';

}

elseif(strtolower($history['status'])=="pending"){

echo '<span class="badge bg-warning text-dark">
Pending
</span>';

}

else{

echo '<span class="badge bg-danger">
Rejected
</span>';

}


?>


</p>


<small class="text-muted">


Uploaded:

<?= date("F d, Y h:i A",strtotime($history['created_at'])) ?>


</small>


</div>

</div>


</div>


<?php endforeach; ?>


<?php else: ?>


<p class="text-muted">

No upload history available.

</p>


<?php endif; ?>


</div>


</div>


</div>



<!-- Achievement Card -->

<div class="card shadow border-success mt-4">


<div class="card-body text-center">


<h3>

🎉 Great Work!

</h3>


<p class="mb-0">


You have submitted

<strong>

<?= $totalDocuments ?>

</strong>

documents.


</p>


</div>


</div>
<style>

/* ===========================
   DOCUMENT PAGE ONLY STYLE
   Does not affect sidebar/header
=========================== */


/* Content wrapper */

.documents-page{

    width: 100%;

}



/* Cards */

.documents-page .card{

    border-radius:16px;

    transition:.3s ease;

    border:none;

}



.documents-page .card:hover{

    transform:translateY(-4px);

    box-shadow:0 10px 25px rgba(0,0,0,.12);

}



/* Statistics cards */

.documents-page .stat-card{

    color:white;

    min-height:150px;

    display:flex;

    align-items:center;

    justify-content:center;

}



.documents-page .stat-icon{

    font-size:35px;

    opacity:.8;

}



/* Progress */

.documents-page .progress{

    height:25px;

    border-radius:20px;

    background:#e9ecef;

}



.documents-page .progress-bar{

    border-radius:20px;

    font-weight:600;

}



/* Table */

.documents-page .table-card{

    overflow:hidden;

}



.documents-page table{

    margin-bottom:0;

}



.documents-page thead th{

    background:#f8f9fa;

    font-size:13px;

    text-transform:uppercase;

    color:#6c757d;

    padding:15px;

}



.documents-page tbody td{

    padding:15px;

    vertical-align:middle;

}



.documents-page tbody tr{

    transition:.2s;

}



.documents-page tbody tr:hover{

    background:#f8fbff;

}



/* Status badges */

.documents-page .badge{

    padding:8px 13px;

    border-radius:20px;

}



/* Buttons */

.documents-page .btn{

    border-radius:8px;

    font-size:13px;

}



/* Search area */

.documents-page .form-control,
.documents-page .form-select{

    height:45px;

    border-radius:10px;

    border:1px solid #ddd;

}



.documents-page .form-control:focus,
.documents-page .form-select:focus{

    box-shadow:0 0 0 .2rem rgba(13,110,253,.15);

}



/* Timeline */

.documents-page .timeline-item{

    border-left:3px solid #0d6efd;

    padding-left:20px;

    position:relative;

}



.documents-page .timeline-item::before{

    content:"";

    width:14px;

    height:14px;

    background:#0d6efd;

    position:absolute;

    left:-9px;

    top:25px;

    border-radius:50%;

}



/* Remove conflicts with dashboard */

.documents-page h1,
.documents-page h2,
.documents-page h3,
.documents-page h4,
.documents-page h5{

    font-weight:700;

}



/* Mobile */

@media(max-width:768px){


.documents-page{

    padding:15px;

}



.documents-page .btn{

    width:100%;

    margin-bottom:5px;

}



.documents-page table{

    font-size:13px;

}


}



</style>