
// ==========================================
// CUSTOM FLOATING MODAL ALERT HELPER
// ==========================================

function showModalAlert(title, message) {
    let modal = document.getElementById('customModalAlert');
    
    // Create the modal HTML dynamically if it doesn't exist yet
    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'modal-error';
        modal.id = 'customModalAlert';
        modal.innerHTML = `
            <div class="modal-box">
                <i class="fa-solid fa-circle-xmark error-icon"></i>
                <h3 id="customModalTitle">Notice</h3>
                <p id="customModalMessage"></p>
                <button type="button" onclick="closeCustomModal()">OK</button>
            </div>
        `;
        document.body.appendChild(modal);
    }

    document.getElementById('customModalTitle').innerText = title;
    document.getElementById('customModalMessage').innerText = message;
    modal.style.display = 'flex';
}

function closeCustomModal() {
    const modal = document.getElementById('customModalAlert');
    if (modal) {
        modal.style.display = 'none';
    }
}
// ==========================================
// STEP ELEMENTS
// ==========================================

const steps = document.querySelectorAll(".form-step");
const progressSteps = document.querySelectorAll(".step");

let currentStep = 0;

// ==========================================
// BUTTONS
// ==========================================

const btnStep2 = document.getElementById("toStep2");
const btnStep3 = document.getElementById("toStep3");
const btnStep4 = document.getElementById("toStep4");

const btnBack1 = document.getElementById("backToStep1");
const btnBack2 = document.getElementById("backToStep2");
const btnBack3 = document.getElementById("backToStep3");

const btnParentBack = document.getElementById("parentBack");
const btnParentNext = document.getElementById("parentNext");

// Safely enable/disable form inputs to avoid "not focusable" errors
function toggleInputs(containerId, disable) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.querySelectorAll("input, select, textarea").forEach(input => {
        input.disabled = disable;
        if(disable) {
            input.removeAttribute("required");
        }
    });
}

// ==========================================
// SHOW STEP
// ==========================================

function showStep(index){
    steps.forEach(step => step.classList.remove("active"));
    if(steps[index]) steps[index].classList.add("active");

    progressSteps.forEach((step,i)=>{
        step.classList.remove("active");
        step.classList.remove("completed");

        if(i < index){
            step.classList.add("completed");
        }

        if(i == index){
            step.classList.add("active");
        }
    });

    currentStep = index;
}

// ==========================================
// STEP 1 -> STEP 2
// ==========================================

if(btnStep2){
    btnStep2.addEventListener("click", () => {
        const account = document.querySelector("input[name='account_type']:checked");

        if(!account){
            showModalAlert("Please select an account type.");
            return;
        }

        if(account.value === "Parent"){
            const studentForm = document.getElementById("studentForm");
            const parentForm = document.getElementById("parentForm");
            if(studentForm) studentForm.style.display = "none";
            if(parentForm) parentForm.style.display = "block";

            toggleInputs("studentForm", true);
            toggleInputs("parentForm", false);

            showStep(2);
            return;
        }

        showStep(1);
    });
}

// ==========================================
// BACK TO STEP 1
// ==========================================

if(btnBack1){
    btnBack1.addEventListener("click", () => {
        showStep(0);
    });
}

// ==========================================
// STEP 2 -> STEP 3
// ==========================================

if(btnStep3){
    btnStep3.addEventListener("click", () => {
        const level = document.querySelector("input[name='student_level']:checked");

        if (!level) {
            showModalAlert("Please choose a student level.");
            return;
        }

        const studentForm = document.getElementById("studentForm");
        const parentForm = document.getElementById("parentForm");
        if(studentForm) studentForm.style.display = "block";
        if(parentForm) parentForm.style.display = "none";

        toggleInputs("studentForm", false);
        toggleInputs("parentForm", true);

        const collegeContainer = document.getElementById("collegeSectionContainer");
        const basicContainer = document.getElementById("basicSectionContainer");

        if(collegeContainer) collegeContainer.style.display = "none";
        if(basicContainer) basicContainer.style.display = "none";

        toggleInputs("collegeSectionContainer", true);
        toggleInputs("basicSectionContainer", true);

        if (level.value === "College") {
            if(collegeContainer) collegeContainer.style.display = "block";
            toggleInputs("collegeSectionContainer", false);
        } else if (level.value === "Basic Education") {
            if(basicContainer) basicContainer.style.display = "block";
            toggleInputs("basicSectionContainer", false);
        }

        showStep(2);
    });
}

