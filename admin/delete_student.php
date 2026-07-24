<?php

require_once __DIR__ . "/../includes/db.php";


if(isset($_GET['id'])){


$id=intval($_GET['id']);


// delete student's requests first
mysqli_query(
$conn,
"DELETE FROM requests WHERE user_id='$id'"
);


// delete student
mysqli_query(
$conn,
"DELETE FROM users WHERE user_id='$id' AND role='Student'"
);


}


header("Location: students.php");

exit();

?>