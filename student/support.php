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
Help & Support
</title>


<link rel="stylesheet" href="../assets/css/student.css">


</head>


<body>


<?php include("sidebar.php"); ?>

<?php include("header.php"); ?>



<div class="help-container">


<div class="help-header">


<div>

<h1>
Help
</h1>


<p>
Find answers, manage your account, and contact the Registrar for assistance.
</p>

</div>



<a href="messages.php" class="help-button">

<i class="fa-solid fa-circle-info"></i>

Need More Help?

</a>



</div>







<!-- CATEGORY BUTTONS -->


<div class="help-tabs">


<button class="help-tab active" data-target="account">

Account Problems

<i class="fa-solid fa-arrow-right"></i>

</button>



<button class="help-tab" data-target="portal">

Student Portal

<i class="fa-solid fa-arrow-right"></i>

</button>



<button class="help-tab" data-target="payment">

Payments

<i class="fa-solid fa-arrow-right"></i>

</button>



<button class="help-tab" data-target="document">

Documents

<i class="fa-solid fa-arrow-right"></i>

</button>



</div>









<!-- ACCOUNT FAQ -->


<div class="faq-container active" id="account">



<div class="faq-item">


<div class="faq-question">

I forgot my password. What should I do?

<span>↓</span>

</div>


<div class="faq-answer">

Use the Forgot Password option on the login page to reset your account password.
If you still cannot access your account, contact the Registrar Office.

</div>


</div>





<div class="faq-item">


<div class="faq-question">

How can I update my profile?

<span>↓</span>

</div>


<div class="faq-answer">

Go to My Profile and update your personal information.

</div>


</div>






<div class="faq-item">


<div class="faq-question">

How will I know if the Registrar replied?

<span>↓</span>

</div>


<div class="faq-answer">

Check the Messages section of your account to view replies from the Registrar.

</div>


</div>



</div>









<!-- STUDENT PORTAL FAQ -->


<div class="faq-container" id="portal">



<div class="faq-item">


<div class="faq-question">

How do I submit a document request?

<span>↓</span>

</div>


<div class="faq-answer">

Open Request Document from the sidebar, select your document,
fill in the required information, and submit.

</div>


</div>






<div class="faq-item">


<div class="faq-question">

How do I check my notifications?

<span>↓</span>

</div>


<div class="faq-answer">

Notifications from the Registrar can be viewed from your dashboard.

</div>


</div>







<div class="faq-item">


<div class="faq-question">

How do I use the Student Portal?

<span>↓</span>

</div>


<div class="faq-answer">

Use the sidebar menu to access requests, payments,
documents, messages, and settings.

</div>


</div>




</div>









<!-- PAYMENT FAQ -->


<div class="faq-container" id="payment">



<div class="faq-item">


<div class="faq-question">

Why can't I upload my payment proof?

<span>↓</span>

</div>


<div class="faq-answer">

Make sure your file format is accepted and the file size
does not exceed the allowed limit.

</div>


</div>






<div class="faq-item">


<div class="faq-question">

Why is my payment still pending?

<span>↓</span>

</div>


<div class="faq-answer">

Payments must be checked and verified by the Registrar Office.

</div>


</div>






<div class="faq-item">


<div class="faq-question">

How can I check my payment status?

<span>↓</span>

</div>


<div class="faq-answer">

Open the Payments page to view your current payment status.

</div>


</div>




</div>









<!-- DOCUMENT FAQ -->


<div class="faq-container" id="document">



<div class="faq-item">


<div class="faq-question">

How can I request documents?

<span>↓</span>

</div>


<div class="faq-answer">

Go to Request Document, choose your document,
complete the request form, and submit.

</div>


</div>






<div class="faq-item">


<div class="faq-question">

How can I track my document?

<span>↓</span>

</div>


<div class="faq-answer">

Open Track Document to view the progress of your request.

</div>


</div>







<div class="faq-item">


<div class="faq-question">

How long does document processing take?

<span>↓</span>

</div>


<div class="faq-answer">

Processing time depends on the document type and Registrar approval.

</div>


</div>




</div>









<div class="contact-box">


<h2>
Need More Help?
</h2>


<p>
For document requests, payments, account concerns, and other issues,
contact the Registrar Office.
</p>



<a href="messages.php">

Contact Registrar

</a>



</div>





</div>









<script>


// CATEGORY SWITCH


document.querySelectorAll(".help-tab").forEach(button=>{


button.addEventListener("click",()=>{


document.querySelectorAll(".help-tab")
.forEach(btn=>btn.classList.remove("active"));



button.classList.add("active");



let target = button.dataset.target;



document.querySelectorAll(".faq-container")
.forEach(container=>{

container.classList.remove("active");

});



document.getElementById(target)
.classList.add("active");



});



});







// FAQ OPEN CLOSE


document.querySelectorAll(".faq-question")
.forEach(question=>{


question.addEventListener("click",()=>{


let item = question.parentElement;


item.classList.toggle("open");



});



});



</script>




</body>

</html>