<?php
session_start();

if(!isset($_SESSION['teacher_id']))
{
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<nav class="navbar navbar-dark bg-primary">

<div class="container-fluid">

<a class="navbar-brand">
Attendance System
</a>

<div>

<a href="dashboard.php" class="btn btn-light">
Dashboard
</a>

<a href="classes.php" class="btn btn-light">
My Classes
</a>

<a href="attendance_sheet.php" class="btn btn-light">
Attendance Sheet
</a>

<a href="logout.php" class="btn btn-danger">
Logout
</a>

</div>

</div>

</nav>

<div class="container mt-5">

<h2>
Welcome,
<?php echo $_SESSION['teacher_name']; ?>
</h2>

</div>

</body>
</html>