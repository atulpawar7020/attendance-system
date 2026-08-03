<?php

session_start();

include("config/db.php");
include("config/mail.php");


$message="";


if(isset($_POST['signup']))
{


$fullname=mysqli_real_escape_string($conn,$_POST['fullname']);

$email=mysqli_real_escape_string($conn,$_POST['email']);

$mobile=mysqli_real_escape_string($conn,$_POST['mobile']);

$college=mysqli_real_escape_string($conn,$_POST['college']);

$department=mysqli_real_escape_string($conn,$_POST['department']);

$designation=mysqli_real_escape_string($conn,$_POST['designation']);

$password=password_hash($_POST['password'],PASSWORD_DEFAULT);



// Check email already exists

$check=mysqli_query($conn,

"SELECT id FROM teachers WHERE email='$email'"

);



if(mysqli_num_rows($check)>0)
{

$message="
<div class='alert alert-danger'>
Email already registered
</div>";

}

else
{


// Generate OTP

$otp=rand(100000,999999);


// Expire 5 minutes

$expire=date(
"Y-m-d H:i:s",
strtotime("+5 minutes")
);



// Delete old OTP

mysqli_query($conn,

"DELETE FROM signup_verifications 
WHERE email='$email'"

);



// Save temporary data

$insert=mysqli_query($conn,

"INSERT INTO signup_verifications

(
full_name,
email,
mobile,
college_name,
department,
designation,
password,
otp,
expires_at
)

VALUES

(
'$fullname',
'$email',
'$mobile',
'$college',
'$department',
'$designation',
'$password',
'$otp',
'$expire'
)

"

);



if($insert)
{


if(sendOTP($email,$otp))
{


$_SESSION['signup_email']=$email;



header("Location: verify_signup_otp.php");

exit();


}
else
{

$message="
<div class='alert alert-danger'>
OTP send failed
</div>";

}


}
else
{

$message="
<div class='alert alert-danger'>
Database Error : ".mysqli_error($conn)."
</div>";

}


}



}



?>



<!DOCTYPE html>

<html>

<head>


<title>Create Teacher Account</title>


<meta name="viewport" content="width=device-width, initial-scale=1">


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">



<style>


body{

background:#f5f7fb;

}



.card{

border:none;

border-radius:15px;

box-shadow:0 5px 15px rgba(0,0,0,.1);

}



.card-header{

background:#0d6efd;

color:white;

border-radius:15px 15px 0 0 !important;

}



.form-control{

height:45px;

border-radius:8px;

}



.form-label{

font-weight:600;

}



.btn{

height:45px;

border-radius:8px;

font-size:16px;

}



</style>


</head>



<body>



<div class="container mt-5">


<div class="row justify-content-center">


<div class="col-md-6">



<div class="card">



<div class="card-header">


<h4>

<i class="fa fa-user-plus"></i>

Create Teacher Account

</h4>


</div>




<div class="card-body">



<?php echo $message; ?>



<form method="POST">



<div class="mb-3">

<label class="form-label">
Full Name
</label>


<input 
type="text"
name="fullname"
class="form-control"
placeholder="Enter Full Name"
required>


</div>




<div class="mb-3">


<label class="form-label">
Email
</label>


<input 
type="email"
name="email"
class="form-control"
placeholder="Enter Email"
required>


</div>





<div class="mb-3">


<label class="form-label">
Mobile
</label>


<input 
type="text"
name="mobile"
class="form-control"
placeholder="Enter Mobile Number">


</div>





<div class="mb-3">


<label class="form-label">
College
</label>


<input 
type="text"
name="college"
class="form-control"
placeholder="Enter College Name">


</div>





<div class="mb-3">


<label class="form-label">
Department
</label>


<input 
type="text"
name="department"
class="form-control"
placeholder="Enter Department">


</div>





<div class="mb-3">


<label class="form-label">
Designation
</label>


<input 
type="text"
name="designation"
class="form-control"
placeholder="Teacher / Professor">


</div>





<div class="mb-3">


<label class="form-label">
Password
</label>


<input 
type="password"
name="password"
class="form-control"
placeholder="Create Password"
required>


</div>





<button 
type="submit"
name="signup"
class="btn btn-primary w-100">


<i class="fa fa-user-plus"></i>

Create Account


</button>




<a href="login.php"
class="btn btn-secondary w-100 mt-3">


<i class="fa fa-arrow-left"></i>

Back to Login


</a>



</form>



</div>



</div>


</div>


</div>


</div>



</body>

</html>