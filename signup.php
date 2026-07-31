<?php
include("config/db.php");

$message="";


if(isset($_POST['signup'])){

    $fullname=$_POST['fullname'];
    $email=$_POST['email'];
    $mobile=$_POST['mobile'];
    $college=$_POST['college'];
    $department=$_POST['department'];
    $designation=$_POST['designation'];
    $password=$_POST['password'];

    $check=mysqli_query($conn,"SELECT * FROM teachers WHERE email='$email'");

    if(mysqli_num_rows($check)>0){

        $message="<div class='alert alert-danger'>Email already exists.</div>";

    }else{

 $sql = "INSERT INTO teachers
(full_name,email,mobile,college_name,department,designation,password)
VALUES
('$fullname','$email','$mobile','$college','$department','$designation','$password')";

$result = mysqli_query($conn, $sql);

if($result){
    die("✅ Account Created Successfully");
}else{
    die("❌ MySQL Error: " . mysqli_error($conn));
}

    }

}
?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Teacher Sign Up</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="assets/css/login.css">

</head>

<body>

<div class="container">

<div class="left">

<h1>🎓 Smart Attendance</h1>

<p>

Create your Teacher Account

Manage Classes

Track Attendance

Generate Reports

</p>

</div>

<div class="right">

<div class="logo">

<h2>Create Account</h2>

</div>

<?php echo $message; ?>

<form method="POST">

<div class="input-group">

<label>Full Name</label>

<input type="text" name="fullname" required>

</div>

<div class="input-group">

<label>Email</label>

<input type="email" name="email" required>

</div>

<div class="input-group">

<label>Mobile</label>

<input type="text" name="mobile">

</div>

<div class="input-group">

<label>College</label>

<input type="text" name="college">

</div>

<div class="input-group">

<label>Department</label>

<input type="text" name="department">

</div>

<div class="input-group">

<label>Designation</label>

<input type="text" name="designation">

</div>

<div class="input-group">

<label>Password</label>

<input type="password" name="password" required>

</div>

<button class="btn" name="signup">

Create Account

</button>

</form>

<div class="links">

<br>

Already have an account?

<br>

<a href="login.php">

Login

</a>

</div>

</div>

</div>

</body>

</html>