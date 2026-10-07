<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../includes/db.php";

// Ensure student is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ref = $_GET['ref'] ?? '';

// Helper function to check if status allows viewing the claim stub
function isClaimableStatus($status) {
    $cleanStatus = strtolower(trim(str_replace('_', ' ', $status ?? '')));
    $allowedStatuses = [
        'approved', 
        'processing', 
        'ready for claim', 
        'ready for pickup', 
        'ready to claim',
        'completed', 
        'claimed'
    ];
    return in_array($cleanStatus, $allowedStatuses);
}

// FETCH ALL STUDENT REQUESTS FOR SELECTOR DROPDOWN
$allRequestsStmt = mysqli_prepare($conn, "
    SELECT r.*, d.document_name 
    FROM requests r 
    JOIN documents d ON r.document_id = d.document_id 
    WHERE r.user_id = ?
    ORDER BY r.request_date DESC
");
mysqli_stmt_bind_param($allRequestsStmt, "i", $user_id);
mysqli_stmt_execute($allRequestsStmt);
$allRequests = mysqli_stmt_get_result($allRequestsStmt);

// FETCH SPECIFIC REQUEST DETAILS IF REF IS PROVIDED
$request = null;
if (!empty($ref)) {
    $stmt = mysqli_prepare($conn, "
        SELECT r.*, d.document_name, d.fee 
        FROM requests r 
        JOIN documents d ON r.document_id = d.document_id 
        WHERE r.tracking_no = ? AND r.user_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "si", $ref, $user_id);
    mysqli_stmt_execute($stmt);
    $request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Stub - eRegistrar</title>
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * { box-sizing: border-box; }
        body { background-color: #f4f6f9; font-family: 'Inter', 'Segoe UI', sans-serif; margin: 0; padding: 0; }
        
        .page-wrapper { width: 100%; display: flex; flex-direction: column; min-height: 100vh; }
        .main-content { max-width: 800px; width: 100%; margin: 20px auto; padding: 0 16px; flex: 1; }

        .card { background: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #e2e8f0; }
        .card h3 { margin-top: 0; font-size: 1.2rem; color: #1e293b; display: flex; align-items: center; gap: 8px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.875rem; color: #475569; }
        .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background-color: #fff; }
        
        .btn-open-modal { background: #2563eb; color: #ffffff; padding: 12px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; font-size: 0.95rem; transition: background 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-open-modal:hover { background: #1d4ed8; }

        .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin: 15px 0; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; font-size: 0.9rem; gap: 10px; }
        .summary-row:last-child { border-bottom: none; }
        .summary-label { color: #64748b; font-weight: 500; }
        .summary-value { color: #0f172a; font-weight: 600; text-align: right; }

        /* FLOATING MODAL - MOBILE FRIENDLY */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 12px;
            overflow-y: auto;
        }

        .modal-card {
            background: #ffffff;
            width: 100%;
            max-width: 550px;
            padding: 24px 18px 20px 18px;
            border-radius: 12px;
            position: relative;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-height: 92vh;
            overflow-y: auto;
            margin: auto;
        }

        .modal-close {
            position: absolute;
            top: 10px; right: 14px;
            background: #f1f5f9; border: none;
            width: 32px; height: 32px;
            border-radius: 50%;
            font-size: 1.25rem; color: #64748b;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            z-index: 10;
        }

        .stub-box { border: 2px dashed #0f172a; padding: 16px; border-radius: 8px; background: #fff; margin-top: 8px; }
        .stub-header { text-align: center; border-bottom: 2px dashed #cbd5e1; padding-bottom: 12px; margin-bottom: 12px; }
        .stub-logo { max-height: 55px; width: auto; display: block; margin: 0 auto 6px auto; object-fit: contain; }
        .stub-header h2 { margin: 0; font-size: 0.95rem; color: #0f172a; line-height: 1.3; }
        .stub-header p { margin: 4px 0 0; color: #64748b; font-size: 0.775rem; font-weight: 500; }

        .ref-tag { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; text-align: center; padding: 8px; border-radius: 6px; margin-bottom: 12px; font-weight: bold; font-size: 0.9rem; word-break: break-all; }

        /* GRID RESPONSIVE FOR MOBILE */
        .info-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 10px 14px; 
            margin-bottom: 12px; 
        }
        .info-item { border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; }
        .info-item span { display: block; font-size: 0.675rem; color: #64748b; text-transform: uppercase; font-weight: 600; letter-spacing: 0.2px; }
        .info-item strong { color: #0f172a; font-size: 0.85rem; word-break: break-word; }

        .notice-box { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 10px 12px; border-radius: 6px; font-size: 0.8rem; }

        .btn-print-action { background: #059669; color: #ffffff; padding: 12px; border: none; border-radius: 6px; font-weight: 600; width: 100%; margin-top: 14px; cursor: pointer; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .btn-print-action:hover { background: #047857; }

        /* RESPONSIVE MEDIA QUERIES */
        @media (max-width: 600px) {
            .main-content { margin: 15px auto; padding: 0 12px; }
            .card { padding: 16px; }
            .modal-card { padding: 20px 12px 16px 12px; }
            .stub-box { padding: 12px; }
            .stub-header h2 { font-size: 0.875rem; }
            .info-grid { grid-template-columns: 1fr; }
            .summary-row { font-size: 0.85rem; }
        }

        @media print {
            body * { visibility: hidden; }
            #printableStubArea, #printableStubArea * { visibility: visible; }
            #printableStubArea { position: absolute; left: 0; top: 0; width: 100%; border: 2px dashed #000; padding: 15px; }
            .modal-overlay { background: none; backdrop-filter: none; position: static; padding: 0; }
            .modal-card { box-shadow: none; padding: 0; width: 100%; max-width: 100%; max-height: none; overflow: visible; }
            .modal-close, .btn-print-action { display: none !important; }
            .stub-logo { max-height: 65px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="page-wrapper">

    <?php include("navbar.php"); ?>

    <main class="main-content">

        <div class="card">
            <h3><i class="fa-solid fa-file-invoice"></i> View Official Claim Stub</h3>
            <p style="color: #64748b; font-size: 0.875rem; margin-top: 4px;">Select your requested document below to view or print your claim stub.</p>

            <div class="form-group">
                <label>Select Requested Document</label>
                <select name="tracking_no" onchange="location = 'claim_stub.php?ref=' + this.value;" required>
                    <option value="">-- Choose Request --</option>
                    <?php 
                    if ($allRequests && mysqli_num_rows($allRequests) > 0) {
                        mysqli_data_seek($allRequests, 0);
                        while ($row = mysqli_fetch_assoc($allRequests)): 
                            $canClaim = isClaimableStatus($row['status'] ?? '');
                            $statusTag = $canClaim ? 'Ready for Claim' : 'Pending';
                        ?>
                            <option value="<?= htmlspecialchars($row['tracking_no']); ?>" <?= ($ref === $row['tracking_no']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($row['tracking_no'] . ' - ' . $row['document_name']); ?> (<?= $statusTag; ?>)
                            </option>
                        <?php 
                        endwhile; 
                    }
                    ?>
                </select>
            </div>

            <?php if ($request): ?>
                <?php $isClaimable = isClaimableStatus($request['status'] ?? ''); ?>

                <div class="summary-box">
                    <div class="summary-row">
                        <span class="summary-label">Tracking Number</span>
                        <span class="summary-value"><?= htmlspecialchars($request['tracking_no']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Document</span>
                        <span class="summary-value"><?= htmlspecialchars($request['document_name']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Status</span>
                        <span class="summary-value" style="text-transform: capitalize; color: <?= $isClaimable ? '#059669' : '#d97706'; ?>;">
                            <?= htmlspecialchars($request['status'] ?? 'Pending'); ?>
                        </span>
                    </div>
                </div>

                <?php if ($isClaimable): ?>
                    <button type="button" class="btn-open-modal" onclick="openClaimStubModal()">
                        <i class="fa-solid fa-receipt"></i> View / Print Claim Stub
                    </button>
                <?php else: ?>
                    <div class="notice-box" style="background: #fff3cd; border-color: #ffeba2; color: #856404; text-align: center;">
                        <i class="fa-solid fa-clock"></i> Claim stub will be available once your request is processed/approved by the Registrar.
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div style="text-align: center; color: #94a3b8; padding: 20px 0;">
                    <i class="fa-solid fa-arrow-up" style="font-size: 1.5rem; margin-bottom: 8px;"></i>
                    <p style="font-size: 0.875rem;">Please select a document from the dropdown above to view its claim stub.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>

</div>

<!-- CLAIM STUB FLOATING MODAL -->
<?php if ($request && isClaimableStatus($request['status'] ?? '')): ?>
<div id="claimStubModal" class="modal-overlay">
    <div class="modal-card">
        <button class="modal-close" onclick="closeClaimStubModal()">&times;</button>
        
        <div class="stub-box" id="printableStubArea">
            <div class="stub-header">
                <img src="../assets/images/logooo.png" alt="Consolacion Community Tech College Logo" class="stub-logo">
                <h2>Consolatrix College of Toledo City, Inc.</h2>
                <p>Official Student Claim Stub</p>
            </div>

            <div class="ref-tag">
                TRACKING NO: <?= htmlspecialchars($request['tracking_no']); ?>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span>Student Name</span>
                    <strong><?= htmlspecialchars($_SESSION['fullname'] ?? 'N/A'); ?></strong>
                </div>
                <div class="info-item">
                    <span>Document Requested</span>
                    <strong><?= htmlspecialchars($request['document_name']); ?></strong>
                </div>
                <div class="info-item">
                    <span>Quantity</span>
                    <strong><?= htmlspecialchars($request['quantity'] ?? 1); ?> copy(ies)</strong>
                </div>
                <div class="info-item">
                    <span>Total Fee</span>
                    <strong>₱<?= number_format(($request['fee'] ?? 0) * ($request['quantity'] ?? 1), 2); ?></strong>
                </div>
                <div class="info-item">
                    <span>Receipt / O.R. No.</span>
                    <strong><?= htmlspecialchars(!empty($request['or_no']) ? $request['or_no'] : 'N/A'); ?></strong>
                </div>
                <div class="info-item">
                    <span>Date Requested</span>
                    <strong><?= htmlspecialchars($request['created_at'] ?? $request['request_date'] ?? date("Y-m-d")); ?></strong>
                </div>
            </div>

            <div class="notice-box">
                <div class="notice-title" style="margin-bottom: 6px;">
                    <i class="fa-solid fa-circle-info"></i> <strong>Important Claiming Instructions:</strong>
                </div>
                <ul style="margin: 0; padding-left: 16px; font-size: 0.75rem; line-height: 1.4;">
                    <li>Present a printed or digital copy of this Claim Stub upon claiming your document at the Registrar's Office.</li>
                    <li><strong>Original ID:</strong> Present a valid Student ID or government ID.</li>
                    <li><strong>Representative:</strong> Bring an Authorization Letter and valid IDs for both the student and representative.</li>
                    <li><strong>Schedule:</strong> Monday to Friday, 8:00 AM – 5:00 PM.</li>
                </ul>
            </div>

            <button class="btn-print-action" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Print Official Claim Stub
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function openClaimStubModal() {
    const modal = document.getElementById('claimStubModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeClaimStubModal() {
    const modal = document.getElementById('claimStubModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('claimStubModal');
    if (event.target === modal) {
        closeClaimStubModal();
    }
}
</script>

</body>
</html>