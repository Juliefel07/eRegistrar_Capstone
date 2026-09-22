<div id="parentForm" style="display:none;">

    <div class="step-header">

        <h2>Parent / Guardian Information</h2>

        <p>
            Register as a parent or guardian. You may link one or more students.
        </p>

    </div>


    <div class="form-grid">

        <!-- Parent Name -->

        <div class="form-group">

            <label>Full Name</label>

            <input
                type="text"
                name="parent_name"
                id="parent_name"
                placeholder="Enter Full Name">

        </div>


        <!-- Contact -->

        <div class="form-group">

            <label>Contact Number</label>

            <input
                type="text"
                name="parent_contact"
                id="parent_contact"
                placeholder="09XXXXXXXXX">

        </div>


        <!-- Email -->

        <div class="form-group">

            <label>Email Address</label>

            <input
                type="email"
                name="parent_email"
                id="parent_email"
                placeholder="example@gmail.com">

        </div>


        <!-- Password -->

        <div class="form-group">

            <label>Password</label>

<input
type="password"
name="parent_password"
id="parent_password"
>

        </div>


        <!-- Confirm Password -->

        <div class="form-group">

            <label>Confirm Password</label>

<input
type="password"
name="parent_confirm_password"
id="parent_confirm_password"
>

        </div>

    </div>


    <hr class="divider">


    <h3>Linked Student(s)</h3>

    <p class="student-note">
        Add the student(s) you are representing.
    </p>


    <div id="studentContainer">

        <div class="student-card">

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

        </div>

    </div>


    <button
        type="button"
        class="btn-add-student"
        id="addStudent">

        <i class="fa-solid fa-plus"></i>

        Add Another Student

    </button>


    <div class="step-buttons">

        <button
            type="button"
            class="btn-secondary"
            id="parentBack">

            <i class="fa-solid fa-arrow-left"></i>

            Back

        </button>


        <button
            type="button"
            class="btn-next"
            id="parentNext">

            Next

            <i class="fa-solid fa-arrow-right"></i>

        </button>

    </div>

</div>