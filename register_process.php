

<?php
session_start();
include "includes/db.php";
require_once "includes/send_verification.php";

// ===============================
// ACCOUNT TYPE
// ===============================

$account_type = $_POST['account_type'] ?? '';


// ===============================
// STUDENT
// ===============================

if($account_type == "Student"){


    $student_level = $_POST['student_level'] ?? '';

    $student_no = $_POST['student_no'] ?? null;

    $lrn = $_POST['lrn'] ?? null;

    $fullname = $_POST['fullname'] ?? '';

    $course = $_POST['course'] ?? null;

    $course_graduated = $_POST['course_graduated'] ?? null;

    $graduation_year = $_POST['graduation_year'] ?? null;

    $grade_level = $_POST['grade_level'] ?? null;

    $section = $_POST['section'] ?? null;

    $year_level = $_POST['year_level'] ?? null;

    $contact_no = $_POST['contact_no'] ?? '';

    $email = $_POST['email'] ?? '';

    $password = $_POST['password'] ?? '';

    $confirm_password = $_POST['confirm_password'] ?? '';



    if($password != $confirm_password){

        $_SESSION['error']="Passwords do not match.";
        header("Location: register.php");
        exit();

    }



    $check = mysqli_query(
        $conn,
        "SELECT user_id FROM users WHERE email='$email'"
    );


    if(mysqli_num_rows($check)>0){

        $_SESSION['error']="Email already exists.";
        header("Location: register.php");
        exit();

    }



    $hash = password_hash($password,PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));


    $sql="
    INSERT INTO users
    (
    account_type,
    student_level,
    student_no,
    lrn,
    fullname,
    course,
    course_graduated,
    graduation_year,
    grade_level,
    section,
    year_level,
    contact_no,
    email,
    password,
    role,
    verification_token,
    is_verified
    )

    VALUES

    (
    'Student',
    '$student_level',
    '$student_no',
    '$lrn',
    '$fullname',
    '$course',
    '$course_graduated',
    '$graduation_year',
    '$grade_level',
    '$section',
    '$year_level',
    '$contact_no',
    '$email',
    '$hash',
    'Student',
    '$token',
    0
    )
    ";



if (mysqli_query($conn, $sql)) {

    sendVerificationEmail($email, $fullname, $token);

} else {

    die("Student Error: " . mysqli_error($conn));

}



}




// ===============================
// PARENT
// ===============================

if ($account_type == "Parent") {

    $fullname   = trim($_POST['parent_name'] ?? '');
    $contact_no = trim($_POST['parent_contact'] ?? '');
    $email      = trim($_POST['parent_email'] ?? '');
    $password   = $_POST['parent_password'] ?? '';
    $confirm    = $_POST['parent_confirm_password'] ?? '';

    // Validate required fields
    if (
        empty($fullname) ||
        empty($contact_no) ||
        empty($email) ||
        empty($password) ||
        empty($confirm)
    ) {
        $_SESSION['error'] = "Please complete all required fields.";
        header("Location: register.php");
        exit();
    }

    // Password check
    if ($password != $confirm) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: register.php");
        exit();
    }

    // Check duplicate email
    $check = mysqli_query(
        $conn,
        "SELECT user_id FROM users WHERE email='$email'"
    );

    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error'] = "Email already exists.";
        header("Location: register.php");
        exit();
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));
    // Insert parent
    $insertParent = mysqli_query(
        $conn,
        "
        INSERT INTO users
        (
account_type,
fullname,
contact_no,
email,
password,
role,
verification_token,
is_verified
        )
        VALUES
        (
'Parent',
'$fullname',
'$contact_no',
'$email',
'$hash',
'Parent',
'$token',
0
        )
        "
    );

if (!$insertParent) {
    die("Parent Insert Error: " . mysqli_error($conn));
}

sendVerificationEmail($email, $fullname, $token);

$parent_id = mysqli_insert_id($conn);

    // Save linked students
    if (isset($_POST['student_names']) && isset($_POST['student_numbers'])) {

        foreach ($_POST['student_names'] as $key => $name) {

            $name = trim($name);
            $number = trim($_POST['student_numbers'][$key]);

            // Skip blank rows
            if ($name == "" && $number == "") {
                continue;
            }

if (!mysqli_query(
    $conn,
    "
    INSERT INTO parent_students
    (
        parent_id,
        student_number,
        student_name
    )
    VALUES
    (
        '$parent_id',
        '$number',
        '$name'
    )
    "
)) {
    die("Parent Student Error: " . mysqli_error($conn));
}
        }
    }
}



$_SESSION['success']="Registration successful.";

header("Location: login.php");

exit();


?>