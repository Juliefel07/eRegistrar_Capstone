<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

$query = "SELECT * FROM documents ORDER BY document_id DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Documents - CCTC eRegistrar</title>
     <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
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

        /* Page Header Title & Top Actions */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .page-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header p {
            font-size: 0.92rem;
            color: #64748b;
            margin: 0;
        }

        .btn-add {
            background-color: #2563eb;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .btn-add:hover {
            background-color: #1d4ed8;
        }

        /* Card Container for Data Table */
        .table-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        /* Custom Table Design */
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.92rem;
        }

        .custom-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .custom-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }

        .custom-table tr:last-child td {
            border-bottom: none;
        }

        .custom-table tr:hover {
            background-color: #f8fafc;
        }

        /* ID Badge */
        .doc-id {
            font-family: monospace;
            font-weight: 700;
            color: #64748b;
            background-color: #f1f5f9;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        /* Fee formatting */
        .doc-fee {
            font-weight: 600;
            color: #059669;
        }

        /* Status Badge Pills */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.active, .status-badge.available {
            background-color: #d1fae5;
            color: #047857;
        }

        .status-badge.inactive, .status-badge.disabled {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        /* Action Buttons */
        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #fee2e2;
            color: #dc2626;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-delete:hover {
            background-color: #dc2626;
            color: #ffffff;
        }

        /* Empty State */
        .empty-state {
            padding: 40px;
            text-align: center;
            color: #64748b;
        }

        /* ==========================================
           MOBILE RESPONSIVE CARD VIEW (≤ 768px)
           ========================================== */
        @media (max-width: 768px) {
            .admin-content {
                padding: 16px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-add {
                width: 100%;
                justify-content: center;
                box-sizing: border-box;
            }

            .table-card {
                background: transparent;
                border: none;
                box-shadow: none;
            }

            .custom-table, 
            .custom-table tbody, 
            .custom-table tr, 
            .custom-table td {
                display: block;
                width: 100%;
            }

            .custom-table thead {
                display: none;
            }

            .custom-table tr {
                background: #ffffff;
                border-radius: 12px;
                border: 1px solid #e2e8f0;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
                margin-bottom: 16px;
                padding: 16px;
                box-sizing: border-box;
            }

            .custom-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                border-bottom: 1px dashed #f1f5f9;
                font-size: 0.9rem;
            }

            .custom-table td:last-child {
                border-bottom: none;
                padding-top: 12px;
                margin-top: 4px;
            }

            .custom-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #64748b;
                font-size: 0.8rem;
                text-transform: uppercase;
            }

            .btn-delete {
                width: 100%;
                justify-content: center;
                padding: 10px;
            }
        }
    </style>
</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

    <div class="container">

        <!-- Page Header & Add Action -->
        <div class="page-header">
            <div>
                <h2><i class="fa-solid fa-folder-open" style="color: #2563eb;"></i> Manage Documents</h2>
                <p>Configure downloadable student documents, fees, and processing times.</p>
            </div>
            <a href="add_document.php" class="btn-add">
                <i class="fa-solid fa-plus"></i> Add New Document
            </a>
        </div>

        <!-- Table Card Container -->
        <div class="table-card">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Document Name</th>
                        <th>Description</th>
                        <th>Fee</th>
                        <th>Processing Days</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td data-label="ID">
                                    <span class="doc-id">#<?php echo $row['document_id']; ?></span>
                                </td>

                                <td data-label="Document Name">
                                    <strong><?php echo htmlspecialchars($row['document_name']); ?></strong>
                                </td>

                                <td data-label="Description">
                                    <span style="color: #475569; font-size: 0.88rem;">
                                        <?php echo htmlspecialchars($row['description'] ?? 'No description available.'); ?>
                                    </span>
                                </td>

                                <td data-label="Fee">
                                    <span class="doc-fee">₱<?php echo number_format($row['fee'], 2); ?></span>
                                </td>

                                <td data-label="Processing Days">
                                    <i class="fa-regular fa-clock" style="color: #94a3b8; margin-right: 4px;"></i>
                                    <?php echo (int)$row['processing_days']; ?> day(s)
                                </td>

                                <td data-label="Status">
                                    <span class="status-badge <?php echo strtolower($row['status']); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>

                                <td data-label="Action">
                                    <a class="btn-delete"
                                       href="delete_document.php?id=<?php echo $row['document_id']; ?>"
                                       onclick="return confirm('Are you sure you want to delete this document?')">
                                        <i class="fa-solid fa-trash-can"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <i class="fa-solid fa-folder-minus" style="font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                No document records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>

</body>
</html>