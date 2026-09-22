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

// ADD THIS HERE
function toggleInputs(containerId, disable) {
    document.querySelectorAll(
        `#${containerId} input, #${containerId} select, #${containerId} textarea`
    ).forEach(input => {
        input.disabled = disable;
    });
}
// ==========================================
// SHOW STEP
// ==========================================

function showStep(index){

    steps.forEach(step => step.classList.remove("active"));

    steps[index].classList.add("active");

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
// STEP 1
// ==========================================

btnStep2.addEventListener("click",()=>{

    const account =
        document.querySelector(
            "input[name='account_type']:checked"
        );

    if(!account){

        alert("Please select an account type.");

        return;

    }

if(account.value === "Parent"){

    document.getElementById("studentForm").style.display = "none";
    document.getElementById("parentForm").style.display = "block";

    toggleInputs("studentForm", true);
    toggleInputs("parentForm", false);

    showStep(2);

    return;

}

    showStep(1);

});

// ==========================================
// BACK TO STEP 1
// ==========================================

btnBack1.addEventListener("click",()=>{

    showStep(0);

});

// ==========================================
// STEP 2
// ==========================================

btnStep3.addEventListener("click",()=>{

    const level =
        document.querySelector(
            "input[name='student_level']:checked"
        );

    if(!level){

        alert("Please choose a student level.");

        return;

    }

    document
        .getElementById("studentForm")
        .style.display = "block";

    document
        .getElementById("parentForm")
        .style.display = "none";

    // Hide everything first

    document
        .querySelectorAll(".basic-only")
        .forEach(el=>el.style.display="none");

    document
        .querySelectorAll(".college-only")
        .forEach(el=>el.style.display="none");

    document
        .querySelectorAll(".alumni-only")
        .forEach(el=>el.style.display="none");

    document
        .querySelector(".student-no-group")
        .style.display="none";

    document
        .querySelector(".lrn-group")
        .style.display="none";

    // BASIC EDUCATION

    if(level.value==="Basic Education"){

        document
            .querySelector(".lrn-group")
            .style.display="block";

        document
            .querySelectorAll(".basic-only")
            .forEach(el=>el.style.display="flex");

    }

    // COLLEGE

    if(level.value==="College"){

        document
            .querySelector(".student-no-group")
            .style.display="block";

        document
            .querySelectorAll(".college-only")
            .forEach(el=>el.style.display="flex");

    }

    // ALUMNI

    if(level.value==="Alumni"){

        document
            .querySelector(".student-no-group")
            .style.display="block";

        document
            .querySelectorAll(".alumni-only")
            .forEach(el=>el.style.display="flex");

    }

    showStep(2);

});

// ==========================================
// BACK BUTTONS
// ==========================================

btnBack2.addEventListener("click", () => {

    showStep(1);

});

btnParentBack.addEventListener("click", () => {

    const account =
        document.querySelector("input[name='account_type']:checked");

    if(account && account.value === "Parent"){

        showStep(0);

    }else{

        showStep(1);

    }

});

// ==========================================
// STEP 3 -> STEP 4
// ==========================================

if(btnStep4){

btnStep4.addEventListener("click", () => {

    const account =
        document.querySelector(
            "input[name='account_type']:checked"
        );


    if(!account){
        alert("Please select account type.");
        return;
    }


    if(account.value === "Parent"){

        const name =
            document.getElementById("parent_name").value;

        const email =
            document.getElementById("parent_email").value;

        const password =
            document.getElementById("parent_password").value;

        const confirm =
            document.getElementById("parent_confirm_password").value;


        if(
            name=="" ||
            email=="" ||
            password=="" ||
            confirm==""
        ){

            alert("Please complete parent information.");
            return;

        }


        if(password !== confirm){

            alert("Passwords do not match.");
            return;

        }

    }


    fillReview();

    showStep(3);

});

}
btnParentNext.addEventListener("click", () => {

    fillReview();

    showStep(3);

});

// ==========================================
// BACK FROM REVIEW
// ==========================================

btnBack3.addEventListener("click", () => {

    const account =
        document.querySelector("input[name='account_type']:checked");

    if(account && account.value === "Parent"){

        showStep(2);

        document.getElementById("studentForm").style.display = "none";
        document.getElementById("parentForm").style.display = "block";

    }else{

        showStep(2);

document.getElementById("studentForm").style.display = "block";
document.getElementById("parentForm").style.display = "none";

toggleInputs("studentForm", false);
toggleInputs("parentForm", true);

    }

});

// ==========================================
// REVIEW PAGE
// ==========================================

function fillReview(){

    // -----------------------------
    // Account Type
    // -----------------------------

    const account =
        document.querySelector("input[name='account_type']:checked");

    document.getElementById("reviewAccountType").innerText =
        account ? account.value : "-";

    // -----------------------------
    // Student Level
    // -----------------------------

    const level =
        document.querySelector("input[name='student_level']:checked");

    document.getElementById("reviewStudentLevel").innerText =
        level ? level.value : "-";

    // -----------------------------
    // Student or Parent?
    // -----------------------------

    if(account && account.value === "Student"){

        document.getElementById("linkedStudentsCard").style.display = "none";

        document.getElementById("reviewFullName").innerText =
            document.getElementById("fullname").value;

        // Student Number or LRN

        let studentNumber = "-";

        if(level){

            if(level.value === "Basic Education"){

                studentNumber =
                    document.getElementById("lrn").value;

            }else if(account){

                studentNumber =
                    document.getElementById("student_no").value;

            }

        }

        document.getElementById("reviewStudentNumber").innerText =
            studentNumber || "-";

        // Course

        let course = "-";

        if(level){

            if(level.value === "College"){

                course =
                    document.getElementById("course").value;

            }

            if(level.value === "Alumni"){

                course =
                    document.getElementById("course_graduated").value;

            }

            if(level.value === "Basic Education"){

                course =
                    document.getElementById("grade_level").value;

            }

        }

        document.getElementById("reviewCourse").innerText =
            course || "-";

        // Year

        let year = "-";

        if(level){

            if(level.value === "College"){

                year =
                    document.getElementById("year_level").value;

            }

            if(level.value === "Alumni"){

                year =
                    document.getElementById("graduation_year").value;

            }

            if(level.value === "Basic Education"){

                year =
                    document.getElementById("section").value;

            }

        }

        document.getElementById("reviewYearLevel").innerText =
            year || "-";

        document.getElementById("reviewContact").innerText =
            document.getElementById("contact_no").value;

        document.getElementById("reviewEmail").innerText =
            document.getElementById("email").value;

    }

    // ===================================
    // PARENT REVIEW
    // ===================================

    else{

        document.getElementById("linkedStudentsCard").style.display = "block";

        document.getElementById("reviewFullName").innerText =
            document.getElementById("parent_name").value;

        document.getElementById("reviewStudentNumber").innerText = "-";

        document.getElementById("reviewCourse").innerText = "-";

        document.getElementById("reviewYearLevel").innerText = "-";

        document.getElementById("reviewContact").innerText =
            document.getElementById("parent_contact").value;

        document.getElementById("reviewEmail").innerText =
            document.getElementById("parent_email").value;

        loadLinkedStudents();

    }

}

// ==========================================
// LOAD LINKED STUDENTS
// ==========================================

function loadLinkedStudents(){

    const container =
        document.getElementById("reviewStudents");

    container.innerHTML = "";

    const studentNumbers =
        document.querySelectorAll("input[name='student_numbers[]']");

    const studentNames =
        document.querySelectorAll("input[name='student_names[]']");

    studentNames.forEach((student,index)=>{

        const card = document.createElement("div");

        card.className = "review-item";

        card.innerHTML = `
            <span>
                ${student.value || "-"}
            </span>

            <strong>
                ${studentNumbers[index].value || "-"}
            </strong>
        `;

        container.appendChild(card);

    });

}


// ==========================================
// ADD STUDENT
// ==========================================

const addStudentBtn =
    document.getElementById("addStudent");

if(addStudentBtn){

    addStudentBtn.addEventListener("click",()=>{

        const container =
            document.getElementById("studentContainer");

        const card = document.createElement("div");

        card.className = "student-card";

        card.innerHTML = `

            <div class="form-grid">

                <div class="form-group">

                    <label>Student Number</label>

                    <input
                        type="text"
                        name="student_numbers[]"
                        placeholder="Student Number">

                </div>

                <div class="form-group">

                    <label>Student Name</label>

                    <input
                        type="text"
                        name="student_names[]"
                        placeholder="Student Name">

                </div>

            </div>

        `;

        container.appendChild(card);

    });

}


// ==========================================
// SIMPLE VALIDATION
const registerForm = document.getElementById("registerForm");

if(registerForm){

    registerForm.addEventListener("submit",(e)=>{

    alert("Registered Successfully");


        const agree = document.getElementById("agreeTerms");

        if(!agree.checked){

            e.preventDefault();

            alert("Please agree to the Terms and Conditions.");

            return false;
        }

    });

}

// ==========================================
// INPUT HIGHLIGHT
// ==========================================

document.querySelectorAll("input,select").forEach(input=>{

    input.addEventListener("blur",()=>{

        if(input.value.trim() !== ""){

            input.classList.add("success");
            input.classList.remove("error");

        }else{

            input.classList.remove("success");

        }

    });

});


// ==========================================
// PASSWORD MATCH
// ==========================================

const password =
    document.getElementById("password");

const confirm =
    document.getElementById("confirm_password");

if(password && confirm){

    confirm.addEventListener("keyup",()=>{

        if(confirm.value===""){

            confirm.classList.remove("success");
            confirm.classList.remove("error");

            return;

        }

        if(password.value===confirm.value){

            confirm.classList.add("success");
            confirm.classList.remove("error");

        }else{

            confirm.classList.add("error");
            confirm.classList.remove("success");

        }

    });

}


// ==========================================
// PARENT PASSWORD MATCH
// ==========================================

const pPassword =
    document.getElementById("parent_password");

const pConfirm =
    document.getElementById("parent_confirm_password");

if(pPassword && pConfirm){

    pConfirm.addEventListener("keyup",()=>{

        if(pConfirm.value===""){

            pConfirm.classList.remove("success");
            pConfirm.classList.remove("error");

            return;

        }

        if(pPassword.value===pConfirm.value){

            pConfirm.classList.add("success");
            pConfirm.classList.remove("error");

        }else{

            pConfirm.classList.add("error");
            pConfirm.classList.remove("success");

        }

    });

}


// ==========================================
// PAGE LOAD
// ==========================================

showStep(0);