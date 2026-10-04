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
     <link rel="icon" type="image/png" href="/assets/images/logooo.png?v=3">
    <!-- Base Stylesheets -->
    <link rel="stylesheet" href="../assets/css/request.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* Container Alignment & Layout (Safe spacing below sticky navbar) */
        .request-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 16px;
            display: flex;
            gap: 24px;
            justify-content: center;
            align-items: flex-start;
            box-sizing: border-box;
            position: relative;
            z-index: 5;
        }

        .request-card {
            flex: 1;
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }

        /* Titles inside card */
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
        }

        .subtitle {
            font-size: 0.9rem;
            color: #64748b;
            margin: 0 0 24px 0;
        }

        /* Dynamic Multi-Document Items */
        .document-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 16px;
            position: relative;
        }

        .item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 12px;
    margin-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}

        .btn-add-doc {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px dashed #10b981;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            margin: 10px 0 20px 0;
            transition: all 0.2s ease;
        }

        .btn-add-doc:hover {
            background-color: #d1fae5;
        }

        /* Document Number Badge (Sleek Pill Style) */
.doc-number-badge {
    background-color: #e0f2fe;
    color: #0369a1;
    font-size: 0.85rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    letter-spacing: 0.3px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* Modern Remove Button (Soft Red Tag) */
.btn-remove-doc {
    background-color: #fef2f2;
    color: #ef4444;
    border: 1px solid #fecaca;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
}

.btn-remove-doc:hover {
    background-color: #fee2e2;
    color: #dc2626;
    border-color: #fca5a5;
    transform: translateY(-1px);
}

        /* Clean Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.4);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 16px;
            box-sizing: border-box;
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .modal-card {
            position: relative;
            background-color: #ffffff;
            width: 100%;
            max-width: 400px;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10;
        }

        .modal-icon {
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #111827;
            margin: 0 0 8px 0;
        }

        .modal-message {
            font-size: 0.9rem;
            color: #4b5563;
            line-height: 1.4;
            margin: 0 0 16px 0;
        }

        .simple-steps-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 20px;
            text-align: left;
            font-size: 0.85rem;
            color: #374151;
        }

        .steps-heading {
            font-weight: 600;
            margin-bottom: 6px;
            color: #1f2937;
        }

        .simple-steps-box ul {
            margin: 0;
            padding-left: 18px;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn {
            font-size: 0.875rem;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary { background-color: #2563eb; color: #ffffff; }
        .btn-primary:hover { background-color: #1d4ed8; }
        .btn-secondary { background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .btn-secondary:hover { background-color: #e5e7eb; }
        .btn-danger { background-color: #dc2626; color: #ffffff; }
        .btn-danger:hover { background-color: #b91c1c; }

        @media (max-width: 768px) {
            .request-container {
                flex-direction: column;
                margin-top: 20px;
                padding: 0 12px;
            }
            .request-card, .summary-card {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . "/navbar.php"; ?>

<div class="request-container">
    <div class="request-card">
        <h1 class="page-title">Request Document</h1>
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

                <!-- CONTAINER FOR DYNAMIC DOCUMENT ITEMS -->
                <div id="documentItemsContainer">
                    <!-- FIRST DOCUMENT ITEM -->
                    <div class="document-item" data-index="0">
                        <div class="item-header">
    <span class="doc-number-badge"><i class="fa-solid fa-file-lines"></i> Document 1</span>
</div>

                        <div class="form-group">
                            <label>Select Document</label>
                            <select name="documents[0][id]" class="document-select" onchange="handleDocChange(this)" required>
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
                            <h4 class="doc-name">Select a document</h4>
                            <p class="doc-desc">Document information will appear here.</p>

                            <div class="info-row">
                                <div>Processing: <strong class="doc-days">0 Days</strong></div>
                                <div>Fee: <strong class="doc-fee">₱0.00</strong></div>
                            </div>

                            <div class="requirements">
                                <h4>Requirements</h4>
                                <div class="requirements-container">
                                    <p>Select a document first.</p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <label>Copies</label>
                                <input type="number" name="documents[0][quantity]" class="doc-quantity" value="1" min="1" oninput="calculateTotal()" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ADD MORE BUTTON -->
                <button type="button" class="btn-add-doc" onclick="addDocumentItem()">
                    <i class="fa-solid fa-plus"></i> Add Another Document
                </button>

                <!-- CCTC ID SURRENDER NOTE -->
                <div class="alert-note" style="background-color: #fffbeeb0; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 15px 0; border-radius: 4px; font-size: 0.875rem; color: #92400e;">
                    <strong>Note:</strong> Students shall surrender their ID as they apply for Transfer/Graduation.
                </div>

                <div class="form-group">
                    <label>Purpose</label>
                    <textarea name="purpose" id="purpose" required placeholder="State your purpose (e.g., Employment, Board Exam, Transfer)..."></textarea>
                </div>

                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" id="payment_method" onchange="toggleEPaymentUpload(this.value)" required>
                        <option value="">Select Payment</option>
                        <option value="On the Counter">On the Counter (Pay at Accounting)</option>
                        <option value="E-Payment">E-Payment (GCash / Maya Upload)</option>
                    </select>
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
                        <label>Document(s)</label>
                        <div id="reviewDocument">-</div>
                    </div>
                    <div class="review-item">
                        <label>Purpose</label>
                        <p id="reviewPurpose">-</p>
                    </div>
                    <div class="review-item">
                        <label>Total Quantity</label>
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

    <!-- RIGHT SUMMARY CARD -->
    <div class="summary-card">
        <h3>Request Summary</h3>
        <div class="summary-item">
            <span>Documents</span>
            <div id="summaryDocument" style="text-align: right; font-weight: 600;">-</div>
        </div>
        <div class="summary-item">
            <span>Total Copies</span>
            <strong id="summaryQuantity">0</strong>
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
    <div class="modal-card">
        <h3 class="modal-title">Confirm Request</h3>
        <p class="modal-message">Are you sure you want to submit this request?</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeConfirm()">Cancel</button>
            <button class="btn btn-primary" onclick="submitRequest()">Yes, Submit</button>
        </div>
    </div>
</div>

<!-- ERROR MODAL: MISSING INFORMATION -->
<div class="modal" id="errorModal">
    <div class="modal-card">
        <div class="modal-icon error-icon">⚠️</div>
        <h3 class="modal-title">Missing Information</h3>
        <p class="modal-message" id="errorText">Please complete all required fields before submitting your request.</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-danger" onclick="closeError()">OK, Got It</button>
        </div>
    </div>
</div>

<!-- SUCCESS MODAL: WAITING FOR APPROVAL -->
<div class="modal success-modal" id="successModal">
    <div class="modal-overlay" onclick="closeSuccess()"></div>
    
    <div class="modal-card">
        <div class="modal-icon success-icon">✅</div>
        <h3 class="modal-title">Request Submitted</h3>
        <p class="modal-message">
            Your request has been filed and sent to the Registrar's Office for review.
        </p>
        
        <div class="simple-steps-box">
            <div class="steps-heading">Next Steps:</div>
            <ul>
                <li>Wait for Registrar approval.</li>
                <li>Proceed to payment once approved.</li>
                <li>Your Payment Slip & Claim Stub will unlock automatically.</li>
            </ul>
        </div>

        <div class="modal-actions">
            <a href="track.php" class="btn btn-primary">Track Request</a>
            <button type="button" class="btn btn-secondary" onclick="closeSuccess()">Close</button>
        </div>
    </div>
</div>

<script>
const documents = <?php echo json_encode($documentData); ?>;
let docIndexCounter = 1;

function handleDocChange(selectElement) {
    const itemCard = selectElement.closest('.document-item');
    const docId = selectElement.value;
    const index = itemCard.dataset.index;

    const docName = itemCard.querySelector('.doc-name');
    const docDesc = itemCard.querySelector('.doc-desc');
    const docDays = itemCard.querySelector('.doc-days');
    const docFee = itemCard.querySelector('.doc-fee');
    const reqContainer = itemCard.querySelector('.requirements-container');

    if (documents[docId]) {
        docName.innerText = documents[docId].name;
        docDesc.innerText = documents[docId].description || "No description";
        docDays.innerText = documents[docId].days + " Working Days";
        docFee.innerText = "₱" + parseFloat(documents[docId].fee).toFixed(2);

        let reqList = documents[docId].requirements;
        let html = "";
        if (reqList.length > 0) {
            reqList.forEach(function(item) {
                html += `
                <div class="requirement-item" style="margin-top: 8px;">
                    <label style="display:block; font-size: 0.85rem; font-weight:600;">${item.name}</label>
                    <input type="file" name="documents[${index}][requirements][${item.id}]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                </div>`;
            });
        } else {
            html = "<p style='font-size: 0.85rem; color: #666;'>No requirements needed for this document.</p>";
        }
        reqContainer.innerHTML = html;
    } else {
        docName.innerText = "Select a document";
        docDesc.innerText = "Document information will appear here.";
        docDays.innerText = "0 Days";
        docFee.innerText = "₱0.00";
        reqContainer.innerHTML = "<p>Select a document first.</p>";
    }

    calculateTotal();
}

function addDocumentItem() {
    const container = document.getElementById('documentItemsContainer');
    const newIndex = docIndexCounter++;

    let optionsHtml = '<option value="">Choose Document</option>';
    for (let id in documents) {
        optionsHtml += `<option value="${id}">${documents[id].name}</option>`;
    }

    const itemDiv = document.createElement('div');
    itemDiv.className = 'document-item';
    itemDiv.dataset.index = newIndex;
    itemDiv.innerHTML = `
        <div class="item-header">
            <span class="doc-number-badge"><i class="fa-solid fa-file-lines"></i> Document ${container.children.length + 1}</span>
            <button type="button" class="btn-remove-doc" onclick="removeDocumentItem(this)">
                <i class="fa-solid fa-trash-can"></i> Remove
            </button>
        </div>

        <div class="form-group">
            <label>Select Document</label>
            <select name="documents[${newIndex}][id]" class="document-select" onchange="handleDocChange(this)" required>
                ${optionsHtml}
            </select>
        </div>

        <div class="document-info">
            <h4 class="doc-name">Select a document</h4>
            <p class="doc-desc">Document information will appear here.</p>

            <div class="info-row">
                <div>Processing: <strong class="doc-days">0 Days</strong></div>
                <div>Fee: <strong class="doc-fee">₱0.00</strong></div>
            </div>

            <div class="requirements">
                <h4>Requirements</h4>
                <div class="requirements-container">
                    <p>Select a document first.</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="form-group">
                <label>Copies</label>
                <input type="number" name="documents[${newIndex}][quantity]" class="doc-quantity" value="1" min="1" oninput="calculateTotal()" required>
            </div>
        </div>
    `;

    container.appendChild(itemDiv);
    renumberItems();
    calculateTotal();
}

function renumberItems() {
    const items = document.querySelectorAll('#documentItemsContainer .document-item');
    items.forEach((item, idx) => {
        const headerSpan = item.querySelector('.doc-number-badge');
        if (headerSpan) {
            headerSpan.innerHTML = `<i class="fa-solid fa-file-lines"></i> Document ${idx + 1}`;
        }
    });
}
function removeDocumentItem(button) {
    const item = button.closest('.document-item');
    item.remove();
    renumberItems();
    calculateTotal();
}



function calculateTotal() {
    let grandTotal = 0;
    let totalQty = 0;
    let summaryListHtml = "";

    const items = document.querySelectorAll('#documentItemsContainer .document-item');
    items.forEach(item => {
        const select = item.querySelector('.document-select');
        const qtyInput = item.querySelector('.doc-quantity');
        const docId = select ? select.value : null;
        const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;

        if (docId && documents[docId]) {
            const fee = parseFloat(documents[docId].fee);
            const subtotal = fee * qty;
            grandTotal += subtotal;
            totalQty += qty;
            summaryListHtml += `<div style="margin-bottom: 4px;">• ${documents[docId].name} (${qty}x)</div>`;
        }
    });

    document.getElementById("summaryDocument").innerHTML = summaryListHtml || "-";
    document.getElementById("summaryQuantity").innerHTML = totalQty;
    document.getElementById("summaryTotal").innerHTML = "₱" + grandTotal.toFixed(2);
}

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

function nextStep(step){
    document.querySelectorAll(".form-step").forEach(el => el.classList.remove("active"));
    document.getElementById("step" + step).classList.add("active");

    document.querySelectorAll(".step").forEach(el => el.classList.remove("active"));
    document.getElementById("step" + step + "Indicator").classList.add("active");

    if(step === 3){
        document.getElementById("reviewDocument").innerHTML = document.getElementById("summaryDocument").innerHTML;
        document.getElementById("reviewQuantity").innerHTML = document.getElementById("summaryQuantity").innerHTML;
        document.getElementById("reviewPayment").innerHTML = document.getElementById("payment_method").value;

        const lastName = document.querySelector("input[name='last_name']").value;
        const firstName = document.querySelector("input[name='first_name']").value;
        const middleName = document.querySelector("input[name='middle_name']").value;
        
        const fullName = [lastName, firstName, middleName].filter(Boolean).join(" ");
        document.getElementById("reviewApplicantName").innerHTML = fullName;

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