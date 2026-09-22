<?php

session_start();

require_once __DIR__ . "/../includes/db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit();
}

if(!isset($_GET['id'])){
    die("Invalid request.");
}

$request_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$request = mysqli_query($conn,"
SELECT
    r.*,
    d.document_name
FROM requests r
JOIN documents d
ON r.document_id=d.document_id
WHERE r.request_id='$request_id'
AND r.user_id='$user_id'
LIMIT 1
");

if(mysqli_num_rows($request)==0){
    die("Request not found.");
}

$request = mysqli_fetch_assoc($request);

$requirements = mysqli_query($conn,"
SELECT
    rrf.*,
    dr.requirement_name
FROM request_requirement_files rrf
JOIN document_requirements dr
ON rrf.requirement_id=dr.requirement_id
WHERE rrf.request_id='$request_id'
ORDER BY dr.requirement_id
");

?>

<!DOCTYPE html>
<html>

<head>

<title>Request Details</title>

<link rel="stylesheet" href="../assets/css/student.css">
<link rel="stylesheet" href="../assets/css/navbar.css">

<style>

.file-card{

background:#fff;

border:1px solid #ddd;

border-radius:10px;

padding:15px;

margin-bottom:15px;

}

.badge{

padding:6px 12px;

border-radius:5px;

font-weight:bold;

}
body{
    background:#f4f7fb;
    font-family:'Segoe UI',sans-serif;
}

.history-card{
    max-width:900px;
    margin:30px auto;
    background:#fff;
    border-radius:15px;
    padding:30px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.history-card h2{
    margin-bottom:20px;
    color:#1e3a8a;
    font-size:28px;
    border-bottom:2px solid #e5e7eb;
    padding-bottom:15px;
}

.history-card p{
    margin:10px 0;
    color:#374151;
    font-size:15px;
}

.history-card hr{
    border:none;
    border-top:1px solid #e5e7eb;
    margin:25px 0;
}

.history-card h3{
    color:#111827;
    margin-bottom:20px;
}

.file-card{
    background:#fafafa;
    border:1px solid #e5e7eb;
    border-left:5px solid #2563eb;
    border-radius:12px;
    padding:20px;
    margin-bottom:20px;
    transition:.3s;
}

.file-card:hover{
    transform:translateY(-3px);
    box-shadow:0 8px 20px rgba(0,0,0,.08);
}

.file-card h4{
    margin:0 0 15px;
    color:#1f2937;
}

.badge{
    display:inline-block;
    padding:6px 14px;
    border-radius:30px;
    font-size:13px;
    font-weight:600;
    text-transform:uppercase;
}

.pending{
    background:#fff7d6;
    color:#b45309;
}

.verified{
    background:#dcfce7;
    color:#166534;
}

.rejected{
    background:#fee2e2;
    color:#b91c1c;
}

input[type=file]{
    width:100%;
    padding:10px;
    margin-top:10px;
    border:1px solid #d1d5db;
    border-radius:8px;
    background:#fff;
}

.btn.approve{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    margin-top:15px;
    padding:10px 20px;
    background:#2563eb;
    color:#fff;
    border:none;
    border-radius:8px;
    text-decoration:none;
    cursor:pointer;
    font-size:14px;
    font-weight:600;
    transition:.3s;
    box-shadow:0 5px 12px rgba(37,99,235,.25);
}

.btn.approve:hover{
    background:#1d4ed8;
    transform:translateY(-2px);
}

.btn.approve:active{
    transform:scale(.98);
}

@media(max-width:768px){

    .history-card{
        margin:15px;
        padding:20px;
    }

    .history-card h2{
        font-size:24px;
    }

    .file-card{
        padding:15px;
    }

}
.pending{

background:#fff3cd;

}

.verified{

background:#d1fae5;

}

.rejected{

background:#fee2e2;

}

</style>

</head>

<body>
<?php if(isset($_SESSION['success'])){ ?>

<div id="successModal" class="floating-modal">
    <div class="floating-content">

        <div class="success-icon">✓</div>

        <h3>Success!</h3>

        <p><?= $_SESSION['success']; ?></p>

        <button onclick="closeModal()">OK</button>

    </div>
</div>

<?php unset($_SESSION['success']); ?>

<?php } ?>
<?php include("navbar.php"); ?>

<div class="student-main">

<div class="history-card">

<h2>Request Details</h2>

<p><strong>Tracking:</strong> <?= $request['tracking_no']; ?></p>

<p><strong>Document:</strong> <?= htmlspecialchars($request['document_name']); ?></p>

<p><strong>Purpose:</strong> <?= htmlspecialchars($request['purpose']); ?></p>

<hr>

<h3>Requirements</h3>

<?php while($row=mysqli_fetch_assoc($requirements)){ ?>

<div class="file-card">

<h4><?= htmlspecialchars($row['requirement_name']); ?></h4>

<p>

Status:

<span class="badge <?= strtolower($row['status']); ?>">

<?= $row['status']; ?>

</span>

</p>

<?php if(!empty($row['remarks'])){ ?>

<p>

<strong>Remarks:</strong><br>

<?= htmlspecialchars($row['remarks']); ?>

</p>

<?php } ?>

<?php if($row['status']=="Rejected"){ ?>

<form
action="reupload_requirement.php"
method="POST"
enctype="multipart/form-data">

    <input
    type="hidden"
    name="file_id"
    value="<?= $row['id']; ?>">

    <input
    type="hidden"
    name="request_id"
    value="<?= $request_id; ?>">

    <input
    type="file"
    name="new_file"
    required>

    <br><br>

    <button
    class="btn approve"
    type="submit">

        Upload New File

    </button>

</form>

<?php } ?>

</div>

<?php } ?>

</div>

</div>
<script>
function closeModal(){
    document.getElementById("successModal").style.display="none";
}
</script>
</body>

</html>