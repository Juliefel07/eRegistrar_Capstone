<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "
    SELECT 
        r.request_id,
        r.tracking_no,
        u.fullname,
        d.document_name,
        r.purpose,
        r.quantity,
        r.status,
        r.request_date,
        r.uploaded_file
    FROM requests r
    JOIN users u ON r.user_id = u.user_id
    JOIN documents d ON r.document_id = d.document_id
    WHERE r.user_id = ?
    ORDER BY r.request_date DESC
");

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die(mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - eRegistrar</title>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">

    <style>
        /* Mobile-First Layout & Responsiveness */
        .student-main {
            padding: 20px 15px;
            max-width: 1200px;
            margin: 0 auto;
            padding-bottom: 90px; /* Space for mobile bottom navigation */
        }

        .history-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .history-card h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 5px;
        }

        .history-card p {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 20px;
        }

        /* Responsive Table Container */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .request-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
            white-space: nowrap;
        }

        .request-table th {
            background: #f8f9fa;
            color: #495057;
            padding: 12px 15px;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
        }

        .request-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            color: #333;
            vertical-align: middle;
        }

        .request-table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Status Badges */
        .status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            text-transform: capitalize;
        }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.approved { background: #d4edda; color: #155724; }
        .status.rejected { background: #f8d7da; color: #721c24; }
        .status.completed { background: #cce5ff; color: #004085; }

        /* Action Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn.approve {
            background: #e7f1ff;
            color: #0056b3;
        }

        .btn.approve:hover {
            background: #d0e1fd;
        }

        .no-action {
            color: #adb5bd;
            font-style: italic;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        /* Mobile specific spacing adjustments */
        @media (max-width: 576px) {
            .student-main {
                padding: 10px 8px;
            }
            .history-card {
                padding: 15px 12px;
                border-radius: 8px;
            }
        }
    </style>
</head>

<body>

    <?php include("navbar.php"); ?>

    <div class="student-main">
        <div class="history-card">
            <h2>My Document Requests</h2>
            <p>Track the progress of your submitted requests.</p>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="request-table">
                        <thead>
                            <tr>
                                <th>Tracking No.</th>
                                <th>Document</th>
                                <th>Purpose</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Requirement File</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['tracking_no']); ?></strong></td>
                                    <td><?= htmlspecialchars($row['document_name']); ?></td>
                                    <td><?= htmlspecialchars($row['purpose']); ?></td>
                                    <td><?= $row['quantity']; ?></td>
                                    <td>
                                        <span class="status <?= strtolower(str_replace(' ', '-', $row['status'])); ?>">
                                            <?= htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?= date("M d, Y", strtotime($row['request_date'])); ?></td>
                                    <td>
                                        <?php if (!empty($row['uploaded_file'])): ?>
                                            <a class="btn approve" href="../assets/uploads/<?= htmlspecialchars($row['uploaded_file']); ?>" target="_blank">
                                                <i class="fa-solid fa-file-arrow-down"></i> View
                                            </a>
                                        <?php else: ?>
                                            <span class="no-action">No File</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a class="btn approve" href="request_details.php?id=<?= $row['request_id']; ?>">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-folder-open" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                    <p>No document requests found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>