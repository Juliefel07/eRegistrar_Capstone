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


    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $year_level = mysqli_real_escape_string($conn, $_POST['year_level']);
    $contact_no = mysqli_real_escape_string($conn, $_POST['contact_no']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);


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

    fullname='$fullname',
    course='$course',
    year_level='$year_level',
    contact_no='$contact_no',
    email='$email',
    profile_image='$profile_image'

    WHERE user_id='$user_id'

    "

    );



if($update){

    $_SESSION['fullname'] = $fullname;
    $_SESSION['profile_image'] = $profile_image; // <-- Add this line

    $_SESSION['success'] = "Profile updated successfully.";

    header("Location: profile.php");

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

<title>Edit Profile</title>


<link rel="stylesheet" href="../assets/css/student.css">


<style>

.profile-avatar img{

    width:100%;
    height:100%;
    object-fit:cover;
    border-radius:50%;

}


.profile-avatar{

    overflow:hidden;

}


</style>


</head>



<body>



<?php include("sidebar.php"); ?>


<?php include("header.php"); ?>




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
name="fullname"
value="<?php echo htmlspecialchars($user['fullname']); ?>"
required>


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
name="email"
value="<?php echo htmlspecialchars($user['email']); ?>"
required>


</div>




</div>







<button 
type="submit"
name="update"
class="profile-btn">

Save Changes

</button>




<a href="profile.php" class="profile-btn">

Cancel

</a>




</form>




</div>


</div>



</body>

</html>