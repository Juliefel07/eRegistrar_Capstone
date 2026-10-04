<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$request = null;
$error_message = "";
$user_id = $_SESSION['user_id'];
$search_query = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tracking_no'])) {
    $search_query = trim($_POST['tracking_no']);

    if (!empty($search_query)) {
        // SQL Injection Protection using Prepared Statements
        $sql = "
            SELECT 
                r.request_id,
                r.tracking_no,
                d.document_name,
                r.status,
                r.request_date,
                r.quantity
            FROM requests r
            JOIN documents d ON r.document_id = d.document_id
            WHERE r.tracking_no = ? AND r.user_id = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $search_query, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                $request = $row;
            } else {
                $error_message = "No document request found matching tracking number: " . htmlspecialchars($search_query);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error_message = "Please enter a tracking number.";
    }
}

// Map Status to Step Level for Timeline Progress
$status_steps = [
    'Pending' => 1,
    'Processing' => 2,
    'Ready' => 3,
    'Ready for Claim' => 3,
    'Completed' => 4,
    'Claimed' => 4,
    'Rejected' => 0
];
$current_step = $request ? ($status_steps[$request['status']] ?? 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/eRegistrar/assets/images/logooo.png">
    <title>Track Document - eRegistrar</title>

    <style>
        /* RESET & SYSTEM STYLING */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #0056b3;
            --primary-dark: #002d62;
            --bg-body: #f4f6f9;
            --card-bg: #ffffff;
            --text-dark: #333333;
            --text-muted: #6c757d;
            --border: #e9ecef;
            --radius-lg: 16px;
            --radius-md: 10px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            padding-bottom: 80px;
        }

        @media (min-width: 992px) {
            body { padding-bottom: 0; }
        }

        /* CONTAINER & LAYOUT */
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 16px;
        }

        .track-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 28px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        .card-header-text {
            text-align: center;
            margin-bottom: 24px;
        }

        .card-header-text h2 {
            font-size: 22px;
            color: var(--primary-dark);
            margin-bottom: 6px;
        }

        .card-header-text p {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* SEARCH FORM */
        .track-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #444;
        }

        .input-group {
            display: flex;
            gap: 10px;
        }

        .input-group input {
            flex: 1;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #ced4da;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .input-group input:focus {
            border-color: var(--primary);
        }

        .submit-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
        }

        /* ALERT NOTIFICATION */
        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            border: 1px solid #f5c2c7;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* TRACKING RESULT DETAILS */
        .tracking-result {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }

        .result-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            background: #f8f9fa;
            padding: 18px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .info-item span.label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .info-item span.value {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
        }

        /* CLAIM STUB CALLOUT BOX */
        .claim-stub-alert {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: var(--radius-md);
            padding: 18px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .claim-stub-info h4 {
            font-size: 15px;
            color: #166534;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .claim-stub-info p {
            font-size: 13px;
            color: #15803d;
            margin: 0;
        }

        .btn-stub-download {
            background-color: #16a34a;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s ease;
        }

        .btn-stub-download:hover {
            background-color: #15803d;
        }

        /* TIMELINE TRACKER */
        .status-timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 30px 0 10px 0;
        }

        .status-timeline::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: #e9ecef;
            z-index: 1;
        }

        .timeline-step {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .step-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e9ecef;
            color: #adb5bd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        .step-label {
            font-size: 12px;
            font-weight: 500;
            color: #6c757d;
        }

        /* Active Timeline States */
        .timeline-step.completed .step-icon {
            background: #198754;
            color: #ffffff;
        }

        .timeline-step.active .step-icon {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 86, 179, 0.2);
        }

        .timeline-step.completed .step-label,
        .timeline-step.active .step-label {
            color: var(--text-dark);
            font-weight: 600;
        }

        /* RESPONSIVE */
        @media (max-width: 576px) {
            .input-group {
                flex-direction: column;
            }

            .result-grid {
                grid-template-columns: 1fr;
            }

            .step-label {
                font-size: 10px;
            }

            .claim-stub-alert {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-stub-download {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- INCLUDE NAVIGATION BAR -->
    <?php require_once __DIR__ . "/navbar.php"; ?>

    <main class="container">
        <div class="track-card">
            <div class="card-header-text">
                <h2>Track Request Status</h2>
                <p>Enter your document tracking number to view real-time updates.</p>
            </div>

            <!-- Tracking Form -->
            <form method="POST" class="track-form">
                <div class="form-group">
                    <label for="tracking_no">Tracking Number</label>
                    <div class="input-group">
                        <input 
                            type="text" 
                            id="tracking_no" 
                            name="tracking_no" 
                            placeholder="Example: REQ202607171200" 
                            value="<?= htmlspecialchars($search_query) ?>" 
                            required>
                        <button type="submit" class="submit-btn">
                            <i class="fa-solid fa-magnifying-glass"></i> Track Now
                        </button>
                    </div>
                </div>
            </form>

            <!-- Error State -->
            <?php if (!empty($error_message)): ?>
                <div class="alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= $error_message ?></span>
                </div>
            <?php endif; ?>

            <!-- Tracking Result Section -->
            <?php if ($request): ?>
                <div class="tracking-result">
                    
                    <!-- Information Summary Grid -->
                    <div class="result-grid">
                        <div class="info-item">
                            <span class="label">Tracking Number</span>
                            <span class="value"><?= htmlspecialchars($request['tracking_no']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Document Requested</span>
                            <span class="value"><?= htmlspecialchars($request['document_name']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Quantity</span>
                            <span class="value"><?= htmlspecialchars($request['quantity']) ?> copy/ies</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Date Requested</span>
                            <span class="value"><?= date("F j, Y", strtotime($request['request_date'])) ?></span>
                        </div>
                    </div>

                    <!-- Claim Stub Download Action (Triggered when Ready for Claim or Claimed/Completed) -->
                    <?php if ($request['status'] === 'Ready for Claim' || $request['status'] === 'Ready' || $request['status'] === 'Claimed' || $request['status'] === 'Completed'): ?>
                        <div class="claim-stub-alert">
                            <div class="claim-stub-info">
                                <h4><i class="fa-solid fa-circle-check"></i> Document is Ready for Pickup!</h4>
                                <p>Your claim stub is ready. Please download or print it and present it to the Registrar Office.</p>
                            </div>
                            <a href="claim_stub.php?id=<?= $request['request_id'] ?>" target="_blank" class="btn-stub-download">
                                <i class="fa-solid fa-ticket"></i> Print Claim Stub
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Visual Timeline Progress -->
                    <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 12px; color: var(--primary-dark);">Progress Tracking</h3>
                    
                    <?php if ($request['status'] === 'Rejected'): ?>
                        <div class="alert-danger" style="margin-top: 0;">
                            <i class="fa-solid fa-circle-xmark"></i>
                            <span>This request was <strong>Rejected</strong> by the registrar. Please check your messages or visit the office for details.</span>
                        </div>
                    <?php else: ?>
                        <div class="status-timeline">
                            <div class="timeline-step <?= ($current_step > 1) ? 'completed' : (($current_step == 1) ? 'active' : '') ?>">
                                <div class="step-icon"><i class="fa-solid fa-clock"></i></div>
                                <span class="step-label">Pending</span>
                            </div>
                            <div class="timeline-step <?= ($current_step > 2) ? 'completed' : (($current_step == 2) ? 'active' : '') ?>">
                                <div class="step-icon"><i class="fa-solid fa-gear"></i></div>
                                <span class="step-label">Processing</span>
                            </div>
                            <div class="timeline-step <?= ($current_step > 3) ? 'completed' : (($current_step == 3) ? 'active' : '') ?>">
                                <div class="step-icon"><i class="fa-solid fa-box-archive"></i></div>
                                <span class="step-label">Ready</span>
                            </div>
                            <div class="timeline-step <?= ($current_step == 4) ? 'completed' : '' ?>">
                                <div class="step-icon"><i class="fa-solid fa-check"></i></div>
                                <span class="step-label">Completed</span>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>