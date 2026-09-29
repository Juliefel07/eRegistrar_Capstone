<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get only E-Payment requests of this student
$sql = "SELECT 
            r.request_id,
            r.payment_status,
            d.document_name,
            d.fee
        FROM requests r
        INNER JOIN documents d
        ON r.document_id = d.document_id
        WHERE r.user_id = ?
        AND r.payment_method = 'E-Payment'
        ORDER BY r.request_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - eRegistrar</title>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">

    <style>
        body {
            background: #f4f7fb;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        .student-main {
            padding: 20px 15px;
            max-width: 1000px;
            margin: 0 auto;
            padding-bottom: 90px; /* Space for mobile bottom navigation */
        }

        .student-main h1 {
            font-size: 22px;
            color: #1e3a8a;
            margin-bottom: 20px;
        }

        .payment-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }

        .payment-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid #e5e7eb;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        .payment-icon {
            font-size: 24px;
            color: #2563eb;
            margin-bottom: 12px;
        }

        .payment-info h3 {
            font-size: 16px;
            color: #1f2937;
            margin: 0 0 4px 0;
            word-break: break-word;
        }

        .payment-info p {
            font-size: 12px;
            color: #6b7280;
            margin: 0 0 12px 0;
        }

        .payment-info h2 {
            font-size: 20px;
            color: #111827;
            margin: 0 0 12px 0;
        }

        .payment-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .payment-status.paid {
            background: #d1fae5;
            color: #065f46;
        }

        .payment-status.pending {
            background: #fff3cd;
            color: #856404;
        }

        /* Action Buttons */
        .pay-btn, .history-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: background 0.2s;
        }

        .pay-btn {
            background: #2563eb;
            color: #fff;
            box-shadow: 0 4px 10px rgba(37,99,235,0.2);
        }

        .pay-btn:hover {
            background: #1d4ed8;
        }

        .history-btn {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .history-btn:hover {
            background: #e5e7eb;
        }

        /* Empty State */
        .empty-payment {
            grid-column: 1 / -1;
            background: #ffffff;
            padding: 40px 20px;
            text-align: center;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            color: #6b7280;
        }

        .empty-payment i {
            font-size: 36px;
            color: #9ca3af;
            margin-bottom: 12px;
        }

        .empty-payment h3 {
            font-size: 16px;
            color: #374151;
            margin: 0 0 6px 0;
        }

        .empty-payment p {
            font-size: 13px;
            margin: 0;
        }

        @media (max-width: 576px) {
            .student-main {
                padding: 15px 10px;
            }
            .payment-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <?php include("navbar.php"); ?>

    <div class="student-main">
        <h1>Payments</h1>

        <div class="payment-container">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="payment-card">
                        <div>
                            <div class="payment-icon">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                            </div>

                            <div class="payment-info">
                                <h3><?= htmlspecialchars($row['document_name']); ?></h3>
                                <p>Document Processing Fee</p>
                                <h2>₱<?= number_format($row['fee'], 2); ?></h2>

                                <?php if ($row['payment_status'] == "Paid"): ?>
                                    <span class="payment-status paid">Paid</span>
                                <?php else: ?>
                                    <span class="payment-status pending">Pending Payment</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <?php if ($row['payment_status'] == "Paid"): ?>
                                <a href="payments_history.php?id=<?= $row['request_id']; ?>" class="history-btn">
                                    <i class="fa-solid fa-receipt"></i> View Receipt
                                </a>
                            <?php else: ?>
                                <a href="payment_upload.php?id=<?= $row['request_id']; ?>" class="pay-btn">
                                    <i class="fa-solid fa-credit-card"></i> Pay Now
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-payment">
                    <i class="fa-solid fa-wallet"></i>
                    <h3>No E-Payment Requests</h3>
                    <p>Your online payment requests will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>