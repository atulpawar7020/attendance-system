<?php

session_start();

date_default_timezone_set("Asia/Kolkata");

include("config/db.php");
include("config/mail.php");


$message="";


if(isset($_POST['submit']))
{

$email=mysqli_real_escape_string($conn,$_POST['email']);


// Check registered email

$check=mysqli_query($conn,

"SELECT id FROM teachers WHERE email='$email'"

);


if(mysqli_num_rows($check)>0)
{


// Generate OTP

$otp=rand(100000,999999);


// Expire after 5 minutes

$expire=date(
"Y-m-d H:i:s",
strtotime("+5 minutes")
);



// Delete old OTP

mysqli_query($conn,

"DELETE FROM password_resets 
WHERE email='$email'"

);



// Insert OTP

$insert=mysqli_query($conn,

"INSERT INTO password_resets
(email,otp,expires_at)

VALUES

('$email','$otp','$expire')"

);



if($insert)
{


if(sendOTP($email,$otp))
{


$_SESSION['reset_email']=$email;


header("Location: verify_reset_otp.php");

exit();


}
else
{

$message="OTP send failed";

}



}
else
{

$message="OTP save failed";

}



}
else
{

$message="Email not registered";

}


}

?>


<!DOCTYPE html>

<html>

<head>

<title>Forgot Password</title>

<link 
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

</head>


<body>


<div class="container mt-5">


<div class="card shadow p-4 mx-auto"
style="max-width:400px;">


<h3 class="text-center">
Forgot Password
</h3>


<form method="POST">


<input type="email"
name="email"
class="form-control mb-3"
placeholder="Enter registered email"
required>



<button 
name="submit"
class="btn btn-primary w-100">

Send OTP

</button>


</form>


<?php

if($message!="")
{

echo "<p class='text-danger mt-3'>
$message
</p>";

}

?>


</div>


</div>


</body>

</html>