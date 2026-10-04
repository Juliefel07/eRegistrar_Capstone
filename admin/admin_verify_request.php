<?php
session_start();
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification.php";

// Ensure admin is logged in (adjust session check based on your admin role setup)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$success_msg = "";
$error_msg = "";

// Handle status updates or verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($request_id > 0) {
        if ($action === 'verify_requirements') {
            // Update status to indicate requirements are checked and payment slip is generated/unlocked
            $updateStmt = mysqli_prepare($conn, "UPDATE requests SET status = 'Payment Slip Generated' WHERE request_id = ?");
            mysqli_stmt_bind_param($updateStmt, "i", $request_id);
            
            if (mysqli_stmt_execute($updateStmt)) {
                // Get user_id to send notification
                $userQuery = mysqli_query($conn, "SELECT user_id, tracking_no FROM requests WHERE request_id = $request_id");
                if ($reqData = mysqli_fetch_assoc($userQuery)) {
                    createNotification($conn, $reqData['user_id'], "Your requirements for request ({$reqData['tracking_no']}) have been verified. You can now view and print your Payment Slip.");
                }
                $success_msg = "Requirements verified successfully! Payment slip unlocked for the student.";
            } else {
                $error_msg = "Failed to update request status.";
            }
            mysqli_stmt_close($updateStmt);
        } elseif ($action === 'reject') {
            $reason = trim($_POST['rejection_reason'] ?? 'Requirements incomplete or invalid.');
            $updateStmt = mysqli_prepare($conn, "UPDATE requests SET status = 'Rejected', remarks = CONCAT(remarks, ' | Rejected: ', ?) WHERE request_id = ?");
            mysqli_stmt_bind_param($updateStmt, "si", $reason, $request_id);
            
            if (mysqli_stmt_execute($updateStmt)) {
                $userQuery = mysqli_query($conn, "SELECT user_id, tracking_no FROM requests WHERE request_id = $request_id");
                if ($reqData = mysqli_fetch_assoc($userQuery)) {
                    createNotification($conn, $reqData['user_id'], "Your request ({$reqData['tracking_no']}) was rejected. Reason: $reason");
                }
                $success_msg = "Request marked as rejected.";
            } else {
                $error_msg = "Failed to reject request.";
            }
            mysqli_stmt_close($updateStmt);
        }
    }
}

// Fetch all pending or active requests for verification
$filter_status = $_GET['status'] ?? 'Pending';
$query = "
    SELECT r.*, d.document_name, d.fee 
    FROM requests r
    JOIN documents d ON r.document_id = d.document_id
    WHERE r.status = ?
    ORDER BY r.created_at DESC
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $filter_status);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Requests - Admin Registrar</title>
    <link rel="icon" type="image/png" href="assets/images/logooo.png">
    <link rel="stylesheet" href="../assets/css/request.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .admin-container { max-width: 1200px; margin: 30px auto; padding: 0 16px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { background: #ffffff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 24px; }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; text-align: left; font-size: 0.9rem; }
        th, td { padding: 12px 16px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; color: #475569; font-weight: 600; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-generated { background: #e0f2fe; color: #0284c7; }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; cursor: pointer; text-decoration: none; border: none; font-weight: 600; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .filter-tab { padding: 8px 16px; border-radius: 6px; background: #f1f5f9; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.85rem; }
        .filter-tab.active { background: #2563eb; color: white; }
        .alert-success { background: #ecfdf5; color: #065f46; padding: 12px; border-radius: 6px; margin-bottom: 16px; }
        .alert-error { background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 16px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: white; padding: 24px; border-radius: 8px; width: 100%; max-width: 500px; }
    </style>
</head>
<body>

<div class="admin-container">
    <div class="card">
        <h2><i class="fa-solid fa-clipboard-check"></i> Student Document Request Verification</h2>
        <p style="color: #64748b; font-size: 0.9rem;">Review submitted student requirements and unlock payment slips upon verification.</p>

        <?php if (!empty($success_msg)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <!-- Filter Navigation Tabs -->
        <div class="filter-tabs" style="margin-top: 20px;">
            <a href="admin_verify_request.php?status=Pending" class="filter-tab <?php echo $filter_status === 'Pending' ? 'active' : ''; ?>">Pending Review</a>
            <a href="admin_verify_request.php?status=Payment Slip Generated" class="filter-tab <?php echo $filter_status === 'Payment Slip Generated' ? 'active' : ''; ?>">Payment Slip Generated</a>
            <a href="admin_verify_request.php?status=Processing" class="filter-tab <?php echo $filter_status === 'Processing' ? 'active' : ''; ?>">Processing</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tracking No.</th>
                        <th>Student Name</th>
                        <th>Document & Qty</th>
                        <th>Purpose</th>
                        <th>Payment Option</th>
                        <th>Requirements Files</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['tracking_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['fullname']); ?><br><small style="color:#666;"><?php echo htmlspecialchars($row['course']); ?></small></td>
                                <td><?php echo htmlspecialchars($row['document_name']); ?> (<strong><?php echo $row['quantity']; ?>x</strong>)</td>
                                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                <td>
                                    <?php
                                    // Fetch requirement files attached to this request
                                    $reqFilesQuery = mysqli_query($conn, "SELECT * FROM request_requirement_files WHERE request_id = {$row['request_id']}");
                                    if (mysqli_num_rows($reqFilesQuery) > 0) {
                                        while ($rf = mysqli_fetch_assoc($reqFilesQuery)) {
                                            echo '<a href="../' . htmlspecialchars($rf['file_path']) . '" target="_blank" style="display:inline-block; margin-right:5px; color:#2563eb; font-size:0.8rem;"><i class="fa-solid fa-file-arrow-down"></i> ' . htmlspecialchars($rf['file_name']) . '</a><br>';
                                        }
                                    } else {
                                        echo '<span style="color:#999; font-size:0.8rem;">No files required</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $row['status'] === 'Pending' ? 'badge-pending' : 'badge-generated'; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'Pending'): ?>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                            <input type="hidden" name="action" value="verify_requirements">
                                            <button type="submit" class="btn-sm btn-success" onclick="return confirm('Verify requirements and generate/unlock payment slip for this student?')">
                                                <i class="fa-solid fa-check"></i> Verify & Generate Slip
                                            </button>
                                        </form>
                                        <button type="button" class="btn-sm btn-danger" onclick="openRejectModal(<?php echo $row['request_id']; ?>)" style="margin-top: 4px;">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                    <?php else: ?>
                                        <span style="color: #64748b; font-size: 0.8rem;">Already Verified</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #64748b; padding: 24px;">No requests found under this filter.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Reject Reason Modal -->
<div class="modal" id="rejectModal">
    <div class="modal-content">
        <h3>Reject Request</h3>
        <p style="font-size:0.9rem; color:#666;">Please specify why this request is being rejected:</p>
        <form method="POST" id="rejectForm">
            <input type="hidden" name="request_id" id="rejectRequestId">
            <input type="hidden" name="action" value="reject">
            <textarea name="rejection_reason" rows="4" style="width:100%; padding:8px; margin-top:8px; border:1px solid #cbd5e1; border-radius:6px;" placeholder="e.g., Missing valid school ID clearance..." required></textarea>
            <div style="margin-top: 16px; text-align: right;">
                <button type="button" class="btn-sm" onclick="closeRejectModal()" style="background:#e2e8f0; color:#334151; margin-right:8px;">Cancel</button>
                <button type="submit" class="btn-sm btn-danger">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(requestId) {
    document.getElementById('rejectRequestId').value = requestId;
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>

</body>
</html>