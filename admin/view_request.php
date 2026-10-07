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
    mysqli_stmt_bind_param($rejectStmt, "si", $remarks,$file_id);
    mysqli_stmt_execute($rejectStmt);

    $getInfoStmt = mysqli_prepare($conn, "
        SELECT r.user_id, r.tracking_no, COALESCE(dr.requirement_name, rrf.file_name) AS req_display_name
        FROM request_requirement_files rrf
        JOIN requests r ON rrf.request_id = r.request_id
        LEFT JOIN document_requirements dr ON rrf.requirement_id = dr.requirement_id
        WHERE rrf.id = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($getInfoStmt, "i", $file_id);
    mysqli_stmt_execute($getInfoStmt);
    $infoResult = mysqli_stmt_get_result($getInfoStmt);

    if ($info = mysqli_fetch_assoc($infoResult)) {
        createNotification(
            $conn,$info['user_id'],
            "Your requirement '" . $info['req_display_name'] . "' for request " . $info['tracking_no'] . " was rejected. Please review the remarks and upload a corrected file."
        );
    }

    header("Location: view_request.php?id=" . $request_id);
    exit();
}

// FETCH ALL REQUIREMENT FILES
$filesStmt = mysqli_prepare($conn, "
    SELECT rrf.*, COALESCE(dr.requirement_name, rrf.file_name) AS requirement_name
    FROM request_requirement_files rrf
    LEFT JOIN document_requirements dr ON rrf.requirement_id = dr.requirement_id
    WHERE rrf.request_id = ?
    ORDER BY rrf.id ASC
");
mysqli_stmt_bind_param($filesStmt, "i", $request_id);
mysqli_stmt_execute($filesStmt);
$filesResult = mysqli_stmt_get_result($filesStmt);

// Split files into ID files and general document requirement files
$idFiles = [];$otherFiles = [];

while ($row = mysqli_fetch_assoc($filesResult)) {
    if (stripos($row['file_name'], 'Valid ID') !== false || is_null($row['requirement_id'])) {
        $idFiles[] =$row;
    } else {
        $otherFiles[] =$row;
    }
}

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
if ($percent >= 100) {$progressColor = "#22c55e"; // Green
} elseif ($percent >= 50) {$progressColor = "#f59e0b"; // Orange
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

        .id-card-section {
            border-left: 4px solid #7c3aed;
        }

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
            margin-bottom: 12px;
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

        .btn-purple { background-color: #7c3aed; }
        .btn-purple:hover { background-color: #6d28d9; }

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

        /* MODAL STYLES */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 16px;
        }

        .modal-container {
            background: #ffffff;
            width: 100%;
            max-width: 550px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
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
        }

        .modal-close-btn {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #64748b;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
            text-align: center;
        }

        .modal-footer {
            padding: 12px 20px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .modal-content-reject {
            width: 100%;
            max-width: 450px;
            background: #ffffff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .modal-content-reject textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px;
            font-size: 0.9rem;
            box-sizing: border-box;
            outline: none;
        }

        @media (max-width: 768px) {
            .admin-content { padding: 16px; }
            .file-card-header { flex-direction: column; align-items: flex-start; }
            .file-actions { width: 100%; }
            .file-actions .btn { flex: 1; }
        }
    </style>
</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

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

    <!-- UPLOADED VALID IDs SECTION -->
    <div class="card id-card-section">
        <h3 class="card-header-title" style="color: #6d28d9;">
            <i class="fa-solid fa-id-card"></i> Student Valid ID Uploads
        </h3>
        
        <?php if (!empty($idFiles)): ?>
            <div class="request-grid">
                <?php foreach ($idFiles as$idFile): ?>
                    <?php 
                        $cleanPath = ltrim(str_replace(['assets/uploads/', '../assets/uploads/'], '',$idFile['file_path']), '/');
                        $fullPath = "../assets/uploads/" . $cleanPath;
                    ?>
                    <div class="request-item" style="background: #ffffff; border-left: 3px solid #7c3aed;">
                        <strong><?= htmlspecialchars($idFile['requirement_name']); ?></strong>
                        <div style="margin: 8px 0;">
                            <span class="badge <?= strtolower($idFile['status']); ?>">
                                <?= htmlspecialchars($idFile['status']); ?>
                            </span>
                        </div>
                        <div style="display: flex; gap: 6px; margin-top: 10px;">
                            <button type="button" class="btn btn-purple" style="padding: 6px 12px; font-size: 0.8rem;" onclick="openImageModal('<?= htmlspecialchars($fullPath, ENT_QUOTES); ?>', '<?= htmlspecialchars($idFile['requirement_name'], ENT_QUOTES); ?>')">
                                <i class="fa-solid fa-eye"></i> Inspect ID
                            </button>
                            <?php if ($idFile['status'] == "Pending"): ?>
                                <a class="btn btn-primary" href="verify_requirement.php?id=<?= $idFile['id']; ?>&request=<?=$request_id; ?>" style="padding: 6px 10px; font-size: 0.8rem;">
                                    <i class="fa-solid fa-check"></i> Verify
                                </a>
                                <button type="button" class="btn btn-danger" onclick="openRejectModal(<?= $idFile['id']; ?>)" style="padding: 6px 10px; font-size: 0.8rem;">
                                    <i class="fa-solid fa-xmark"></i> Reject
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($idFile['remarks'])): ?>
                            <div style="margin-top: 8px; font-size: 0.8rem; color: #64748b;">
                                <strong>Remarks:</strong> <?= htmlspecialchars($idFile['remarks']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="color: #64748b; font-size: 0.9rem; font-style: italic;">
                No valid ID files uploaded for this request.
            </div>
        <?php endif; ?>
    </div>

    <!-- Requirement Verification Progress Bar -->
    <div class="card">
        <h3 class="card-header-title">
            <i class="fa-solid fa-list-check" style="color: #2563eb;"></i> Verification Progress
        </h3>
        <div class="progress-bar-bg">
            <div class="progress-bar-fill" style="width: <?= $percent; ?>%; background: <?=$progressColor; ?>;"></div>
        </div>
        <div style="font-size: 0.88rem; color: #64748b; font-weight: 500;">
            <strong><?= $verified; ?></strong> of <strong><?= $total; ?></strong> requirements verified (<?= round($percent); ?>%)
        </div>
    </div>

    <!-- Uploaded Requirements List -->
    <h3 style="font-size: 1.1rem; color: #0f172a; margin: 24px 0 16px 0; font-weight: 700;">Uploaded Document Requirements</h3>

    <?php if (!empty($otherFiles)): ?>
        <?php foreach ($otherFiles as$row): ?>
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
                        <?php 
                            $cleanReqPath = "../assets/uploads/" . ltrim(str_replace(['assets/uploads/', '../assets/uploads/'], '', $row['file_path']), '/');
                        ?>
                        <button type="button" class="btn btn-secondary" onclick="openImageModal('<?= htmlspecialchars($cleanReqPath, ENT_QUOTES); ?>', '<?= htmlspecialchars($row['requirement_name'], ENT_QUOTES); ?>')">
                            <i class="fa-solid fa-eye"></i> View File
                        </button>

                        <?php if ($row['status'] == "Pending"): ?>
                            <a class="btn btn-primary" href="verify_requirement.php?id=<?= $row['id']; ?>&request=<?=$request_id; ?>">
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
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card" style="text-align: center; color: #64748b; font-style: italic;">
            No additional document requirements uploaded for this request.
        </div>
    <?php endif; ?>

    <!-- Final Approval Button / Notice -->
    <div style="margin-top: 30px; text-align: left;">
        <?php if ($verified == $total &&$total > 0): ?>
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

<!-- FLOATING IMAGE & FILE PREVIEW MODAL -->
<div id="imageModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fa-solid fa-id-card" style="color: #7c3aed;"></i> File Preview</h3>
            <button type="button" class="modal-close-btn" onclick="closeImageModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalImageBody"></div>
        <div class="modal-footer">
            <a id="modalFullscreenBtn" href="#" target="_blank" class="btn btn-purple" style="font-size: 0.8rem;"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open Fullscreen</a>
            <button type="button" class="btn btn-secondary" onclick="closeImageModal()" style="font-size: 0.8rem;">Close</button>
        </div>
    </div>
</div>

<!-- REJECT REQUIREMENT MODAL -->
<div id="rejectModal" class="modal-overlay">
    <div class="modal-content-reject">
        <button type="button" class="modal-close-btn" style="position: absolute; right: 16px; top: 16px;" onclick="closeRejectModal()">&times;</button>
        <h3 style="margin-top: 0; color: #0f172a;">Reject Requirement</h3>
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
function openImageModal(filePath, title) {
    const fileExt = filePath.split('.').pop().toLowerCase();
    let bodyHtml = '';
    
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
        bodyHtml = `<img src="${filePath}" alt="ID Preview" style="max-width: 100%; max-height: 60vh; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: contain;">`;
    } else if (fileExt === 'pdf') {
        bodyHtml = `<iframe src="${filePath}" style="width: 100%; height: 55vh; border: none; border-radius: 8px;"></iframe>`;
    } else {
        bodyHtml = `<p style="color: #64748b;">Preview unavailable for this file format.</p>`;
    }

    document.getElementById("modalTitle").innerText = title;
    document.getElementById("modalImageBody").innerHTML = bodyHtml;
    document.getElementById("modalFullscreenBtn").href = filePath;
    document.getElementById("imageModal").style.display = "flex";
}

function closeImageModal() {
    document.getElementById("imageModal").style.display = "none";
}

function openRejectModal(fileId) {
    document.getElementById("reject_file_id").value = fileId;
    document.getElementById("rejectModal").style.display = "flex";
}

function closeRejectModal() {
    document.getElementById("rejectModal").style.display = "none";
}

window.onclick = function(event) {
    let imgModal = document.getElementById("imageModal");
    let rejModal = document.getElementById("rejectModal");
    if (event.target == imgModal) {
        closeImageModal();
    }
    if (event.target == rejModal) {
        closeRejectModal();
    }
}
</script>

</body>
</html>