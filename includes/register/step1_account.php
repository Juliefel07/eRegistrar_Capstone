<div class="step-header">

    <h2>Create Your Account</h2>

    <p>
        Choose the account type that best describes you.
    </p>

</div>


<div class="account-selection">

    <!-- STUDENT -->

    <label class="account-card">

        <input
            type="radio"
            name="account_type"
            value="Student"
            required>

        <div class="card-content">

            <div class="icon">
                <i class="fa-solid fa-user-graduate"></i>
            </div>

            <div class="card-text">
                <h3>I'm a Student</h3>

                <p>
                    Register as a currently enrolled student,
                    basic education learner, or college student.
                </p>
            </div>

            <div class="card-arrow">
                
            </div>

        </div>

    </label>


    <!-- PARENT -->

    <label class="account-card">

        <input
            type="radio"
            name="account_type"
            value="Parent">

        <div class="card-content">

            <div class="icon">
                <i class="fa-solid fa-people-roof"></i>
            </div>

            <div class="card-text">
                <h3>I'm a Parent / Guardian</h3>

                <p>
                    Register to request and monitor documents
                    for your child or children.
                </p>
            </div>

            <div class="card-arrow">
                
            </div>

        </div>

    </label>

</div>

<div class="login-link">
    <p>
        Already have an account?
        <a href="login.php">Log In</a>
    </p>
</div>

<div class="step-buttons">

    <a href="index.php" class="btn-back">

        <i class="fa-solid fa-arrow-left"></i>

        Back

    </a>


    <button
        type="button"
        class="btn-next"
        id="toStep2">

        Next

        <i class="fa-solid fa-arrow-right"></i>

    </button>

</div>