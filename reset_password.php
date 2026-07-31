<?php

include("config/db.php");

session_start();


if(!isset($_SESSION['reset_email'])){

header("Location: login.php");
exit();

}


$email = $_SESSION['reset_email'];

$message="";


if(isset($_POST['submit'])){


$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];



if($password != $confirm_password){


$message = "Password and Confirm Password do not match";


}

else{


$query = mysqli_query($conn,

"UPDATE teachers 
SET password='$password'
WHERE email='$email'

");



if($query){


unset($_SESSION['reset_email']);


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


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">



<style>

body{

background:#f5f7fb;

}


.card{

width:400px;

margin:100px auto;

padding:30px;

border-radius:15px;

box-shadow:0 5px 15px rgba(0,0,0,.1);

}


input{

height:50px;

}


.btn{

height:50px;

}


</style>


</head>


<body>



<div class="card">


<h2 class="text-center mb-4">

Create New Password

</h2>



<?php

if($message!=""){

echo "

<p class='text-danger text-center'>

$message

</p>

";

}

?>



<form method="POST">


<input 

type="password"

name="password"

class="form-control mb-3"

placeholder="New Password"

required>



<input 

type="password"

name="confirm_password"

class="form-control mb-3"

placeholder="Confirm Password"

required>



<button 

name="submit"

class="btn btn-success w-100">

Reset Password

</button>



</form>


</div>



</body>

</html>