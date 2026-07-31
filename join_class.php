<?php
include("config/db.php");

if(!isset($_GET['code'])){
    die("Invalid Invite Link");
}

$code = mysqli_real_escape_string($conn,$_GET['code']);

$classQuery = mysqli_query($conn,
"SELECT * FROM classes WHERE invite_code='$code'");

if(mysqli_num_rows($classQuery)==0){
    die("Invalid Invite Link");
}

$class = mysqli_fetch_assoc($classQuery);

$class_id = $class['id'];

?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Join Class</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
background:#f5f7fb;
}

.card{
border:none;
border-radius:18px;
box-shadow:0 5px 20px rgba(0,0,0,.15);
}

</style>

</head>

<body>

<div class="container mt-5">

<div class="row justify-content-center">

<div class="col-md-6">

<div class="card">

<div class="card-header bg-primary text-white">

<h3>Join Class</h3>

</div>

<div class="card-body">

<h5>

Class :
<?php echo $class['class_name']; ?>

</h5>

<p>

Subject :
<b>

<?php echo $class['subject']; ?>

</b>

</p>

<hr>

<form action="save_join_student.php" method="POST">

<input
type="hidden"
name="class_id"
value="<?php echo $class_id; ?>">

<div class="mb-3">

<label>Roll Number</label>

<input
type="text"
name="roll_no"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Full Name</label>

<input
type="text"
name="full_name"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Mobile Number</label>

<input
type="text"
name="mobile"
class="form-control">

</div>

<div class="mb-3">

<label>Email</label>

<input
type="email"
name="email"
class="form-control">

</div>

<button
class="btn btn-success w-100">

Join Class

</button>

</form>

</div>

</div>

</div>

</div>

</div>

</body>

</html>