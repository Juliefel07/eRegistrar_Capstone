<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ==========================================
// Fetch Student Requests THAT HAVE STORED RECEIPTS ONLY
// ==========================================
$sql = "SELECT 
            r.request_id,
            r.tracking_no,
            r.status AS request_status,
            r.payment_proof,
            r.uploaded_file,
            r.quantity,
            r.request_date,
            d.document_name,
            d.fee
        FROM requests r
        INNER JOIN documents d ON r.document_id = d.document_id
        WHERE r.user_id = ?
        AND (
            (r.payment_proof IS NOT NULL AND TRIM(r.payment_proof) != '')
            OR 
            (r.uploaded_file IS NOT NULL AND TRIM(r.uploaded_file) != '')
        )
        ORDER BY r.request_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Storage - eRegistrar</title>
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <!-- Anti-Flicker Script: Load dark mode before page render -->
<script>
    if (localStorage.getItem("darkMode") === "enabled") {
        document.documentElement.classList.add("dark-mode");
        document.addEventListener("DOMContentLoaded", () => {
            document.body.classList.add("dark-mode");
        });
    }
</script>

    <style>
        :root {
            --primary: #4338ca;
            --primary-hover: #3730a3;
            --primary-light: #e0e7ff;
            --bg-main: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius-lg: 16px;
            --radius-md: 8px;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
        }

        body {
            background-color: var(--bg-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .student-main {
            padding: 32px 24px;
            max-width: 1200px;
            margin: 0 auto;
            padding-bottom: 100px;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .student-main h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-main h1 i {
            color: var(--primary);
            background: var(--primary-light);
            padding: 8px;
            border-radius: var(--radius-md);
            font-size: 18px;
        }

        .student-main p.subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0;
        }

        /* Table Wrapper Styles */
        .table-container {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        .modern-table th {
            background: #f8fafc;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
        }

        .modern-table td {
            padding: 16px 20px;
            font-size: 14px;
            color: var(--text-main);
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .modern-table tbody tr {
            transition: background 0.15s ease;
        }

        .modern-table tbody tr:hover {
            background: #f8fafc;
        }

        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Tracking Badge */
        .tracking-tag {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-main);
            background: #f1f5f9;
            padding: 5px 10px;
            border-radius: 6px;
            border: 1px solid var(--border);
        }

        /* Document details cell */
        .doc-title {
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 2px;
        }

        .doc-meta {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Status Badges */
        .payment-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .payment-status::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .payment-status.approved, .payment-status.paid, .payment-status.completed {
            background: #dcfce7;
            color: #166534;
        }
        .payment-status.approved::before, .payment-status.paid::before, .payment-status.completed::before {
            background: #22c55e;
        }

        .payment-status.pending {
            background: #fef3c7;
            color: #92400e;
        }
        .payment-status.pending::before {
            background: #f59e0b;
        }

        /* Table Action Button */
        .table-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: var(--radius-md);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(67, 56, 202, 0.2);
        }

        .table-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        /* Empty State */
        .empty-payment {
            padding: 60px 24px;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-payment i {
            font-size: 42px;
            color: #cbd5e1;
            margin-bottom: 12px;
            background: #f8fafc;
            padding: 16px;
            border-radius: 50%;
        }

        .empty-payment h3 {
            font-size: 16px;
            color: var(--text-main);
            margin: 0 0 4px 0;
            font-weight: 600;
        }

        .empty-payment p {
            font-size: 13px;
            margin: 0;
        }

        /* Modern Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-container {
            background: var(--surface);
            width: 100%;
            max-width: 580px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            transform: scale(0.95);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid var(--border);
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-overlay.active .modal-container {
            transform: scale(1);
        }

        .modal-header {
            padding: 20px 24px;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 17px;
            font-weight: 600;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close-btn {
            background: #f1f5f9;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 16px;
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .modal-close-btn:hover {
            background: #e2e8f0;
            color: var(--text-main);
        }

        .modal-body {
            padding: 24px;
            text-align: center;
            background: #f8fafc;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 16px 24px;
            background: #ffffff;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-footer .table-btn {
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-secondary {
            background: #f1f5f9 !important;
            color: var(--text-muted) !important;
            box-shadow: none !important;
        }

        .btn-secondary:hover {
            background: #e2e8f0 !important;
            color: var(--text-main) !important;
        }

        @media (max-width: 640px) {
            .student-main { padding: 20px 16px; }
        }
    </style>
</head>

<body>

    <?php include("navbar.php"); ?>

    <div class="student-main">
        <div class="page-header">
            <h1><i class="fa-solid fa-vault"></i> Receipt Storage</h1>
            <p class="subtitle">View and access official payment receipts issued for your document requests.</p>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Tracking No.</th>
                            <th>Document Details</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php 
                                    $totalFee = ($row['fee'] ?? 0) * ($row['quantity'] ?? 1);

                                    // Extract valid stored receipt path
                                    $stored_receipt = !empty($row['payment_proof']) ? $row['payment_proof'] : $row['uploaded_file'];
                                    $clean_receipt  = ltrim(str_replace(['assets/uploads/', '../assets/uploads/'], '', $stored_receipt), '/');
                                    $receipt_path   = "../assets/uploads/" . $clean_receipt;
                                ?>
                                <tr>
                                    <td>
                                        <span class="tracking-tag"><?= htmlspecialchars($row['tracking_no']); ?></span>
                                    </td>
                                    <td>
                                        <div class="doc-title"><?= htmlspecialchars($row['document_name']); ?></div>
                                        <div class="doc-meta"><?= $row['quantity']; ?> copy(ies)</div>
                                    </td>
                                    <td style="font-weight: 600;">
                                        ₱<?= number_format($totalFee, 2); ?>
                                    </td>
                                    <td>
                                        <span class="payment-status <?= strtolower(str_replace(' ', '-', $row['request_status'])); ?>">
                                            <?= htmlspecialchars($row['request_status']); ?>
                                        </span>
                                    </td>
                                    <td style="color: var(--text-muted); font-size: 13px;">
                                        <?= date('M d, Y', strtotime($row['request_date'])); ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="button" class="table-btn" onclick="openReceiptModal('<?= htmlspecialchars($receipt_path, ENT_QUOTES); ?>', '<?= htmlspecialchars($row['tracking_no'], ENT_QUOTES); ?>')">
                                            <i class="fa-solid fa-eye"></i> View Receipt
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-payment">
                                        <i class="fa-solid fa-folder-open"></i>
                                        <h3>No Stored Receipts Available</h3>
                                        <p>Official receipts will appear here once attached or issued by the Accounting Office.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PAYMENT RECEIPT DISPLAY MODAL -->
    <div id="receiptModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fa-solid fa-file-invoice-dollar" style="color: var(--primary);"></i> Official Receipt View</h3>
                <button type="button" class="modal-close-btn" onclick="closeReceiptModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalReceiptContent"></div>
            <div class="modal-footer">
                <a id="modalReceiptDownloadBtn" href="#" target="_blank" class="table-btn"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open Fullscreen</a>
                <button type="button" class="table-btn btn-secondary" onclick="closeReceiptModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        function openReceiptModal(filePath, trackingNo) {
            const fileExt = filePath.split('.').pop().toLowerCase();
            let previewHtml = '<div style="margin-bottom: 14px; font-weight: 600; font-size: 13px; color: var(--text-muted); text-align: left;">Tracking No: <span style="color: var(--text-main);">' + trackingNo + '</span></div>';
            
            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                previewHtml += '<img src="' + filePath + '" alt="Receipt Preview" style="max-width: 100%; max-height: 55vh; border-radius: 8px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); object-fit: contain;">';
            } else if (fileExt === 'pdf') {
                previewHtml += '<iframe src="' + filePath + '" style="width: 100%; height: 50vh; border: none; border-radius: 8px; background: #fff;"></iframe>';
            } else {
                previewHtml += '<p style="color: var(--text-muted); padding: 30px 0;">Preview unavailable for this file type.</p>';
            }

            document.getElementById('modalReceiptContent').innerHTML = previewHtml;
            document.getElementById('modalReceiptDownloadBtn').href = filePath;
            
            const modal = document.getElementById('receiptModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('active'), 10);
        }

        function closeReceiptModal() {
            const modal = document.getElementById('receiptModal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Close modal when clicking outside of container
        window.onclick = function(event) {
            const modal = document.getElementById('receiptModal');
            if (event.target === modal) {
                closeReceiptModal();
            }
        }
    </script>
</body>
</html>