<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

// 1. STATISTICAL TOTALS
$total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM requests");
$total_requests = mysqli_fetch_assoc($total)['total'];

$pending = mysqli_query($conn, "SELECT COUNT(*) AS total FROM requests WHERE status='Pending'");
$pending_requests = mysqli_fetch_assoc($pending)['total'];

$approved = mysqli_query($conn, "SELECT COUNT(*) AS total FROM requests WHERE status='Approved'");
$approved_requests = mysqli_fetch_assoc($approved)['total'];

$rejected = mysqli_query($conn, "SELECT COUNT(*) AS total FROM requests WHERE status='Rejected'");
$rejected_requests = mysqli_fetch_assoc($rejected)['total'];

$claimed = mysqli_query($conn, "SELECT COUNT(*) AS total FROM requests WHERE status='Claimed'");
$claimed_requests = mysqli_fetch_assoc($claimed)['total'];

// 2. BACKEND SEARCH FILTER
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

$where_clause = "";
if (!empty($search_term)) {
    $where_clause = " WHERE (
        r.tracking_no LIKE '%$search_term%' OR 
        u.fullname LIKE '%$search_term%' OR 
        d.document_name LIKE '%$search_term%' OR 
        r.status LIKE '%$search_term%'
    )";
}

// 3. RECENT REQUESTS QUERY
$query = "
SELECT
    r.tracking_no,
    u.fullname,
    d.document_name,
    r.status,
    r.request_date
FROM requests r
JOIN users u ON r.user_id = u.user_id
JOIN documents d ON r.document_id = d.document_id
$where_clause
ORDER BY r.request_date DESC
LIMIT 15
";

$recent = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Reports - CCTC eRegistrar</title>
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
            padding: 20px;
            max-width: 1280px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
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

        /* Compact Pure White Metric Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }

        .card {
            background: #ffffff;
            border-radius: 8px;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .card h3 {
            margin: 0 0 2px 0;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
        }

        .card p {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }

        /* Search Input */
        .search-wrapper {
            position: relative;
            width: 100%;
            max-width: 320px;
        }

        .search-wrapper i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.88rem;
        }

        .search-input {
            width: 100%;
            padding: 8px 12px 8px 36px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.85rem;
            outline: none;
            background: #ffffff;
            box-sizing: border-box;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .search-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Table Card Container */
        .table-card {
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .section-header {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            background: #ffffff;
        }

        .section-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        table.reports-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }

        table.reports-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        table.reports-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }

        table.reports-table tr:hover {
            background-color: #f8fafc;
        }

        /* Clean Neutral Status Tags */
        .status-badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.78rem;
            font-weight: 600;
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }

        /* Mobile Card View (< 768px) */
        @media (max-width: 768px) {
            .admin-content { padding: 12px; }
            .search-wrapper { max-width: 100%; }

            .reports-table, 
            .reports-table thead, 
            .reports-table tbody, 
            .reports-table th, 
            .reports-table td, 
            .reports-table tr {
                display: block;
            }

            .reports-table thead { display: none; }

            .reports-table tr {
                margin: 10px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                background: #ffffff;
                padding: 10px;
            }

            .reports-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 6px 0;
                border-bottom: 1px solid #f1f5f9;
                font-size: 0.85rem;
            }

            .reports-table td:last-child { border-bottom: none; }

            .reports-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #64748b;
                font-size: 0.8rem;
                padding-right: 12px;
            }
        }
    </style>
</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

    

    <div class="page-header">
        <h2 class="page-title">
            <i class="fa-solid fa-chart-line" style="color: #0c65e1;"></i> Request Reports
        </h2>
    </div>

    <!-- ULTRA-COMPACT WHITE METRICS DASHBOARD -->
    <div class="dashboard-cards">
        <div class="card">
            <h3>Total</h3>
            <p><?php echo number_format($total_requests); ?></p>
        </div>

        <div class="card">
            <h3>Pending</h3>
            <p><?php echo number_format($pending_requests); ?></p>
        </div>

        <div class="card">
            <h3>Approved</h3>
            <p><?php echo number_format($approved_requests); ?></p>
        </div>

        <div class="card">
            <h3>Rejected</h3>
            <p><?php echo number_format($rejected_requests); ?></p>
        </div>

        <div class="card">
            <h3>Claimed</h3>
            <p><?php echo number_format($claimed_requests); ?></p>
        </div>
    </div>

    <!-- RECENT REQUESTS TABLE CARD WITH SEARCH -->
    <div class="table-card">
        <div class="section-header">
            <h3>Recent Requests</h3>

            <!-- SEARCH BAR -->
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input 
                    type="text" 
                    id="reportSearchInput" 
                    class="search-input" 
                    placeholder="Search tracking no, name, document..." 
                    value="<?php echo htmlspecialchars($search_term); ?>"
                    onkeyup="filterReportsTable()">
            </div>
        </div>

        <table class="reports-table" id="reportsTable">
            <thead>
                <tr>
                    <th>Tracking No</th>
                    <th>Student</th>
                    <th>Document</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($recent) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                        <tr class="report-row">
                            <td data-label="Tracking No">
                                <strong><?php echo htmlspecialchars($row['tracking_no']); ?></strong>
                            </td>
                            <td data-label="Student">
                                <?php echo htmlspecialchars($row['fullname']); ?>
                            </td>
                            <td data-label="Document">
                                <?php echo htmlspecialchars($row['document_name']); ?>
                            </td>
                            <td data-label="Status">
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td data-label="Date">
                                <?php echo date("M d, Y", strtotime($row['request_date'])); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr id="noResultsRow">
                        <td colspan="5" style="text-align: center; padding: 24px; color: #64748b;">
                            <i class="fa-regular fa-folder-open" style="font-size: 1.8rem; margin-bottom: 6px; display: block; color: #cbd5e1;"></i>
                            No request records found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
// Live Search Filter Function
function filterReportsTable() {
    const filter = document.getElementById('reportSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#reportsTable tbody tr.report-row');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

</body>

</html>