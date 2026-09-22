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

        <!-- Course -->

        <div class="form-group college-only">

            <label>Course</label>

            <input
                type="text"
                name="course"
                id="course"
                placeholder="Program/Department">

        </div>

        <!-- Graduation Course -->

        <div class="form-group alumni-only" style="display:none;">

            <label>Course Graduated</label>

            <input
                type="text"
                name="course_graduated"
                id="course_graduated"
                placeholder="Course Graduated">

        </div>

        <!-- Year Level -->

        <div class="form-group college-only">

            <label>Year Level</label>

            <select
                name="year_level"
                id="year_level">

                <option value="">Select Year Level</option>
                <option>1st Year</option>
                <option>2nd Year</option>
                <option>3rd Year</option>
                <option>4th Year</option>

            </select>

        </div>

        <!-- Grade Level -->

        <div class="form-group basic-only" style="display:none;">

            <label>Grade Level</label>

            <select
                name="grade_level"
                id="grade_level">

                <option value="">Select Grade Level</option>

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

        <!-- Graduation Year -->

        <div class="form-group alumni-only" style="display:none;">

            <label>Graduation Year</label>

            <input
                type="number"
                name="graduation_year"
                id="graduation_year"
                min="1980"
                max="2100">

        </div>

        <!-- Contact -->

        <div class="form-group">

            <label>Contact Number</label>

            <input
                type="text"
                name="contact_no"
                id="contact_no"
                placeholder="09XXXXXXXXX"
                >

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
                id="password"
                >

        </div>

        <!-- Confirm Password -->

        <div class="form-group">

            <label>Confirm Password</label>

            <input
                type="password"
                name="confirm_password"
                id="confirm_password"
                >

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