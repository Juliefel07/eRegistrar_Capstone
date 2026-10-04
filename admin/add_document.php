<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

$error = "";

if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['document_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $fee = isset($_POST['fee']) && $_POST['fee'] !== '' ? (float)$_POST['fee'] : 0.00;
    $days = isset($_POST['processing_days']) && $_POST['processing_days'] !== '' ? (int)$_POST['processing_days'] : 1;
    $requirements = trim($_POST['requirements']);

    $sql = "INSERT INTO documents (document_name, description, fee, processing_days, status) 
            VALUES ('$name', '$description', '$fee', '$days', 'Available')";

    if (mysqli_query($conn, $sql)) {
        $document_id = mysqli_insert_id($conn);

        // Process requirements line by line
        if (!empty($requirements)) {
            $lines = explode("\n", $requirements);

            foreach ($lines as $req) {
                $req = trim($req);
                if ($req !== "") {
                    $req_escaped = mysqli_real_escape_string($conn, $req);
                    mysqli_query($conn, "INSERT INTO document_requirements (document_id, requirement_name) 
                                         VALUES ('$document_id', '$req_escaped')");
                }
            }
        }

        header("Location: documents.php");
        exit();
    } else {
        $error = "Database Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Document - CCTC eRegistrar</title>
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
            max-width: 800px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        /* Top Bar / Navigation */
        .page-header {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .btn-back:hover {
            color: #0f172a;
        }

        /* Form Card */
        .form-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            padding: 28px;
        }

        .form-card h2 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-card p.subtitle {
            font-size: 0.88rem;
            color: #64748b;
            margin: 0 0 24px 0;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.92rem;
            color: #0f172a;
            background-color: #ffffff;
            box-sizing: border-box;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 90px;
        }

        .form-group .help-text {
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 4px;
        }

        /* Form Grid Layout */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* Submit Button */
        .save-btn {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            margin-top: 10px;
        }

        .save-btn:hover {
            background-color: #1d4ed8;
        }

        .alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 20px;
        }

        /* Mobile Styles */
        @media (max-width: 640px) {
            .admin-content {
                padding: 16px;
            }

            .form-card {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

    <div class="page-header">
        <a href="documents.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Documents
        </a>
    </div>

    <div class="form-card">
        <h2><i class="fa-solid fa-file-circle-plus" style="color: #2563eb;"></i> Add New Document</h2>
        <p class="subtitle">Fill out the details below to add a new document to the registrar requests list.</p>

        <?php if (!empty($error)): ?>
            <div class="alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label for="document_name">Document Name *</label>
                <input 
                    type="text" 
                    id="document_name"
                    name="document_name"
                    placeholder="e.g., Official Transcript of Records (TOR)"
                    required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea 
                    id="description"
                    name="description"
                    placeholder="Provide a brief description of this document..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fee">Fee (₱)</label>
                    <input 
                        type="number"
                        id="fee"
                        name="fee"
                        step="0.01"
                        min="0"
                        placeholder="0.00">
                </div>

                <div class="form-group">
                    <label for="processing_days">Processing Days</label>
                    <input 
                        type="number"
                        id="processing_days"
                        name="processing_days"
                        min="1"
                        placeholder="e.g., 3">
                </div>
            </div>

            <div class="form-group">
                <label for="requirements">Requirements</label>
                <textarea
                    id="requirements"
                    name="requirements"
                    rows="5"
                    placeholder="Enter each requirement on a new line&#10;&#10;Example:&#10;Valid Student ID&#10;Recent 2x2 Picture&#10;Clearance Form"></textarea>
                <div class="help-text">Write one requirement per line. They will be saved as separate checklist items for students.</div>
            </div>

            <button type="submit" class="save-btn" name="submit">
                <i class="fa-solid fa-floppy-disk"></i> Save Document
            </button>

        </form>

    </div>

</div>

</body>
</html>