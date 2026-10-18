<div id="studentForm" style="display:none;">

    <div class="step-header">
        <h2>Student Information</h2>
        <p>Complete all required information.</p>
    </div>

    <!-- ==========================================
        COLLEGE SECTION CONTAINER
    ========================================== -->
    <div id="collegeSectionContainer">
        <div class="form-grid">
            <div class="form-group">
                <label>Student Number</label>
                <input type="text" name="student_no" id="student_no" placeholder="Enter Student Number">
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname_college" id="fullname_college" placeholder="Enter Full Name">
            </div>
            <div class="form-group">
                <label>Course</label>
                <select name="course" id="course">
                    <option value="" disabled selected>Select Program/Department</option>
                    <option value="BSIT">Bachelor of Science in Information Technology (BSIT)</option>
                    <option value="BSED">Bachelor of Secondary Education (BSED)</option>
                    <option value="BEED">Bachelor of Elementary Education (BEED)</option>
                    <option value="BSHM">Bachelor of Science in Hospitality Management (BSHM)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="student_status" id="student_status" onchange="toggleCollegeStatus()">
                    <option value="Enrolled" selected>Currently Enrolled</option>
                    <option value="Graduated">Graduated / Alumni</option>
                </select>
            </div>
            <!-- Year Level (Hidden automatically if Graduated) -->
        <div class="form-group" id="collegeYearLevelGroup">
    <label>Year Level</label>
    <select name="year_level" id="year_level" required>
        <option value="" disabled selected>Select Year Level</option>
        <option value="1st Year">1st Year</option>
        <option value="2nd Year">2nd Year</option>
        <option value="3rd Year">3rd Year</option>
        <option value="4th Year">4th Year</option>
    </select>
</div>

        <div class="form-group">
            <label id="collegeSchoolYearLabel">School Year</label>
            <select name="school_year" id="school_year">
                <option value="" disabled selected>Select School Year</option>
                <option value="2023-2024">2023–2024</option>
                <option value="2024-2025">2024–2025</option>
                <option value="2025-2026">2025–2026</option>
                <option value="2026-2027">2026–2027</option>
            </select>
        </div>
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact_no_college" id="contact_no_college" placeholder="09XXXXXXXXX">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email_college" id="email_college" placeholder="example@gmail.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password_college" id="password_college">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password_college" id="confirm_password_college">
            </div>
        </div>
    </div>


    <!-- ==========================================
        BASIC EDUCATION SECTION CONTAINER
    ========================================== -->
    <div id="basicSectionContainer" style="display:none;">
        <div class="form-grid">
            <div class="form-group">
                <label>LRN</label>
                <input type="text" name="lrn" id="lrn" placeholder="Enter Learner Reference Number">
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname_basic" id="fullname_basic" placeholder="Enter Full Name">
            </div>
            <div class="form-group">
                <label>Grade Level</label>
                <select name="grade_level" id="grade_level">
                    <option value="" disabled selected>Select Grade Level</option>
                    <option>Kindergarten</option>
                    <option>Grade 1</option>
                    <option>Grade 2</option>
                    <option>Grade 3</option>
                    <option>Grade 4</option>
                    <option>Grade 5</option>
                    <option>Grade 6</option>
                    <option>Grade 7</option>
                    <option>Grade 8</option>
                    <option>Grade 9</option>
                    <option>Grade 10</option>
                    <option>Grade 11</option>
                    <option>Grade 12</option>
                </select>
            </div>
            <div class="form-group">
                <label>Section</label>
                <input type="text" name="section" id="section" placeholder="Enter Section">
            </div>
            <div class="form-group">
                <label>School Year</label>
                <select name="school_year_basic" id="school_year_basic">
                    <option value="" disabled selected>Select School Year</option>
                    <option value="2023-2024">2023–2024</option>
                    <option value="2024-2025">2024–2025</option>
                    <option value="2025-2026">2025–2026</option>
                    <option value="2026-2027">2026–2027</option>
                </select>
            </div>
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact_no_basic" id="contact_no_basic" placeholder="09XXXXXXXXX">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email_basic" id="email_basic" placeholder="example@gmail.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password_basic" id="password_basic">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password_basic" id="confirm_password_basic">
            </div>
        </div>
    </div>


    <div class="step-buttons">
        <button type="button" class="btn-secondary" id="backToStep2">
            <i class="fa-solid fa-arrow-left"></i> Back
        </button>

        <button type="button" class="btn-next" id="toStep4">
            Next <i class="fa-solid fa-arrow-right"></i>
        </button>
    </div>

</div>
<script>
// Define the function globally so the HTML onchange="toggleCollegeStatus()" can find it
function toggleCollegeStatus() {
    const statusSelect = document.getElementById('student_status');
    const yearLevelGroup = document.getElementById('collegeYearLevelGroup');
    const yearLevelSelect = document.getElementById('year_level');
    const schoolYearLabel = document.getElementById('collegeSchoolYearLabel');

    if (!statusSelect) return;

    const val = statusSelect.value;
    console.log("Selected Status:", val);

    if (val === 'Graduated' || val.toLowerCase().includes('graduated')) {
        if (yearLevelGroup) {
            yearLevelGroup.style.setProperty('display', 'none', 'important');
        }
        if (yearLevelSelect) {
            yearLevelSelect.required = false;
            yearLevelSelect.value = '';
        }
        if (schoolYearLabel) {
            schoolYearLabel.textContent = 'Graduation School Year';
        }
    } else {
        if (yearLevelGroup) {
            yearLevelGroup.style.removeProperty('display');
        }
        if (yearLevelSelect) {
            yearLevelSelect.required = true;
        }
        if (schoolYearLabel) {
            schoolYearLabel.textContent = 'School Year';
        }
    }
}

// Run once on page load to set the initial state
document.addEventListener("DOMContentLoaded", function() {
    toggleCollegeStatus();
});
</script>