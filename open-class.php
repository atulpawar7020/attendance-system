<?php

session_start();

include("config/db.php");

date_default_timezone_set("Asia/Kolkata");


if(!isset($_SESSION['teacher_id'])){

    header("Location: login.php");
    exit();

}


$teacher_id = $_SESSION['teacher_id'];


if(!isset($_GET['class_id'])){

    die("Class ID Missing");

}


$class_id = intval($_GET['class_id']);


// Selected Date

$attendance_date = date("Y-m-d");


if(isset($_GET['date'])){

    $attendance_date = $_GET['date'];

}




// Class Details

$classQuery = mysqli_query($conn,

"SELECT * FROM classes

WHERE id='$class_id'

AND teacher_id='$teacher_id'

");


$classData = mysqli_fetch_assoc($classQuery);



if(!$classData){

    die("Class Not Found");

}





// Students

$students = mysqli_query($conn,

"SELECT * FROM students

WHERE class_id='$class_id'

ORDER BY roll_no ASC"

);



?>



<!DOCTYPE html>

<html>

<head>


<title>Open Class</title>


<meta name="viewport" content="width=device-width, initial-scale=1">


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


.table th{

background:#0d6efd;

color:white;

}



</style>


</head>




<body>


<div class="container mt-4">


<div class="card">


<div class="card-body">



<div class="d-flex justify-content-between">


<div>


<h3>

<?php echo $classData['class_name']; ?>

</h3>


<p>

Subject :

<b>

<?php echo $classData['subject']; ?>

</b>

</p>



</div>



<div>


<a href="add_student.php?class_id=<?php echo $class_id; ?>"

class="btn btn-primary">


<i class="fa fa-user-plus"></i>

Add Student


</a>


</div>


</div>



<hr>




<!-- Date Selection -->


<form method="GET" class="row mb-4">


<input type="hidden"

name="class_id"

value="<?php echo $class_id; ?>">



<div class="col-md-5">


<label class="fw-bold">

Select Attendance Date

</label>


<input type="date"

name="date"

value="<?php echo $attendance_date; ?>"

class="form-control"

required>


</div>



<div class="col-md-3 mt-4">


<button class="btn btn-primary">

<i class="fa fa-calendar"></i>

Load Date

</button>


</div>


</form>





<h5>

Attendance Date :

<?php echo date("d-m-Y",strtotime($attendance_date)); ?>

</h5>



<hr>





<form action="save_attendance.php" method="POST">



<input type="hidden"

name="class_id"

value="<?php echo $class_id; ?>">



<input type="hidden"

name="attendance_date"

value="<?php echo $attendance_date; ?>">





<table class="table table-bordered">


<tr>


<th>

Roll No

</th>


<th>

Student Name

</th>


<th>

Attendance

</th>


</tr>





<?php while($row=mysqli_fetch_assoc($students)){



$student_id=$row['id'];




// Check selected date attendance


$check=mysqli_query($conn,

"

SELECT status

FROM attendance

WHERE student_id='$student_id'

AND class_id='$class_id'

AND attendance_date='$attendance_date'

"

);



$status="Absent";



if(mysqli_num_rows($check)>0){


$data=mysqli_fetch_assoc($check);


$status=$data['status'];


}



?>



<tr>


<td>

<?php echo $row['roll_no']; ?>

</td>




<td>


<?php echo $row['full_name']; ?>


<input type="hidden"

name="student_id[]"

value="<?php echo $student_id; ?>">


</td>





<td>



<button type="button"

class="btn attendance-btn

<?php echo ($status=="Present")?'btn-success':'btn-danger'; ?>">



<?php echo $status; ?>


</button>



<input type="hidden"

name="status[]"

class="status-input"

value="<?php echo $status; ?>">



</td>


</tr>



<?php } ?>



</table>





<button class="btn btn-success">


<i class="fa fa-save"></i>

Save Attendance


</button>



<a href="classes.php"

class="btn btn-secondary">

Back

</a>



</form>



</div>


</div>


</div>






<script>


document.querySelectorAll(".attendance-btn")

.forEach(function(btn){


btn.addEventListener("click",function(){



let input=this.parentElement.querySelector(".status-input");



if(input.value=="Absent"){



input.value="Present";


this.innerHTML="Present";


this.classList.remove("btn-danger");

this.classList.add("btn-success");


}

else{


input.value="Absent";


this.innerHTML="Absent";


this.classList.remove("btn-success");

this.classList.add("btn-danger");


}


});


});



</script>




</body>

</html>