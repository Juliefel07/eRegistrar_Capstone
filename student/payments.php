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
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">

    <style>
        body {
            background: #f4f7fb;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        .student-main {
            padding: 20px 15px;
            max-width: 1000px;
            margin: 0 auto;
            padding-bottom: 90px;
        }

        .student-main h1 {
            font-size: 22px;
            color: #1e3a8a;
            margin-bottom: 4px;
        }

        .student-main p.subtitle {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 20px;
        }

        .payment-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }

        .payment-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid #e5e7eb;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        .payment-icon {
            font-size: 24px;
            color: #2563eb;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tracking-tag {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .payment-info h3 {
            font-size: 16px;
            color: #1f2937;
            margin: 0 0 4px 0;
            word-break: break-word;
        }

        .payment-info p {
            font-size: 12px;
            color: #6b7280;
            margin: 0 0 12px 0;
        }

        .payment-info h2 {
            font-size: 20px;
            color: #111827;
            margin: 0 0 12px 0;
        }

        .payment-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
            margin-bottom: 15px;
        }

        .payment-status.approved, .payment-status.paid, .payment-status.completed {
            background: #d1fae5;
            color: #065f46;
        }

        .payment-status.pending {
            background: #fff3cd;
            color: #856404;
        }

        /* View Action Buttons */
        .history-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: background 0.2s;
            border: none;
            width: 100%;
            box-sizing: border-box;
            background: #0284c7;
            color: #ffffff;
        }

        .history-btn:hover {
            background: #0369a1;
        }

        /* Empty State */
        .empty-payment {
            grid-column: 1 / -1;
            background: #ffffff;
            padding: 40px 20px;
            text-align: center;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            color: #6b7280;
        }

        .empty-payment i {
            font-size: 36px;
            color: #9ca3af;
            margin-bottom: 12px;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 16px;
        }

        .modal-container {
            background: #ffffff;
            width: 100%;
            max-width: 520px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .modal-header {
            padding: 16px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
        }

        .modal-close-btn {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #6c757d;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
            text-align: center;
        }

        .modal-footer {
            padding: 12px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            text-align: right;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        @media (max-width: 576px) {
            .student-main { padding: 15px 10px; }
            .payment-container { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <?php include("navbar.php"); ?>

    <div class="student-main">
        <h1><i class="fa-solid fa-vault" style="color: #1e3a8a;"></i> Receipt Storage</h1>
        <p class="subtitle">View and access official payment receipts issued for your document requests.</p>

        <div class="payment-container">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php 
                        $totalFee = ($row['fee'] ?? 0) * ($row['quantity'] ?? 1);

                        // Extract valid stored receipt path
                        $stored_receipt = !empty($row['payment_proof']) ? $row['payment_proof'] : $row['uploaded_file'];
                        $clean_receipt  = ltrim(str_replace(['assets/uploads/', '../assets/uploads/'], '', $stored_receipt), '/');
                        $receipt_path   = "../assets/uploads/" . $clean_receipt;
                    ?>
                    <div class="payment-card">
                        <div>
                            <div class="payment-icon">
                                <i class="fa-solid fa-receipt"></i>
                                <span class="tracking-tag"><?= htmlspecialchars($row['tracking_no']); ?></span>
                            </div>

                            <div class="payment-info">
                                <h3><?= htmlspecialchars($row['document_name']); ?></h3>
                                <p>Quantity: <?= $row['quantity']; ?> copy(ies)</p>
                                <h2>₱<?= number_format($totalFee, 2); ?></h2>

                                <span class="payment-status <?= strtolower(str_replace(' ', '-', $row['request_status'])); ?>">
                                    <?= htmlspecialchars($row['request_status']); ?>
                                </span>
                            </div>
                        </div>

                        <div>
                            <button type="button" class="history-btn" onclick="openReceiptModal('<?= htmlspecialchars($receipt_path, ENT_QUOTES); ?>', '<?= htmlspecialchars($row['tracking_no'], ENT_QUOTES); ?>')">
                                <i class="fa-solid fa-eye"></i> View Stored Receipt
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-payment">
                    <i class="fa-solid fa-folder-open"></i>
                    <h3>No Stored Receipts Available</h3>
                    <p>Official receipts will appear here once attached or issued by the Accounting Office.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- PAYMENT RECEIPT DISPLAY MODAL -->
    <div id="receiptModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fa-solid fa-file-invoice-dollar" style="color: #0284c7;"></i> Official Receipt View</h3>
                <button type="button" class="modal-close-btn" onclick="closeReceiptModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalReceiptContent"></div>
            <div class="modal-footer">
                <a id="modalReceiptDownloadBtn" href="#" target="_blank" class="history-btn" style="width: auto; padding: 6px 14px;"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open Fullscreen</a>
                <button type="button" class="history-btn" style="width: auto; background: #64748b; padding: 6px 14px;" onclick="closeReceiptModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        function openReceiptModal(filePath, trackingNo) {
            const fileExt = filePath.split('.').pop().toLowerCase();
            let previewHtml = '<div style="margin-bottom: 12px; font-weight: 600; color: #475569;">Tracking No: ' + trackingNo + '</div>';
            
            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                previewHtml += '<img src="' + filePath + '" alt="Receipt Preview" style="max-width: 100%; max-height: 55vh; border-radius: 8px; border: 1px solid #e2e8f0;">';
            } else if (fileExt === 'pdf') {
                previewHtml += '<iframe src="' + filePath + '" style="width: 100%; height: 50vh; border: none; border-radius: 8px;"></iframe>';
            } else {
                previewHtml += '<p style="color: #64748b;">Preview unavailable for this file type.</p>';
            }

            document.getElementById('modalReceiptContent').innerHTML = previewHtml;
            document.getElementById('modalReceiptDownloadBtn').href = filePath;
            document.getElementById('receiptModal').style.display = 'flex';
        }

        function closeReceiptModal() {
            document.getElementById('receiptModal').style.display = 'none';
        }
    </script>
</body>
</html>