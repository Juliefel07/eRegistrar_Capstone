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
$uploadMessage = "";
$uploadStatus = "";

// FETCH ALL STUDENT REQUESTS FOR SELECTOR DROPDOWN
$allRequestsStmt = mysqli_prepare($conn, "
    SELECT r.*, d.document_name 
    FROM requests r 
    JOIN documents d ON r.document_id = d.document_id 
    WHERE r.user_id = ?
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

// HANDLE RECEIPT / O.R. UPLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    $target_ref = $_POST['tracking_no'] ?? $ref;
    $or_number = trim($_POST['or_number'] ?? '');
    $file = $_FILES['payment_proof'];

    if (!empty($target_ref) && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

        if (in_array($ext, $allowed)) {
            $uploadDir = __DIR__ . "/../uploads/payments/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = "proof_" . time() . "_" . uniqid() . "." . $ext;
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $filePath = "uploads/payments/" . $filename;
                
                // Update database with payment proof and O.R. number
                $updateStmt = mysqli_prepare($conn, "
                    UPDATE requests 
                    SET proof_of_payment = ?, or_no = ? 
                    WHERE tracking_no = ? AND user_id = ?
                ");
                mysqli_stmt_bind_param($updateStmt, "sssi", $filePath, $or_number, $target_ref, $user_id);
                mysqli_stmt_execute($updateStmt);

                $uploadStatus = "success";
                $uploadMessage = "Payment proof submitted successfully! Claim Stub activated below.";
                $ref = $target_ref;

                // Refresh active request data
                $request['proof_of_payment'] = $filePath;
                $request['or_no'] = $or_number;
            } else {
                $uploadStatus = "error";
                $uploadMessage = "File upload failed. Please try again.";
            }
        } else {
            $uploadStatus = "error";
            $uploadMessage = "Invalid file type. Allowed formats: JPG, PNG, PDF.";
        }
    } else {
        $uploadStatus = "error";
        $uploadMessage = "Please select a request and choose a valid file to upload.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Stub - eRegistrar</title>
    
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * { box-sizing: border-box; }
        body { background-color: #f4f6f9; font-family: 'Inter', 'Segoe UI', sans-serif; margin: 0; padding: 0; }
        
        .page-wrapper { width: 100%; display: flex; flex-direction: column; min-height: 100vh; }
        .main-content { max-width: 900px; width: 100%; margin: 30px auto; padding: 0 20px; flex: 1; }

        .card { background: #ffffff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #e2e8f0; }
        .card h3 { margin-top: 0; font-size: 1.2rem; color: #1e293b; display: flex; align-items: center; gap: 8px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.875rem; color: #475569; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; }
        
        .btn-submit { background: #2563eb; color: #ffffff; padding: 12px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; font-size: 0.95rem; transition: background 0.2s; }
        .btn-submit:hover { background: #1d4ed8; }

        /* Alert Boxes */
        .alert { padding: 12px 15px; border-radius: 6px; font-size: 0.875rem; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        /* Claim Stub Formatting */
        .stub-box { background: #ffffff; padding: 30px; border: 2px dashed #0f172a; border-radius: 8px; position: relative; }
        .stub-header { text-align: center; border-bottom: 2px dashed #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; }
        .stub-header h2 { margin: 0; font-size: 1.3rem; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .stub-header p { margin: 4px 0 0; color: #64748b; font-size: 0.85rem; }
        
        .ref-tag { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; text-align: center; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; font-size: 1.1rem; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .info-item { border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; }
        .info-item span { display: block; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600; }
        .info-item strong { color: #0f172a; font-size: 0.95rem; }

        .notice-box { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; padding: 12px; border-radius: 6px; font-size: 0.8rem; margin-top: 15px; }

        .btn-print { background: #059669; color: #ffffff; padding: 12px; border: none; border-radius: 6px; font-weight: 600; width: 100%; margin-top: 20px; cursor: pointer; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-print:hover { background: #047857; }

        @media print {
            .top-navbar, .upload-card, .btn-print, .alert { display: none !important; }
            body { background: #ffffff; padding: 0; }
            .main-content { max-width: 100%; margin: 0; padding: 0; }
            .stub-box { border: 2px dashed #000000; box-shadow: none; border-radius: 0; padding: 20px; }
            .ref-tag { background: none; border: 1px solid #000000; color: #000000; }
        }
    </style>
</head>
<body>

<div class="page-wrapper">

    <!-- INCLUDE NAVIGATION BAR -->
    <?php include("navbar.php"); ?>

    <main class="main-content">

        <?php if (!empty($uploadMessage)): ?>
            <div class="alert alert-<?= $uploadStatus; ?>">
                <?= htmlspecialchars($uploadMessage); ?>
            </div>
        <?php endif; ?>

        <!-- SELECTION & UPLOAD CARD -->
        <div class="card upload-card">
            <h3><i class="fa-solid fa-receipt"></i> Document Payment & Claim Stub</h3>
            <p style="color: #64748b; font-size: 0.875rem; margin-top: 4px;">Select your requested document below to upload your payment receipt and view your claim stub[cite: 1].</p>

            <form action="claim_stub.php<?= !empty($ref) ? '?ref='.urlencode($ref) : '' ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Select Requested Document</label>
                    <select name="tracking_no" onchange="location = 'claim_stub.php?ref=' + this.value;" required>
                        <option value="">-- Choose Request --</option>
                        <?php 
                        if ($allRequests && mysqli_num_rows($allRequests) > 0) {
                            mysqli_data_seek($allRequests, 0);
                            while ($row = mysqli_fetch_assoc($allRequests)): 
                            ?>
                                <option value="<?= htmlspecialchars($row['tracking_no']); ?>" <?= ($ref === $row['tracking_no']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($row['tracking_no'] . ' - ' . $row['document_name']); ?>
                                </option>
                            <?php 
                            endwhile; 
                        }
                        ?>
                    </select>
                </div>

                <?php if ($request): ?>
                    <?php 
                        $statusLower = strtolower($request['status'] ?? 'pending');
                        $isApproved = in_array($statusLower, ['approved', 'approved for payment', 'ready for payment', 'paid', 'completed']);
                    ?>

                    <?php if (!$isApproved): ?>
                        <!-- STATE 1: PENDING REGISTRAR VERIFICATION -->
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-user-clock"></i> <strong>Awaiting Registrar Review:</strong> Your uploaded requirement documents are currently being checked by staff. Once approved, you can pay at the Accounting Office and upload your Official Receipt here.
                        </div>

                    <?php elseif ($statusLower === 'rejected'): ?>
                        <!-- STATE 2: REJECTED BY ADMIN -->
                        <div class="alert alert-error">
                            <i class="fa-solid fa-circle-xmark"></i> <strong>Request Rejected:</strong> Your request was marked as ineligible. Please contact the Registrar's Office for clarification.
                        </div>

                    <?php else: ?>
                        <!-- STATE 3: APPROVED — ALLOW RECEIPT UPLOAD -->
                        <div class="alert alert-success">
                            <i class="fa-solid fa-circle-check"></i> <strong>Cleared for Payment:</strong> Your requirements are verified! Please pay at the Accounting Office and upload your Official Receipt below.
                        </div>

                        <div class="form-group">
                            <label>Official Receipt (O.R.) / Reference No.</label>
                            <input type="text" name="or_number" value="<?= htmlspecialchars($request['or_no'] ?? ''); ?>" placeholder="e.g. OR-891234 or GCash Ref" required>
                        </div>

                        <div class="form-group">
                            <label>Upload Payment Slip / Receipt (JPG, PNG, PDF)</label>
                            <input type="file" name="payment_proof" accept="image/*,.pdf" <?= (empty($request['proof_of_payment'])) ? 'required' : ''; ?>>
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Receipt & Unlock Stub
                        </button>
                    <?php endif; ?>

                <?php endif; ?>
            </form>
        </div>

        <!-- CLAIM STUB DISPLAY -->
        <?php if ($request && (!empty($request['proof_of_payment']) || !empty($request['or_no']))): ?>
            <div class="stub-box">
                <div class="stub-header">
                    <h2>Consolacion Community Tech College</h2>
                    <p>Office of the College Registrar — Official Student Claim Stub</p>
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
                        <strong><?= htmlspecialchars(!empty($request['or_no']) ? $request['or_no'] : 'Attached Online'); ?></strong>
                    </div>
                    <div class="info-item">
                        <span>Date Requested</span>
                        <strong><?= htmlspecialchars($request['created_at'] ?? $request['date_requested'] ?? date("Y-m-d")); ?></strong>
                    </div>
                </div>

                <div class="notice-box">
                    <i class="fa-solid fa-circle-info"></i> <strong>Claiming Instruction:</strong> Present a printed or digital copy of this Claim Stub along with a valid Student ID upon claiming your document at the Registrar's Office[cite: 1].
                </div>

                <button class="btn-print" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> Print Official Claim Stub
                </button>
            </div>
        <?php elseif ($request && $isApproved): ?>
            <div class="card" style="text-align: center; color: #64748b; padding: 40px 20px;">
                <i class="fa-solid fa-lock" style="font-size: 2rem; color: #94a3b8; margin-bottom: 10px;"></i>
                <p>Please upload your payment slip or receipt above to generate your official claim stub[cite: 1].</p>
            </div>
        <?php endif; ?>

    </main>

</div>

</body>
</html>