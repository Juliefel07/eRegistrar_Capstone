<?php
require_once __DIR__ . "/includes/db.php";

$newPassword = "admin_cctc";
$freshHash = password_hash($newPassword, PASSWORD_BCRYPT);

$stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = 'admin@consolatrix.edu'");
mysqli_stmt_bind_param($stmt, "s", $freshHash);

if (mysqli_stmt_execute($stmt)) {
    echo "<h3 style='color:green;'>Password updated successfully!</h3>";
    echo "<strong>Email:</strong> admin@consolatrix.edu<br>";
    echo "<strong>Password:</strong> " . $newPassword . "<br>";
    echo "<strong>New Hash:</strong> " . $freshHash;
} else {
    echo "Error updating password: " . mysqli_error($conn);
}
?>