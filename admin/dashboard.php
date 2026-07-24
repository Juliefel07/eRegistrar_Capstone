<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

/* =========================
   DASHBOARD COUNTS
========================= */

// Students
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='Student'");
$student_count = mysqli_fetch_assoc($result)['total'];

// Documents
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM documents");
$document_count = mysqli_fetch_assoc($result)['total'];

// Pending Requests
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM requests WHERE status='Pending'");
$pending_count = mysqli_fetch_assoc($result)['total'];

// Approved Requests
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM requests WHERE status='Approved'");
$approved_count = mysqli_fetch_assoc($result)['total'];

// Ready for Claim
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM requests WHERE status='Ready for Claim'");
$ready_count = mysqli_fetch_assoc($result)['total'];

// Claimed
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM requests WHERE status='Claimed'");
$claimed_count = mysqli_fetch_assoc($result)['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

    <?php include("header.php"); ?>

    <div class="container">

        <!-- Welcome Section -->
        <div class="dashboard-welcome">

            <h2>
                Welcome, <?php echo $_SESSION['fullname']; ?>!!
            </h2>

            <p>
                Manage student records, monitor document requests,
                and oversee registrar operations from one dashboard.
            </p>

        </div>

        <!-- Statistics -->
        <h2 class="section-title">
            Dashboard Overview
        </h2>

        <div class="dashboard-cards">

            <div class="card white">
                <h3>Students</h3>
                <p><?php echo $student_count; ?></p>
            </div>

            <div class="card yellow">
                <h3>Documents</h3>
                <p><?php echo $document_count; ?></p>
            </div>

             <div class="card red">
                <h3>Pending</h3>
                <p><?php echo $pending_count; ?></p>
            </div>

             <div class="card green">
                <h3>Approved</h3>
                <p><?php echo $approved_count; ?></p>
            </div>

             <div class="card purple">
                <h3>Ready for Claim</h3>
                <p><?php echo $ready_count; ?></p>
            </div>

             <div class="card dark blue">
                <h3>Claimed</h3>
                <p><?php echo $claimed_count; ?></p>
            </div>

        </div>

        <!-- Quick Actions -->
        <h2 class="section-title">
            Quick Actions
        </h2>

        <div class="quick-actions">

            <a href="students.php" class="action-box">
                <div class="icon">👨‍🎓</div>
                <h3>Students</h3>
                <p>Manage student accounts.</p>
            </a>

            <a href="documents.php" class="action-box">
                <div class="icon">📄</div>
                <h3>Documents</h3>
                <p>Manage available documents.</p>
            </a>

            <a href="requests.php" class="action-box">
                <div class="icon">📋</div>
                <h3>Requests</h3>
                <p>Approve and process requests.</p>
            </a>

            <a href="reports.php" class="action-box">
                <div class="icon">📊</div>
                <h3>Reports</h3>
                <p>View registrar statistics.</p>
            </a>

        </div>

    </div>

</div>

</body>

</html>