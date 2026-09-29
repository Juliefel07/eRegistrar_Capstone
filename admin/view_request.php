<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification.php";

if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$request_id = intval($_GET['id']);

// FETCH REQUEST DETAILS (Prepared Statement)
$stmt = mysqli_prepare($conn, "
    SELECT r.*, d.document_name, d.fee
    FROM requests r
    JOIN documents d ON r.document_id = d.document_id
    WHERE r.request_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $request_id);
mysqli_stmt_execute($stmt);
$requestResult = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($requestResult) == 0) {
    die("Request not found.");
}

$request = mysqli_fetch_assoc($requestResult);

// HANDLE REQUIREMENT REJECTION
if (isset($_POST['reject_requirement'])) {
    $file_id = intval($_POST['file_id']);
    $remarks = trim($_POST['remarks']);

    $rejectStmt = mysqli_prepare($conn, "
        UPDATE request_requirement_files
        SET status = 'Rejected', remarks = ?
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($rejectStmt, "si", $remarks, $file_id);
    mysqli_stmt_execute($rejectStmt);

    $getInfoStmt = mysqli_prepare($conn, "
        SELECT r.user_id, r.tracking_no, dr.requirement_name
        FROM request_requirement_files rrf
        JOIN requests r ON rrf.request_id = r.request_id
        JOIN document_requirements dr ON rrf.requirement_id = dr.requirement_id
        WHERE rrf.id = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($getInfoStmt, "i", $file_id);
    mysqli_stmt_execute($getInfoStmt);
    $infoResult = mysqli_stmt_get_result($getInfoStmt);

    if ($info = mysqli_fetch_assoc($infoResult)) {
        createNotification(
            $conn,
            $info['user_id'],
            "Your requirement '" . $info['requirement_name'] . "' for request " . $info['tracking_no'] . " was rejected. Please review the remarks and upload a corrected file."
        );
    }

    header("Location: view_request.php?id=" . $request_id);
    exit();
}

// FETCH REQUIREMENT FILES
$filesStmt = mysqli_prepare($conn, "
    SELECT rrf.*, dr.requirement_name
    FROM request_requirement_files rrf
    JOIN document_requirements dr ON rrf.requirement_id = dr.requirement_id
    WHERE rrf.request_id = ?
    ORDER BY dr.requirement_id ASC
");
mysqli_stmt_bind_param($filesStmt, "i", $request_id);
mysqli_stmt_execute($filesStmt);
$files = mysqli_stmt_get_result($filesStmt);

// PROGRESS CALCULATION
$totalStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM request_requirement_files WHERE request_id = ?");
mysqli_stmt_bind_param($totalStmt, "i", $request_id);
mysqli_stmt_execute($totalStmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($totalStmt))['total'];

$verifiedStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS verified FROM request_requirement_files WHERE request_id = ? AND status = 'Verified'");
mysqli_stmt_bind_param($verifiedStmt, "i", $request_id);
mysqli_stmt_execute($verifiedStmt);
$verified = mysqli_fetch_assoc(mysqli_stmt_get_result($verifiedStmt))['verified'];

$percent = ($total > 0) ? ($verified / $total) * 100 : 0;

$progressColor = "#ef4444"; // Red
if ($percent >= 100) {
    $progressColor = "#22c55e"; // Green
} elseif ($percent >= 50) {
    $progressColor = "#f59e0b"; // Orange
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request #<?= htmlspecialchars($request['tracking_no']); ?></title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .container { max-width: 1100px; margin: auto; padding-bottom: 40px; }
        .request-header { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,.08); margin-bottom: 25px; }
        .request-header h2 { color: #1e3a8a; margin-bottom: 20px; font-size: 1.5rem; }
        
        .request-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .request-item { background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .request-item strong { display: block; color: #64748b; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 5px; }
        .request-item span { font-size: 1rem; color: #0f172a; font-weight: 600; }

        .progress-card { background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,.08); margin-bottom: 25px; }
        .progress-title { font-size: 16px; font-weight: 600; color: #1e3a8a; margin-bottom: 12px; }
        .progress-bar { width: 100%; height: 16px; background: #e2e8f0; border-radius: 30px; overflow: hidden; margin-bottom: 10px; }
        .progress-fill { height: 100%; border-radius: 30px; transition: 0.4s ease; }

        .payment-card { background: #ffffff; padding: 20px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,.08); margin-bottom: 25px; border-left: 5px solid #059669; }
        .payment-card h3 { color: #065f46; margin-top: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px; }

        .file-card { background: #fff; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 20px rgba(0,0,0,.08); border-left: 5px solid #2563eb; transition: transform .25s; }
        .file-card:hover { transform: translateY(-3px); }
        .file-card h4 { color: #1e3a8a; margin-top: 0; margin-bottom: 12px; }

        .badge { display: inline-block; padding: 6px 14px; border-radius: 30px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .badge.pending { background: #fff7d6; color: #b45309; }
        .badge.verified, .badge.approved { background: #dcfce7; color: #166534; }
        .badge.rejected { background: #fee2e2; color: #b91c1c; }

        .btn { padding: 10px 18px; border-radius: 8px; text-decoration: none; color: #fff; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: .25s; border: none; cursor: pointer; font-size: 0.9rem; }
        .btn.approve { background: #2563eb; }
        .btn.approve:hover { background: #1d4ed8; }
        .btn.reject { background: #dc2626; }
        .btn.reject:hover { background: #b91c1c; }
        .btn.view { background: #475569; }
        .btn.view:hover { background: #334155; }

        /* Modal */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); justify-content: center; align-items: center; z-index: 99999; }
        .modal-content { width: 450px; max-width: 90%; background: #fff; padding: 30px; border-radius: 18px; box-shadow: 0 20px 50px rgba(0,0,0,.25); animation: popup .25s ease; position: relative; }
        .close { position: absolute; right: 18px; top: 15px; font-size: 28px; cursor: pointer; color: #666; }
        .close:hover { color: #dc2626; }
        .modal h2 { color: #1e3a8a; margin-top: 0; margin-bottom: 20px; }
        .modal label { display: block; margin-bottom: 10px; font-weight: 600; }
        .modal textarea { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 12px; resize: vertical; min-height: 120px; box-sizing: border-box; }

        @keyframes popup { from { transform: scale(.85); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

<?php include("header.php"); ?>

<div class="container">

    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
            <?= htmlspecialchars($_SESSION['error']); ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- REQUEST SUMMARY HEADER -->
    <div class="request-header">
        <h2>Request Details</h2>
        <div class="request-grid">
            <div class="request-item">
                <strong>Tracking Number</strong>
                <span><?= htmlspecialchars($request['tracking_no']); ?></span>
            </div>
            <div class="request-item">
                <strong>Student Name</strong>
                <span><?= htmlspecialchars($request['fullname'] ?? 'N/A'); ?></span>
            </div>
            <div class="request-item">
                <strong>Document Requested</strong>
                <span><?= htmlspecialchars($request['document_name']); ?></span>
            </div>
            <div class="request-item">
                <strong>Purpose</strong>
                <span><?= htmlspecialchars($request['purpose']); ?></span>
            </div>
            <div class="request-item">
                <strong>Request Status</strong>
                <span class="badge <?= strtolower($request['status'] ?? 'pending'); ?>">
                    <?= htmlspecialchars($request['status'] ?? 'Pending'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- VERIFICATION PROGRESS BAR -->
    <div class="progress-card">
        <div class="progress-title">Requirement Verification Progress</div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?= $percent; ?>%; background: <?= $progressColor; ?>;"></div>
        </div>
        <div style="font-size: 0.9rem; color: #475569;">
            <strong><?= $verified; ?></strong> / <strong><?= $total; ?></strong> Requirements Verified
        </div>
    </div>

    <!-- PAYMENT / RECEIPT INFORMATION CARD -->
    <?php if (!empty($request['proof_of_payment']) || !empty($request['or_no'])): ?>
        <div class="payment-card">
            <h3><i class="fa-solid fa-file-invoice-dollar"></i> Payment & Receipt Information</h3>
            <div class="request-grid" style="margin-top: 15px;">
                <div class="request-item">
                    <strong>Official Receipt / Ref No.</strong>
                    <span><?= htmlspecialchars($request['or_no'] ?? 'N/A'); ?></span>
                </div>
                <div class="request-item">
                    <strong>Total Fee</strong>
                    <span>₱<?= number_format(($request['fee'] ?? 0) * ($request['quantity'] ?? 1), 2); ?></span>
                </div>
                <div class="request-item" style="grid-column: span 2;">
                    <strong>Uploaded Payment Proof</strong>
                    <div style="margin-top: 8px;">
                        <?php if (!empty($request['proof_of_payment'])): ?>
                            <a href="../<?= htmlspecialchars($request['proof_of_payment']); ?>" target="_blank" class="btn view">
                                <i class="fa-solid fa-eye"></i> View Receipt Attachment
                            </a>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-weight: normal;">No receipt image attached</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <h3>Uploaded Requirements</h3>

    <?php if (mysqli_num_rows($files) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($files)): ?>
            <div class="file-card">
                <h4><?= htmlspecialchars($row['requirement_name']); ?></h4>

                <p>
                    Status:
                    <span class="badge <?= strtolower($row['status']); ?>">
                        <?= htmlspecialchars($row['status']); ?>
                    </span>
                </p>

                <div class="file-actions">
                    <a target="_blank" href="../<?= htmlspecialchars($row['file_path']); ?>" class="btn view">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> View File
                    </a>

                    <?php if ($row['status'] == "Pending"): ?>
                        <a class="btn approve" href="verify_requirement.php?id=<?= $row['id']; ?>&request=<?= $request_id; ?>">
                            <i class="fa-solid fa-check"></i> Verify
                        </a>

                        <button type="button" class="btn reject" onclick="openRejectModal(<?= $row['id']; ?>)">
                            <i class="fa-solid fa-xmark"></i> Reject
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($row['remarks'])): ?>
                    <p style="margin-top: 15px; background: #f1f5f9; padding: 10px; border-radius: 8px; font-size: 0.9rem;">
                        <strong style="color: #475569;">Remarks:</strong> <?= htmlspecialchars($row['remarks']); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="color: #64748b; font-style: italic;">No uploaded requirements found for this request.</p>
    <?php endif; ?>

    <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;">

    <!-- APPROVAL BUTTON / WARNING -->
    <?php if ($verified == $total && $total > 0): ?>
        <a class="btn approve" href="approve.php?id=<?= $request_id; ?>" style="font-size: 1rem; padding: 12px 24px;">
            <i class="fa-solid fa-circle-check"></i> Approve Entire Request
        </a>
    <?php else: ?>
        <p style="color: #dc2626; font-weight: bold; background: #fee2e2; padding: 12px; border-radius: 8px; display: inline-block;">
            <i class="fa-solid fa-triangle-exclamation"></i> Verify all requirements before approving this request.
        </p>
    <?php endif; ?>

</div>

</div>

<!-- REJECT REQUIREMENT MODAL -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeRejectModal()">&times;</span>
        <h2>Reject Requirement</h2>
        <form method="POST">
            <input type="hidden" name="file_id" id="reject_file_id">
            
            <label>Reason for rejection</label>
            <textarea name="remarks" rows="5" placeholder="Specify why this document is rejected..." required></textarea>
            
            <br><br>
            <button type="submit" name="reject_requirement" class="btn reject" style="width: 100%;">
                <i class="fa-solid fa-paper-plane"></i> Submit Rejection
            </button>
        </form>
    </div>
</div>

<script>
function openRejectModal(fileId) {
    document.getElementById("reject_file_id").value = fileId;
    document.getElementById("rejectModal").style.display = "flex";
}

function closeRejectModal() {
    document.getElementById("rejectModal").style.display = "none";
}

window.onclick = function(event) {
    let modal = document.getElementById("rejectModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

</body>
</html>