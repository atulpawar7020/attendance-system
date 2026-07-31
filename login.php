<?php
session_start();

include("config/db.php");

if(isset($_SESSION['teacher_id'])){
    header("Location: classes.php");
    exit();
}

$error="";


if(isset($_POST['login'])){


    $email = trim($_POST['email']);
    $password = trim($_POST['password']);


    $sql = "SELECT * FROM teachers WHERE email=? AND password=?";


    $stmt = mysqli_prepare($conn,$sql);


    mysqli_stmt_bind_param($stmt,"ss",$email,$password);


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result($stmt);



    if(mysqli_num_rows($result)==1){


        $teacher = mysqli_fetch_assoc($result);


        $_SESSION['teacher_id']=$teacher['id'];

        $_SESSION['teacher_name']=$teacher['full_name'];



        header("Location: classes.php");

        exit();


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


<br><br>


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