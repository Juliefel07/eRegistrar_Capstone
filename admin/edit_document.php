<?php
session_start();
include("../includes/db.php");

// Check if document ID exists
if (!isset($_GET['id'])) {
    header("Location: documents.php");
    exit();
}

$id = intval($_GET['id']);

// Get document information
$result = mysqli_query($conn, "SELECT * FROM documents WHERE document_id = '$id'");

if (mysqli_num_rows($result) == 0) {
    die("Document not found.");
}

$document = mysqli_fetch_assoc($result);

// Update document
if (isset($_POST['update'])) {

    $name = mysqli_real_escape_string($conn, $_POST['document_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $fee = mysqli_real_escape_string($conn, $_POST['fee']);
    $processing_days = mysqli_real_escape_string($conn, $_POST['processing_days']);
    $requirements = mysqli_real_escape_string($conn, $_POST['requirements']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $update = mysqli_query($conn, "
        UPDATE documents SET
            document_name='$name',
            description='$description',
            fee='$fee',
            processing_days='$processing_days',
            requirements='$requirements',
            status='$status'
        WHERE document_id='$id'
    ");

    if ($update) {
        header("Location: documents.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Document</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">

    <h2>Edit Document</h2>

    <form method="POST">

        <label>Document Name</label>
        <input type="text" name="document_name"
        value="<?php echo htmlspecialchars($document['document_name']); ?>" required>

        <label>Description</label>
        <textarea name="description"><?php echo htmlspecialchars($document['description']); ?></textarea>

        <label>Fee</label>
        <input type="number" step="0.01" name="fee"
        value="<?php echo $document['fee']; ?>" required>

        <label>Processing Days</label>
        <input type="number" name="processing_days"
        value="<?php echo $document['processing_days']; ?>" required>

        <label>Requirements</label>
        <textarea name="requirements"><?php echo htmlspecialchars($document['requirements']); ?></textarea>

        <label>Status</label>
        <select name="status">

            <option value="Available"
                <?php if($document['status']=="Available") echo "selected"; ?>>
                Available
            </option>

            <option value="Unavailable"
                <?php if($document['status']=="Unavailable") echo "selected"; ?>>
                Unavailable
            </option>

        </select>

        <br><br>

        <button type="submit" name="update">
            Update Document
        </button>

    </form>

</div>

</body>
</html>