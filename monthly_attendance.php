<?php

session_start();
include("config/db.php");

date_default_timezone_set("Asia/Kolkata");

if(!isset($_SESSION['teacher_id'])){
    header("Location: login.php");
    exit();
}


if(!isset($_GET['class_id'])){
    die("Class ID Missing");
}


$class_id=$_GET['class_id'];


// Month Year

$month=date('m');
$year=date('Y');


if(isset($_GET['month'])){
    $month=$_GET['month'];
}


if(isset($_GET['year'])){
    $year=$_GET['year'];
}



// Class Details

$class=mysqli_fetch_assoc(
mysqli_query($conn,
"SELECT * FROM classes WHERE id='$class_id'")
);




// Attendance Dates Only

$dateQuery=mysqli_query($conn,

"SELECT DISTINCT attendance_date 
FROM attendance
WHERE class_id='$class_id'
AND MONTH(attendance_date)='$month'
AND YEAR(attendance_date)='$year'
ORDER BY attendance_date ASC");


$dates=[];


while($d=mysqli_fetch_assoc($dateQuery)){

    $dates[]=$d['attendance_date'];

}




// Students

$students=mysqli_query($conn,

"SELECT * FROM students
WHERE class_id='$class_id'
ORDER BY roll_no ASC");



?>


<!DOCTYPE html>

<html>

<head>

<title>
Monthly Attendance
</title>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


<style>


body{

background:#f4f7fc;

}


.card{

border-radius:15px;

}


th{

background:#0d6efd!important;

color:white;

text-align:center;

}


td{

text-align:center;

}


.present{

color:green;
font-weight:bold;

}


.absent{

color:red;
font-weight:bold;

}



</style>


</head>


<body>


<div class="container mt-4">


<div class="card shadow">


<div class="card-header bg-primary text-white">


<h3>

<?php echo $class['class_name']; ?>

</h3>


<h6>

Subject :
<?php echo $class['subject']; ?>

</h6>


</div>



<div class="card-body">



<!-- Filter -->


<form method="GET" class="row mb-4">


<input type="hidden"
name="class_id"
value="<?php echo $class_id ?>">



<div class="col-md-4">


<label>
Month
</label>


<select name="month"
class="form-control">


<?php

for($i=1;$i<=12;$i++){

$sel=($month==$i)?"selected":"";


echo "

<option value='$i' $sel>

".date("F",mktime(0,0,0,$i,1))."

</option>";

}


?>


</select>


</div>




<div class="col-md-4">


<label>
Year
</label>


<select name="year"
class="form-control">


<?php

for($y=2025;$y<=2035;$y++){


$sel=($year==$y)?"selected":"";


echo "

<option value='$y' $sel>

$y

</option>";

}


?>


</select>


</div>




<div class="col-md-4 mt-4">


<button class="btn btn-primary">

View Sheet

</button>


</div>


</form>





<div class="table-responsive">


<table class="table table-bordered">


<tr>


<th>
Roll No
</th>


<th>
Name
</th>



<?php

foreach($dates as $date){


echo "

<th>

".date("d-m-Y",strtotime($date))."

</th>";

}


?>


<th>
Overall %
</th>


</tr>





<?php


while($student=mysqli_fetch_assoc($students)){


$total=0;
$present=0;


?>


<tr>


<td>

<?php echo $student['roll_no']; ?>

</td>



<td>

<?php echo $student['full_name']; ?>

</td>




<?php



foreach($dates as $date){


$total++;



$q=mysqli_query($conn,


"SELECT status FROM attendance

WHERE student_id='".$student['id']."'

AND attendance_date='$date'");



if(mysqli_num_rows($q)>0){



$row=mysqli_fetch_assoc($q);



if($row['status']=="Present"){


$present++;


echo "

<td class='present'>
P
</td>";

}
else{


echo "

<td class='absent'>
A
</td>";

}



}

else{


echo "<td>-</td>";

}



}



// Percentage


if($total>0){

$percentage=
round(($present/$total)*100,2);

}
else{

$percentage=0;

}



?>


<td>

<?php echo $percentage; ?> %

</td>



</tr>


<?php


}


?>


</table>


</div>




<br>



<div class="text-center">



<a href="export_pdf.php?class_id=<?php echo $class_id; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>"
class="btn btn-danger">
Download PDF
</a>


<a href="export_excel.php?class_id=<?php echo $class_id; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>"
class="btn btn-success">
Download Excel




</a>
</div>



<br>

<a href="classes.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left"></i> Back
</a>

</a>


</div>



</div>


</div>


</div>


</body>


</html>