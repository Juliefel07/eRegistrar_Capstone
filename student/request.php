<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}


$user_id = $_SESSION['user_id'];


// Get logged in user information

$userQuery = mysqli_query($conn,"
    SELECT *
    FROM users
    WHERE user_id='$user_id'
");


$user = mysqli_fetch_assoc($userQuery);



// Success modal

$showSuccess = false;


if(isset($_SESSION['request_success'])){

    $showSuccess = true;

    unset($_SESSION['request_success']);

}



// Load available documents

$documents = mysqli_query($conn,"
    SELECT *
    FROM documents
    WHERE status='Available'
");



if(!$documents){

    die(mysqli_error($conn));

}



// Store documents for javascript

$documentData = [];



$docQuery = mysqli_query($conn,"
    SELECT *
    FROM documents
    WHERE status='Available'
");



while($doc = mysqli_fetch_assoc($docQuery)){


    $documentData[$doc['document_id']] = [

        "name"=>$doc['document_name'],

        "fee"=>$doc['fee'],

        "days"=>$doc['processing_days'],

        "description"=>$doc['description'],

        "requirements"=>$doc['requirements']

    ];


}




?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>Request Document</title>


<link rel="stylesheet" href="../assets/css/student.css">
<link rel="stylesheet" href="../assets/css/request.css">


</head>


<body>



<?php include("sidebar.php"); ?>


<?php include("header.php"); ?>



<div class="request-container">



<div class="request-card">



<h2>
Request Document
</h2>


<p class="subtitle">
Fill up the form to request your official documents.
</p>




<!-- PROGRESS -->

<div class="steps">


<div class="step active" id="step1Indicator">

<span>1</span>

Request Details

</div>



<div class="step" id="step2Indicator">

<span>2</span>

Requester

</div>



<div class="step" id="step3Indicator">

<span>3</span>

Student Info

</div>



<div class="step" id="step4Indicator">

<span>4</span>

Review

</div>



</div>





<form 
id="requestForm"
action="request_process.php"
method="POST"
enctype="multipart/form-data"
>




<!-- STEP 1 -->


<div class="form-step active" id="step1">



<h3>
Request Details
</h3>



<div class="form-group">


<label>
Select Document
</label>


<select 
name="document_id"
id="documentSelect"
required
>


<option value="">
Choose Document
</option>


<?php while($row=mysqli_fetch_assoc($documents)){ ?>


<option value="<?php echo $row['document_id']; ?>">

<?php echo htmlspecialchars($row['document_name']); ?>

</option>


<?php } ?>


</select>


</div>





<div class="document-info">


<h4 id="docName">
Select a document
</h4>


<p id="docDescription">
Document information will appear here.
</p>



<div class="info-row">


<div>

Processing

<strong id="docDays">
0 Days
</strong>


</div>



<div>

Fee

<strong id="docFee">
₱0.00
</strong>


</div>


</div>



<div class="requirements">


<h4>
Requirements
</h4>


<ul id="requirementsList">

<li>
Select document first
</li>


</ul>


</div>


</div>





<div class="form-group">


<label>
Purpose
</label>


<textarea
name="purpose"
required
placeholder="Enter purpose..."
></textarea>


</div>





<div class="row">


<div class="form-group">


<label>
Copies
</label>


<input
type="number"
name="quantity"
id="quantity"
value="1"
min="1"
>


</div>



<div class="form-group">


<label>
Payment Method
</label>


<select name="payment_method" required>


<option value="">
Select Payment
</option>


<option value="E-Payment">
E-Payment
</option>


<option value="On the Counter">
On the Counter
</option>


</select>


</div>


</div>





<div class="form-group">


<label>
Upload Requirements
</label>


<input
type="file"
name="requirements"
accept=".pdf,.jpg,.png,.jpeg,.doc,.docx"
>


</div>



<button 
type="button"
class="next-btn"
onclick="nextStep(2)"
>

Next

</button>



</div>

<!-- STEP 2 -->

<div class="form-step" id="step2">


<h3>
Requester Information
</h3>



<div class="row">


<div class="form-group">


<label>
Full Name
</label>


<input 
type="text"
name="fullname"
value="<?php echo htmlspecialchars($user['fullname']); ?>"
required
>


</div>



<div class="form-group">


<label>
Student Number
</label>


<input 
type="text"
name="student_no"
value="<?php echo htmlspecialchars($user['student_no']); ?>"
required
>


</div>



</div>





<div class="row">


<div class="form-group">


<label>
Course
</label>


<input 
type="text"
name="course"
value="<?php echo htmlspecialchars($user['course']); ?>"
required
>


</div>




<div class="form-group">


<label>
Year Level
</label>


<input 
type="text"
name="year_level"
value="<?php echo htmlspecialchars($user['year_level']); ?>"
required
>


</div>



</div>





<div class="row">


<div class="form-group">


<label>
Email
</label>


<input 
type="email"
name="email"
value="<?php echo htmlspecialchars($user['email']); ?>"
required
>


</div>




<div class="form-group">


<label>
Contact Number
</label>


<input 
type="text"
value="<?php echo htmlspecialchars($user['contact_no']); ?>"
readonly
>


</div>



</div>






<div class="button-group">


<button
type="button"
class="back-btn"
onclick="previousStep(1)"
>

Back

</button>



<button
type="button"
class="next-btn"
onclick="nextStep(3)"
>

Next

</button>



</div>



</div>

<!-- STEP 3 -->

<div class="form-step" id="step3">


<h3>
Student Information
</h3>



<div class="row">


<div class="form-group">

<label>
Student Name
</label>


<input
type="text"
id="reviewName"
value="<?php echo htmlspecialchars($user['fullname']); ?>"
readonly
>


</div>




<div class="form-group">


<label>
Student Number
</label>


<input
type="text"
id="reviewStudentNo"
value="<?php echo htmlspecialchars($user['student_no']); ?>"
readonly
>


</div>


</div>





<div class="row">


<div class="form-group">


<label>
Course
</label>


<input
type="text"
id="reviewCourse"
value="<?php echo htmlspecialchars($user['course']); ?>"
readonly
>


</div>




<div class="form-group">


<label>
Year Level
</label>


<input
type="text"
id="reviewYear"
value="<?php echo htmlspecialchars($user['year_level']); ?>"
readonly
>


</div>



</div>





<div class="form-group">


<label>
Additional Remarks
</label>


<textarea
name="remarks"
id="remarks"
placeholder="Optional remarks..."
></textarea>


</div>





<div class="button-group">


<button
type="button"
class="back-btn"
onclick="previousStep(2)"
>

Back

</button>



<button
type="button"
class="next-btn"
onclick="nextStep(4)"
>

Next

</button>


</div>


</div>









<!-- STEP 4 REVIEW -->


<div class="form-step" id="step4">


<h3>
Review Request
</h3>




<div class="review-card">


<div class="review-item">

<label>
Document
</label>

<p id="reviewDocument">
-
</p>


</div>



<div class="review-item">

<label>
Purpose
</label>

<p id="reviewPurpose">
-
</p>

</div>




<div class="review-item">

<label>
Quantity
</label>

<p id="reviewQuantity">
1
</p>

</div>




<div class="review-item">

<label>
Payment Method
</label>

<p id="reviewPayment">
-
</p>


</div>





<div class="review-item">


<label>
Requester
</label>


<p>
<?php echo htmlspecialchars($user['fullname']); ?>
</p>


</div>



</div>





<div class="button-group">


<button
type="button"
class="back-btn"
onclick="previousStep(3)"
>

Back

</button>



<button
type="button"
class="submit-btn"
onclick="confirmRequest()"
>

Submit Request

</button>


</div>




</div>






</form>



</div>






<!-- RIGHT SUMMARY -->

<div class="summary-card">


<h3>
Request Summary
</h3>



<div class="summary-item">

<span>
Document
</span>

<strong id="summaryDocument">
-
</strong>

</div>




<div class="summary-item">

<span>
Processing
</span>

<strong id="summaryDays">
-
</strong>

</div>




<div class="summary-item">

<span>
Quantity
</span>

<strong id="summaryQuantity">
1
</strong>

</div>





<div class="summary-item">

<span>
Payment
</span>

<strong id="summaryPayment">
-
</strong>

</div>





<hr>




<div class="total">


Total


<strong id="summaryTotal">
₱0.00
</strong>


</div>



</div>




</div>









<!-- CONFIRM MODAL -->


<div class="modal" id="confirmModal">


<div class="modal-box">


<h3>
Confirm Request
</h3>


<p>
Are you sure you want to submit this request?
</p>




<div class="modal-buttons">


<button
class="cancel-btn"
onclick="closeConfirm()"
>

Cancel

</button>



<button
class="confirm-btn"
onclick="submitRequest()"
>

Yes Submit

</button>


</div>



</div>


</div>









<!-- SUCCESS MODAL -->


<div class="modal" id="successModal">


<div class="modal-box success">


<h2>
 Request Submitted
</h2>


<p>

</p>



<button
class="confirm-btn"
onclick="closeSuccess()"
>

OK

</button>


</div>


</div>
<script>

const documents = <?php echo json_encode($documentData); ?>;



const documentSelect = document.getElementById("documentSelect");


documentSelect.addEventListener("change",function(){


let id=this.value;


if(documents[id]){


document.getElementById("docName").innerHTML =
documents[id].name;


document.getElementById("docDescription").innerHTML =
documents[id].description ?? "No description";


document.getElementById("docDays").innerHTML =
documents[id].days+" Working Days";


document.getElementById("docFee").innerHTML =
"₱"+parseFloat(documents[id].fee).toFixed(2);



document.getElementById("summaryDocument").innerHTML =
documents[id].name;



document.getElementById("summaryDays").innerHTML =
documents[id].days+" Days";



let req = documents[id].requirements;


let html="";


if(req){

let list=req.split(",");


list.forEach(function(item){

html += "<li>✔ "+item+"</li>";

});


}else{


html="<li>No requirements</li>";

}


document.getElementById("requirementsList").innerHTML=html;



updateTotal();


}


});





document.getElementById("quantity").addEventListener("input",function(){

document.getElementById("summaryQuantity").innerHTML=this.value;

updateTotal();

});




document.querySelector(
"select[name='payment_method']"
).addEventListener("change",function(){

document.getElementById("summaryPayment").innerHTML=this.value;

});





document.querySelector(
"textarea[name='purpose']"
).addEventListener("input",function(){

document.getElementById("reviewPurpose").innerHTML=this.value;

});






function updateTotal(){


let id=documentSelect.value;


if(documents[id]){


let qty=document.getElementById("quantity").value;


let total =
documents[id].fee * qty;



document.getElementById("summaryTotal").innerHTML =
"₱"+parseFloat(total).toFixed(2);



}


}







function nextStep(step){


document.querySelectorAll(".form-step")
.forEach(function(el){

el.classList.remove("active");

});



document.getElementById("step"+step)
.classList.add("active");



document.querySelectorAll(".step")
.forEach(function(el){

el.classList.remove("active");

});



document.getElementById(
"step"+step+"Indicator"
).classList.add("active");



if(step==4){

document.getElementById("reviewDocument").innerHTML =
document.getElementById("summaryDocument").innerHTML;


document.getElementById("reviewQuantity").innerHTML =
document.getElementById("quantity").value;


document.getElementById("reviewPayment").innerHTML =
document.querySelector(
"select[name='payment_method']"
).value;


}


}






function previousStep(step){

nextStep(step);

}





function confirmRequest(){

document.getElementById("confirmModal")
.style.display="flex";

}




function closeConfirm(){

document.getElementById("confirmModal")
.style.display="none";

}




function submitRequest(){

document.getElementById("requestForm").submit();

}





function closeSuccess(){

document.getElementById("successModal")
.style.display="none";

}




<?php if($showSuccess){ ?>

document.getElementById("successModal")
.style.display="flex";

<?php } ?>


</script>


</body>

</html>