<?php

session_start();

include("config/db.php");

if(!isset($_SESSION['teacher_id']))
{
    header("Location: login.php");
    exit();
}


if(!isset($_GET['class_id']))
{
    die("Class ID Missing");
}


$class_id = intval($_GET['class_id']);


// Students fetch

$students = mysqli_query($conn,

"
SELECT *

FROM students

WHERE class_id='$class_id'

ORDER BY roll_no ASC

"

);


?>


<!DOCTYPE html>

<html>

<head>

<title>Manage Students Excel</title>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">




<style>

body{

    background:#f5f7fb;
    font-family:'Segoe UI',sans-serif;

}


/* Main Card */

.card{

    border:none;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,0.1);

}


/* Heading */

h3{

    color:#0d6efd;
    font-weight:600;

}


/* Table */

.table{

    background:white;
    border-radius:10px;
    overflow:hidden;

}



.table th{

    background:#0d6efd;
    color:white;
    text-align:center;
    font-size:15px;

}


.table td{

    vertical-align:middle;

}



/* Input box */

.form-control{

    border-radius:8px;
    border:1px solid #ddd;
    padding:9px;

}


.form-control:focus{

    border-color:#0d6efd;
    box-shadow:0 0 5px rgba(13,110,253,.3);

}



/* Buttons */

.btn{

    border-radius:8px;
    padding:10px 20px;
    font-weight:500;

}


.btn-success{

    background:#198754;
    border:none;

}


.btn-success:hover{

    background:#157347;

}


.btn-secondary{

    background:#6c757d;
    border:none;

}



/* Hover row */

.table tbody tr:hover{

    background:#f1f7ff;

}



/* Responsive */

@media(max-width:768px){


.table{

    font-size:13px;

}


.form-control{

    padding:6px;

}


}


</style>





</head>


<body>


<div class="container mt-4">


<div class="card">


<div class="card-body">



<h3 class="mb-4">

Student Excel Sheet

</h3>



<form action="save_students_excel.php" method="POST">



<input type="hidden" name="class_id"
value="<?php echo $class_id; ?>">



<table class="table table-bordered">


<tr>

<th>Roll No</th>

<th>Name</th>

<th>Mobile</th>

<th>Email</th>

</tr>




<?php while($row=mysqli_fetch_assoc($students)){ ?>


<tr>


<td>

<input class="form-control"

name="roll_no[]"

value="<?php echo $row['roll_no']; ?>">

</td>



<td>

<input class="form-control"

name="name[]"

value="<?php echo $row['full_name']; ?>">

</td>



<td>

<input class="form-control"

name="mobile[]"

value="<?php echo $row['mobile']; ?>">

</td>



<td>

<input class="form-control"

name="email[]"

value="<?php echo $row['email']; ?>">

</td>


</tr>



<?php } ?>






<!-- New Empty Rows -->


<?php for($i=0;$i<10;$i++){ ?>


<tr>


<td>

<input class="form-control"
name="roll_no[]">

</td>



<td>

<input class="form-control"
name="name[]">

</td>



<td>

<input class="form-control"
name="mobile[]">

</td>



<td>

<input class="form-control"
name="email[]">

</td>


</tr>



<?php } ?>




</table>



<button class="btn btn-success">

Save Students

</button>



<a href="classes.php"
class="btn btn-secondary">

Back

</a>




</form>




</div>

</div>

</div>



</body>

</html>