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

$progressColor = "#dc2626"; // Red
if ($percent >= 100) {$progressColor = "#059669"; // Green
} elseif ($percent >= 50) {$progressColor = "#d97706"; // Amber
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request #<?= htmlspecialchars($request['tracking_no']); ?> | eRegistrar Admin</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --registrar-navy: #0f172a;
            --registrar-blue: #1d4ed8;
            --registrar-blue-hover: #1e40af;
            --success: #047857;
            --success-hover: #065f46;
            --danger: #b91c1c;
            --danger-hover: #991b1b;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-900: #0f172a;
        }

        body {
            background-color: var(--slate-50);
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: var(--slate-900);
            margin: 0;
            padding: 0;
        }

        .admin-content {
            padding: 32px 24px;
            max-width: 1100px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .back-nav {
            margin-bottom: 20px;
        }

        .back-nav a {
            color: var(--slate-600);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s ease;
        }

        .back-nav a:hover {
            color: var(--registrar-blue);
        }

        .card {
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid var(--slate-200);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            padding: 24px;
            margin-bottom: 24px;
        }

        .card-header-title {
            color: var(--registrar-navy) !important;
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.01em;
        }

        .request-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
        }

        .request-item {
            background: var(--slate-50);
            padding: 16px;
            border-radius: 6px;
            border: 1px solid var(--slate-200);
        }

        .request-item strong {
            display: block;
            color: var(--slate-600);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 6px;
        }

        .request-item span {
            font-size: 0.95rem;
            color: var(--slate-900);
            font-weight: 600;
        }

        .progress-bar-bg {
            width: 100%;
            height: 8px;
            background: var(--slate-200);
            border-radius: 4px;
            overflow: hidden;
            margin: 12px 0 8px 0;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.4s ease;
        }

        .file-card {
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid var(--slate-200);
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.01);
            transition: border-color 0.15s ease;
        }

        .file-card:hover {
            border-color: var(--slate-300);
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
            font-size: 0.95rem;
            color: var(--slate-900);
            font-weight: 600;
        }

        .file-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
            letter-spacing: 0.3px;
        }

        .badge.pending { background-color: #fef3c7; color: #b45309; }
        .badge.verified, .badge.approved { background-color: #d1fae5; color: #047857; }
        .badge.rejected { background-color: #fee2e2; color: #b91c1c; }

        /* PROFESSIONAL ENTERPRISE BUTTON STYLING */
        .btn {
            padding: 7px 14px;
            border-radius: 6px;
            text-decoration: none;
            color: #ffffff;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background-color 0.15s ease, border-color 0.15s ease;
            border: 1px solid transparent;
            cursor: pointer;
            font-size: 0.82rem;
            line-height: 1.4;
        }

        .btn-primary { 
            background-color: var(--registrar-blue); 
            border-color: #1d4ed8;
        }
        .btn-primary:hover { 
            background-color: var(--registrar-blue-hover); 
        }

        .btn-success { 
            background-color: var(--success); 
            border-color: #047857;
        }
        .btn-success:hover { 
            background-color: var(--success-hover); 
        }

        .btn-danger { 
            background-color: var(--danger); 
            border-color: #b91c1c;
        }
        .btn-danger:hover { 
            background-color: var(--danger-hover); 
        }

        .btn-secondary { 
            background-color: #ffffff; 
            color: var(--slate-700); 
            border-color: var(--slate-300);
        }
        .btn-secondary:hover { 
            background-color: var(--slate-100); 
            color: var(--slate-900);
            border-color: var(--slate-400, #94a3b8);
        }

        .alert-message {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #fca5a5;
        }

        /* MODAL STYLES */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(2px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 16px;
        }

        .modal-container {
            background: #ffffff;
            width: 100%;
            max-width: 650px;
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
            border: 1px solid var(--slate-200);
        }

        .modal-header {
            padding: 14px 20px;
            background: var(--slate-50);
            border-bottom: 1px solid var(--slate-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1rem;
            color: var(--slate-900);
            font-weight: 600;
        }

        .modal-close-btn {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--slate-600);
            cursor: pointer;
            line-height: 1;
        }

        .modal-body {
            padding: 20px;
            text-align: center;
            background: #0f172a;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 350px;
        }

        .modal-footer {
            padding: 12px 20px;
            background: var(--slate-50);
            border-top: 1px solid var(--slate-200);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .modal-content-reject {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            position: relative;
            border: 1px solid var(--slate-200);
        }

        .modal-content-reject textarea {
            width: 100%;
            border: 1px solid var(--slate-300);
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 0.88rem;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.15s;
            resize: vertical;
        }

        .modal-content-reject textarea:focus {
            border-color: var(--registrar-blue);
            box-shadow: 0 0 0 2px rgba(29, 78, 216, 0.15);
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
            <i class="fa-solid fa-file-lines" style="color: var(--registrar-blue);"></i> Request Details
        </h3>
        <div class="request-grid">
            <div class="request-item">
                <strong>Tracking Number</strong>
                <span style="font-family: monospace; color: var(--registrar-blue); font-size: 0.95rem;"><?= htmlspecialchars($request['tracking_no']); ?></span>
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
                <div style="margin-top: 4px;">
                    <span class="badge <?= strtolower($request['status'] ?? 'pending'); ?>">
                        <?= htmlspecialchars($request['status'] ?? 'Pending'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- UPLOADED VALID IDs SECTION -->
    <div class="card">
        <h3 class="card-header-title">
            <i class="fa-solid fa-id-card" style="color: var(--registrar-blue);"></i> Student Valid ID Uploads
        </h3>
        
        <?php if (!empty($idFiles)): ?>
            <div class="request-grid">
                <?php foreach ($idFiles as$idFile): ?>
                    <?php 
                        $cleanPath = ltrim(str_replace(['assets/uploads/', '../assets/uploads/'], '',$idFile['file_path']), '/');
                        $fullPath = "../assets/uploads/" . $cleanPath;
                    ?>
                    <div class="request-item" style="background: #ffffff;">
                        <strong><?= htmlspecialchars($idFile['requirement_name']); ?></strong>
                        <div style="margin: 8px 0;">
                            <span class="badge <?= strtolower($idFile['status']); ?>">
                                <?= htmlspecialchars($idFile['status']); ?>
                            </span>
                        </div>
                        <div style="display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap;">
                            <button type="button" class="btn btn-secondary" onclick="openImageModal('<?= htmlspecialchars($fullPath, ENT_QUOTES); ?>', '<?= htmlspecialchars($idFile['requirement_name'], ENT_QUOTES); ?>')">
                                <i class="fa-solid fa-eye"></i> Inspect ID
                            </button>
                            <?php if ($idFile['status'] == "Pending"): ?>
                                <a class="btn btn-primary" href="verify_requirement.php?id=<?= $idFile['id']; ?>&request=<?=$request_id; ?>">
                                    <i class="fa-solid fa-check"></i> Verify
                                </a>
                                <button type="button" class="btn btn-danger" onclick="openRejectModal(<?= $idFile['id']; ?>)">
                                    <i class="fa-solid fa-xmark"></i> Reject
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($idFile['remarks'])): ?>
                            <div style="margin-top: 8px; padding: 6px 8px; background: var(--slate-100); border-radius: 4px; font-size: 0.78rem; color: var(--slate-600);">
                                <strong style="color: var(--slate-700);">Remarks:</strong> <?= htmlspecialchars($idFile['remarks']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="color: var(--slate-600); font-size: 0.88rem; font-style: italic;">
                No valid ID files uploaded for this request.
            </div>
        <?php endif; ?>
    </div>

    <!-- Requirement Verification Progress Bar -->
    <div class="card">
        <h3 class="card-header-title">
            <i class="fa-solid fa-list-check" style="color: var(--registrar-blue);"></i> Verification Progress
        </h3>
        <div class="progress-bar-bg">
            <div class="progress-bar-fill" style="width: <?= $percent; ?>%; background: <?=$progressColor; ?>;"></div>
        </div>
        <div style="font-size: 0.85rem; color: var(--slate-600); font-weight: 500; display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
            <span><strong><?= $verified; ?></strong> of <strong><?=$total; ?></strong> requirements verified</span>
            <span><strong><?= round($percent); ?>%</strong></span>
        </div>
    </div>

    <!-- Uploaded Requirements List -->
    <h3 style="font-size: 1.05rem; color: var(--registrar-navy); margin: 24px 0 14px 0; font-weight: 700;">Uploaded Document Requirements</h3>

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
                    <div style="background: var(--slate-100); padding: 8px 12px; border-radius: 4px; font-size: 0.85rem; color: var(--slate-700); margin-top: 10px;">
                        <strong style="color: var(--slate-900);">Remarks:</strong> <?= htmlspecialchars($row['remarks']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card" style="text-align: center; color: var(--slate-600); font-style: italic; padding: 20px;">
            No additional document requirements uploaded for this request.
        </div>
    <?php endif; ?>

    <!-- Final Approval Button / Notice -->
    <div style="margin-top: 28px;">
        <?php if ($verified == $total &&$total > 0): ?>
            <a class="btn btn-success" href="approve.php?id=<?= $request_id; ?>" style="font-size: 0.9rem; padding: 10px 20px;">
                <i class="fa-solid fa-circle-check"></i> Approve Entire Request
            </a>
        <?php else: ?>
            <div class="alert-message" style="background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
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
            <h3 id="modalTitle"><i class="fa-solid fa-file-shield" style="color: var(--registrar-blue);"></i> File Preview</h3>
            <button type="button" class="modal-close-btn" onclick="closeImageModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalImageBody"></div>
        <div class="modal-footer">
            <a id="modalFullscreenBtn" href="#" target="_blank" class="btn btn-secondary"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open Fullscreen</a>
            <button type="button" class="btn btn-secondary" onclick="closeImageModal()">Close</button>
        </div>
    </div>
</div>

<!-- REJECT REQUIREMENT MODAL -->
<div id="rejectModal" class="modal-overlay">
    <div class="modal-content-reject">
        <button type="button" class="modal-close-btn" style="position: absolute; right: 16px; top: 16px;" onclick="closeRejectModal()">&times;</button>
        <h3 style="margin-top: 0; color: var(--slate-900); font-size: 1.05rem; margin-bottom: 14px;">Reject Requirement</h3>
        <form method="POST">
            <input type="hidden" name="file_id" id="reject_file_id">
            
            <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.82rem; color: var(--slate-700);">
                Reason for rejection
            </label>
            <textarea name="remarks" rows="4" placeholder="Specify why this document is rejected..." required></textarea>
            
            <div style="margin-top: 16px;">
                <button type="submit" name="reject_requirement" class="btn btn-danger" style="width: 100%;">
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
        bodyHtml = `<img src="${filePath}" alt="Preview" style="max-width: 100%; max-height: 70vh; border-radius: 4px; object-fit: contain;">`;
    } else if (fileExt === 'pdf') {
        bodyHtml = `<iframe src="${filePath}" style="width: 100%; height: 70vh; border: none; border-radius: 4px; background: #fff;"></iframe>`;
    } else {
        bodyHtml = `<p style="color: #cbd5e1;">Preview unavailable for this file format.</p>`;
    }

    document.getElementById("modalTitle").innerHTML = `<i class="fa-solid fa-file-shield" style="color: var(--registrar-blue);"></i> ${title}`;
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