// ==========================================
// BACK BUTTONS
// ==========================================

if(btnBack2){
    btnBack2.addEventListener("click", () => {
        showStep(1);
    });
}

if(btnParentBack){
    btnParentBack.addEventListener("click", () => {
        showStep(0);
    });
}

// ==========================================
// STEP 3 -> STEP 4 (REVIEW)
// ==========================================

if(btnStep4){
    btnStep4.addEventListener("click", () => {
        fillReview();
        showStep(3);
    });
}

if(btnParentNext){
    btnParentNext.addEventListener("click", () => {
        const name = document.getElementById("parent_name")?.value.trim() || "";
        const email = document.getElementById("parent_email")?.value.trim() || "";
        const password = document.getElementById("parent_password")?.value || "";
        const confirm = document.getElementById("parent_confirm_password")?.value || "";

        if(name === "" || email === "" || password === "" || confirm === ""){
            showModalAlert("Please complete parent information.");
            return;
        }

        if(password !== confirm){
            showModalAlert("Passwords do not match.");
            return;
        }

        fillReview();
        showStep(3);
    });
}

// ==========================================
// BACK FROM REVIEW
// ==========================================

if(btnBack3){
    btnBack3.addEventListener("click", () => {
        const account = document.querySelector("input[name='account_type']:checked");

        if(account && account.value === "Parent"){
            showStep(2);
            if(document.getElementById("studentForm")) document.getElementById("studentForm").style.display = "none";
            if(document.getElementById("parentForm")) document.getElementById("parentForm").style.display = "block";
        } else {
            showStep(2);
            if(document.getElementById("studentForm")) document.getElementById("studentForm").style.display = "block";
            if(document.getElementById("parentForm")) document.getElementById("parentForm").style.display = "none";
        }
    });
}

// ==========================================
// REVIEW PAGE (EXACT ROW REMOVAL FOR PARENTS)
// ==========================================
// ==========================================
// FAIL-SAFE REVIEW PAGE (PARENTS)
// ==========================================

function fillReview(){
    // 1. Check account type radio button safely
    const accountInput = document.querySelector("input[name='account_type']:checked");
    const accountValue = accountInput ? accountInput.value.trim() : "";

    // 2. Fallback check: Is the parent form currently active/visible on step 3?
    const parentForm = document.getElementById("parentForm");
    const isParentFormVisible = parentForm && parentForm.style.display !== "none";

    // 3. Determine if this is a Parent registration
    const isParent = accountValue.toLowerCase().includes("parent") || isParentFormVisible;

    // Update Account Type label
    const reviewAccountType = document.getElementById("reviewAccountType");
    if(reviewAccountType) reviewAccountType.innerText = isParent ? "Parent" : (accountValue || "-");

    const level = document.querySelector("input[name='student_level']:checked");
    const linkedStudentsCard = document.getElementById("linkedStudentsCard");

    if(isParent){
        // ==========================================
        // PARENT VIEW: REMOVE ALL STUDENT ROWS
        // ==========================================
        if(linkedStudentsCard) linkedStudentsCard.style.display = "block";

        // Target every row in the review page
        const allReviewItems = document.querySelectorAll('.review-page .review-item');
        allReviewItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            
            // Hide Student Level, Student Number, LRN, Course, and Year Level
            if(text.includes('student level') || 
               text.includes('student number') || 
               text.includes('lrn') || 
               text.includes('course') || 
               text.includes('year level')){
                item.style.setProperty("display", "none", "important");
            } else {
                item.style.setProperty("display", "flex", "important");
            }
        });

        // Assign Parent Info safely
        const parentName = document.getElementById("parent_name")?.value || "-";
        const parentContact = document.getElementById("parent_contact")?.value || "-";
        const parentEmail = document.getElementById("parent_email")?.value || "-";

        const reviewFullName = document.getElementById("reviewFullName");
        const reviewContact = document.getElementById("reviewContact");
        const reviewEmail = document.getElementById("reviewEmail");

        if(reviewFullName) reviewFullName.innerText = parentName;
        if(reviewContact) reviewContact.innerText = parentContact;
        if(reviewEmail) reviewEmail.innerText = parentEmail;

        loadLinkedStudents();

    } else {
        // ==========================================
        // STUDENT VIEW: SHOW ALL ROWS
        // ==========================================
        if(linkedStudentsCard) linkedStudentsCard.style.display = "none";

        const allReviewItems = document.querySelectorAll('.review-page .review-item');
        allReviewItems.forEach(item => {
            item.style.setProperty("display", "flex", "important");
        });

        const reviewStudentLevel = document.getElementById("reviewStudentLevel");
        if(reviewStudentLevel) reviewStudentLevel.innerText = level ? level.value : "-";

        if(level && level.value === "College"){
            document.getElementById("reviewFullName").innerText = document.getElementById("fullname_college")?.value || "-";
            document.getElementById("reviewStudentNumber").innerText = document.getElementById("student_no")?.value || "-";
            document.getElementById("reviewCourse").innerText = document.getElementById("course")?.value || "-";

            const status = document.getElementById("student_status")?.value;
            const reviewYearLevel = document.getElementById("reviewYearLevel");
            if(reviewYearLevel) {
                if(status === "Graduated" || (status && status.toLowerCase().includes("graduated"))){
                    reviewYearLevel.innerText = "Graduated / Alumni";
                } else {
                    reviewYearLevel.innerText = document.getElementById("year_level")?.value || "-";
                }
            }

            document.getElementById("reviewContact").innerText = document.getElementById("contact_no_college")?.value || "-";
            document.getElementById("reviewEmail").innerText = document.getElementById("email_college")?.value || "-";

        } else if(level && level.value === "Basic Education"){
            allReviewItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if(text.includes('course') || text.includes('year level')){
                    item.style.setProperty("display", "none", "important");
                }
            });

            document.getElementById("reviewFullName").innerText = document.getElementById("fullname_basic")?.value || "-";
            document.getElementById("reviewStudentNumber").innerText = document.getElementById("lrn")?.value || "-";
            document.getElementById("reviewContact").innerText = document.getElementById("contact_no_basic")?.value || "-";
            document.getElementById("reviewEmail").innerText = document.getElementById("email_basic")?.value || "-";
        }
    }
}
// ==========================================
// LOAD LINKED STUDENTS
// ==========================================

