<?php

session_start();

include("config/db.php");



if(!isset($_SESSION['otp_verified']))
{

header("Location: forgot_password.php");

exit();

}



$email=$_SESSION['reset_email'];

$message="";



if(isset($_POST['submit']))
{


$password=$_POST['password'];

$confirm=$_POST['confirm_password'];



if($password != $confirm)
{

$message="Password not match";

}

else
{


$password=password_hash(
$password,
PASSWORD_DEFAULT
);



$update=mysqli_query($conn,

"UPDATE teachers

SET password='$password'

WHERE email='$email'"

);



if($update)
{


unset($_SESSION['reset_email']);

unset($_SESSION['otp_verified']);



echo "

<script>

alert('Password Changed Successfully');

window.location='login.php';

</script>

";


}


}



}

?>



<!DOCTYPE html>

<html>

<head>

<title>Reset Password</title>


<link 
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


</head>


<body>


<div class="container mt-5">


<div class="card shadow p-4 mx-auto"
style="max-width:400px;">


<h3 class="text-center">

Create New Password

</h3>



<form method="POST">


<input type="password"

name="password"

class="form-control mb-3"

placeholder="New Password"

required>



<input type="password"

name="confirm_password"

class="form-control mb-3"

placeholder="Confirm Password"

required>



<button name="submit"

class="btn btn-success w-100">

Reset Password

</button>


</form>


<p class="text-danger">

<?php echo $message; ?>

</p>


</div>


</div>


</body>

</html>