<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$request_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$request = mysqli_query($conn, "
    SELECT 
        r.*, 
        d.document_name 
    FROM requests r 
    JOIN documents d 
    ON r.document_id = d.document_id 
    WHERE r.request_id = '$request_id' 
    AND r.user_id = '$user_id' 
    LIMIT 1
");

if (mysqli_num_rows($request) == 0) {
    die("Request not found.");
}

$request = mysqli_fetch_assoc($request);

$requirements = mysqli_query($conn, "
    SELECT 
        rrf.*, 
        dr.requirement_name 
    FROM request_requirement_files rrf 
    JOIN document_requirements dr 
    ON rrf.requirement_id = dr.requirement_id 
    WHERE rrf.request_id = '$request_id' 
    ORDER BY dr.requirement_id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details - eRegistrar</title>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">

    <style>
        body {
            background: #f4f7fb;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        .student-main {
            padding: 20px 15px;
            padding-bottom: 90px; /* Extra padding for mobile bottom nav */
        }

        .history-card {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,.08);
        }

        .history-card h2 {
            margin-bottom: 15px;
            color: #1e3a8a;
            font-size: 24px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 12px;
        }

        .history-card p {
            margin: 8px 0;
            color: #374151;
            font-size: 14px;
            word-break: break-word;
        }

        .history-card hr {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 20px 0;
        }

        .history-card h3 {
            color: #111827;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .file-card {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-left: 5px solid #2563eb;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            transition: .3s;
        }

        .file-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,.06);
        }

        .file-card h4 {
            margin: 0 0 10px;
            color: #1f2937;
            font-size: 15px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge.verified, .badge.approved {
            background: #d1fae5;
            color: #10b981;
        }

        .badge.rejected {
            background: #fee2e2;
            color: #b91c1c;
        }

        input[type=file] {
            width: 100%;
            padding: 8px;
            margin-top: 8px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            box-sizing: border-box;
            font-size: 13px;
        }

        .btn.approve {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            padding: 10px 16px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: .3s;
            box-shadow: 0 4px 10px rgba(37,99,235,.2);
        }

        .btn.approve:hover {
            background: #1d4ed8;
        }

        /* Floating Success Modal Styles */
        .floating-modal {
            display: flex;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 3000;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .floating-content {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            max-width: 380px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .success-icon {
            width: 50px;
            height: 50px;
            background: #d1fae5;
            color: #10b981;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 15px auto;
        }

        .floating-content h3 {
            margin: 0 0 8px 0;
            color: #111827;
        }

        .floating-content p {
            font-size: 13px;
            color: #4b5563;
            margin-bottom: 20px;
        }

        .floating-content button {
            background: #2563eb;
            color: #white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            color: #fff;
        }

        @media(max-width: 768px) {
            .history-card {
                margin: 10px;
                padding: 15px;
            }
            .history-card h2 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>

    <?php if(isset($_SESSION['success'])): ?>
    <div id="successModal" class="floating-modal">
        <div class="floating-content">
            <div class="success-icon">✓</div>
            <h3>Success!</h3>
            <p><?= htmlspecialchars($_SESSION['success']); ?></p>
            <button onclick="closeModal()">OK</button>
        </div>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php include("navbar.php"); ?>

    <div class="student-main">
        <div class="history-card">
            <h2>Request Details</h2>
            <p><strong>Tracking No:</strong> <?= htmlspecialchars($request['tracking_no']); ?></p>
            <p><strong>Document:</strong> <?= htmlspecialchars($request['document_name']); ?></p>
            <p><strong>Purpose:</strong> <?= htmlspecialchars($request['purpose']); ?></p>

            <hr>

            <h3>Requirements</h3>

            <?php while($row = mysqli_fetch_assoc($requirements)): ?>
                <div class="file-card">
                    <h4><?= htmlspecialchars($row['requirement_name']); ?></h4>
                    <p>
                        Status: 
                        <span class="badge <?= strtolower($row['status']); ?>">
                            <?= htmlspecialchars($row['status']); ?>
                        </span>
                    </p>

                    <?php if(!empty($row['remarks'])): ?>
                        <p><strong>Remarks:</strong><br><?= htmlspecialchars($row['remarks']); ?></p>
                    <?php endif; ?>

                    <?php if($row['status'] == "Rejected"): ?>
                        <form action="reupload_requirement.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="file_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="request_id" value="<?= $request_id; ?>">
                            <input type="file" name="new_file" required>
                            <button class="btn approve" type="submit">
                                <i class="fa-solid fa-cloud-arrow-up" style="margin-right: 5px;"></i> Upload New File
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
        function closeModal() {
            let modal = document.getElementById("successModal");
            if (modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>