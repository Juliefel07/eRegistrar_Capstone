<?php

session_start();


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}

?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>
Upload Payment
</title>


<link rel="stylesheet" href="../assets/css/student.css">


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


</head>



<body>


<?php include("sidebar.php"); ?>

<?php include("header.php"); ?>





<div class="student-main">



<h1>
Upload Payment Proof
</h1>





<div class="payment-upload-card">



<form action="payment_process.php" method="POST" enctype="multipart/form-data">





<div class="form-group">


<label>
Select Document Request
</label>


<select name="request_id" required>


<option value="">
Select Request
</option>


<option value="REQ001">
Transcript of Records - ₱100
</option>


<option value="REQ002">
Certificate of Enrollment - ₱50
</option>


<option value="REQ003">
Good Moral Certificate - ₱50
</option>


</select>


</div>






<div class="form-group">


<label>
Payment Method
</label>



<select name="payment_method" required>


<option value="">
Select Method
</option>


<option value="GCash">
GCash
</option>


<option value="Bank Transfer">
Bank Transfer
</option>


<option value="Cash Payment">
Cash Payment
</option>


</select>


</div>






<div class="form-group">


<label>
Reference Number
</label>



<input 
type="text"
name="reference_number"
placeholder="Enter payment reference number"
required>


</div>







<div class="form-group">


<label>
Upload Receipt / Screenshot
</label>



<input 
type="file"
name="payment_proof"
accept="image/*,.pdf"
required>



</div>








<div class="payment-note">


<i class="fa-solid fa-circle-info"></i>


Make sure your receipt is clear and readable.
The registrar will verify your payment before processing your request.


</div>






<button type="submit">


<i class="fa-solid fa-upload"></i>

Submit Payment


</button>





</form>



</div>






</div>



</body>

</html>