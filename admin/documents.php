<?php
session_start();
require_once __DIR__ . "/../includes/db.php";
if(!isset($_SESSION['user_id'])){
    die("Access denied.");
}


$query = "SELECT * FROM documents ORDER BY document_id DESC";

$result = mysqli_query($conn,$query);

?>

<!DOCTYPE html>
<html>

<head>

<title>Manage Documents</title>

<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/admin.css">

</head>


<body>


<?php include("sidebar.php"); ?>


<div class="admin-content">
<?php include("header.php"); ?>

<div class="container">


<h2>Manage Documents</h2>


<a href="add_document.php" class="btn approve">
+ Add Document
</a>


<br><br>


<table>


<tr>

<th>ID</th>
<th>Document Name</th>
<th>Description</th>
<th>Fee</th>
<th>Processing Days</th>
<th>Status</th>
<th>Action</th>

</tr>


<?php while($row=mysqli_fetch_assoc($result)){ ?>


<tr>

<td>
<?php echo $row['document_id']; ?>
</td>


<td>
<?php echo htmlspecialchars($row['document_name']); ?>
</td>


<td>
<?php echo htmlspecialchars($row['description']); ?>
</td>


<td>
₱<?php echo number_format($row['fee'],2); ?>
</td>


<td>
<?php echo $row['processing_days']; ?> days
</td>


<td>

<span class="status 
<?php echo strtolower($row['status']); ?>">

<?php echo $row['status']; ?>

</span>

</td>


<td>

<a class="btn reject"
href="delete_document.php?id=<?php echo $row['document_id']; ?>"
onclick="return confirm('Delete this document?')">

Delete

</a>

</td>


</tr>


<?php } ?>


</table>


</div>


</div>


</body>

</html>