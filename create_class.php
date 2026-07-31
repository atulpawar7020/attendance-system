<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['teacher_id'])){
    header("Location: login.php");
    exit();
}

$message = "";

if(isset($_POST['save'])){

    $teacher_id = $_SESSION['teacher_id'];

    $class_name = mysqli_real_escape_string($conn,$_POST['class_name']);
    $subject = mysqli_real_escape_string($conn,$_POST['subject']);
    $semester = mysqli_real_escape_string($conn,$_POST['semester']);
    $department = mysqli_real_escape_string($conn,$_POST['department']);
    $academic_year = mysqli_real_escape_string($conn,$_POST['academic_year']);

    // Generate Unique Invite Code
    do{
        $invite_code = strtoupper(substr(md5(uniqid(rand(), true)),0,8));

        $check = mysqli_query($conn,
        "SELECT id FROM classes WHERE invite_code='$invite_code'");

    }while(mysqli_num_rows($check)>0);


    $sql = "INSERT INTO classes
    (
        teacher_id,
        class_name,
        subject,
        semester,
        department,
        academic_year,
        invite_code
    )
    VALUES
    (
        '$teacher_id',
        '$class_name',
        '$subject',
        '$semester',
        '$department',
        '$academic_year',
        '$invite_code'
    )";


    if(mysqli_query($conn,$sql)){

        $_SESSION['success']="Class Created Successfully";

        header("Location: classes.php");

        exit();

    }else{

        $message="<div class='alert alert-danger'>"
        .mysqli_error($conn).
        "</div>";

    }

}
?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>Create Class</title>

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

border-radius:15px 15px 0 0 !important;

}

</style>

</head>

<body>

<div class="container mt-5">

<div class="row justify-content-center">

<div class="col-md-6">

<div class="card">

<div class="card-header bg-primary text-white">

<h4>

<i class="fa fa-plus-circle"></i>

Create New Class

</h4>

</div>

<div class="card-body">

<?php echo $message; ?>

<form method="POST">

<div class="mb-3">

<label class="form-label">

Class Name

</label>

<input
type="text"
name="class_name"
class="form-control"
placeholder="Enter Class Name"
required>

</div>

<div class="mb-3">

<label class="form-label">

Subject

</label>

<input
type="text"
name="subject"
class="form-control"
placeholder="Enter Subject"
required>

</div>

<div class="mb-3">

<label class="form-label">

Semester

</label>

<input
type="text"
name="semester"
class="form-control"
placeholder="Enter Semester"
required>

</div>

<div class="mb-3">

<label class="form-label">

Department

</label>

<input
type="text"
name="department"
class="form-control"
placeholder="Enter Department"
required>

</div>

<div class="mb-3">

<label class="form-label">

Academic Year

</label>

<input
type="text"
name="academic_year"
class="form-control"
placeholder="2026-27"
required>

</div>

<button
type="submit"
name="save"
class="btn btn-primary w-100">

<i class="fa fa-save"></i>

Create Class

</button>

<br><br>

<a href="classes.php"
class="btn btn-secondary w-100">

<i class="fa fa-arrow-left"></i>

Back

</a>

</form>

</div>

</div>

</div>

</div>

</div>

</body>

</html>