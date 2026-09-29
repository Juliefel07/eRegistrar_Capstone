<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$ref = $_GET['ref'] ?? '';

// Fetch request and document details
$stmt = mysqli_prepare($conn, "
    SELECT r.*, d.document_name, d.fee 
    FROM requests r 
    JOIN documents d ON r.document_id = d.document_id 
    WHERE r.tracking_no = ? AND r.user_id = ?
");
mysqli_stmt_bind_param($stmt, "si", $ref, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$request = mysqli_fetch_assoc($result);

if (!$request) {
    die("Payment slip not found or unauthorized access.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Slip - <?php echo htmlspecialchars($request['tracking_no']); ?></title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #eef2f6; padding: 20px; color: #1e293b; }
        .slip-card { 
            max-width: 650px; 
            margin: auto; 
            background: #fff; 
            padding: 30px; 
            border: 2px solid #0f172a; 
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px; }
        .header h3 { margin: 0; font-size: 1.1rem; color: #0f172a; text-transform: uppercase; }
        .header h2 { margin: 4px 0; font-size: 1.3rem; color: #1e3a8a; }
        .header p { margin: 0; font-size: 0.85rem; color: #64748b; }
        
        .badge-notice {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 10px;
            font-size: 0.85rem;
            color: #92400e;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .info-item { font-size: 0.9rem; }
        .info-item label { color: #64748b; display: block; font-size: 0.75rem; text-transform: uppercase; }
        .info-item strong { color: #0f172a; }

        .assessment-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .assessment-table th, .assessment-table td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; font-size: 0.9rem; }
        .assessment-table th { background: #f8fafc; font-weight: bold; }
        .assessment-table .total-row td { font-weight: bold; font-size: 1rem; background: #f1f5f9; }

        .cashier-section { 
            border: 1px dashed #94a3b8; 
            padding: 15px; 
            border-radius: 6px; 
            margin-top: 20px; 
            background: #fafafa;
        }
        .cashier-section h4 { margin: 0 0 10px 0; font-size: 0.85rem; text-transform: uppercase; color: #475569; }
        .signature-line { margin-top: 40px; border-top: 1px solid #0f172a; width: 200px; text-align: center; font-size: 0.8rem; padding-top: 4px; }

        .actions { display: flex; gap: 10px; margin-top: 25px; }
        .btn { flex: 1; padding: 12px; text-align: center; text-decoration: none; border-radius: 6px; font-weight: bold; cursor: pointer; border: none; font-size: 0.9rem; }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-close { background: #e2e8f0; color: #334155; }

        @media print {
            body { background: #fff; padding: 0; }
            .slip-card { border: 2px solid #000; box-shadow: none; max-width: 100%; }
            .actions { display: none; }
        }
    </style>
</head>
<body>

<div class="slip-card">
    <div class="header">
        <h3>CONSOLATRIX COLLEGE OF TOLEDO CITY, INC.</h3>
        <p>Magsaysay Hills, Toledo City • Office of the Registrar / Accounting</p>
        <h2>ASSESSMENT / ORDER OF PAYMENT SLIP</h2>
    </div>

    <div class="badge-notice">
        <strong>Instruction:</strong> Present this printed assessment slip to the <strong>Accounting / Cashier Office (Teller)</strong> to pay your requested fees before processing[cite: 3].
    </div>

    <div class="info-grid">
        <div class="info-item">
            <label>Tracking Number</label>
            <strong style="color: #2563eb; font-size: 1.1rem;"><?php echo htmlspecialchars($request['tracking_no']); ?></strong>
        </div>
        <div class="info-item">
            <label>Date Requested</label>
            <strong><?php echo date("F d, Y", strtotime($request['created_at'] ?? 'now')); ?></strong>
        </div>
        <div class="info-item">
            <label>Student Name</label>
            <strong><?php echo htmlspecialchars($request['fullname']); ?></strong>
        </div>
        <div class="info-item">
            <label>Course & Year / Address</label>
            <strong><?php echo htmlspecialchars($request['course'] . ' ' . $request['year_level']); ?></strong>
        </div>
    </div>

    <table class="assessment-table">
        <thead>
            <tr>
                <th>Item / Description</th>
                <th>Qty</th>
                <th>Unit Fee</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo htmlspecialchars($request['document_name']); ?></td>
                <td><?php echo htmlspecialchars($request['quantity']); ?></td>
                <td>₱<?php echo number_format($request['fee'], 2); ?></td>
                <td style="text-align: right;">₱<?php echo number_format($request['fee'] * $request['quantity'], 2); ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">TOTAL ASSESSMENT DUE:</td>
                <td style="text-align: right; color: #b91c1c;">₱<?php echo number_format($request['total_amount'], 2); ?></td>
            </tr>
        </tbody>
    </table>

    <!-- CASHIER / TELLER SECTION -->
    <div class="cashier-section">
        <h4>To be filled by Teller / Accounting Office[cite: 3]</h4>
        <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
            <div>
                <p>Official Receipt (O.R.) No.: ____________________</p>
                <p>Date Paid: ____________________</p>
            </div>
            <div>
                <div class="signature-line">
                    Teller's Signature / Stamp[cite: 3]
                </div>
            </div>
        </div>
    </div>

    <div class="actions">
        <button class="btn btn-print" onclick="window.print()">🖨 Print Payment Slip</button>
        <button class="btn btn-close" onclick="window.close()">Close</button>
    </div>
</div>

</body>
</html>