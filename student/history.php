<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ==========================================
// 1. Handle Payment Proof Upload Submission
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_proof'])) {
    $request_id = intval($_POST['request_id']);
    
    $check_stmt = mysqli_prepare($conn, "SELECT request_id FROM requests WHERE request_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($check_stmt, "ii", $request_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['payment_proof']['tmp_name'];
            $file_name = $_FILES['payment_proof']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];
            if (in_array($file_ext, $allowed_exts)) {
                $new_filename = "proof_" . $request_id . "_" . time() . "." . $file_ext;
                $upload_dir = __DIR__ . "/../assets/uploads/";
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    $update_stmt = mysqli_prepare($conn, "UPDATE requests SET payment_proof = ?, status = 'Payment Uploaded' WHERE request_id = ? AND user_id = ?");
                    mysqli_stmt_bind_param($update_stmt, "sii", $new_filename, $request_id, $user_id);
                    mysqli_stmt_execute($update_stmt);
                    
                    header("Location: history.php?success=uploaded");
                    exit();
                }
            }
        }
    }
}

// ==============================================
// 2. Handle Single Rejected File Re-upload
// ==============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reupload_single_file'])) {
    $file_table_id = intval($_POST['file_table_id']); // Maps to `id` column
    $request_id = intval($_POST['request_id']);

    // Verify ownership of the request
    $check_stmt = mysqli_prepare($conn, "
        SELECT rf.id, rf.file_path 
        FROM request_requirement_files rf 
        JOIN requests r ON rf.request_id = r.request_id 
        WHERE rf.id = ? AND r.user_id = ?
    ");
    mysqli_stmt_bind_param($check_stmt, "ii", $file_table_id,$user_id);
    mysqli_stmt_execute($check_stmt);
    $check_res = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_res) > 0) {
        if (isset($_FILES['new_file']) && $_FILES['new_file']['error'] === UPLOAD_ERR_OK) {$tmp_name = $_FILES['new_file']['tmp_name'];$filename = $_FILES['new_file']['name'];$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));$allowed_exts = ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'doc'];

            if (in_array($ext, $allowed_exts)) {$upload_dir = __DIR__ . "/../assets/uploads/requirements/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $new_filename = time() . "_" . $file_table_id . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "_", $filename);
                $relative_path = "assets/uploads/requirements/" . $new_filename;

                if (move_uploaded_file($tmp_name, $upload_dir .$new_filename)) {
                    // Update the specific rejected row & reset status back to Pending
                    $update_file = mysqli_prepare($conn, "
                        UPDATE request_requirement_files 
                        SET file_name = ?, file_path = ?, status = 'Pending', remarks = NULL, uploaded_at = NOW() 
                        WHERE id = ?
                    ");
                    mysqli_stmt_bind_param($update_file, "ssi", $filename, $relative_path,$file_table_id);
                    mysqli_stmt_execute($update_file);

                    // Reset main request status to Pending for admin re-evaluation
                    $update_req = mysqli_prepare($conn, "UPDATE requests SET status = 'Pending' WHERE request_id = ?");
                    mysqli_stmt_bind_param($update_req, "i", $request_id);
                    mysqli_stmt_execute($update_req);

                    header("Location: history.php?success=file_replaced");
                    exit();
                }
            }
        }
    }
}

