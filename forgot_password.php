<?php

include("config/db.php");

session_start();

$message="";


if(isset($_POST['submit'])){


$email = $_POST['email'];



$query = mysqli_query($conn,

"SELECT id FROM teachers WHERE email='$email'"

);



if(mysqli_num_rows($query)>0){


$_SESSION['reset_email']=$email;


header("Location: reset_password.php");


exit();


}

else{


$message="Email not found";


}


}


?>


<!DOCTYPE html>
<html>

<head>

<title>Forgot Password</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>


<body>


<div class="container mt-5">


<div class="card p-4 mx-auto" style="max-width:400px;">


<h3 class="text-center">

Forgot Password

</h3>


<form method="POST">


<input type="email"

name="email"

class="form-control mb-3"

placeholder="Enter Email"

required>



<button name="submit"

class="btn btn-primary w-100">

Continue

</button>


</form>



<p class="text-danger mt-3">

<?php echo $message; ?>

</p>


</div>


</div>


</body>

</html>