function loadLinkedStudents(){
    const container = document.getElementById("reviewStudents");
    if(!container) return;
    container.innerHTML = "";

    const studentNumbers = document.querySelectorAll("input[name='student_numbers[]']");
    const studentNames = document.querySelectorAll("input[name='student_names[]']");

    let hasValidStudent = false;

    studentNames.forEach((student, index)=>{
        const nameVal = student.value.trim();
        const numVal = studentNumbers[index]?.value.trim() || "";

        if(nameVal !== "" || numVal !== ""){
            hasValidStudent = true;
            const card = document.createElement("div");
            card.className = "review-item";
            card.innerHTML = `
                <span>${nameVal || "-"}</span>
                <strong>${numVal || "-"}</strong>
            `;
            container.appendChild(card);
        }
    });

    if(!hasValidStudent){
        const noneCard = document.createElement("div");
        noneCard.className = "review-item";
        noneCard.innerHTML = `
            <span>Linked Student Status</span>
            <strong>None</strong>
        `;
        container.appendChild(noneCard);
    }
}

// ==========================================
// ADD STUDENT
// ==========================================

const addStudentBtn = document.getElementById("addStudent");

if(addStudentBtn){
    addStudentBtn.addEventListener("click",()=>{
        const container = document.getElementById("studentContainer");
        if(!container) return;
        const card = document.createElement("div");
        card.className = "student-card";

        card.innerHTML = `
            <div class="form-grid">
                <div class="form-group">
                    <label>Student Number</label>
                    <input type="text" name="student_numbers[]" placeholder="Student Number">
                </div>
                <div class="form-group">
                    <label>Student Name</label>
                    <input type="text" name="student_names[]" placeholder="Student Name">
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

// ==========================================
// SIMPLE VALIDATION
// ==========================================

const registerForm = document.getElementById("registerForm");

if(registerForm){
    registerForm.addEventListener("submit",(e)=>{
        const agree = document.getElementById("agreeTerms");

        if(agree && !agree.checked){
            e.preventDefault();
            showModalAlert("Please agree to the Terms and Conditions.");
            return false;
        }

        showModalAlert("Registered Successfully");
    });
}

// ==========================================
// PAGE LOAD INITIALIZER
// ==========================================

showStep(0);