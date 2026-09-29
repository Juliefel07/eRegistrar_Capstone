<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id =$_SESSION['user_id'];

// Get logged in user information securely
$userStmt = mysqli_prepare($conn, "SELECT * FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($userStmt, "i", $user_id);
mysqli_stmt_execute($userStmt);
$userResult = mysqli_stmt_get_result($userStmt);
$user = mysqli_fetch_assoc($userResult);
mysqli_stmt_close($userStmt);

// Success modal flag
$showSuccess = false;
$lastTrackingNo = "";

if (isset($_SESSION['request_success'])) {$showSuccess = true;
    $lastTrackingNo =$_SESSION['last_tracking_no'] ?? '';
    unset($_SESSION['request_success']);
    unset($_SESSION['last_tracking_no']);
}

// Load available documents
$documents = mysqli_query($conn, "
    SELECT *
    FROM documents
    WHERE status = 'Available'
");
if (!$documents) {
    die(mysqli_error($conn));
}

// Store documents and requirements for Javascript
$documentData = [];
$docQuery = mysqli_query($conn, "
    SELECT *
    FROM documents
    WHERE status = 'Available'
");

while ($doc = mysqli_fetch_assoc($docQuery)) {$requirements = [];
    $doc_id = (int)$doc['document_id'];
    
    $reqQuery = mysqli_query($conn, "
        SELECT *
        FROM document_requirements
        WHERE document_id = {$doc_id}
        ORDER BY requirement_id ASC
    ");

    if ($reqQuery) {
        while ($req = mysqli_fetch_assoc($reqQuery)) {$requirements[] = [
                "id"   => $req['requirement_id'],
                "name" => $req['requirement_name']
            ];
        }
    }

    $documentData[$doc['document_id']] = [
        "name"        => $doc['document_name'],
        "fee"         => $doc['fee'],
        "days"        => $doc['processing_days'],
        "description" => $doc['description'],
        "requirements"=> $requirements
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Document - CCTC eRegistrar</title>

    <!-- Base Stylesheets -->

    <link rel="stylesheet" href="../assets/css/request.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

   
</head>
<body>

    <?php require_once __DIR__ . "/navbar.php"; ?>

<div class="request-hero">
    <div>
        <h1>Request Documents</h1>
        <p>
            Submit your official document requests online. 
            Complete the form below and track the status anytime.
        </p>
    </div>
</div>

<div class="request-container">
    <div class="request-card">
        <h2>Request Document</h2>
        <p class="subtitle">Fill up the form to request your official school records.</p>

        <!-- PROGRESS STEPS -->
        <div class="steps">
            <div class="step active" id="step1Indicator"><span>1</span> Request Details</div>
            <div class="step" id="step2Indicator"><span>2</span> Student Info</div>
            <div class="step" id="step3Indicator"><span>3</span> Review</div>
        </div>

        <form id="requestForm" action="request_process.php" method="POST" enctype="multipart/form-data">

            <!-- STEP 1: REQUEST DETAILS -->
            <div class="form-step active" id="step1">
                <h3>Request Details</h3>

                <div class="form-group">
                    <label>Select Document</label>
                    <select name="document_id" id="documentSelect" required>
                        <option value="">Choose Document</option>
                        <?php 
                        mysqli_data_seek($documents, 0);
                        while($row = mysqli_fetch_assoc($documents)){ 
                        ?>
                            <option value="<?php echo $row['document_id']; ?>">
                                <?php echo htmlspecialchars($row['document_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="document-info">
                    <h4 id="docName">Select a document</h4>
                    <p id="docDescription">Document information will appear here.</p>

                    <div class="info-row">
                        <div>Processing: <strong id="docDays">0 Days</strong></div>
                        <div>Fee: <strong id="docFee">₱0.00</strong></div>
                    </div>

                    <div class="requirements">
                        <h4>Requirements</h4>
                        <div id="requirementsContainer">
                            <p>Select a document first.</p>
                        </div>
                    </div>
                </div>

                <!-- CCTC ID SURRENDER NOTE -->
                <div class="alert-note" style="background-color: #fffbeeb0; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 15px 0; border-radius: 4px; font-size: 0.875rem; color: #92400e;">
                    <strong>Note:</strong> Students shall surrender their ID as they apply for Transfer/Graduation.
                </div>

                <div class="form-group">
                    <label>Purpose</label>
                    <textarea name="purpose" id="purpose" required placeholder="State your purpose (e.g., Employment, Board Exam, Transfer)..."></textarea>
                </div>

                <div class="row">
                    <div class="form-group">
                        <label>Copies</label>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" id="payment_method" onchange="toggleEPaymentUpload(this.value)" required>
                            <option value="">Select Payment</option>
                            <option value="On the Counter">On the Counter (Pay at Accounting)</option>
                            <option value="E-Payment">E-Payment (GCash / Maya Upload)</option>
                        </select>
                    </div>
                </div>

                <!-- DYNAMIC E-PAYMENT PROOF UPLOAD -->
                <div class="form-group" id="epayment_group" style="display: none; margin-top: 10px;">
                    <label>Upload Payment Receipt / Proof</label>
                    <input type="file" name="proof_of_payment" id="proof_of_payment" accept="image/*,.pdf">
                    <small style="color:#666; display:block; margin-top:4px;">Upload GCash/Maya screenshot showing Ref No.</small>
                </div>

                <button type="button" class="next-btn" onclick="nextStep(2)">Next</button>
            </div>

            <!-- STEP 2: STUDENT INFORMATION -->
            <div class="form-step" id="step2">
                <h3>Student Information</h3>

                <div class="row">
                    <div class="form-group">
                        <label>Last Name / Family Name</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Present Address</label>
                    <input type="text" name="address" placeholder="Street, Barangay, City/Municipality, Province" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
                </div>

                <div class="row">
                    <div class="form-group">
                        <label>Program / Course & Year</label>
                        <input type="text" name="course_year" value="<?php echo htmlspecialchars(($user['course'] ?? '') . ' ' . ($user['year_level'] ?? '')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tel / Cellular No.</label>
                        <input type="text" name="contact_no" value="<?php echo htmlspecialchars($user['contact_no'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group">
                        <label>Are you a CCTC Graduate?</label>
                        <select name="is_graduate" required>
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Last Term / Semester / School Year in CCTC</label>
                        <input type="text" name="last_sy_attended" placeholder="e.g. 1st Sem 2023-2024" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Additional Remarks / Notes</label>
                    <textarea name="remarks" id="remarks" placeholder="Optional remarks..."></textarea>
                </div>

                <div class="button-group">
                    <button type="button" class="back-btn" onclick="previousStep(1)">Back</button>
                    <button type="button" class="next-btn" onclick="nextStep(3)">Next</button>
                </div>
            </div>

            <!-- STEP 3: REVIEW REQUEST -->
            <div class="form-step" id="step3">
                <h3>Review Request</h3>

                <div class="review-card">
                    <div class="review-item">
                        <label>Document</label>
                        <p id="reviewDocument">-</p>
                    </div>
                    <div class="review-item">
                        <label>Purpose</label>
                        <p id="reviewPurpose">-</p>
                    </div>
                    <div class="review-item">
                        <label>Quantity</label>
                        <p id="reviewQuantity">1</p>
                    </div>
                    <div class="review-item">
                        <label>Payment Method</label>
                        <p id="reviewPayment">-</p>
                    </div>
                    <div class="review-item">
                        <label>Applicant Name</label>
                        <p id="reviewApplicantName">-</p>
                    </div>
                    <div class="review-item">
                        <label>Last SY Attended</label>
                        <p id="reviewSY">-</p>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" class="back-btn" onclick="previousStep(2)">Back</button>
                    <button type="button" class="submit-btn" onclick="confirmRequest()">Submit Request</button>
                </div>
            </div>

        </form>
    </div>

    <!-- RIGHT SUMMARY -->
    <div class="summary-card">
        <h3>Request Summary</h3>
        <div class="summary-item">
            <span>Document</span>
            <strong id="summaryDocument">-</strong>
        </div>
        <div class="summary-item">
            <span>Processing</span>
            <strong id="summaryDays">-</strong>
        </div>
        <div class="summary-item">
            <span>Quantity</span>
            <strong id="summaryQuantity">1</strong>
        </div>
        <div class="summary-item">
            <span>Payment</span>
            <strong id="summaryPayment">-</strong>
        </div>
        <hr>
        <div class="total">
            Total <strong id="summaryTotal">₱0.00</strong>
        </div>
    </div>
</div>

<!-- CONFIRM MODAL -->
<div class="modal" id="confirmModal">
    <div class="modal-box">
        <h3>Confirm Request</h3>
        <p>Are you sure you want to submit this request?</p>
        <div class="modal-buttons">
            <button class="cancel-btn" onclick="closeConfirm()">Cancel</button>
            <button class="confirm-btn" onclick="submitRequest()">Yes, Submit</button>
        </div>
    </div>
</div>

<!-- ERROR MODAL -->
<div class="modal" id="errorModal">
    <div class="success-modal-box">
        <h2 style="color:#dc2626;">Missing Information</h2>
        <p id="errorText">Please complete all required fields before submitting your request.</p>
        <button class="confirm-btn" onclick="closeError()">OK</button>
    </div>
</div>

<!-- SUCCESS MODAL: WAITING FOR APPROVAL -->
<div class="modal success-modal" id="successModal">
    <div class="success-modal-box" style="text-align: center; padding: 25px;">
        <h2 style="color: #16a34a; margin-bottom: 10px;">✅ Request Submitted Successfully</h2>
        <p style="color: #374151; font-size: 0.95rem;">
            Your request has been filed and submitted to the Registrar's Office for review.
        </p>
        
        <div style="background-color: #f0fdf4; border-left: 4px solid #22c55e; padding: 12px; margin: 15px 0; text-align: left; border-radius: 4px; font-size: 0.85rem; color: #166534;">
            📌 <strong>Next Steps:</strong>
            <ol style="margin: 5px 0 0 18px; padding: 0; line-height: 1.5;">
                <li>Wait for the Registrar to approve your request.</li>
                <li>Once approved, proceed to payment (Accounting or E-Payment).</li>
                <li>Your Payment Slip & Claim Stub will unlock automatically after approval/payment.</li>
            </ol>
        </div>

        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-top: 20px;">
            <a href="track.php" class="confirm-btn" style="background-color: #2563eb; text-decoration: none; padding: 10px 18px; border-radius: 6px; color: white; font-weight: bold; display: inline-block;">
                🔍 Track Request Status
            </a>
            <button class="cancel-btn" onclick="closeSuccess()" style="padding: 10px 18px;">Close</button>
        </div>
    </div>
</div>

<script>
const documents = <?php echo json_encode($documentData); ?>;
const documentSelect = document.getElementById("documentSelect");

documentSelect.addEventListener("change", function(){
    let id = this.value;
    if(documents[id]){
        document.getElementById("docName").innerHTML = documents[id].name;
        document.getElementById("docDescription").innerHTML = documents[id].description ?? "No description";
        document.getElementById("docDays").innerHTML = documents[id].days + " Working Days";
        document.getElementById("docFee").innerHTML = "₱" + parseFloat(documents[id].fee).toFixed(2);

        document.getElementById("summaryDocument").innerHTML = documents[id].name;
        document.getElementById("summaryDays").innerHTML = documents[id].days + " Days";

        let req = documents[id].requirements;
        let html = "";
        if(req.length > 0){
            req.forEach(function(item){
                html += `
                <div class="requirement-item">
                    <label>${item.name}</label>
                    <input type="file" name="requirements[${item.id}]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                </div>`;
            });
        } else {
            html = "<p>No requirements required for this document.</p>";
        }
        document.getElementById("requirementsContainer").innerHTML = html;
        updateTotal();
    }
});

document.getElementById("quantity").addEventListener("input", function(){
    document.getElementById("summaryQuantity").innerHTML = this.value;
    updateTotal();
});

document.getElementById("payment_method").addEventListener("change", function(){
    document.getElementById("summaryPayment").innerHTML = this.value;
});

document.getElementById("purpose").addEventListener("input", function(){
    document.getElementById("reviewPurpose").innerHTML = this.value;
});

function toggleEPaymentUpload(val) {
    const group = document.getElementById("epayment_group");
    const input = document.getElementById("proof_of_payment");
    if (val === "E-Payment") {
        group.style.display = "block";
        input.required = true;
    } else {
        group.style.display = "none";
        input.required = false;
    }
}

function updateTotal(){
    let id = documentSelect.value;
    if(documents[id]){
        let qty = document.getElementById("quantity").value;
        let total = documents[id].fee * qty;
        document.getElementById("summaryTotal").innerHTML = "₱" + parseFloat(total).toFixed(2);
    }
}

function nextStep(step){
    document.querySelectorAll(".form-step").forEach(el => el.classList.remove("active"));
    document.getElementById("step" + step).classList.add("active");

    document.querySelectorAll(".step").forEach(el => el.classList.remove("active"));
    document.getElementById("step" + step + "Indicator").classList.add("active");

    if(step === 3){
        document.getElementById("reviewDocument").innerHTML = document.getElementById("summaryDocument").innerHTML;
        document.getElementById("reviewQuantity").innerHTML = document.getElementById("quantity").value;
        document.getElementById("reviewPayment").innerHTML = document.getElementById("payment_method").value;

        const lastName = document.querySelector("input[name='last_name']").value;
        const firstName = document.querySelector("input[name='first_name']").value;
        const middleName = document.querySelector("input[name='middle_name']").value;
        document.getElementById("reviewApplicantName").innerHTML = `${lastName}, ${firstName} ${middleName}`;

        document.getElementById("reviewSY").innerHTML = document.querySelector("input[name='last_sy_attended']").value;
    }
}

function previousStep(step){
    nextStep(step);
}

function confirmRequest(){
    let form = document.getElementById("requestForm");
    if(!form.checkValidity()){
        document.getElementById("errorText").innerHTML = "Please complete all required fields before submitting.";
        document.getElementById("errorModal").style.display = "flex";
        return;
    }
    document.getElementById("confirmModal").style.display = "flex";
}

function closeConfirm(){
    document.getElementById("confirmModal").style.display = "none";
}

function submitRequest(){
    document.getElementById("requestForm").submit();
}

function closeSuccess(){
    document.getElementById("successModal").style.display = "none";
}

function closeError(){
    document.getElementById("errorModal").style.display = "none";
}

<?php if($showSuccess){ ?>
document.getElementById("successModal").style.display = "flex";
<?php } ?>
</script>

</body>
</html>