<?php

require_once __DIR__ . "/../includes/db.php";

if(isset($_POST['document_id'])){

    $document_id = intval($_POST['document_id']);

    $result = mysqli_query($conn,"
        SELECT requirement_name
        FROM document_requirements
        WHERE document_id='$document_id'
    ");

    if(mysqli_num_rows($result)>0){

        echo "<div class='requirements-box'>";

        echo "<h4><i class='fa-solid fa-circle-info'></i> Requirements</h4>";

        echo "<ul>";

        while($row=mysqli_fetch_assoc($result)){

            echo "<li>
                    <i class='fa-solid fa-check'></i>
                    ".htmlspecialchars($row['requirement_name'])."
                  </li>";
        }

        echo "</ul>";

        echo "</div>";

    }else{

        echo "<div class='requirements-box'>";

        echo "<h4><i class='fa-solid fa-circle-info'></i> Requirements</h4>";

        echo "<p>No requirements available for this document.</p>";

        echo "</div>";

    }

}
?>