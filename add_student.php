<?php

session_start();
include("config/db.php");


if(!isset($_SESSION['teacher_id'])){
    header("Location: login.php");
    exit();
}


if(!isset($_GET['class_id'])){
    die("Class ID Missing");
}


$class_id = $_GET['class_id'];


// Get Class Details

$classQuery = mysqli_query($conn,
"SELECT * FROM classes WHERE id='$class_id'");


$class = mysqli_fetch_assoc($classQuery);

$invite_link = "http://localhost/attendance-system/join_class.php?code=" . $class['invite_code'];


if(!$class){
    die("Class Not Found");
}



// Add Student

if(isset($_POST['add_student'])){


    $roll_no = $_POST['roll_no'];
    $full_name = $_POST['full_name'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];


    // Duplicate Roll Check

    $check = mysqli_query($conn,
    "SELECT * FROM students 
    WHERE class_id='$class_id'
    AND roll_no='$roll_no'");


    if(mysqli_num_rows($check)>0){

        echo "<script>
        alert('This Roll Number already exists in this class');
        </script>";

    }
    else{


        $class_name = $class['class_name'];
        $subject = $class['subject'];



        $insert = mysqli_query($conn,

        "INSERT INTO students
        (
        class_id,
        roll_no,
        full_name,
        mobile,
        email,
        class_name,
        subject
        )

        VALUES

        (
        '$class_id',
        '$roll_no',
        '$full_name',
        '$mobile',
        '$email',
        '$class_name',
        '$subject'
        )"

        );



        if($insert){

            echo "<script>

            alert('Student Added Successfully');

            window.location='open-class.php?class_id=$class_id';

            </script>";

        }
        else{

            echo mysqli_error($conn);

        }


    }


}



?>


<!DOCTYPE html>
<html>

<head>

<title>Add Student</title>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


<style>

body{

background:#f4f7fc;

}


.card{

border:none;
border-radius:20px;
box-shadow:0 5px 15px rgba(0,0,0,.15);

}


</style>


</head>


<body>


<div class="container mt-5">


<div class="card">


<div class="card-header bg-primary text-white">


<h3>
👨‍🎓 Add Student
</h3>


<h6>
Class :
<?php echo $class['class_name']; ?>

<br>

Subject :
<?php echo $class['subject']; ?>

</h6>


</div>



<div class="card-body">


<form method="POST">


<div class="mb-3">

<label>Roll Number</label>

<input type="text"
name="roll_no"
class="form-control"
required>

</div>



<div class="mb-3">

<label>Full Name</label>

<input type="text"
name="full_name"
class="form-control"
required>

</div>



<div class="mb-3">

<label>Mobile Number</label>

<input type="text"
name="mobile"
class="form-control">

</div>



<div class="mb-3">

<label>Email</label>

<input type="email"
name="email"
class="form-control">

</div>
<br>


<br>

<button type="submit"
name="add_student"
class="btn btn-success">

Save Student

</button>


<a href="open-class.php?class_id=<?php echo $class_id; ?>"
class="btn btn-secondary">

Back

</a>



<hr>

<div class="card mt-4">

<div class="card-header bg-success text-white">

<h5 class="mb-0">

🔗 Invite Students

</h5>

</div>

<div class="card-body">

<label class="form-label">

Invite Link

</label>

<div class="input-group">

<input
type="text"
id="inviteLink"
class="form-control"
value="<?php echo $invite_link; ?>"
readonly>

<button
class="btn btn-primary"
type="button"
onclick="copyInviteLink()">

📋 Copy Link

</button>

</div>

<small class="text-muted">

Copy this link and send it to students.

</small>

</div>

</div>



</form>


</div>

</div>

</div>


</body>

</html>
