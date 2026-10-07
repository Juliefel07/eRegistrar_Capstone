<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

$message = "";

if (isset($_POST['create'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role']; // 'Admin' or 'Staff'

    if (!empty($fullname) && !empty($email) && !empty($password)) {
        // Securely hash the password using PHP's native function
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into database
        $stmt = mysqli_prepare($conn, "INSERT INTO users (fullname, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "ssss", $fullname, $email, $hashed_password, $role);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "<p style='color: green;'>Successfully created $role account for <strong>" . htmlspecialchars($fullname) . "</strong>! You can now log in.</p>";
        } else {
            $message = "<p style='color: red;'>Error: " . mysqli_error($conn) . "</p>";
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "<p style='color: red;'>Please fill in all fields.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Staff Account</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 24px; border-radius: 10px; border: 1px solid #e2e8f0; width: 320px; box-shadow: 0 4px 6px rgba(0,0,0,0.03); }
        h2 { font-size: 1.2rem; margin-top: 0; color: #0f172a; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: #475569; }
        input, select { width: 100%; padding: 8px 12px; margin-bottom: 14px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        button { background: #2563eb; color: white; border: none; padding: 10px; width: 100%; border-radius: 6px; font-weight: 600; cursor: pointer; }
        button:hover { background: #1d4ed8; }
    </style>
</head>
<body>

<div class="box">
    <h2>Quick Staff Creator</h2>
    <?php echo $message; ?>
    <form method="POST">
        <label>Full Name</label>
        <input type="text" name="fullname" placeholder="e.g. Jane Staff" required>

        <label>Email Address</label>
        <input type="email" name="email" placeholder="staff@consolatrix.edu" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>

        <label>Role</label>
        <select name="role">
            <option value="Staff">Staff</option>
            <option value="Admin">Admin</option>
        </select>

        <button type="submit" name="create">Create Account</button>
    </form>
</div>

</body>
</html>