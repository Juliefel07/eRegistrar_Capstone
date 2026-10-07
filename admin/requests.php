<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

// Check admin/staff login and role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../login.php");
    exit();
}

$sql = "
SELECT
    r.request_id,
    r.tracking_no,
    u.fullname,
    u.email,
    u.contact_no,
    d.document_name,
    r.purpose,
    r.quantity,
    r.status,
    r.request_date,
    r.payment_proof,
    p.fullname AS processor_name
    FROM requests r
    JOIN users u ON r.user_id = u.user_id
    JOIN documents d ON r.document_id = d.document_id
    LEFT JOIN users p ON r.processed_by = p.user_id
    WHERE LOWER(r.status) NOT IN ('completed', 'claimed')
    ORDER BY r.request_date DESC
";

$result = mysqli_query($conn,$sql);

if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests - CCTC eRegistrar</title>
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
            max-width: 1300px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        /* Page Header Title */
        .page-header {
            margin-bottom: 24px;
        }

        .page-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header p {
            font-size: 0.95rem;
            color: #64748b;
            margin: 0;
        }

        /* Card Container for Data Table */
        .table-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            overflow-x: auto;
        }

        /* Custom Table Design */
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.90rem;
            white-space: nowrap;
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

        /* Tracking Number Code Style */
        .tracking-code {
            font-family: monospace;
            font-weight: 700;
            color: #2563eb;
            background-color: #eff6ff;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.85rem;
        }

        /* Status Badge Pills */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
        }

        .badge-pending           { background-color: #fef3c7; color: #b45309; }
        .badge-approved         { background-color: #d1fae5; color: #047857; }
        .badge-processing       { background-color: #e0f2fe; color: #0369a1; }
        .badge-payment-uploaded { background-color: #ede9fe; color: #7c3aed; }
        .badge-ready            { background-color: #f3e8ff; color: #6b21a8; }
        .badge-claimed          { background-color: #e2e8f0; color: #334155; }
        .badge-rejected         { background-color: #fee2e2; color: #b91c1c; }

        /* Action Buttons Container & Buttons */
        .action-group {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #2563eb;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .btn-action:hover {
            background-color: #1d4ed8;
        }

        .btn-receipt {
            background-color: #0284c7;
        }
        .btn-receipt:hover {
            background-color: #0369a1;
        }

        .btn-secondary-action {
            background-color: #64748b;
        }
        .btn-secondary-action:hover {
            background-color: #475569;
        }

        .text-muted {
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            padding: 40px;
            text-align: center;
            color: #64748b;
        }

        /* ==========================================
            FLOATING MODAL STYLES
           ========================================== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 16px;
        }

        .modal-container {
            background: #ffffff;
            width: 100%;
            max-width: 500px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: modalFadeIn 0.25s ease-out;
            overflow: hidden;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            padding: 16px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-close-btn {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #64748b;
            cursor: pointer;
        }

        .modal-close-btn:hover {
            color: #0f172a;
        }

        .modal-body {
            padding: 20px;
            font-size: 0.92rem;
            color: #334155;
        }

        .modal-row {
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #f1f5f9;
            padding-bottom: 8px;
        }

        .modal-row span.label {
            font-weight: 600;
            color: #64748b;
        }

        .modal-footer {
            padding: 12px 20px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: right;
        }

        /* ==========================================
            MOBILE RESPONSIVE CARD VIEW (≤ 768px)
           ========================================== */
        @media (max-width: 768px) {
            .admin-content {
                padding: 16px;
            }

            .table-card {
                background: transparent;
                border: none;
                box-shadow: none;
                overflow: visible;
            }

            .custom-table, 
            .custom-table tbody, 
            .custom-table tr, 
            .custom-table td {
                display: block;
                width: 100%;
                white-space: normal;
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

            .action-group {
                flex-direction: column;
                width: 100%;
            }

            .btn-action {
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

        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fa-solid fa-file-circle-check" style="color: #2563eb;"></i> Student Document Requests</h2>
            <p>Review, track, verify payment receipts, and update processing statuses.</p>
        </div>

        <!-- Request Table / Cards Container -->
        <div class="table-card">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Tracking No</th>
                        <th>Student</th>
                        <th>Document</th>
                        <th>Purpose</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Payment Proof</th>
                        <th>Processed By</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td data-label="Tracking No">
                                    <span class="tracking-code"><?php echo htmlspecialchars($row['tracking_no']); ?></span>
                                </td>

                                <td data-label="Student">
                                    <strong><?php echo htmlspecialchars($row['fullname']); ?></strong>
                                </td>

                                <td data-label="Document">
                                    <?php echo htmlspecialchars($row['document_name']); ?>
                                </td>

                                <td data-label="Purpose">
                                    <?php echo htmlspecialchars($row['purpose'] ?? 'N/A'); ?>
                                </td>

                                <td data-label="Quantity">
                                    <?php echo (int)$row['quantity']; ?>
                                </td>

                                <td data-label="Status">
                                    <?php
                                    $status = $row['status'];$badgeClass = 'badge-pending';

                                    if ($status === 'Approved')$badgeClass = 'badge-approved';
                                    elseif ($status === 'Processing')$badgeClass = 'badge-processing';
                                    elseif ($status === 'Payment Uploaded')$badgeClass = 'badge-payment-uploaded';
                                    elseif ($status === 'Ready for Claim')$badgeClass = 'badge-ready';
                                    elseif ($status === 'Claimed')$badgeClass = 'badge-claimed';
                                    elseif ($status === 'Rejected')$badgeClass = 'badge-rejected';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>

                                <!-- Payment Proof Column -->
                                <td data-label="Payment Proof">
                                    <?php if (!empty($row['payment_proof'])): ?>
                                        <a class="btn-action btn-receipt" href="../assets/uploads/<?php echo htmlspecialchars($row['payment_proof']); ?>" target="_blank">
                                            <i class="fa-solid fa-receipt"></i> View Receipt
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No Receipt Yet</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Handled By Column -->
                                <td data-label="Handled By">
                                    <?php if (!empty($row['processor_name'])): ?>
                                        <span style="font-weight: 600; color: #0284c7;">
                                            <i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($row['processor_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Unassigned</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Date">
                                    <?php echo date("M d, Y", strtotime($row['request_date'])); ?>
                                </td>

                                <!-- Action Column / Workflow Buttons -->
                                <td data-label="Action">
                                    <div class="action-group">
                                        <?php 
                                            $admin_status_lower = strtolower(trim($row['status']));
                                        ?>

                                        <?php if ($admin_status_lower === 'claimed'): ?>
                                            <button type="button" class="btn-action btn-secondary-action" onclick="openDetailsModal(
                                                '<?php echo htmlspecialchars($row['tracking_no'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['fullname'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['document_name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['purpose'] ?? 'N/A', ENT_QUOTES); ?>',
                                                '<?php echo (int)$row['quantity']; ?>',
                                                '<?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>',
                                                '<?php echo date("M d, Y h:i A", strtotime($row['request_date'])); ?>',
                                                '<?php echo htmlspecialchars($row['processor_name'] ?? 'None', ENT_QUOTES); ?>'
                                            )">
                                                <i class="fa-solid fa-eye"></i> View Details
                                            </button>
                                        <?php else: ?>
                                            <a href="view_request.php?id=<?php echo $row['request_id']; ?>" class="btn-action btn-secondary-action">
                                                <i class="fa-solid fa-eye"></i> View Details
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($row['status'] == "Pending"): ?>
                                            <span class="text-muted">Awaiting Payment</span>

                                        <?php elseif ($row['status'] == "Payment Uploaded" || ($row['status'] == "Approved" && !empty($row['payment_proof']))): ?>
                                            <a class="btn-action" href="processing.php?id=<?php echo $row['request_id']; ?>">
                                                <i class="fa-solid fa-gears"></i> Start Processing
                                            </a>

                                        <?php elseif ($row['status'] == "Processing"): ?>
                                            <a class="btn-action" href="ready.php?id=<?php echo $row['request_id']; ?>">
                                                <i class="fa-solid fa-box-archive"></i> Ready for Claim
                                            </a>

                                        <?php elseif ($row['status'] == "Ready for Claim"): ?>
                                            <a class="btn-action" href="claimed.php?id=<?php echo $row['request_id']; ?>">
                                                <i class="fa-solid fa-check-double"></i> Mark Claimed
                                            </a>

                                        <?php elseif ($row['status'] == "Claimed"): ?>
                                            <span class="text-muted"><i class="fa-solid fa-circle-check"></i> Completed</span>

                                        <?php elseif ($row['status'] == "Rejected"): ?>
                                            <span class="text-muted"><i class="fa-solid fa-circle-xmark"></i> Rejected</span>

                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="empty-state">
                                <i class="fa-solid fa-inbox" style="font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                No document requests found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>

<!-- Floating Details Modal Structure -->
<div id="detailsModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3><i class="fa-solid fa-circle-info" style="color: #2563eb;"></i> Request Details</h3>
            <button type="button" class="modal-close-btn" onclick="closeDetailsModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBodyContent">
            <!-- Dynamically populated via JavaScript -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-action btn-secondary-action" onclick="closeDetailsModal()">Close</button>
        </div>
    </div>
</div>

<script>
    function openDetailsModal(trackingNo, studentName, documentName, purpose, quantity, status, date, processorName) {
        const content = `
            <div class="modal-row"><span class="label">Tracking No:</span> <span>${trackingNo}</span></div>
            <div class="modal-row"><span class="label">Student Name:</span> <span>${studentName}</span></div>
            <div class="modal-row"><span class="label">Document Requested:</span> <span>${documentName}</span></div>
            <div class="modal-row"><span class="label">Purpose:</span> <span>${purpose}</span></div>
            <div class="modal-row"><span class="label">Quantity:</span> <span>${quantity}</span></div>
            <div class="modal-row"><span class="label">Current Status:</span> <span><strong>${status}</strong></span></div>
            <div class="modal-row"><span class="label">Handled By:</span> <span>${processorName}</span></div>
            <div class="modal-row" style="border-bottom:none;"><span class="label">Request Date:</span> <span>${date}</span></div>
        `;
        document.getElementById('modalBodyContent').innerHTML = content;
        document.getElementById('detailsModal').style.display = 'flex';
    }

    function closeDetailsModal() {
        document.getElementById('detailsModal').style.display = 'none';
    }

    // Close modal when clicking outside of the card box
    window.onclick = function(event) {
        const modal = document.getElementById('detailsModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
</script>

</body>
</html>