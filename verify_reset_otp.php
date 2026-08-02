<?php

session_start();

include("config/db.php");


$message="";


if(!isset($_SESSION['reset_email']))
{

header("Location: forgot_password.php");
exit();

}



$email=$_SESSION['reset_email'];



if(isset($_POST['verify']))
{


$otp=mysqli_real_escape_string(
$conn,
$_POST['otp']
);



$query=mysqli_query($conn,


"SELECT * FROM password_resets

WHERE email='$email'

AND otp='$otp'

ORDER BY id DESC

LIMIT 1"


);



if(mysqli_num_rows($query)>0)
{


$data=mysqli_fetch_assoc($query);



if(strtotime($data['expires_at']) >= time())
{


$_SESSION['otp_verified']=true;


header("Location: reset_password.php");

exit();


}
else
{

$message="OTP Expired";

}


}
else
{

$message="Invalid OTP";

}


}


?>



<!DOCTYPE html>

<html>

<head>

<title>Verify OTP</title>


<link 
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


</head>


<body>


<div class="container mt-5">


<div class="card shadow p-4 mx-auto"
style="max-width:400px;">


<h3 class="text-center">
Verify OTP
</h3>


<p>

OTP sent to:

<b>
<?php echo $email; ?>
</b>

</p>



<form method="POST">


<input type="text"

name="otp"

class="form-control mb-3"

placeholder="Enter OTP"

required>



<button name="verify"

class="btn btn-success w-100">

Verify OTP

</button>


</form>



<p class="text-danger mt-3">

<?php echo $message; ?>

</p>



</div>


</div>


</body>

</html>