<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}



$request = null;


if(isset($_POST['tracking_no'])){


    $tracking_no = $_POST['tracking_no'];


    $user_id = $_SESSION['user_id'];



    $sql = "

    SELECT

    r.tracking_no,
    d.document_name,
    r.status,
    r.request_date,
    r.quantity


    FROM requests r


    JOIN documents d

    ON r.document_id = d.document_id


    WHERE r.tracking_no='$tracking_no'

    AND r.user_id='$user_id'

    ";



    $result=mysqli_query($conn,$sql);



    if(mysqli_num_rows($result)>0){

        $request=mysqli_fetch_assoc($result);

    }


}


?>



<!DOCTYPE html>
<html>


<head>


<title>Track Document</title>


<link rel="stylesheet" href="../assets/css/student.css">


</head>



<body>


<?php include("sidebar.php"); ?>


<?php include("header.php"); ?>



<div class="student-main">



<div class="form-card">



<h2>
Track Document
</h2>


<p>
Enter your tracking number to check your request status.
</p>




<form method="POST">



<div class="form-group">


<label>
Tracking Number
</label>


<input 
type="text"
name="tracking_no"
placeholder="Example: REQ202607171200"
required>


</div>



<button class="submit-btn">

Track Now

</button>



</form>





<?php if(isset($_POST['tracking_no']) && !$request){ ?>


<div class="not-found">

No request found.

</div>


<?php } ?>





<?php if($request){ ?>


<div class="tracking-result">



<h3>
Document Information
</h3>



<p>
<strong>
Tracking No:
</strong>

<?php echo $request['tracking_no']; ?>

</p>




<p>
<strong>
Document:
</strong>

<?php echo $request['document_name']; ?>

</p>




<p>
<strong>
Quantity:
</strong>

<?php echo $request['quantity']; ?>

</p>




<p>
<strong>
Date Requested:
</strong>

<?php echo $request['request_date']; ?>

</p>




<h3>
Current Status
</h3>



<div class="status 
<?php echo strtolower(str_replace(' ','-',$request['status'])); ?>">


<?php echo $request['status']; ?>


</div>




</div>



<?php } ?>




</div>



</div>


</body>


</html>