<?php

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification.php";

if(!isset($_SESSION['user_id'])){
    die("Access denied.");
}

if(!isset($_GET['id'])){
    die("Invalid request.");
}

$request_id = intval($_GET['id']);

$sql = "
SELECT

r.*,
d.document_name

FROM requests r

JOIN documents d
ON r.document_id = d.document_id

WHERE r.request_id='$request_id'

LIMIT 1
";

$result = mysqli_query($conn,$sql);

if(mysqli_num_rows($result)==0){
    die("Request not found.");
}

$request = mysqli_fetch_assoc($result);
if(isset($_POST['reject_requirement'])){

    $file_id = intval($_POST['file_id']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

mysqli_query($conn,"
    UPDATE request_requirement_files
    SET
        status='Rejected',
        remarks='$remarks'
    WHERE id='$file_id'
");

$getInfo = mysqli_query($conn,"
SELECT
    r.user_id,
    r.tracking_no,
    dr.requirement_name
FROM request_requirement_files rrf
JOIN requests r
    ON rrf.request_id = r.request_id
JOIN document_requirements dr
    ON rrf.requirement_id = dr.requirement_id
WHERE rrf.id='$file_id'
LIMIT 1
");

if($info = mysqli_fetch_assoc($getInfo)){

    createNotification(
        $conn,
        $info['user_id'],
        "Your requirement '".$info['requirement_name']."' for request ".$info['tracking_no']." was rejected. Please review the remarks and upload a corrected file."
    );

}

    header("Location: view_request.php?id=".$request_id);
    exit();
}
$files = mysqli_query($conn,"
SELECT

rrf.*,
dr.requirement_name

FROM request_requirement_files rrf

JOIN document_requirements dr

ON rrf.requirement_id=dr.requirement_id

WHERE rrf.request_id='$request_id'

ORDER BY dr.requirement_id ASC
");

?>

<!DOCTYPE html>
<html>

<head>

<title>View Request</title>

<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/admin.css">

<style>
.container{
    max-width:1100px;
    margin:auto;
}

.request-header{
    background:#fff;
    padding:25px;
    border-radius:15px;
    box-shadow:0 8px 20px rgba(0,0,0,.08);
    margin-bottom:25px;
}

.request-header h2{
    color:#1e3a8a;
    margin-bottom:20px;
}

.request-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:15px;
}

.request-item{
    background:#f8fafc;
    padding:15px;
    border-radius:10px;
}

.request-item strong{
    display:block;
    color:#64748b;
    margin-bottom:5px;
}

.progress-card{
    background:#fff;
    padding:20px;
    border-radius:15px;
    box-shadow:0 8px 20px rgba(0,0,0,.08);
    margin-bottom:25px;
}

.progress-title{
    font-size:18px;
    font-weight:600;
    color:#1e3a8a;
    margin-bottom:15px;
}

.progress-bar{
    width:100%;
    height:18px;
    background:#e5e7eb;
    border-radius:30px;
    overflow:hidden;
}

.progress-fill{
    height:100%;
    border-radius:30px;
    transition:.4s;
}

.file-card{
    background:#fff;
    border-radius:15px;
    padding:20px;
    margin-bottom:20px;
    box-shadow:0 8px 20px rgba(0,0,0,.08);
    border-left:5px solid #2563eb;
    transition:.25s;
}

.file-card:hover{
    transform:translateY(-3px);
}

.file-card h4{
    color:#1e3a8a;
    margin-bottom:12px;
}

.file-actions{
    display:flex;
    gap:10px;
    margin-top:15px;
    flex-wrap:wrap;
}

.badge{
    display:inline-block;
    padding:6px 14px;
    border-radius:30px;
    font-size:12px;
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

.btn.approve,
.btn.reject{
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
    color:#fff;
    font-weight:600;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    transition:.25s;
    border:none;
    cursor:pointer;
}

.btn.approve{
    background:#2563eb;
}

.btn.approve:hover{
    background:#1d4ed8;
}

.btn.reject{
    background:#dc2626;
}

.btn.reject:hover{
    background:#b91c1c;
}

/* Modal */

.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    justify-content:center;
    align-items:center;
    z-index:99999;
}

.modal-content{
    width:450px;
    max-width:90%;
    background:#fff;
    padding:30px;
    border-radius:18px;
    box-shadow:0 20px 50px rgba(0,0,0,.25);
    animation:popup .25s ease;
    position:relative;
}

.close{
    position:absolute;
    right:18px;
    top:15px;
    font-size:28px;
    cursor:pointer;
    color:#666;
}

.close:hover{
    color:#dc2626;
}

.modal h2{
    color:#1e3a8a;
    margin-bottom:20px;
}

.modal label{
    display:block;
    margin-bottom:10px;
    font-weight:600;
}

.modal textarea{
    width:100%;
    border:1px solid #d1d5db;
    border-radius:10px;
    padding:12px;
    resize:vertical;
    min-height:120px;
}

@keyframes popup{

    from{
        transform:scale(.85);
        opacity:0;
    }

    to{
        transform:scale(1);
        opacity:1;
    }

}

</style>

</head>

<body>

<?php include("sidebar.php"); ?>

<div class="admin-content">

<?php include("header.php"); ?>

<div class="container">

<?php if(isset($_SESSION['error'])){ ?>

<div style="
background:#fee2e2;
color:#991b1b;
padding:12px;
border-radius:6px;
margin-bottom:15px;
">

<?= $_SESSION['error']; ?>

</div>

<?php unset($_SESSION['error']); } ?>

<h2>Request Details</h2>

<p><strong>Tracking:</strong> <?= $request['tracking_no']; ?></p>

<p><strong>Student:</strong> <?= htmlspecialchars($request['fullname']); ?></p>

<p><strong>Document:</strong> <?= htmlspecialchars($request['document_name']); ?></p>

<p><strong>Purpose:</strong> <?= htmlspecialchars($request['purpose']); ?></p>

<hr>
<?php



$totalResult = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM request_requirement_files
WHERE request_id='$request_id'
");

$total = mysqli_fetch_assoc($totalResult)['total'];

$verifiedResult = mysqli_query($conn,"
SELECT COUNT(*) AS verified
FROM request_requirement_files
WHERE request_id='$request_id'
AND status='Verified'
");

$verified = mysqli_fetch_assoc($verifiedResult)['verified'];

$percent = ($total > 0) ? ($verified / $total) * 100 : 0;

$color = "#ef4444"; // Red

if($percent >= 100){
    $color = "#22c55e"; // Green
}elseif($percent >= 50){
    $color = "#f59e0b"; // Orange
}

?>
<h3>Uploaded Requirements</h3>

<div style="margin-bottom:20px;padding:15px;background:#f8fafc;border-radius:8px;">

<strong>Verification Progress</strong><br><br>

<div style="background:#ddd;height:18px;border-radius:20px;overflow:hidden;">

<div
style="
width:<?= $percent; ?>%;
height:100%;
background:<?= $color; ?>;
transition:.3s;
">
</div>

</div>

<br>

<?= $verified; ?> / <?= $total; ?> Requirements Verified

</div>

<?php while($row=mysqli_fetch_assoc($files)){ ?>

<div class="file-card">

    <h4><?= htmlspecialchars($row['requirement_name']); ?></h4>

    <p>
        Status:
        <span class="badge <?= strtolower($row['status']); ?>">
            <?= $row['status']; ?>
        </span>
    </p>

    <p>
        <a
        target="_blank"
        href="../<?= $row['file_path']; ?>"
        class="btn approve">
            View File
        </a>

<?php if($row['status']=="Pending"){ ?>

    <a
    class="btn approve"
    href="verify_requirement.php?id=<?= $row['id']; ?>&request=<?= $request_id; ?>">
        ✓ Verify
    </a>

<button
type="button"
class="btn reject"
onclick="openRejectModal(<?= $row['id']; ?>)">

✗ Reject

</button>

<?php } ?>

    </p>

    <?php if(!empty($row['remarks'])){ ?>

        <p>
            <strong>Remarks:</strong>
            <?= htmlspecialchars($row['remarks']); ?>
        </p>

    <?php } ?>

</div>

<?php } ?>

<hr>

<?php if($verified == $total){ ?>

<a
class="btn approve"
href="approve.php?id=<?= $request_id; ?>">

Approve Request

</a>

<?php } else { ?>

<p style="color:#dc2626;font-weight:bold;">
Verify all requirements before approving this request.
</p>

<?php } ?>



</div>

</div>
<!-- Reject Requirement Modal -->

<div id="rejectModal" class="modal">

    <div class="modal-content">

        <span class="close" onclick="closeRejectModal()">&times;</span>

        <h2>Reject Requirement</h2>

        <form method="POST">

            <input
            type="hidden"
            name="file_id"
            id="reject_file_id">

            <label>Reason for rejection</label>

            <textarea
            name="remarks"
            rows="5"
            required></textarea>

            <br><br>

            <button
            type="submit"
            name="reject_requirement"
            class="btn reject">

                Reject Requirement

            </button>

        </form>

    </div>

</div>
<script>

function openRejectModal(fileId){

    document.getElementById("reject_file_id").value = fileId;

    document.getElementById("rejectModal").style.display = "block";

}

function closeRejectModal(){

    document.getElementById("rejectModal").style.display = "none";

}

window.onclick = function(event){

    let modal = document.getElementById("rejectModal");

    if(event.target == modal){

        modal.style.display = "none";

    }

}

</script>
</body>

</html>