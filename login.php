<?php
session_start();

include("config/db.php");

if(isset($_SESSION['teacher_id'])){
    header("Location: classes.php");
    exit();
}

$error="";


if(isset($_POST['login'])){


$email = $_POST['email'];
$password = $_POST['password'];


$query=mysqli_query($conn,

"SELECT * FROM teachers WHERE email='$email'"

);


if(mysqli_num_rows($query)>0)
{

$row=mysqli_fetch_assoc($query);


if(password_verify($password,$row['password']))
{

$_SESSION['teacher_id']=$row['id'];

header("Location: classes.php");
exit();

}
else
{

echo "Wrong Password";

}


}

    else{


        $error="Invalid Email or Password";


    }


}

?>



<!DOCTYPE html>
<html>

<head>

<title>Teacher Login</title>


<link rel="stylesheet" href="assets/css/login.css">


<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">




<style>




.password-box{

position:relative;

}


.password-box input{

width:100%;

padding-right:45px;

}


.password-box i{

position:absolute;

right:15px;

top:45px;

cursor:pointer;

color:#555;

}


</style>


</head>





<body>

<br><br>

<div class="container">



<div class="left">


<h1>🎓 Smart Attendance</h1>


<p>

Attendance Management System

<br><br>

✔ Manage Classes

<br>

✔ Track Attendance

<br>

✔ Generate Reports

<br>

✔ Student Registration


</p>


</div>




<div class="right">



<h2>Teacher Login</h2>




<?php

if($error!=""){

echo "<p style='color:red;text-align:center;'>$error</p>";

}

?>





<form method="POST">



<div class="input-group">


<label>Email</label>


<input 

type="email"

name="email"

required>


</div>





<div class="input-group password-box">


<label>Password</label>


<input 

type="password"

name="password"

id="password"

required>


<i class="fa fa-eye" 

id="togglePassword"></i>


</div>





<button class="btn" name="login">

Login

</button>




</form>




<div class="links">


<br>


<a href="forgot_password.php">

Forgot Password?

</a>


<br><br>


Don't have an account?


<br>


<a href="signup.php">

Create Account

</a>



</div>



</div>


</div>






<script>


const togglePassword = document.querySelector("#togglePassword");


const password = document.querySelector("#password");



togglePassword.onclick=function(){



if(password.type==="password"){


password.type="text";


this.classList.remove("fa-eye");


this.classList.add("fa-eye-slash");


}

else{


password.type="password";


this.classList.remove("fa-eye-slash");


this.classList.add("fa-eye");


}



}



</script>



</body>


</html>