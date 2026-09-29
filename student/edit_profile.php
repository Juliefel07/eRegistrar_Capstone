<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}


$user_id = $_SESSION['user_id'];



// Get current user data

$result = mysqli_query($conn,

"
SELECT * 
FROM users
WHERE user_id='$user_id'
"

);


$user = mysqli_fetch_assoc($result);




// Update profile

if(isset($_POST['update'])){


    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $year_level = mysqli_real_escape_string($conn, $_POST['year_level']);
    $contact_no = mysqli_real_escape_string($conn, $_POST['contact_no']);
  
    $birthdate = mysqli_real_escape_string($conn, $_POST['birthdate']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $guardian_name = mysqli_real_escape_string($conn, $_POST['guardian_name']);
    $emergency_contact = mysqli_real_escape_string($conn, $_POST['emergency_contact']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);


    // Keep old image

    $profile_image = $user['profile_image'];



    // Upload new image

    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0){


        $file = $_FILES['profile_image'];

        $extension = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );


        $allowed = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];



        if(in_array($extension,$allowed)){


            $newName = "profile_".$user_id."_".time().".".$extension;


            $uploadPath = "uploads/".$newName;



            if(move_uploaded_file($file['tmp_name'], $uploadPath)){


                $profile_image = $newName;


            }


        }


    }



    $update = mysqli_query($conn,

    "

    UPDATE users SET

    course='$course',
    year_level='$year_level',
    contact_no='$contact_no',
    
    birthdate='$birthdate',
    gender='$gender',
    address='$address',
    guardian_name='$guardian_name',
    emergency_contact='$emergency_contact',
    bio='$bio',
    profile_image='$profile_image'

    WHERE user_id='$user_id'

    "

    );



if($update){

    $_SESSION['profile_image'] = $profile_image;

    $_SESSION['success'] = "Profile updated successfully.";

  echo "
<script>
    window.parent.location.href='profile.php';
</script>";
exit();

}else{


        echo mysqli_error($conn);


    }


}


?>













<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Profile</title>

    <link rel="stylesheet" href="../assets/css/edit.css">

</head>

<body>

<div class="student-main">



<div class="profile-card">



<div class="profile-header">



<div class="profile-avatar">


<?php if(!empty($user['profile_image'])){ ?>


<img 
src="uploads/<?php echo htmlspecialchars($user['profile_image']); ?>"
alt="Profile">


<?php }else{ ?>


<?php echo strtoupper(substr($user['fullname'],0,1)); ?>


<?php } ?>


</div>




<h2>
Edit Profile
</h2>


<p>
Update your student information
</p>



</div>







<form 
method="POST"
enctype="multipart/form-data">





<div class="profile-item">


<label>
Change Profile Picture
</label>


<input 
type="file"
name="profile_image"
accept="image/*">


</div>







<div class="profile-details">



<div class="profile-item">

<label>
Student Number
</label>


<p>
<?php echo htmlspecialchars($user['student_no']); ?>
</p>


</div>





<div class="profile-item">

<label>
Full Name
</label>


<input 
type="text"
value="<?php echo htmlspecialchars($user['fullname']); ?>"
readonly>


</div>







<div class="profile-item">

<label>
Course
</label>


<input 
type="text"
name="course"
value="<?php echo htmlspecialchars($user['course']); ?>"
required>


</div>







<div class="profile-item">

<label>
Year Level
</label>


<input 
type="text"
name="year_level"
value="<?php echo htmlspecialchars($user['year_level']); ?>"
required>


</div>







<div class="profile-item">

<label>
Contact Number
</label>


<input 
type="text"
name="contact_no"
value="<?php echo htmlspecialchars($user['contact_no']); ?>"
required>


</div>







<div class="profile-item">

<label>
Email Address
</label>


<input
    type="email"
    value="<?php echo htmlspecialchars($user['email']); ?>"
    readonly>



</div>


<div class="profile-item">
    <label>Birthdate</label>

    <input
        type="date"
        name="birthdate"
        value="<?php echo htmlspecialchars($user['birthdate']); ?>">
</div>

<div class="profile-item">
    <label>Gender</label>

    <select name="gender">

        <option value="">Select Gender</option>

        <option value="Male"
            <?php if($user['gender']=="Male") echo "selected"; ?>>
            Male
        </option>

        <option value="Female"
            <?php if($user['gender']=="Female") echo "selected"; ?>>
            Female
        </option>

    </select>

</div>
<div class="profile-item">

    <label>Address</label>

    <textarea
        name="address"
        rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>

</div>

<div class="profile-item">

    <label>Guardian Name</label>

    <input
        type="text"
        name="guardian_name"
        value="<?php echo htmlspecialchars($user['guardian_name']); ?>">

</div>

<div class="profile-item">

    <label>Emergency Contact</label>

    <input
        type="text"
        name="emergency_contact"
        value="<?php echo htmlspecialchars($user['emergency_contact']); ?>">

</div>

<div class="profile-item">

    <label>Bio</label>

    <textarea
        name="bio"
        rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea>

</div>

</div>







<div class="button-group">


    <button
        type="submit"
        name="update"
        class="save-btn">
        Save Changes
    </button>
    <a href="profile.php" class="cancel-btn">
        Cancel
    </a>



</div>




</form>




</div>


</div>


</body>
</html>