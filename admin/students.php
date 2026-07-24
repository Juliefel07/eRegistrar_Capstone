<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if(!isset($_SESSION['user_id'])){
    die("Access denied.");
}


$query = "
SELECT *
FROM users
WHERE role='Student'
ORDER BY user_id DESC
";


$result = mysqli_query($conn,$query);


?>


<!DOCTYPE html>
<html>

<head>

<title>Manage Students</title>

<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/admin.css">

</head>


<body>


<?php include("sidebar.php"); ?>


<div class="admin-content">

<?php include("header.php"); ?>
<div class="container">


<h2>Student Management</h2>



<table>


<tr>

<th>ID</th>
<th>Student No.</th>
<th>Name</th>
<th>Course</th>
<th>Year Level</th>
<th>Contact</th>
<th>Email</th>
<th>Action</th>

</tr>



<?php while($row=mysqli_fetch_assoc($result)){ ?>


<tr>


<td>
<?php echo $row['user_id']; ?>
</td>


<td>
<?php echo htmlspecialchars($row['student_no']); ?>
</td>


<td>
<?php echo htmlspecialchars($row['fullname']); ?>
</td>


<td>
<?php echo htmlspecialchars($row['course']); ?>
</td>


<td>
<?php echo htmlspecialchars($row['year_level']); ?>
</td>


<td>
<?php echo htmlspecialchars($row['contact_no']); ?>
</td>


<td>
<?php echo htmlspecialchars($row['email']); ?>
</td>


<td>


<a class="btn reject"
href="delete_student.php?id=<?php echo $row['user_id']; ?>"
onclick="return confirm('Delete this student?')">

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