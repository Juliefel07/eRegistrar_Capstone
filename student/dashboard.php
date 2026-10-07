<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all available documents
$documents = mysqli_query($conn,"SELECT * FROM documents ORDER BY document_id DESC");

// Get statistics
$pending = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM requests WHERE user_id='$user_id' AND status='Pending'"))['total'];
$processing = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM requests WHERE user_id='$user_id' AND status='Processing'"))['total'];
$ready = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM requests WHERE user_id='$user_id' AND status='Ready'"))['total'];
$completed = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM requests WHERE user_id='$user_id' AND status='Completed'"))['total'];

// Recent requests
$recentRequests = mysqli_query($conn,"
    SELECT r.request_id, r.tracking_no, d.document_name, r.status, r.request_date
    FROM requests r
    INNER JOIN documents d ON r.document_id = d.document_id
    WHERE r.user_id='$user_id'
    ORDER BY r.request_date DESC
    LIMIT 5
");

// Fetch active student announcements from database
$student_announcements = mysqli_query($conn, "
    SELECT a.*, u.fullname AS author 
    FROM announcements a 
    LEFT JOIN users u ON a.created_by = u.user_id 
    WHERE a.status = 'active' AND a.target_audience IN ('all', 'students') 
    ORDER BY a.created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>eRegistrar - Student Portal</title>
    
    <style>
        /* RESET & SYSTEM STYLING */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #0056b3;
            --primary-dark: #002d62;
            --bg-body: #f4f6f9;
            --card-bg: #ffffff;
            --text-dark: #333333;
            --text-muted: #6c757d;
            --border: #e9ecef;
            --radius-lg: 16px;
            --radius-md: 10px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            padding-bottom: 70px; /* Space for Mobile Bottom Bar */
        }

        @media (min-width: 992px) {
            body { padding-bottom: 0; }
        }

        /* MAIN CONTAINER & DASHBOARD LAYOUT */
        .container {
            max-width: 1250px;
            margin: 0 auto;
            padding: 20px 16px;
        }

        /* Hero Banner */
        .hero-banner {
            background: linear-gradient(135deg, #0056b3, #002d62);
            color: white;
            border-radius: var(--radius-lg);
            padding: 30px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 14px rgba(0,86,179,0.15);
        }

        .hero-text h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .hero-text p {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 20px;
            max-width: 500px;
            line-height: 1.4;
        }

        .btn-request {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
        }

        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            border: 1px solid var(--border);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon.pending { background: #fff8e6; color: #d97706; }
        .stat-icon.processing { background: #e0f2fe; color: #0284c7; }
        .stat-icon.ready { background: #dcfce7; color: #16a34a; }
        .stat-icon.completed { background: #f3e8ff; color: #9333ea; }

        .stat-info h3 { font-size: 20px; }
        .stat-info span { font-size: 12px; color: var(--text-muted); }

        /* Multi-Column Main Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: 20px;
            border: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title a {
            font-size: 12px;
            color: var(--primary);
            text-decoration: none;
        }

        /* Responsive Table */
        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table.custom-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        table.custom-table th, table.custom-table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border);
            text-align: left;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.processing { background: #cce5ff; color: #004085; }
        .status-badge.ready { background: #d4edda; color: #155724; }
        .status-badge.completed { background: #e2e3e5; color: #383d41; }

        /* DYNAMIC ANNOUNCEMENT CARD STYLES */
        .announcement-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 12px;
        }

        .announcement-item:last-child {
            margin-bottom: 0;
        }

        .announcement-item h4 {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .announcement-item p {
            font-size: 13px;
            color: #475569;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .announcement-img {
            width: 100%;
            max-height: 180px;
            border-radius: 6px;
            object-fit: cover;
            margin-bottom: 8px;
            border: 1px solid #cbd5e1;
        }

        .announcement-date {
            font-size: 11px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* MEDIA QUERIES */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .hero-banner {
                padding: 20px 16px;
                text-align: center;
                flex-direction: column;
            }

            .hero-text h1 {
                font-size: 18px;
            }

            .btn-request {
                width: 100%;
                justify-content: center;
            }

            table.custom-table thead { display: none; }
            table.custom-table, table.custom-table tbody, table.custom-table tr, table.custom-table td {
                display: block;
                width: 100%;
            }
            table.custom-table tr {
                border: 1px solid var(--border);
                border-radius: 8px;
                margin-bottom: 10px;
                padding: 10px;
                background: #fff;
            }
            table.custom-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 6px 0;
                border-bottom: 1px dashed #eee;
            }
            table.custom-table td:last-child { border-bottom: none; }
            table.custom-table td::before {
                content: attr(data-label);
                font-weight: bold;
                color: var(--text-muted);
            }
        }
    </style>
    
</head>
<body>

    <!-- INCLUDE SHARED NAVIGATION BAR -->
    <?php require_once __DIR__ . "/navbar.php"; ?>

    <!-- MAIN BODY CONTENT -->
    <main class="container">

        <!-- Hero Section -->
        <section class="hero-banner">
            <div class="hero-text">
                <h1>Welcome back, <?= htmlspecialchars($_SESSION['fullname']); ?></h1>
                <p>Manage your document requests, monitor progress, and receive real-time updates from the Registrar's Office.</p>
                <a href="request.php" class="btn-request"><i class="fa-solid fa-file-circle-plus"></i> Request Document</a>
            </div>
        </section>

        <!-- Stats Grid -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon pending"><i class="fa-solid fa-ban"></i></div>
                <div class="stat-info">
                    <h3><?= $pending ?></h3>
                    <span>Pending</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon processing"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="stat-info">
                    <h3><?= $processing ?></h3>
                    <span>Processing</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon ready"><i class="fa-solid fa-file-lines"></i></div>
                <div class="stat-info">
                    <h3><?= $ready ?></h3>
                    <span>Ready</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon completed"><i class="fa-solid fa-square-check"></i></div>
                <div class="stat-info">
                    <h3><?= $completed ?></h3>
                    <span>Completed</span>
                </div>
            </div>
        </section>

        <!-- Grid Layout for Tables & Widgets -->
        <div class="dashboard-grid">
            <!-- Left Main Column -->
            <div>
                <!-- Recent Requests Card -->
                <div class="card">
                    <div class="card-title">
                        Recent Requests
                        <a href="history.php">View All</a>
                    </div>
                    <div class="table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Tracking No.</th>
                                    <th>Document</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($recentRequests) > 0) { ?>
                                    <?php while($row = mysqli_fetch_assoc($recentRequests)) { ?>
                                        <tr>
                                            <td data-label="Tracking No."><strong><?= htmlspecialchars($row['tracking_no']); ?></strong></td>
                                            <td data-label="Document"><?= htmlspecialchars($row['document_name']); ?></td>
                                            <td data-label="Status">
                                                <span class="status-badge <?= strtolower($row['status']); ?>">
                                                    <?= htmlspecialchars($row['status']); ?>
                                                </span>
                                            </td>
                                            <td data-label="Action"><a href="history.php" style="color:var(--primary);">View</a></td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr><td colspan="4" style="text-align:center;">No recent requests found.</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Available Documents Card -->
                <div class="card">
                    <div class="card-title">Available Documents</div>
                    <div class="table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Document Name</th>
                                    <th>Fee</th>
                                    <th>Processing Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($doc = mysqli_fetch_assoc($documents)) { ?>
                                    <tr>
                                        <td data-label="Document"><?= htmlspecialchars($doc['document_name']); ?></td>
                                        <td data-label="Fee">₱<?= number_format($doc['fee'], 2); ?></td>
                                        <td data-label="Processing"><?= $doc['processing_days']; ?> Working Days</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar Column -->
            <div>
                <!-- DYNAMIC ANNOUNCEMENTS CARD -->
                <div class="card">
                    <div class="card-title"><i class="fa-solid fa-bullhorn" style="color: var(--primary);"></i> Announcements</div>
                    
                    <?php if ($student_announcements && mysqli_num_rows($student_announcements) > 0): ?>
                        <?php while ($ann = mysqli_fetch_assoc($student_announcements)): ?>
                            <div class="announcement-item">
                                <h4><?= htmlspecialchars($ann['title']); ?></h4>
                                <p><?= nl2br(htmlspecialchars($ann['content'])); ?></p>
                                
                                <?php if (!empty($ann['image_path'])): ?>
                                    <img src="../<?= htmlspecialchars($ann['image_path']); ?>" alt="Banner" class="announcement-img">
                                <?php endif; ?>

                                <div class="announcement-date">
                                    <i class="fa-regular fa-clock"></i> <?= date("M d, Y - h:i A", strtotime($ann['created_at'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="font-size:13px; color:#666; font-style:italic;">No official announcements posted at this time.</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-title">Office Information</div>
                    <div style="font-size:13px; display:flex; flex-direction:column; gap:10px;">
                        <div>
                            <strong>Office Hours:</strong>
                            <p style="color:#666;">Monday – Friday (8:00 AM – 5:00 PM)</p>
                        </div>
                        <div>
                            <strong>Location:</strong>
                            <p style="color:#666;">Registrar's Office, Main Campus</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

</body>
</html>