// ==========================================
// 3. Fetch Requests with Requirement Files & Processor Name
// ==========================================
$stmt = mysqli_prepare($conn, "
    SELECT 
        r.request_id,
        r.tracking_no,
        u.fullname,
        d.document_name,
        r.purpose,
        r.quantity,
        r.status AS request_status,
        r.request_date,
        r.payment_proof,
        p.fullname AS processor_name,
        GROUP_CONCAT(rf.id ORDER BY rf.id ASC SEPARATOR '||') as file_ids,
        GROUP_CONCAT(rf.file_path ORDER BY rf.id ASC SEPARATOR '||') as file_paths,
        GROUP_CONCAT(rf.file_name ORDER BY rf.id ASC SEPARATOR '||') as file_names,
        GROUP_CONCAT(IFNULL(rf.status, 'Pending') ORDER BY rf.id ASC SEPARATOR '||') as file_statuses,
        GROUP_CONCAT(IFNULL(rf.remarks, '') ORDER BY rf.id ASC SEPARATOR '||') as file_remarks
    FROM requests r
    JOIN users u ON r.user_id = u.user_id
    JOIN documents d ON r.document_id = d.document_id
    LEFT JOIN users p ON r.processed_by = p.user_id
    LEFT JOIN request_requirement_files rf ON r.request_id = rf.request_id
    WHERE r.user_id = ?
    GROUP BY r.request_id
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
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">

    <style>
        .student-main {
            padding: 20px 15px;
            max-width: 1250px;
            margin: 0 auto;
            padding-bottom: 90px;
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
        .status.processing { background: #e0f2fe; color: #0369a1; }
        .status.rejected { background: #f8d7da; color: #721c24; }
        .status.reupload { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .status.completed { background: #cce5ff; color: #004085; }
        .status.claimed { background: #d1fae5; color: #065f46; }

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
            border: none;
        }

        .btn.approve { background: #e7f1ff; color: #0056b3; }
        .btn.approve:hover { background: #d0e1fd; }

        .btn.upload-btn { background: #7c3aed; color: #fff; }
        .btn.upload-btn:hover { background: #6d28d9; }

        .btn.reupload-btn { background: #dc2626; color: #fff; }
        .btn.reupload-btn:hover { background: #b91c1c; }

        .btn.view-receipt { background: #0284c7; color: #fff; }
        .btn.view-receipt:hover { background: #0369a1; }

        .no-action {
            color: #adb5bd;
            font-style: italic;
            font-size: 12px;
        }

        .alert-success {
            background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; font-size: 13px;
        }

        /* MODAL STYLES */
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
            max-width: 580px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            animation: modalFadeIn 0.25s ease-out;
            overflow: hidden;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-close-btn {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #6c757d;
            cursor: pointer;
        }

        .modal-close-btn:hover {
            color: #333;
        }

        .modal-body {
            padding: 20px;
            font-size: 0.92rem;
            color: #334155;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 12px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            text-align: right;
        }
    </style>
</head>

<body>

    <?php include("navbar.php"); ?>

    <div class="student-main">
        <div class="history-card">
            <h2>My Document Requests</h2>
            <p>Track the progress of your submitted requests and upload payment proof once approved.</p>

            <?php if (isset($_GET['success']) &&$_GET['success'] === 'uploaded'): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i> Payment receipt successfully uploaded!
                </div>
            <?php elseif (isset($_GET['success']) &&$_GET['success'] === 'file_replaced'): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i> Requirement file re-uploaded successfully! Request status reset to Pending.
                </div>
            <?php endif; ?>

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
                                <th>Proof of Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['tracking_no']); ?></strong></td>
                                    <td><?= htmlspecialchars($row['document_name']); ?></td>
                                    <td><?= htmlspecialchars($row['purpose']); ?></td>
                                    <td><?= $row['quantity']; ?></td>

                                    <!-- Status Column -->
                                    <td>
                                        <?php 
                                            $file_statuses = !empty($row['file_statuses']) ? explode('\vert{}\vert{}', $row['file_statuses']) : [];
                                            $has_rejected_file = in_array('Rejected',$file_statuses);
                                        ?>

                                        <?php if ($has_rejected_file): ?>
                                            <span class="status reupload">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Re-upload Needed
                                            </span>
                                        <?php else: ?>
                                            <span class="status <?= strtolower(str_replace(' ', '-', $row['request_status'])); ?>">
                                                <?= htmlspecialchars($row['request_status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= date("M d, Y", strtotime($row['request_date'])); ?></td>
                                    
                                    <!-- Requirement File Column -->
                                    <td>
                                        <?php if (!empty($row['file_paths'])): ?>
                                            <button type="button" class="btn approve" onclick="openMultiFileModal(
                                                '<?= htmlspecialchars($row['file_ids'] ?? '', ENT_QUOTES); ?>',
                                                '<?= htmlspecialchars($row['file_paths'] ?? '', ENT_QUOTES); ?>',
                                                '<?= htmlspecialchars($row['file_names'] ?? '', ENT_QUOTES); ?>',
                                                '<?= htmlspecialchars($row['file_statuses'] ?? '', ENT_QUOTES); ?>',
                                                '<?= htmlspecialchars($row['file_remarks'] ?? '', ENT_QUOTES); ?>',
                                                '<?= htmlspecialchars($row['tracking_no'], ENT_QUOTES); ?>',
                                                <?= $row['request_id']; ?>
                                            )">
                                                <i class="fa-solid fa-file-arrow-down"></i> View Files
                                            </button>
                                        <?php else: ?>
                                            <span class="no-action">No File</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Action Column -->
                                    <td>
                                        <button type="button" class="btn approve" onclick="openDetailsModal(
                                            '<?= htmlspecialchars($row['tracking_no'], ENT_QUOTES); ?>',
                                            '<?= htmlspecialchars($row['document_name'], ENT_QUOTES); ?>',
                                            '<?= htmlspecialchars($row['purpose'], ENT_QUOTES); ?>',
                                            '<?= $row['quantity']; ?>',
                                            '<?= htmlspecialchars($row['request_status'], ENT_QUOTES); ?>',
                                            '<?= date("M d, Y", strtotime($row['request_date'])); ?>',
                                            '<?= htmlspecialchars($row['processor_name'] ?? 'Unassigned', ENT_QUOTES); ?>'
                                        )">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                    </td>

                                    <!-- Proof of Payment Column -->
                                    <td>
                                        <?php 
                                            $status_lower = strtolower(trim($row['request_status']));
                                            $is_approved_for_payment = in_array($status_lower, ['approved', 'payment uploaded', 'processing', 'completed', 'claimed']);
                                        ?>

                                        <?php if ($is_approved_for_payment): ?>
                                            <?php if (!empty($row['payment_proof'])): ?>
                                                <button type="button" class="btn view-receipt" onclick="openModal('receiptModal', 'modalReceiptContent', 'modalReceiptDownloadBtn', '../assets/uploads/<?= htmlspecialchars($row['payment_proof'], ENT_QUOTES); ?>', '<?= htmlspecialchars($row['payment_proof'], ENT_QUOTES); ?>', '<?= htmlspecialchars($row['tracking_no'], ENT_QUOTES); ?>')">
                                                    <i class="fa-solid fa-receipt"></i> View Receipt
                                                </button>
                                            <?php else: ?>
                                                <form action="history.php" method="POST" enctype="multipart/form-data" style="margin: 0; display: inline-block;" onsubmit="return confirm('Upload this payment receipt?');">
                                                    <input type="hidden" name="request_id" value="<?= $row['request_id']; ?>">
                                                    <label class="btn upload-btn" style="cursor: pointer; margin: 0;">
                                                        <i class="fa-solid fa-upload"></i> Upload
                                                        <input type="file" name="payment_proof" accept=".jpg, .jpeg, .png, .pdf" required style="display: none;" onchange="this.form.submit()">
                                                    </label>
                                                    <input type="hidden" name="upload_proof" value="1">
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="no-action">Awaiting Approval</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state" style="text-align: center; padding: 40px; color: #6c757d;">
                    <i class="fa-solid fa-folder-open" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                    <p>No document requests found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 1. Requirement File Preview Modal -->
    <div id="requirementModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fa-solid fa-file-lines" style="color: #0056b3;"></i> Requirement Files</h3>
                <button type="button" class="modal-close-btn" onclick="closeModal('requirementModal')">&times;</button>
            </div>
            <div class="modal-body" id="modalRequirementContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background-color: #6c757d; color: #fff;" onclick="closeModal('requirementModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- 2. Proof of Payment Modal -->
    <div id="receiptModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fa-solid fa-receipt" style="color: #0284c7;"></i> Payment Receipt Preview</h3>
                <button type="button" class="modal-close-btn" onclick="closeModal('receiptModal')">&times;</button>
            </div>
            <div class="modal-body" id="modalReceiptContent" style="text-align: center;"></div>
            <div class="modal-footer">
                <a id="modalReceiptDownloadBtn" href="#" target="_blank" class="btn view-receipt" style="margin-right: 5px;"><i class="fa-solid fa-external-link-alt"></i> Open Fullscreen</a>
                <button type="button" class="btn" style="background-color: #6c757d; color: #fff;" onclick="closeModal('receiptModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- 3. Request Details Modal -->
    <div id="detailsModal" class="modal-overlay">
        <div class="modal-container" style="max-width: 500px;">
            <div class="modal-header">
                <h3><i class="fa-solid fa-circle-info" style="color: #0056b3;"></i> Request Information</h3>
                <button type="button" class="modal-close-btn" onclick="closeModal('detailsModal')">&times;</button>
            </div>
            <div class="modal-body" id="modalDetailsContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background-color: #6c757d; color: #fff;" onclick="closeModal('detailsModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function openMultiFileModal(fileIdsStr, filePathsStr, fileNamesStr, fileStatusesStr, fileRemarksStr, trackingNo, requestId) {
            const ids = fileIdsStr ? fileIdsStr.split('||') : [];
            const paths = filePathsStr ? filePathsStr.split('||') : [];
            const names = fileNamesStr ? fileNamesStr.split('||') : [];
            const statuses = fileStatusesStr ? fileStatusesStr.split('||') : [];
            const remarks = fileRemarksStr ? fileRemarksStr.split('||') : [];

            let htmlContent = '<div style="margin-bottom: 15px; font-weight: 600; color: #495057; font-size: 1rem;">Tracking No: ' + trackingNo + '</div>';

            paths.forEach((path, index) => {
                let fileTableId = ids[index] || '';
                let cleanPath = "../" + path.trim();
                let fileName = names[index] ? names[index].trim() : ('File ' + (index + 1));
                let fileStatus = statuses[index] ? statuses[index].trim() : 'Pending';
                let fileRemark = remarks[index] ? remarks[index].trim() : '';
                let fileExt = fileName.split('.').pop().toLowerCase();

                htmlContent += '<div style="margin-bottom: 20px; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc;">';
                
                // File Header & Status Badge
                htmlContent += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">';
                htmlContent += '<span style="font-weight: 600; font-size: 0.88rem; color: #1e293b;"><i class="fa-solid fa-file"></i> ' + fileName + '</span>';
                
                if (fileStatus.toLowerCase() === 'rejected') {
                    htmlContent += '<span class="status rejected"><i class="fa-solid fa-triangle-exclamation"></i> Rejected</span>';
                } else if (fileStatus.toLowerCase() === 'approved') {
                    htmlContent += '<span class="status approved"><i class="fa-solid fa-circle-check"></i> Approved</span>';
                } else {
                    htmlContent += '<span class="status pending"><i class="fa-solid fa-clock"></i> Pending</span>';
                }
                htmlContent += '</div>';

                // File Preview Content
                htmlContent += '<div style="text-align: center;">';
                if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                    htmlContent += '<img src="' + cleanPath + '" alt="File Preview" style="max-width: 100%; max-height: 35vh; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 8px;">';
                } else if (fileExt === 'pdf') {
                    htmlContent += '<iframe src="' + cleanPath + '" style="width: 100%; height: 35vh; border: none; border-radius: 6px; margin-bottom: 8px;"></iframe>';
                } else {
                    htmlContent += '<p style="color: #64748b; font-style: italic;">File preview unavailable.</p>';
                }
                htmlContent += '</div>';

                // Re-upload Box (If File Status is Rejected)
                if (fileStatus.toLowerCase() === 'rejected') {
                    htmlContent += '<div style="margin-top: 10px; padding: 10px; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 6px;">';
                    if (fileRemark !== '') {
                        htmlContent += '<p style="color: #991b1b; font-size: 0.82rem; margin: 0 0 8px 0;"><strong>Reason:</strong> ' + fileRemark + '</p>';
                    }
                    htmlContent += '<form action="history.php" method="POST" enctype="multipart/form-data" style="display: flex; gap: 8px; align-items: center;">';
                    htmlContent += '<input type="hidden" name="file_table_id" value="' + fileTableId + '">';
                    htmlContent += '<input type="hidden" name="request_id" value="' + requestId + '">';
                    htmlContent += '<input type="hidden" name="reupload_single_file" value="1">';
                    htmlContent += '<input type="file" name="new_file" required accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" style="font-size: 11px; flex: 1;">';
                    htmlContent += '<button type="submit" class="btn reupload-btn" style="padding: 5px 12px; font-size: 11px;"><i class="fa-solid fa-upload"></i> Re-upload</button>';
                    htmlContent += '</form>';
                    htmlContent += '</div>';
                } else {
                    htmlContent += '<div style="text-align: center; margin-top: 6px;">';
                    htmlContent += '<a href="' + cleanPath + '" target="_blank" class="btn approve" style="font-size: 11px; padding: 4px 10px;"><i class="fa-solid fa-external-link-alt"></i> Open Fullscreen</a>';
                    htmlContent += '</div>';
                }

                htmlContent += '</div>';
            });

            document.getElementById('modalRequirementContent').innerHTML = htmlContent;
            document.getElementById('requirementModal').style.display = 'flex';
        }

        function openModal(modalId, contentId, downloadBtnId, filePath, fileName, trackingNo) {
            let previewHtml = '';
            const fileExt = fileName.split('.').pop().toLowerCase();
            
            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                previewHtml = '<img src="' + filePath + '" alt="File Preview" style="max-width: 100%; max-height: 50vh; border-radius: 6px; border: 1px solid #e9ecef;">';
            } else if (fileExt === 'pdf') {
                previewHtml = '<iframe src="' + filePath + '" style="width: 100%; height: 50vh; border: none; border-radius: 6px;"></iframe>';
            }
            previewHtml = '<div style="margin-bottom: 10px; font-weight: 600; color: #495057;">Tracking No: ' + trackingNo + '</div>' + previewHtml;

            document.getElementById(contentId).innerHTML = previewHtml;
            document.getElementById(downloadBtnId).href = filePath;
            document.getElementById(modalId).style.display = 'flex';
        }

        function openDetailsModal(trackingNo, documentName, purpose, quantity, status, requestDate, processorName) {
            let htmlContent = `
                <div style="display: flex; flex-direction: column; gap: 14px; font-size: 0.95rem;">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b; font-weight: 500;">Tracking No:</span>
                        <span style="font-weight: 700; color: #0f172a;">${trackingNo}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b; font-weight: 500;">Document:</span>
                        <span style="font-weight: 600; color: #0f172a;">${documentName}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b; font-weight: 500;">Purpose:</span>
                        <span style="color: #334155;">${purpose}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b; font-weight: 500;">Quantity:</span>
                        <span style="font-weight: 600; color: #0f172a;">${quantity}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b; font-weight: 500;">Status:</span>
                        <span class="status ${status.toLowerCase().replace(/\s+/g, '-')}">${status}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b; font-weight: 500;">Processed By:</span>
                        <span style="font-weight: 600; color: #0284c7;"><i class="fa-solid fa-user-shield"></i> ${processorName}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b; font-weight: 500;">Requested Date:</span>
                        <span style="color: #334155;">${requestDate}</span>
                    </div>
                </div>
            `;
            document.getElementById('modalDetailsContent').innerHTML = htmlContent;
            document.getElementById('detailsModal').style.display = 'flex';
        }
    </script>

</body>
</html>