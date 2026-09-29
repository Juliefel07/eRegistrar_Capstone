<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if(!isset($_SESSION['user_id'])){
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

$query = "
SELECT *
FROM users
WHERE role='Student'
ORDER BY user_id DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Students</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .btn-edit {
            background-color: #3182ce;
            color: #fff;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 13px;
        }
        .modal-edit {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 999;
        }
        .modal-edit-box {
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            width: 380px;
        }
        .modal-edit-box h3 { margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group select { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
        .modal-actions { text-align: right; margin-top: 20px; }
        .modal-actions button { padding: 8px 16px; border-radius: 4px; border: none; cursor: pointer; }
        .btn-save { background: #38a169; color: #fff; }
        .btn-cancel { background: #e2e8f0; color: #333; margin-right: 8px; }
    </style>
</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

    <?php include("header.php"); ?>

    <div class="container">

        <h2>Student Management</h2>

        <table>
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
                <th>Action</th>
            </tr>

            <?php while($row=mysqli_fetch_assoc($result)){ ?>
            <tr>
                <td><?php echo $row['user_id']; ?></td>
                <td><?php echo htmlspecialchars($row['student_no']); ?></td>
                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                <td><?php echo htmlspecialchars($row['course']); ?></td>
                <td><?php echo htmlspecialchars($row['year_level']); ?></td>
                <td><strong><?php echo htmlspecialchars($row['student_status'] ?? 'Enrolled'); ?></strong></td>
                <td><?php echo htmlspecialchars($row['academic_standing'] ?? 'Regular'); ?></td>
                <td><?php echo htmlspecialchars($row['contact_no']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td>
                    <button 
                        type="button" 
                        class="btn-edit" 
                        onclick="openEditModal('<?php echo $row['user_id']; ?>', '<?php echo htmlspecialchars($row['student_status'] ?? 'Enrolled'); ?>', '<?php echo htmlspecialchars($row['academic_standing'] ?? 'Regular'); ?>')">
                        Edit
                    </button>

                    <a class="btn reject"
                       href="delete_student.php?id=<?php echo $row['user_id']; ?>"
                       onclick="return confirm('Delete this student?')">
                        Delete
                    </a>
                </td>
            </tr>
            <?php } ?>
        </table>

    </div>

</div>

<!-- EDIT STATUS MODAL -->
<div class="modal-edit" id="editModal">
    <div class="modal-edit-box">
        <h3>Update Student Status</h3>
        <form method="POST" action="students.php">
            <input type="hidden" name="user_id" id="modal_user_id">
            <input type="hidden" name="update_student_status" value="1">

            <div class="form-group">
                <label>Student Status</label>
                <select name="student_status" id="modal_student_status">
                    <option value="Enrolled">Enrolled</option>
                    <option value="Irregular">Irregular</option>
                    <option value="Dropped">Dropped</option>
                    <option value="LOA">Leave of Absence (LOA)</option>
                    <option value="Graduated">Graduated</option>
                </select>
            </div>

            <div class="form-group">
                <label>Academic Standing</label>
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
function openEditModal(userId, status, standing) {
    document.getElementById('modal_user_id').value = userId;
    document.getElementById('modal_student_status').value = status;
    document.getElementById('modal_academic_standing').value = standing;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

</body>
</html>