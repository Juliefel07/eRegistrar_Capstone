<?php
session_start();
include "../includes/db.php";

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get only the logged-in user's requests
$stmt = $conn->prepare("
    SELECT request_id, document_type, purpose, status, request_date
    FROM document_requests
    WHERE user_id = ?
    ORDER BY request_date DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Requests</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>My Requests</h2>

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>Request ID</th>
        <th>Document</th>
        <th>Purpose</th>
        <th>Status</th>
        <th>Date Requested</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($row = $result->fetch_assoc()): ?>

        <tr>
            <td><?= htmlspecialchars($row['request_id']) ?></td>
            <td><?= htmlspecialchars($row['document_type']) ?></td>
            <td><?= htmlspecialchars($row['purpose']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td><?= htmlspecialchars($row['request_date']) ?></td>
        </tr>

        <?php endwhile; ?>

    <?php else: ?>

        <tr>
            <td colspan="5">No requests found.</td>
        </tr>

    <?php endif; ?>

</table>

</body>
</html>