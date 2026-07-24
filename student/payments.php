<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}


$user_id = $_SESSION['user_id'];


// Get only E-Payment requests of this student

$sql = "SELECT
            r.request_id,
            r.payment_status,
            d.document_name,
            d.fee
        FROM requests r
        INNER JOIN documents d
        ON r.document_id = d.document_id
        WHERE r.user_id = ?
        AND r.payment_method = 'E-Payment'
        ORDER BY r.request_date DESC";


$stmt = mysqli_prepare($conn,$sql);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


?>



<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>
Payments
</title>


<link rel="stylesheet" href="../assets/css/student.css">


</head>



<body>



<?php include("sidebar.php"); ?>


<?php include("header.php"); ?>





<div class="student-main">



<h1>
Payments
</h1>




<div class="payment-container">





<?php if(mysqli_num_rows($result) > 0){ ?>



<?php while($row = mysqli_fetch_assoc($result)){ ?>



<div class="payment-card">



<div class="payment-icon">

<i class="fa-solid fa-file-invoice-dollar"></i>

</div>





<div class="payment-info">



<h3>

<?php echo htmlspecialchars($row['document_name']); ?>

</h3>



<p>
Document Processing Fee
</p>




<h2>

₱<?php echo number_format($row['fee'],2); ?>

</h2>





<?php if($row['payment_status']=="Paid"){ ?>


<span class="payment-status paid">

Paid

</span>



<?php }else{ ?>


<span class="payment-status pending">

Pending Payment

</span>



<?php } ?>




</div>





<?php if($row['payment_status']=="Paid"){ ?>



<a 
href="payments_history.php?id=<?php echo $row['request_id']; ?>" 
class="history-btn">

View Receipt

</a>



<?php }else{ ?>



<a 
href="payment_upload.php?id=<?php echo $row['request_id']; ?>" 
class="pay-btn">

Pay Now

</a>



<?php } ?>





</div>





<?php } ?>





<?php }else{ ?>



<div class="empty-payment">


<i class="fa-solid fa-wallet"></i>


<h3>
No E-Payment Requests
</h3>


<p>
Your online payment requests will appear here.
</p>



</div>



<?php } ?>





</div>





</div>





</body>

</html>     