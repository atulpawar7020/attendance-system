<?php

session_start();

include("config/db.php");


if(!isset($_SESSION['teacher_id'])){

header("Location: login.php");
exit();

}


$id=$_SESSION['teacher_id'];



// Update Profile

if(isset($_POST['update'])){


$name=$_POST['full_name'];
$mobile=$_POST['mobile'];
$college=$_POST['college_name'];
$department=$_POST['department'];
$designation=$_POST['designation'];



$photo="";



if($_FILES['profile_photo']['name']!=""){


$photo=$_FILES['profile_photo']['name'];

$tmp=$_FILES['profile_photo']['tmp_name'];


move_uploaded_file(

$tmp,

"uploads/".$photo

);



mysqli_query($conn,

"UPDATE teachers SET

profile_photo='$photo'

WHERE id='$id'

");


}



mysqli_query($conn,


"UPDATE teachers SET

full_name='$name',

mobile='$mobile',

college_name='$college',

department='$department',

designation='$designation'

WHERE id='$id'

"

);



echo "

<script>

alert('Profile Updated Successfully');

window.location='teacher_profile.php';

</script>

";


}




// Get Data

$query=mysqli_query($conn,


"SELECT * FROM teachers WHERE id='$id'"

);


$data=mysqli_fetch_assoc($query);



?>



<!DOCTYPE html>

<html>

<head>

<title>Teacher Profile</title>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


<style>

body{

background:#f5f7fb;

}


.card{

border-radius:15px;

box-shadow:0 5px 15px #ddd;

}


.profile-img{

width:130px;

height:130px;

border-radius:50%;

object-fit:cover;

}


</style>


</head>


<body>


<div class="container mt-5">


<div class="card">


<div class="card-body">


<h3 class="mb-4">

Teacher Profile

</h3>



<form method="POST" enctype="multipart/form-data">



<div class="text-center">


<img src="uploads/<?php echo $data['profile_photo']; ?>"

class="profile-img mb-3">



<br>


<input type="file"

name="profile_photo"

class="form-control">


</div>



<hr>



<label>Name</label>

<input type="text"

name="full_name"

class="form-control"

value="<?php echo $data['full_name']; ?>">



<br>


<label>Email</label>

<input type="email"

class="form-control"

value="<?php echo $data['email']; ?>"

readonly>



<br>


<label>Mobile</label>

<input type="text"

name="mobile"

class="form-control"

value="<?php echo $data['mobile']; ?>">



<br>


<label>College Name</label>

<input type="text"

name="college_name"

class="form-control"

value="<?php echo $data['college_name']; ?>">



<br>


<label>Department</label>

<input type="text"

name="department"

class="form-control"

value="<?php echo $data['department']; ?>">



<br>


<label>Designation</label>

<input type="text"

name="designation"

class="form-control"

value="<?php echo $data['designation']; ?>">



<br>


<button name="update"

class="btn btn-success">

<i class="fa fa-save"></i>

Save Profile

</button>



<a href="classes.php"

class="btn btn-secondary">

Back

</a>



</form>



</div>

</div>


</div>



</body>

</html>