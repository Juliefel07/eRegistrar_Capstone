<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

// Fetch pending and processing requests
$query = "SELECT r.*, d.document_name 
          FROM requests r 
          JOIN documents d ON r.document_id = d.document_id 
          ORDER BY r.request_id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Document Requests & Payments</title>
    <link rel="icon" type="image/png" href="assets/images/logooo.png">
    <link rel="stylesheet" href="../assets/css/student.css">
    <style>
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        .admin-table th, .admin-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .admin-table th { background-color: #2563eb; color: white; }
        .badge-pending { background: #f59e0b; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .badge-paid { background: #16a34a; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .btn-verify { background: #2563eb; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

<div style="padding: 30px;">
    <h2>📋 Student Requests & Payment Management</h2>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Tracking No.</th>
                <th>Student Name</th>
                <th>Document</th>
                <th>Amount</th>
                <th>Payment Method</th>
                <th>Payment Status</th>
                <th>O.R. Number</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($result)){ ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['tracking_no']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                <td><?php echo htmlspecialchars($row['document_name']); ?></td>
                <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                <td>
                    <span class="<?php echo ($row['payment_status'] === 'Paid') ? 'badge-paid' : 'badge-pending'; ?>">
                        <?php echo htmlspecialchars($row['payment_status']); ?>
                    </span>
                </td>
                <td><?php echo htmlspecialchars($row['receipt_no'] ?? 'N/A'); ?></td>
                <td>
                    <?php if($row['payment_status'] !== 'Paid'){ ?>
                        <button class="btn-verify" onclick="openPaymentModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                            Verify & Enter O.R.
                        </button>
                    <?php } else { ?>
                        <span style="color:#16a34a;">✔ Verified</span>
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- VERIFICATION MODAL -->
<div id="paymentModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
    <div style="background:#fff; padding:25px; border-radius:8px; width:400px;">
        <h3>Verify Payment & Issue O.R.</h3>
        <form action="admin_verify_payment.php" method="POST">
            <input type="hidden" name="request_id" id="modal_request_id">
            
            <p><strong>Tracking:</strong> <span id="modal_tracking"></span></p>
            <p><strong>Amount Due:</strong> <span id="modal_amount"></span></p>

            <div id="proof_link_container" style="margin-bottom:15px; display:none;">
                <strong>Proof of Payment:</strong><br>
                <a id="proof_link" href="#" target="_blank" style="color:#2563eb;">View Uploaded Receipt</a>
            </div>

            <div style="margin-bottom:15px;">
                <label>Official Receipt (O.R.) Number:</label>
                <input type="text" name="receipt_no" required style="width:100%; padding:8px; margin-top:5px;">
            </div>

            <div style="margin-bottom:15px;">
                <label>Receipt Date:</label>
                <input type="date" name="receipt_date" value="<?php echo date('Y-m-d'); ?>" required style="width:100%; padding:8px; margin-top:5px;">
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" onclick="closeModal()" style="padding:8px 15px;">Cancel</button>
                <button type="submit" name="action" value="approve" style="padding:8px 15px; background:#16a34a; color:#fff; border:none; border-radius:4px;">Approve & Process</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal(data) {
    document.getElementById('modal_request_id').value = data.request_id;
    document.getElementById('modal_tracking').innerText = data.tracking_no;
    document.getElementById('modal_amount').innerText = '₱' + parseFloat(data.total_amount).toFixed(2);
    
    if (data.proof_of_payment) {
        document.getElementById('proof_link_container').style.display = 'block';
        document.getElementById('proof_link').href = '../assets/uploads/payments/' + data.proof_of_payment;
    } else {
        document.getElementById('proof_link_container').style.display = 'none';
    }

    document.getElementById('paymentModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('paymentModal').style.display = 'none';
}
</script>

</body>
</html>