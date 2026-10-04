<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

// UPDATE STUDENT STATUS & STANDING HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student_status'])) {
    $user_id           = mysqli_real_escape_string($conn, $_POST['user_id']);
    $student_status    = mysqli_real_escape_string($conn, $_POST['student_status']);
    $academic_standing = mysqli_real_escape_string($conn, $_POST['academic_standing']);

    $update_sql = "UPDATE users SET 
                   student_status = '$student_status', 
                   academic_standing = '$academic_standing' 
                   WHERE user_id = '$user_id'";

    if (mysqli_query($conn, $update_sql)) {
        $_SESSION['success'] = "Student status updated successfully.";
    } else {
        $_SESSION['error'] = "Update failed: " . mysqli_error($conn);
    }
    header("Location: students.php");
    exit();
}

// BACKEND SEARCH QUERY
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

$where_clause = "WHERE role='Student'";
if (!empty($search_term)) {
    $where_clause .= " AND (
        fullname LIKE '%$search_term%' OR 
        student_no LIKE '%$search_term%' OR 
        course LIKE '%$search_term%' OR 
        email LIKE '%$search_term%' OR 
        contact_no LIKE '%$search_term%' OR
        student_status LIKE '%$search_term%'
    )";
}

$query = "
SELECT *
FROM users
$where_clause
ORDER BY user_id DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - CCTC eRegistrar</title>
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
            max-width: 1280px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 20px;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Search Bar Controls */
        .search-wrapper {
            position: relative;
            width: 100%;
            max-width: 360px;
        }

        .search-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.95rem;
        }

        .search-input {
            width: 100%;
            padding: 10px 14px 10px 40px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            background: #ffffff;
            box-sizing: border-box;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .search-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Responsive Table Container */
        .table-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        table.students-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }

        table.students-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        table.students-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }

        table.students-table tr:hover {
            background-color: #f8fafc;
        }

        /* Action Buttons */
        .action-btns {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-edit {
            background-color: #2563eb;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.2s ease;
        }

        .btn-edit:hover { background-color: #1d4ed8; }

        .btn-delete {
            background-color: #ef4444;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.2s ease;
        }

        .btn-delete:hover { background-color: #dc2626; }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.78rem;
            font-weight: 600;
            background-color: #dbeafe;
            color: #1e40af;
        }

        /* Modal Styles */
        .modal-edit {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(2px);
            align-items: center;
            justify-content: center;
            z-index: 10002;
            padding: 16px;
            box-sizing: border-box;
        }

        .modal-edit-box {
            background: #ffffff;
            padding: 24px;
            border-radius: 12px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-edit-box h3 {
            margin-top: 0;
            margin-bottom: 18px;
            font-size: 1.15rem;
            color: #0f172a;
        }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.88rem; font-weight: 600; color: #334155; margin-bottom: 6px; }
        .form-group select {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 0.9rem;
            outline: none;
            box-sizing: border-box;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 24px;
        }

        .modal-actions button {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
        }

        .btn-save { background: #2563eb; color: #ffffff; }
        .btn-save:hover { background: #1d4ed8; }
        .btn-cancel { background: #f1f5f9; color: #475569; }
        .btn-cancel:hover { background: #e2e8f0; }

        /* Mobile Card View (< 768px) */
        @media (max-width: 768px) {
            .admin-content { padding: 16px 12px; }
            .search-wrapper { max-width: 100%; }

            .students-table, .students-table thead, .students-table tbody, .students-table th, .students-table td, .students-table tr {
                display: block;
            }

            .students-table thead { display: none; }

            .students-table tr {
                margin-bottom: 12px;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #ffffff;
                padding: 12px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            }

            .students-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                border-bottom: 1px solid #f1f5f9;
                font-size: 0.88rem;
            }

            .students-table td:last-child { border-bottom: none; padding-top: 12px; }

            .students-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #64748b;
                font-size: 0.82rem;
                padding-right: 12px;
            }

            .action-btns { justify-content: flex-end; width: 100%; }
        }
    </style>
</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

    

    <div class="page-header">
        <h2 class="page-title">
            <i class="fa-solid fa-user-graduate" style="color: #2563eb;"></i> Student Management
        </h2>

        <!-- STUDENT SEARCH BAR -->
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input 
                type="text" 
                id="studentSearchInput" 
                class="search-input" 
                placeholder="Search students..." 
                value="<?php echo htmlspecialchars($search_term); ?>"
                onkeyup="filterStudentTable()">
        </div>
    </div>

    <div class="table-card">
        <table class="students-table" id="studentsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student No.</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Status</th>
                    <th>Standing</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="student-row">
                            <td data-label="ID"><?php echo $row['user_id']; ?></td>
                            <td data-label="Student No."><strong><?php echo htmlspecialchars($row['student_no']); ?></strong></td>
                            <td data-label="Name"><?php echo htmlspecialchars($row['fullname']); ?></td>
                            <td data-label="Course"><?php echo htmlspecialchars($row['course']); ?></td>
                            <td data-label="Year Level"><?php echo htmlspecialchars($row['year_level']); ?></td>
                            <td data-label="Status">
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($row['student_status'] ?? 'Enrolled'); ?>
                                </span>
                            </td>
                            <td data-label="Standing"><?php echo htmlspecialchars($row['academic_standing'] ?? 'Regular'); ?></td>
                            <td data-label="Contact"><?php echo htmlspecialchars($row['contact_no']); ?></td>
                            <td data-label="Email"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td data-label="Action">
                                <div class="action-btns">
                                    <button 
                                        type="button" 
                                        class="btn-edit" 
                                        onclick="openEditModal('<?php echo $row['user_id']; ?>', '<?php echo htmlspecialchars($row['student_status'] ?? 'Enrolled'); ?>', '<?php echo htmlspecialchars($row['academic_standing'] ?? 'Regular'); ?>')">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>

                                    <a class="btn-delete"
                                       href="delete_student.php?id=<?php echo $row['user_id']; ?>"
                                       onclick="return confirm('Are you sure you want to delete this student?')">
                                       <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr id="noResultsRow">
                        <td colspan="10" style="text-align: center; padding: 30px; color: #64748b;">
                            <i class="fa-regular fa-user" style="font-size: 2rem; margin-bottom: 8px; display: block; color: #cbd5e1;"></i>
                            No student records found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- EDIT STATUS MODAL -->
<div class="modal-edit" id="editModal">
    <div class="modal-edit-box">
        <h3><i class="fa-solid fa-user-gear" style="color: #2563eb;"></i> Update Student Status</h3>
        <form method="POST" action="students.php">
            <input type="hidden" name="user_id" id="modal_user_id">
            <input type="hidden" name="update_student_status" value="1">

            <div class="form-group">
                <label for="modal_student_status">Student Status</label>
                <select name="student_status" id="modal_student_status">
                    <option value="Enrolled">Enrolled</option>
                    <option value="Irregular">Irregular</option>
                    <option value="Dropped">Dropped</option>
                    <option value="LOA">Leave of Absence (LOA)</option>
                    <option value="Graduated">Graduated</option>
                </select>
            </div>

            <div class="form-group">
                <label for="modal_academic_standing">Academic Standing</label>
                <select name="academic_standing" id="modal_academic_standing">
                    <option value="Regular">Regular</option>
                    <option value="Irregular">Irregular</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal Toggle Functions
function openEditModal(userId, status, standing) {
    document.getElementById('modal_user_id').value = userId;
    document.getElementById('modal_student_status').value = status;
    document.getElementById('modal_academic_standing').value = standing;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close Modal when clicking background overlay
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
}

// Client-side Instant Table Filter
function filterStudentTable() {
    const filter = document.getElementById('studentSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#studentsTable tbody tr.student-row');

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