<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');
require_once __DIR__ . "/../includes/db.php";
mysqli_query($conn, "SET time_zone = '+08:00'");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// FETCH TABLE COLUMNS DYNAMICALLY TO CHECK AVAILABLE FIELDS
$columns = [];
$col_result = mysqli_query($conn, "SHOW COLUMNS FROM requests");
if ($col_result) {
    while ($col = mysqli_fetch_assoc($col_result)) {
        $columns[] = $col['Field'];
    }
}

// Determine the correct date column to sort by
$sort_column = 'id'; // fallback
foreach (['created_at', 'date_requested', 'request_date', 'date'] as $candidate) {
    if (in_array($candidate, $columns)) {
        $sort_column = $candidate;
        break;
    }
}

// FILTER & SEARCH SETUP (Strictly restricted to Claimed documents)
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clauses = [];

if (in_array('status', $columns)) {
    $where_clauses[] = "LOWER(r.status) = 'claimed'";
}

if (!empty($search_query) && !empty($columns)) {
    $safe_search = mysqli_real_escape_string($conn, $search_query);
    $search_conditions = [];
    
    $possible_search_cols = ['tracking_number', 'reference_no', 'student_name', 'fullname', 'name', 'document_type', 'doc_type', 'document'];
    foreach ($possible_search_cols as $col) {
        if (in_array($col, $columns)) {
            $search_conditions[] = "r.$col LIKE '%$safe_search%'";
        }
    }
    
    if (!empty($search_conditions)) {
        $where_clauses[] = "(" . implode(" OR ", $search_conditions) . ")";
    }
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Check if processed_by column exists before joining users table
$has_processed_by = in_array('processed_by', $columns);
if ($has_processed_by) {
    $query = "SELECT r.*, u.fullname AS processor_name 
              FROM requests r 
              LEFT JOIN users u ON r.processed_by = u.user_id 
              $where_sql 
              ORDER BY r.$sort_column DESC LIMIT 100";
} else {
    $query = "SELECT r.*, 'System Admin' AS processor_name 
              FROM requests r 
              $where_sql 
              ORDER BY r.$sort_column DESC LIMIT 100";
}

$transactions_result = @mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - CCTC eRegistrar</title>
    <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #e0e7ff;
            --success: #10b981;
            --success-bg: #d1fae5;
            --dark: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --bg-main: #f8fafc;
            --card-bg: #ffffff;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--bg-main);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            margin: 0;
            padding: 0;
        }

        .admin-content {
            padding: 32px;
            max-width: 1200px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-container {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 24px;
            background: var(--card-bg);
            padding: 16px 20px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .filter-container input {
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            outline: none;
            color: var(--text-main);
            min-width: 280px;
            font-family: 'Inter', sans-serif;
        }

        .filter-container input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }

        .btn-filter {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
        }

        .btn-filter:hover {
            background-color: var(--primary-hover);
        }

        /* Table Card Styling */
        .table-card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }

        th {
            background-color: #f1f5f9;
            color: var(--text-muted);
            font-weight: 600;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge.claimed { background-color: var(--success-bg); color: #047857; }

        .btn-view {
            background-color: var(--primary-light);
            color: var(--primary);
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-view:hover {
            background-color: var(--primary);
            color: #ffffff;
        }

        /* STYLIZED FLOATING MODAL DETAILS */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(6px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
            animation: fadeIn 0.25s ease-in-out;
        }

        .modal-container {
            background: var(--card-bg);
            width: 100%;
            max-width: 600px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            padding: 20px 24px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: #f1f5f9;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: var(--danger-bg, #fee2e2);
            color: var(--danger, #ef4444);
        }

        .modal-body {
            padding: 24px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            background: #f8fafc;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }

        .detail-item.full-width {
            grid-column: span 2;
        }

        .detail-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .detail-value {
            font-size: 0.95rem;
            color: var(--dark);
            font-weight: 600;
            word-break: break-word;
        }

        .modal-footer {
            padding: 16px 24px;
            background: #f8fafc;
            border-top: 1px solid var(--border);
            text-align: right;
        }

        .btn-close-modal {
            background-color: #64748b;
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-close-modal:hover {
            background-color: #475569;
        }

        .no-data {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-muted);
        }

        .no-data i {
            font-size: 2.5rem;
            margin-bottom: 12px;
            color: #cbd5e1;
        }
    </style>
</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

    <div class="page-header">
        <h2 class="page-title">
            <i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);"></i> Transaction History
        </h2>
    </div>

    <!-- SEARCH FORM -->
    <form method="GET" action="transaction_history.php" class="filter-container">
        <div style="flex: 1;">
            <input type="text" name="search" placeholder="Search by reference, name, or document..." value="<?= htmlspecialchars($search_query); ?>">
        </div>
        <button type="submit" class="btn-filter">
            <i class="fa-solid fa-magnifying-glass"></i> Search
        </button>
        <?php if (!empty($search_query)): ?>
            <a href="transaction_history.php" style="font-size: 0.88rem; color: var(--text-muted); text-decoration: none; margin-left: 8px; font-weight: 500;">Reset</a>
        <?php endif; ?>
    </form>

    <!-- TABLE WRAPPER -->
    <div class="table-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Reference / ID</th>
                        <th>Student Name</th>
                        <th>Document Type</th>
                        <th>Status</th>
                        <th>Date Claimed / Requested</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions_result && mysqli_num_rows($transactions_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($transactions_result)): ?>
                            <?php 
                                $date_val = $row['created_at'] ?? $row['date_requested'] ?? $row['request_date'] ?? $row['date'] ?? null;
                                $formatted_date = $date_val ? date("M d, Y • h:i A", strtotime($date_val)) : 'N/A';
                                
                                $ref_val = $row['tracking_number'] ?? $row['reference_no'] ?? ($row['id'] ?? 'RECORD');
                                $student_name = $row['student_name'] ?? $row['fullname'] ?? $row['name'] ?? 'N/A';
                                
                                $doc_type = $row['document_type'] ?? $row['doc_type'] ?? $row['document'] ?? $row['type'] ?? 'N/A';
                                $processed_by = $row['processor_name'] ?? $row['processed_by_name'] ?? $row['admin_name'] ?? 'System / Registrar Admin';
                                
                                $id_no = $row['student_id'] ?? $row['id_number'] ?? $row['school_id'] ?? 'N/A';
                                $purpose = $row['purpose'] ?? $row['reason'] ?? 'N/A';
                                $copies = $row['copies'] ?? $row['number_of_copies'] ?? '1';
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ref_val); ?></strong></td>
                                <td><?= htmlspecialchars($student_name); ?></td>
                                <td><span style="font-weight: 500; color: var(--primary);"><?= htmlspecialchars($doc_type); ?></span></td>
                                <td>
                                    <span class="badge claimed">Claimed</span>
                                </td>
                                <td><?= $formatted_date; ?></td>
                                <td>
                                    <button type="button" class="btn-view" 
                                        data-ref="<?= htmlspecialchars($ref_val); ?>"
                                        data-name="<?= htmlspecialchars($student_name); ?>"
                                        data-idno="<?= htmlspecialchars($id_no); ?>"
                                        data-doctype="<?= htmlspecialchars($doc_type); ?>"
                                        data-copies="<?= htmlspecialchars($copies); ?>"
                                        data-purpose="<?= htmlspecialchars($purpose); ?>"
                                        data-processed="<?= htmlspecialchars($processed_by); ?>"
                                        data-status="Claimed"
                                        data-date="<?= htmlspecialchars($formatted_date); ?>"
                                        onclick="openTransactionModal(this)">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">
                                <i class="fa-regular fa-folder-open"></i>
                                <p style="margin: 0;">No claimed transaction history records found.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- STYLIZED FLOATING MODAL DETAILS -->
<div id="transactionModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-file-invoice" style="color: var(--primary);"></i> Transaction Details</h3>
            <button type="button" class="modal-close" onclick="closeTransactionModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Reference / Tracking No.</span>
                    <span class="detail-value" id="modalRef">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span class="detail-value"><span class="badge claimed" id="modalStatus">-</span></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Student Name</span>
                    <span class="detail-value" id="modalName">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Student ID Number</span>
                    <span class="detail-value" id="modalIdNo">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Document Type</span>
                    <span class="detail-value" id="modalDocType" style="color: var(--primary);">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Number of Copies</span>
                    <span class="detail-value" id="modalCopies">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Processed By</span>
                    <span class="detail-value" id="modalProcessed">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Date Requested / Claimed</span>
                    <span class="detail-value" id="modalDate">-</span>
                </div>
                <div class="detail-item full-width">
                    <span class="detail-label">Purpose / Remarks</span>
                    <span class="detail-value" id="modalPurpose">-</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-close-modal" onclick="closeTransactionModal()">Close</button>
        </div>
    </div>
</div>

<script>
    function openTransactionModal(btn) {
        document.getElementById('modalRef').textContent = btn.getAttribute('data-ref');
        document.getElementById('modalStatus').textContent = btn.getAttribute('data-status');
        document.getElementById('modalName').textContent = btn.getAttribute('data-name');
        document.getElementById('modalIdNo').textContent = btn.getAttribute('data-idno');
        document.getElementById('modalDocType').textContent = btn.getAttribute('data-doctype');
        document.getElementById('modalCopies').textContent = btn.getAttribute('data-copies');
        document.getElementById('modalPurpose').textContent = btn.getAttribute('data-purpose');
        document.getElementById('modalProcessed').textContent = btn.getAttribute('data-processed');
        document.getElementById('modalDate').textContent = btn.getAttribute('data-date');

        document.getElementById('transactionModal').style.display = 'flex';
    }

    function closeTransactionModal() {
        document.getElementById('transactionModal').style.display = 'none';
    }

    window.onclick = function(event) {
        let modal = document.getElementById('transactionModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
</script>

</body>
</html>