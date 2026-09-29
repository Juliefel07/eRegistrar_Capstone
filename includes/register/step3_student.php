<div id="studentForm" style="display:none;">

    <div class="step-header">

        <h2>Student Information</h2>

        <p>
            Complete all required information.
        </p>

    </div>

    <div class="form-grid">

        <!-- Student Number -->

        <div class="form-group student-no-group">

            <label>Student Number</label>

            <input
                type="text"
                name="student_no"
                id="student_no"
                placeholder="Enter Student Number">

        </div>

        <!-- LRN -->

        <div class="form-group lrn-group" style="display:none;">

            <label>LRN</label>

            <input
                type="text"
                name="lrn"
                id="lrn"
                placeholder="Enter Learner Reference Number">

        </div>

        <!-- Full Name -->

        <div class="form-group">

            <label>Full Name</label>

            <input
                type="text"
                name="fullname"
                id="fullname"
                placeholder="Enter Full Name">

        </div>

        <!-- Course (Updated to Dropdown) -->

        <div class="form-group college-only">

            <label>Course</label>

            <select
                name="course"
                id="course">

                <option value="" disabled selected>Select Program/Department</option>
                <option value="BSIT">Bachelor of Science in Information Technology (BSIT)</option>
                <option value="BSED">Bachelor of Secondary Education (BSED)</option>
                <option value="BEED">Bachelor of Elementary Education (BEED)</option>
                <option value="BSHM">Bachelor of Science in Hospitality Management (BSHM)</option>

            </select>

        </div>

        <!-- Status (College Only) -->

        <div class="form-group college-only">

            <label>Status</label>

            <select
                name="student_status"
                id="student_status"
                onchange="toggleStudentStatus()">

                <option value="Enrolled" selected>Currently Enrolled</option>
                <option value="Graduated">Graduated / Alumni</option>

            </select>

        </div>

        <!-- Year Level -->

        <div class="form-group college-only" id="year_level_group">

            <label>Year Level</label>

            <select
                name="year_level"
                id="year_level">

                <option value="" disabled selected>Select Year Level</option>
                <option value="1st Year">1st Year</option>
                <option value="2nd Year">2nd Year</option>
                <option value="3rd Year">3rd Year</option>
                <option value="4th Year">4th Year</option>

            </select>

        </div>

        <!-- School Year -->

        <div class="form-group">

            <label id="school_year_label">School Year</label>

            <select
                name="school_year"
                id="school_year"
                required>

                <option value="" disabled selected>Select School Year</option>
                <option value="2023-2024">2023–2024</option>
                <option value="2024-2025">2024–2025</option>
                <option value="2025-2026">2025–2026</option>
                <option value="2026-2027">2026–2027</option>

            </select>

        </div>

        <!-- Grade Level -->

        <div class="form-group basic-only" style="display:none;">

            <label>Grade Level</label>

            <select
                name="grade_level"
                id="grade_level">

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

        <!-- Section -->

        <div class="form-group basic-only" style="display:none;">

            <label>Section</label>

            <input
                type="text"
                name="section"
                id="section"
                placeholder="Enter Section">

        </div>

        <!-- Contact -->

        <div class="form-group">

            <label>Contact Number</label>

            <input
                type="text"
                name="contact_no"
                id="contact_no"
                placeholder="09XXXXXXXXX">

        </div>

        <!-- Email -->

        <div class="form-group">

            <label>Email Address</label>

            <input
                type="email"
                name="email"
                id="email"
                placeholder="example@gmail.com">

        </div>

        <!-- Password -->

        <div class="form-group">

            <label>Password</label>

            <input
                type="password"
                name="password"
                id="password">

        </div>

        <!-- Confirm Password -->

        <div class="form-group">

            <label>Confirm Password</label>

            <input
                type="password"
                name="confirm_password"
                id="confirm_password">

        </div>

    </div>

    <div class="step-buttons">

        <button
            type="button"
            class="btn-secondary"
            id="backToStep2">

            <i class="fa-solid fa-arrow-left"></i>

            Back

        </button>

        <button
            type="button"
            class="btn-next"
            id="toStep4">

            Next

            <i class="fa-solid fa-arrow-right"></i>

        </button>

    </div>

</div>

<script>
function toggleStudentStatus() {
    const status = document.getElementById('student_status').value;
    const yearLevelGroup = document.getElementById('year_level_group');
    const schoolYearLabel = document.getElementById('school_year_label');

    if (status === 'Graduated') {
        yearLevelGroup.style.display = 'none';
        schoolYearLabel.textContent = 'Graduation School Year';
    } else {
        yearLevelGroup.style.display = 'block';
        schoolYearLabel.textContent = 'School Year';
    }
}
</script>