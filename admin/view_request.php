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
    SELECT r.*, d.document_name, d.fee, u.fullname
    FROM requests r
    JOIN documents d ON r.document_id = d.document_id
    JOIN users u ON r.user_id = u.user_id
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
    <title>View Request #<?= htmlspecialchars($request['tracking_no']); ?> - Admin</title>

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
            max-width: 1100px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        /* Top Action / Back Nav */
        .back-nav {
            margin-bottom: 16px;
        }

        .back-nav a {
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s ease;
        }

        .back-nav a:hover {
            color: #2563eb;
        }

        /* Cards Base */
        .card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            padding: 24px;
            margin-bottom: 24px;
        }

        .card-header-title {
            color: #030917 !important;
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Request Summary Grid */
        .request-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .request-item {
            background: #f8fafc;
            padding: 14px 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .request-item strong {
            display: block;
            color: #000205;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .request-item span {
            font-size: 0.95rem;
            color: #000612;
            font-weight: 600;
        }

        /* Progress Card */
        .progress-bar-bg {
            width: 100%;
            height: 12px;
            background: #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            margin: 12px 0;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 20px;
            transition: width 0.4s ease;
        }

        /* Payment Card */
        .payment-card {
            border-left: 4px solid #059669;
        }

        /* File Requirement Cards */
        .file-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #2563eb;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .file-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
        }

        .file-card h4 {
            margin: 0;
            font-size: 1rem;
            color: #0f172a;
            font-weight: 700;
        }

        .file-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge.pending { background-color: #fef3c7; color: #b45309; }
        .badge.verified, .badge.approved { background-color: #d1fae5; color: #047857; }
        .badge.rejected { background-color: #fee2e2; color: #b91c1c; }

        /* Buttons */
        .btn {
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            color: #ffffff;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background-color 0.2s ease;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .btn-primary { background-color: #2563eb; }
        .btn-primary:hover { background-color: #1d4ed8; }

        .btn-success { background-color: #059669; }
        .btn-success:hover { background-color: #047857; }

        .btn-danger { background-color: #dc2626; }
        .btn-danger:hover { background-color: #b91c1c; }

        .btn-secondary { background-color: #475569; }
        .btn-secondary:hover { background-color: #334155; }

        .alert-message {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(2px);
            justify-content: center;
            align-items: center;
            z-index: 99999;
            padding: 16px;
        }

        .modal-content {
            width: 100%;
            max-width: 450px;
            background: #ffffff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .modal h3 {
            margin: 0 0 16px 0;
            color: #0f172a;
            font-size: 1.15rem;
        }

        .close-btn {
            position: absolute;
            right: 16px;
            top: 16px;
            font-size: 1.25rem;
            cursor: pointer;
            color: #94a3b8;
            border: none;
            background: transparent;
        }

        .close-btn:hover { color: #dc2626; }

        .modal textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px;
            font-size: 0.9rem;
            box-sizing: border-box;
            outline: none;
        }

        .modal textarea:focus {
            border-color: #2563eb;
        }

        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            .admin-content {
                padding: 16px;
            }

            .file-card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .file-actions {
                width: 100%;
            }

            .file-actions .btn {
                flex: 1;
            }
        }
    </style>
</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

    <!-- Navigation Back Link -->
    <div class="back-nav">
        <a href="requests.php"><i class="fa-solid fa-arrow-left"></i> Back to Requests</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-message">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div><?= htmlspecialchars($_SESSION['error']); ?></div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Request Details Card -->
    <div class="card">
        <h3 class="card-header-title">
            <i class="fa-solid fa-file-lines" style="color: #2563eb;"></i> Request Details
        </h3>
        <div class="request-grid">
            <div class="request-item">
                <strong>Tracking Number</strong>
                <span style="font-family: monospace; color: #2563eb;"><?= htmlspecialchars($request['tracking_no']); ?></span>
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
                <strong>Status</strong>
                <div>
                    <span class="badge <?= strtolower($request['status'] ?? 'pending'); ?>">
                        <?= htmlspecialchars($request['status'] ?? 'Pending'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Requirement Verification Progress Bar -->
    <div class="card">
        <h3 class="card-header-title">
            <i class="fa-solid fa-list-check" style="color: #2563eb;"></i> Verification Progress
        </h3>
        <div class="progress-bar-bg">
            <div class="progress-bar-fill" style="width: <?= $percent; ?>%; background: <?= $progressColor; ?>;"></div>
        </div>
        <div style="font-size: 0.88rem; color: #64748b; font-weight: 500;">
            <strong><?= $verified; ?></strong> of <strong><?= $total; ?></strong> requirements verified (<?= round($percent); ?>%)
        </div>
    </div>

    <!-- Payment & Receipt Info -->
    <?php if (!empty($request['proof_of_payment']) || !empty($request['or_no'])): ?>
        <div class="card payment-card">
            <h3 class="card-header-title" style="color: #065f46;">
                <i class="fa-solid fa-file-invoice-dollar"></i> Payment & Receipt Information
            </h3>
            <div class="request-grid">
                <div class="request-item">
                    <strong>Official Receipt / Ref No.</strong>
                    <span><?= htmlspecialchars($request['or_no'] ?? 'N/A'); ?></span>
                </div>
                <div class="request-item">
                    <strong>Total Fee</strong>
                    <span style="color: #059669;">₱<?= number_format(($request['fee'] ?? 0) * ($request['quantity'] ?? 1), 2); ?></span>
                </div>
                <div class="request-item" style="grid-column: span 1;">
                    <strong>Payment Attachment</strong>
                    <div style="margin-top: 4px;">
                        <?php if (!empty($request['proof_of_payment'])): ?>
                            <a href="../<?= htmlspecialchars($request['proof_of_payment']); ?>" target="_blank" class="btn btn-secondary">
                                <i class="fa-solid fa-receipt"></i> View Attachment
                            </a>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-weight: normal; font-size: 0.85rem;">No receipt image attached</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Uploaded Requirements List -->
    <h3 style="font-size: 1.1rem; color: #0f172a; margin: 24px 0 16px 0; font-weight: 700;">Uploaded Requirements</h3>

    <?php if (mysqli_num_rows($files) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($files)): ?>
            <div class="file-card">
                <div class="file-card-header">
                    <div>
                        <h4><?= htmlspecialchars($row['requirement_name']); ?></h4>
                        <div style="margin-top: 6px;">
                            <span class="badge <?= strtolower($row['status']); ?>">
                                <?= htmlspecialchars($row['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="file-actions">
                        <a target="_blank" href="../<?= htmlspecialchars($row['file_path']); ?>" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> View File
                        </a>

                        <?php if ($row['status'] == "Pending"): ?>
                            <a class="btn btn-primary" href="verify_requirement.php?id=<?= $row['id']; ?>&request=<?= $request_id; ?>">
                                <i class="fa-solid fa-check"></i> Verify
                            </a>

                            <button type="button" class="btn btn-danger" onclick="openRejectModal(<?= $row['id']; ?>)">
                                <i class="fa-solid fa-xmark"></i> Reject
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($row['remarks'])): ?>
                    <div style="background: #f1f5f9; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem; color: #334155;">
                        <strong style="color: #475569;">Remarks:</strong> <?= htmlspecialchars($row['remarks']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card" style="text-align: center; color: #64748b; font-style: italic;">
            No uploaded requirement documents found for this request.
        </div>
    <?php endif; ?>

    <!-- Final Approval Button / Notice -->
    <div style="margin-top: 30px; text-align: left;">
        <?php if ($verified == $total && $total > 0): ?>
            <a class="btn btn-success" href="approve.php?id=<?= $request_id; ?>" style="font-size: 1rem; padding: 12px 24px;">
                <i class="fa-solid fa-circle-check"></i> Approve Entire Request
            </a>
        <?php else: ?>
            <div class="alert-message" style="display: inline-flex; border: 1px solid #fca5a5;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Please verify all uploaded requirements before approving this request.</span>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- REJECT REQUIREMENT MODAL -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <button type="button" class="close-btn" onclick="closeRejectModal()">&times;</button>
        <h3>Reject Requirement</h3>
        <form method="POST">
            <input type="hidden" name="file_id" id="reject_file_id">
            
            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; color: #334155;">
                Reason for rejection
            </label>
            <textarea name="remarks" rows="4" placeholder="Specify why this document is rejected..." required></textarea>
            
            <div style="margin-top: 20px;">
                <button type="submit" name="reject_requirement" class="btn btn-danger" style="width: 100%; padding: 10px;">
                    <i class="fa-solid fa-paper-plane"></i> Submit Rejection
                </button>
            </div>
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