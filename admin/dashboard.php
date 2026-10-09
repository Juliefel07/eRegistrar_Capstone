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
$student_count = mysqli_fetch_assoc($result)['total'] ?? 0;

// Documents
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM documents");
$document_count = mysqli_fetch_assoc($result)['total'] ?? 0;

// Pending Requests
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM requests WHERE status='Pending'");
$pending_count = mysqli_fetch_assoc($result)['total'] ?? 0;

// Approved Requests
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM requests WHERE status='Approved'");
$approved_count = mysqli_fetch_assoc($result)['total'] ?? 0;

// Ready for Claim
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM requests WHERE status='Ready for Claim'");
$ready_count = mysqli_fetch_assoc($result)['total'] ?? 0;

// Claimed
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM requests WHERE status='Claimed'");
$claimed_count = mysqli_fetch_assoc($result)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CCTC eRegistrar</title>
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* Modern Base Styles */
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        .admin-content {
            padding: 24px;
            max-width: 1200px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        /* Hero Welcome Banner */
        .dashboard-welcome {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 45%, #2563eb 80%, #60a5fa 100%);
            border-radius: 14px;
            padding: 36px;
            color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25), 0 8px 10px -6px rgba(15, 23, 42, 0.25);
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dashboard-welcome-content {
            position: relative;
            z-index: 1;
            max-width: 65%;
        }

        /* Right-Side Banner Image Styles */
        .dashboard-welcome-image {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-right: 10px;
        }

        .dashboard-welcome-image img {
            max-height: 150px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.2));
        }

        /* Welcome Badge (Pill Style) */
        .welcome-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            margin-bottom: 16px;
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        /* Large Multi-Line Title Styling */
        .dashboard-welcome h2 {
            font-size: 2.2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 12px 0;
            line-height: 1.15;
            letter-spacing: -0.5px;
        }

        /* Admin Name Highlight */
        .dashboard-welcome h2 .admin-name {
            color: #fde047; /* Glowing gold */
            font-weight: 900;
            text-shadow: 0 2px 12px rgba(253, 224, 71, 0.35);
        }

        .dashboard-welcome p {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
            line-height: 1.5;
            font-weight: 400;
        }

        /* Section Titles */
        .section-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 12px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Overview Stat Cards Grid (Compact) */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 28px;
        }

        .card {
            background: #ffffff;
            border-radius: 10px;
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }

        .card h3 {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin: 0 0 4px 0;
        }

        .card p {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            line-height: 1.1;
        }

        /* Quick Actions Grid */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
        }

        .action-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .action-box:hover {
            border-color: #cbd5e1;
            background-color: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(0, 0, 0, 0.05);
        }

        .action-box .icon {
            font-size: 1.25rem;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background-color: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .action-box h3 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 4px 0;
        }

        .action-box p {
            font-size: 0.8rem;
            color: #64748b;
            margin: 0;
            line-height: 1.35;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .admin-content {
                padding: 16px;
            }

            .dashboard-welcome {
                flex-direction: column;
                text-align: center;
                padding: 24px;
            }

            .dashboard-welcome-content {
                max-width: 100%;
            }

            .dashboard-welcome h2 {
                font-size: 1.6rem;
            }

            .dashboard-welcome-image {
                margin-top: 20px;
                padding-right: 0;
            }

            .dashboard-welcome-image img {
                max-height: 110px;
            }

            .dashboard-cards {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }

            .card {
                padding: 10px 12px;
            }

            .card p {
                font-size: 1.25rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }

        @media (max-width: 480px) {
            .dashboard-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

    <div class="container">

        <!-- Welcome Section with Large Multi-Line Typography & track.png -->
        <div class="dashboard-welcome">
            <div class="dashboard-welcome-content">
                <div class="welcome-badge">
                    <i class="fa-solid fa-shield-halved"></i> Administrator Portal
                </div>
                <h2>
                    Welcome back,<br>
                    <span class="admin-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span> ! 
                </h2>
                <p>Manage student records, monitor document requests, and oversee registrar operations seamlessly.</p>
            </div>
            
        </div>

        <!-- Statistics -->
        <h2 class="section-title">
            <i class="fa-solid fa-chart-pie" style="color: #2563eb;"></i> Dashboard Overview
        </h2>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Students</h3>
                <p><?php echo $student_count; ?></p>
            </div>

            <div class="card">
                <h3>Documents</h3>
                <p><?php echo $document_count; ?></p>
            </div>

            <div class="card">
                <h3>Pending</h3>
                <p><?php echo $pending_count; ?></p>
            </div>

            <div class="card">
                <h3>Approved</h3>
                <p><?php echo $approved_count; ?></p>
            </div>

            <div class="card">
                <h3>Ready for Claim</h3>
                <p><?php echo $ready_count; ?></p>
            </div>

            <div class="card">
                <h3>Claimed</h3>
                <p><?php echo $claimed_count; ?></p>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2 class="section-title">
            <i class="fa-solid fa-bolt" style="color: #2563eb;"></i> Quick Actions
        </h2>

        <div class="quick-actions">
            <a href="students.php" class="action-box">
                <div class="icon"><i class="fa-solid fa-user-graduate"></i></div>
                <h3>Students</h3>
                <p>Manage registered student accounts and records.</p>
            </a>

            <a href="documents.php" class="action-box">
                <div class="icon"><i class="fa-solid fa-file-lines"></i></div>
                <h3>Documents</h3>
                <p>Configure available documents and fees.</p>
            </a>

            <a href="requests.php" class="action-box">
                <div class="icon"><i class="fa-solid fa-clipboard-list"></i></div>
                <h3>Requests</h3>
                <p>Review, approve, and process student requests.</p>
            </a>

            <a href="reports.php" class="action-box">
                <div class="icon"><i class="fa-solid fa-chart-line"></i></div>
                <h3>Reports</h3>
                <p>View administrative metrics and reports.</p>
            </a>
        </div>

    </div>

</div>

</body>
</html>