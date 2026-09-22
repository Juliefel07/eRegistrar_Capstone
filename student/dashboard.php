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
/* ==========================================
   DASHBOARD STATISTICS
========================================== */

$user_id = $_SESSION['user_id'];

$pending = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) total
FROM requests
WHERE user_id='$user_id'
AND status='Pending'
"))['total'];

$processing = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) total
FROM requests
WHERE user_id='$user_id'
AND status='Processing'
"))['total'];

$ready = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) total
FROM requests
WHERE user_id='$user_id'
AND status='Ready'
"))['total'];

$completed = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) total
FROM requests
WHERE user_id='$user_id'
AND status='Completed'
"))['total'];
if(!$documents){
    die(mysqli_error($conn));
}
/* ==========================================
   RECENT REQUESTS
========================================== */

$recentRequests = mysqli_query($conn,"
SELECT
    r.request_id,
    r.tracking_no,
    d.document_name,
    r.status,
    r.request_date
FROM requests r
INNER JOIN documents d
ON r.document_id = d.document_id
WHERE r.user_id='$user_id'
ORDER BY r.request_date DESC
LIMIT 5
");
?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>Student Dashboard</title>


<link rel="stylesheet" href="../assets/css/student.css">
<link rel="stylesheet" href="../assets/css/navbar.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>


<body>


<?php include("navbar.php"); ?>





<div class="student-main">



<!-- HERO SECTION -->
<!-- HERO -->

<div class="dashboard-hero">

    <div class="banner-overlay"></div>

    <div class="banner-content">

        <h1>
            Welcome back,
            <?= htmlspecialchars($_SESSION['fullname']); ?> 
        </h1>

        <p>
            Manage your document requests, monitor progress,
            and receive updates from the Registrar's Office.
        </p>

        <a href="request.php" class="hero-btn">
            📄 Request Document
        </a>

    </div>

</div>
<div class="stats-grid">

    <div class="stat-card">

        <div class="stat-icon pending">
            🚫
        </div>

        <div>

            <h2><?= $pending ?></h2>

            <span>Pending</span>

        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon processing">
            ⌛
        </div>

        <div>

            <h2><?= $processing ?></h2>

            <span>Processing</span>

        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon ready">
            📝
        </div>

        <div>

            <h2><?= $ready ?></h2>

            <span>Ready</span>

        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon completed">
            ☑️
        </div>

        <div>

            <h2><?= $completed ?></h2>

            <span>Completed</span>

        </div>

    </div>

</div>

<div class="dashboard-row">

    <div class="dashboard-card recent-card">

        <div class="card-header">

            <h2> Recent Requests</h2>

            <a href="history.php">View All</a>

        </div>

        <table class="recent-table">

            <thead>

            <tr>

                <th>Tracking</th>
                <th>Document</th>
                <th>Status</th>
                <th></th>

            </tr>

            </thead>

            <tbody>

            <?php while($row=mysqli_fetch_assoc($recentRequests)){ ?>

                <tr>

                    <td><?= htmlspecialchars($row['tracking_no']); ?></td>

                    <td><?= htmlspecialchars($row['document_name']); ?></td>

                    <td>

                        <span class="status <?= strtolower($row['status']); ?>">

                            <?= htmlspecialchars($row['status']); ?>

                        </span>

                    </td>

                    <td>

                        <a href="history.php">

                            View

                        </a>

                    </td>

                </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>

<div class="dashboard-card">

<h2>📢 Announcements</h2>

<div class="announcement">

<h4>Registrar Office</h4>

<p>
Online document requests are now available.
</p>

</div>

<div class="announcement">

<h4>Office Hours</h4>

<p>

Monday-Friday

8:00 AM - 5:00 PM

</p>

</div>

</div>

</div>



<!-- SCHOOL INFORMATION -->
<!-- MAIN GRID -->

<div class="dashboard-grid">

    <!-- LEFT -->

    <div class="dashboard-card">

        <h2>Available Documents</h2>

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

                    <td><?= htmlspecialchars($doc['document_name']); ?></td>

                    <td>₱<?= number_format($doc['fee'],2); ?></td>

                    <td><?= $doc['processing_days']; ?></td>

                </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>



    <!-- RIGHT -->

    <div>

        <div class="dashboard-card">

            <h2> Quick Actions</h2>

            <div class="quick-grid">

                <a href="request.php" class="quick-btn">
                    
                    <span>Request</span>
                </a>

                <a href="history.php" class="quick-btn">
                    
                    <span>History</span>
                </a>

                <a href="track.php" class="quick-btn">
                    
                    <span>Track</span>
                </a>

                <a href="messages.php" class="quick-btn">
                    
                    <span>Messages</span>
                </a>

            </div>

        </div>

<div class="dashboard-card office-card">

    <div class="office-header">
        <div class="office-header-icon">
            <i class="fa-solid fa-building"></i>
        </div>
        <h2>Office Information</h2>
    </div>

    <div class="office-item">
        <div class="office-icon">
            <i class="fa-solid fa-clock"></i>
        </div>

        <div class="office-details">
            <h4>Office Hours</h4>
            <p>Monday - Friday</p>
            <span>8:00 AM – 5:00 PM</span>
        </div>
    </div>

    <div class="office-item">
        <div class="office-icon">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>

        <div class="office-details">
            <h4>Processing Time</h4>
            <p>3–5 Working Days</p>
            <span>Depending on the requested document.</span>
        </div>
    </div>

    <div class="office-item">
        <div class="office-icon">
            <i class="fa-solid fa-location-dot"></i>
        </div>

        <div class="office-details">
            <h4>Office Location</h4>
            <p>Registrar's Office</p>
            <span>University Campus</span>
        </div>
    </div>

</div>


    </div>

</div>










</body>

</html>
