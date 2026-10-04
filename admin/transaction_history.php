<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = (int)$_SESSION['user_id'];

// FETCH TABLE COLUMNS DYNAMICALLY TO PREVENT ERRORS
$columns = [];
$col_result = mysqli_query($conn, "SHOW COLUMNS FROM requests");
if ($col_result) {
    while ($col = mysqli_fetch_assoc($col_result)) {
        $columns[] = $col['Field'];
    }
}

// FILTER & SEARCH SETUP (Strictly restricted to Claimed documents)
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_clauses = [];

// PERMANENT RESTRICTION: Only show claimed documents in transaction history
if (in_array('status', $columns)) {
    $where_clauses[] = "LOWER(status) = 'claimed'";
}

// Safe Search Filter (still works, but only searches within claimed documents)
if (!empty($search_query) && !empty($columns)) {
    $safe_search = mysqli_real_escape_string($conn, $search_query);
    $search_conditions = [];
    
    $possible_search_cols = ['tracking_number', 'reference_no', 'student_name', 'fullname', 'name', 'document_type', 'doc_type', 'document'];
    foreach ($possible_search_cols as $col) {
        if (in_array($col, $columns)) {
            $search_conditions[] = "$col LIKE '%$safe_search%'";
        }
    }
    
    if (!empty($search_conditions)) {
        $where_clauses[] = "(" . implode(" OR ", $search_conditions) . ")";
    }
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Execute safe query
$query = "SELECT * FROM requests $where_sql LIMIT 100";
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
            max-width: 1200px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
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
            margin-bottom: 20px;
            background: #ffffff;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .filter-container input {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.88rem;
            outline: none;
            color: #1e293b;
            min-width: 260px;
        }

        .filter-container input:focus {
            border-color: #2563eb;
        }

        .btn-filter {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-filter:hover {
            background-color: #1d4ed8;
        }

        /* Table Card Styling */
        .table-card {
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
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
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 12px;
            text-transform: capitalize;
        }

        .badge.claimed { background-color: #e0f2fe; color: #0369a1; }

        .btn-view {
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }

        .btn-view:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }

        /* Floating Modal Styling */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.6);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 16px;
            box-sizing: border-box;
        }

        .modal-container {
            background: #ffffff;
            width: 100%;
            max-width: 550px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            animation: modalFadeIn 0.25s ease-out;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            padding: 18px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-close {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: #64748b;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: #0f172a;
        }

        .modal-body {
            padding: 24px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .detail-item.full-width {
            grid-column: span 2;
        }

        .detail-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .detail-value {
            font-size: 0.95rem;
            color: #1e293b;
            font-weight: 500;
            word-break: break-word;
        }

        .modal-footer {
            padding: 16px 24px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: right;
        }

        .btn-close-modal {
            background-color: #64748b;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
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
            padding: 40px;
            color: #64748b;
        }

        .no-data i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #cbd5e1;
        }
    </style>
</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

    <div class="page-header">
        <h2 class="page-title">
            <i class="fa-solid fa-clock-rotate-left" style="color: #2563eb;"></i> Claimed Document Transaction History
        </h2>
    </div>

    <!-- SEARCH FORM ONLY (Status filter removed since it's strictly claimed documents now) -->
    <form method="GET" action="transaction_history.php" class="filter-container">
        <div>
            <input type="text" name="search" placeholder="Search claimed records..." value="<?= htmlspecialchars($search_query); ?>">
        </div>
        <button type="submit" class="btn-filter">
            <i class="fa-solid fa-magnifying-glass"></i> Search
        </button>
        <?php if (!empty($search_query)): ?>
            <a href="transaction_history.php" style="font-size: 0.85rem; color: #64748b; text-decoration: none; margin-left: 8px;">Reset Search</a>
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
                        <th>Date Requested</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions_result && mysqli_num_rows($transactions_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($transactions_result)): ?>
                            <?php 
                                $date_val = $row['created_at'] ?? $row['date_requested'] ?? $row['request_date'] ?? $row['date'] ?? null;
                                $formatted_date = $date_val ? date("M d, Y h:i A", strtotime($date_val)) : 'N/A';
                                
                                $ref_val = $row['tracking_number'] ?? $row['reference_no'] ?? ($row['id'] ?? 'RECORD');
                                $student_name = $row['student_name'] ?? $row['fullname'] ?? $row['name'] ?? 'N/A';
                                $doc_type = $row['document_type'] ?? $row['doc_type'] ?? $row['document'] ?? 'N/A';
                                $status = strtolower($row['status'] ?? 'claimed');
                                
                                $id_no = $row['student_id'] ?? $row['id_number'] ?? $row['school_id'] ?? 'N/A';
                                $purpose = $row['purpose'] ?? $row['reason'] ?? 'N/A';
                                $copies = $row['copies'] ?? $row['number_of_copies'] ?? '1';
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ref_val); ?></strong></td>
                                <td><?= htmlspecialchars($student_name); ?></td>
                                <td><?= htmlspecialchars($doc_type); ?></td>
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
                                        data-purpose="<?= htmlspecialchars($purpose); ?>"
                                        data-copies="<?= htmlspecialchars($copies); ?>"
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
                                <p>No claimed transaction history records found.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- FLOATING MODAL DETAILS -->
<div id="transactionModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-file-invoice" style="color: #2563eb;"></i> Transaction Details</h3>
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
                    <span class="detail-value" id="modalStatus">-</span>
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
                    <span class="detail-value" id="modalDocType">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Number of Copies</span>
                    <span class="detail-value" id="modalCopies">-</span>
                </div>
                <div class="detail-item full-width">
                    <span class="detail-label">Purpose / Remarks</span>
                    <span class="detail-value" id="modalPurpose">-</span>
                </div>
                <div class="detail-item full-width">
                    <span class="detail-label">Date Requested</span>
                    <span class="detail-value" id="modalDate">-</span>
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