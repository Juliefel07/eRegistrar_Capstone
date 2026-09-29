<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Search database for matching requests or document names
$results = [];
if ($search_query !== '') {
    $escaped_query = mysqli_real_escape_string($conn, $search_query);
    
    $sql = "
        SELECT r.request_id, r.tracking_no, d.document_name, r.status, r.request_date
        FROM requests r
        INNER JOIN documents d ON r.document_id = d.document_id
        WHERE r.user_id = '$user_id' 
        AND (r.tracking_no LIKE '%$escaped_query%' OR d.document_name LIKE '%$escaped_query%')
        ORDER BY r.request_date DESC
    ";
    $results = mysqli_query($conn, $sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - eRegistrar</title>
    <!-- Include your global CSS/dashboard styles here -->
    <link rel="stylesheet" href="../assets/css/student.css">
    
    <!-- Mobile Responsive Table Styling -->
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Responsive Table adjustments for mobile */
        @media (max-width: 768px) {
            .custom-table thead {
                display: none; /* Hide table headers on small screens */
            }
            .custom-table, .custom-table tbody, .custom-table tr, .custom-table td {
                display: block;
                width: 100%;
            }
            .custom-table tr {
                margin-bottom: 15px;
                background: #ffffff;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                padding: 10px;
            }
            .custom-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                text-align: right;
                padding: 8px 10px;
                border-bottom: 1px solid #f1f3f5;
            }
            .custom-table td:last-child {
                border-bottom: none;
            }
            .custom-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #6c757d;
                text-align: left;
            }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . "/navbar.php"; ?>

    <main class="container" style="padding: 30px 16px;">
        <h2 style="font-size: 22px; color: #333; margin-bottom: 5px;">Search Results for "<?= htmlspecialchars($search_query); ?>"</h2>
        <p style="color: var(--text-muted, #6c757d); margin-bottom: 20px; font-size: 14px;">
            <?= $results ? mysqli_num_rows($results) : 0; ?> result(s) found.
        </p>

        <div class="card" style="background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <div class="table-wrap" style="overflow-x: auto;">
                <table class="custom-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #e9ecef; color: #495057; font-size: 13px;">
                            <th style="padding: 12px;">Tracking No.</th>
                            <th style="padding: 12px;">Document</th>
                            <th style="padding: 12px;">Status</th>
                            <th style="padding: 12px;">Date Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($results && mysqli_num_rows($results) > 0) { ?>
                            <?php while($row = mysqli_fetch_assoc($results)) { ?>
                                <tr style="border-bottom: 1px solid #f1f3f5; font-size: 13px;">
                                    <td data-label="Tracking No." style="padding: 12px;"><strong><?= htmlspecialchars($row['tracking_no']); ?></strong></td>
                                    <td data-label="Document" style="padding: 12px;"><?= htmlspecialchars($row['document_name']); ?></td>
                                    <td data-label="Status" style="padding: 12px;">
                                        <span class="status-badge <?= strtolower($row['status']); ?>" style="padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                            <?= htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td data-label="Date Requested" style="padding: 12px;"><?= htmlspecialchars($row['request_date']); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr><td colspan="4" style="text-align:center; padding: 20px; color: #6c757d;">No matching requests found.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>