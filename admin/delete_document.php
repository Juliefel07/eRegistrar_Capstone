<?php

require_once __DIR__ . "/../includes/db.php";


if(isset($_GET['id'])){


$id=intval($_GET['id']);


mysqli_query(
$conn,
"DELETE FROM documents WHERE document_id='$id'"
);


}


header("Location: documents.php");

exit();

?>