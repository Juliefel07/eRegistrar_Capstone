<?php

session_start();
require_once __DIR__ . "/../includes/db.php";

if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}

// Get all available documents from the admin
$documents = mysqli_query($conn,"
    SELECT *
    FROM documents
    ORDER BY document_id DESC
");

if(!$documents){
    die(mysqli_error($conn));
}

?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>Student Dashboard</title>


<link rel="stylesheet" href="../assets/css/student.css">


</head>


<body>


<?php include("sidebar.php"); ?>

<?php include("header.php"); ?>



<div class="student-main">



<!-- HERO SECTION -->

<div class="dashboard-hero">


<div class="hero-content">


<h1>

Welcome back,
<?php echo $_SESSION['fullname']; ?>

</h1>


<p>

Your digital registrar assistant. Request documents, track progress,
and stay updated anytime.

</p>



<a href="request.php" class="hero-btn">

Request a Document

</a>


</div>




<div class="school-image">


<img src="../assets/images/about-banner.png" 
alt="Consolatrix School">


</div>



</div>







<!-- SCHOOL INFORMATION -->


<div class="school-info">



<div class="info-card">


<h3>🏫 Consolatrix School</h3>


<p>

Registrar services are available during official school working days.

</p>


</div>





<div class="info-card">


<h3>📅 Working Days</h3>


<p>

Monday - Friday

<br>

8:00 AM - 5:00 PM

</p>


</div>






<div class="info-card">


<h3>⏳ Processing Time</h3>


<p>

Document requests are usually processed within

<strong>3-5 working days</strong>.

</p>


</div>




</div>








<!-- QUICK FEATURES -->

<h2 class="section-title">
Student Services
</h2>

<div class="dashboard-services">

    <!-- LEFT SIDE -->
    <div class="services-panel">

        <h3>Available Services</h3>

        <table class="services-table">

            <thead>
                <tr>
                    <th>Document</th>
                    <th>Fee</th>
                    <th>Days</th>
                </tr>
            </thead>

            <tbody>

            <?php while($doc=mysqli_fetch_assoc($documents)){ ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($doc['document_name']); ?>
                    </td>

                    <td>
                        ₱<?php echo number_format($doc['fee'],2); ?>
                    </td>

                    <td>
                        <?php echo $doc['processing_days']; ?>
                    </td>

                </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>

    <!-- RIGHT SIDE -->

    <div class="dashboard-right">

        <div class="action-card">

            <div class="icon green">📋</div>

            <h3>Track Requests</h3>

            <p>
                Monitor your request status.
            </p>

            <a href="history.php">
                View Requests
            </a>

        </div>

        <div class="action-card">

            <div class="icon purple">🔎</div>

            <h3>Track Document</h3>

            <p>
                Track your document anytime.
            </p>

            <a href="track.php">
                Track Now
            </a>

        </div>

        <div class="action-card">

            <div class="icon orange">🔔</div>

            <h3>Notifications</h3>

            <p>
                View your latest updates.
            </p>

            <a href="notifications.php">
                View Updates
            </a>

        </div>

    </div>

</div>


</div>





</div>







</body>

</html>
