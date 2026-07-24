<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit();
}


$user_id = $_SESSION['user_id'];

$notification_id = $_GET['id'];


mysqli_query($conn,"
    UPDATE notifications
    SET status='Read'
    WHERE notification_id='$notification_id'
    AND user_id='$user_id'
");


$result = mysqli_query($conn,"
    SELECT *
    FROM notifications
    WHERE notification_id='$notification_id'
    AND user_id='$user_id'
");


$notification = mysqli_fetch_assoc($result);


?>
<link rel="stylesheet" href="../assets/css/student.css">
<h2>Notification</h2>

<p>
<?php echo htmlspecialchars($notification['message']); ?>
</p>

<a href="notifications.php">
Back to notifications
</a>