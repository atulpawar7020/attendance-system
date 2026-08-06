<?php

session_start();

include("config/db.php");

date_default_timezone_set("Asia/Kolkata");


// Teacher Login Check

if(!isset($_SESSION['teacher_id'])){

    header("Location: login.php");
    exit();

}


// Class ID Check

if(!isset($_GET['class_id'])){

    die("Class ID Missing");

}


$class_id = intval($_GET['class_id']);



// Month Year Filter

$month="";
$year="";


if(isset($_GET['month']) && $_GET['month']!=""){

    $month=$_GET['month'];

}


if(isset($_GET['year']) && $_GET['year']!=""){

    $year=$_GET['year'];

}



// Get Class Details

$classQuery=mysqli_query($conn,

"SELECT * FROM classes WHERE id='$class_id'");


$class=mysqli_fetch_assoc($classQuery);



if(!$class){

    die("Class Not Found");

}




// Get Students

$students=[];


$studentQuery=mysqli_query($conn,


"SELECT * FROM students

WHERE class_id='$class_id'

ORDER BY roll_no ASC");


while($row=mysqli_fetch_assoc($studentQuery)){


    $students[]=$row;


}




// Get Attendance Taken Dates

$dateSql="

SELECT DISTINCT attendance_date

FROM attendance

WHERE class_id='$class_id'

";



if($month!="" && $year!=""){


$dateSql.="

AND MONTH(attendance_date)='$month'

AND YEAR(attendance_date)='$year'

";


}



$dateSql.=" ORDER BY attendance_date ASC";



$dateQuery=mysqli_query($conn,$dateSql);



$dates=[];



while($row=mysqli_fetch_assoc($dateQuery)){


    $dates[]=$row['attendance_date'];


}


?>



<!DOCTYPE html>

<html>

<head>


<title>
Attendance Sheet
</title>


<meta name="viewport" content="width=device-width, initial-scale=1">


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">



<style>


body{

background:#f4f7fc;

}



.card{

border-radius:15px;

}



.table th{

background:#0d6efd!important;

color:white;

text-align:center;

white-space:nowrap;

}



.table td{

text-align:center;

vertical-align:middle;

white-space:nowrap;

}



.present{

color:green;

font-size:20px;

font-weight:bold;

}



.absent{

color:red;

font-size:20px;

font-weight:bold;

}



.percent{

color:#0d6efd;

font-weight:bold;

}



.studentName{

font-weight:500;

}



</style>



</head>


<body>



<div class="container-fluid mt-4">


<div class="card shadow">


<div class="card-header bg-primary text-white">


<h3>

<?php echo $class['class_name']; ?>

</h3>


<p>

Subject :

<?php echo $class['subject']; ?>

</p>


</div>



<div class="card-body">




<!-- MONTH YEAR FILTER -->


<form method="GET" class="row mb-3">


<input type="hidden"

name="class_id"

value="<?php echo $class_id; ?>">



<div class="col-md-5">


<select name="month" class="form-control">


<option value="">

Select Month

</option>


<?php


for($i=1;$i<=12;$i++){


$sel=($month==$i)?"selected":"";


?>


<option value="<?php echo $i;?>" <?php echo $sel;?>>

<?php echo date("F",mktime(0,0,0,$i,1)); ?>

</option>


<?php

}

?>


</select>


</div>





<div class="col-md-5">


<select name="year" class="form-control">


<option value="">

Select Year

</option>



<?php


for($y=2025;$y<=2035;$y++){


$sel=($year==$y)?"selected":"";


?>


<option value="<?php echo $y;?>" <?php echo $sel;?>>

<?php echo $y; ?>

</option>


<?php

}

?>


</select>


</div>




<div class="col-md-2">


<button class="btn btn-primary w-100">

View

</button>


</div>


</form>





<input type="text"

id="search"

class="form-control mb-3"

placeholder="Search Student Name...">




<!-- TABLE PART 2 HERE -->


<!-- ATTENDANCE TABLE -->


<div class="table-responsive">


<table class="table table-bordered table-striped">


<thead>


<tr>


<th>Roll No</th>


<th>Student Name</th>



<?php foreach($dates as $date){ ?>


<th>

<?php echo date("d M",strtotime($date)); ?>

</th>


<?php } ?>



<th>Present</th>


<th>Absent</th>


<th>%</th>



</tr>


</thead>



<tbody>



<?php foreach($students as $student){ ?>


<tr class="studentRow">



<td>

<?php echo $student['roll_no']; ?>

</td>




<td class="studentName">

<?php echo $student['full_name']; ?>

</td>




<?php


$present=0;

$absent=0;



foreach($dates as $date){



$status="";



$attQuery=mysqli_query($conn,


"SELECT status FROM attendance

WHERE student_id='".$student['id']."'

AND class_id='$class_id'

AND attendance_date='$date'"

);



if(mysqli_num_rows($attQuery)>0){


$att=mysqli_fetch_assoc($attQuery);


$status=$att['status'];


}



?>



<td>


<?php



if($status=="Present"){


echo "<span class='present'>P</span>";

$present++;


}


elseif($status=="Absent"){


echo "<span class='absent'>A</span>";

$absent++;


}


else{


echo "-";


}



?>


</td>



<?php } ?>






<?php


$total=$present+$absent;


$percentage=0;


if($total>0){


$percentage=round(($present/$total)*100);


}



?>




<td class="present">

<?php echo $present; ?>

</td>




<td class="absent">

<?php echo $absent; ?>

</td>




<td class="percent">

<?php echo $percentage; ?>%

</td>



</tr>



<?php } ?>



</tbody>



</table>


</div>





<br>


<hr>
<br>

<!-- BUTTONS -->


<div class="text-center">


<a href="export_excel.php?class_id=<?php echo $class_id; ?>&month=<?php echo $month; ?>&year=<?php echo $year;?>"

class="btn btn-success">


<i class="fa fa-file-excel"></i>

Download Excel


</a>




<a href="export_pdf.php?class_id=<?php echo $class_id;?>&month=<?php echo $month;?>&year=<?php echo $year;?>"

class="btn btn-danger">


<i class="fa fa-file-pdf"></i>

Download PDF


</a>




<a href="classes.php"

class="btn btn-secondary">


Back


</a>



</div>



</div>

</div>

</div>






<script>


// Student Search


document.getElementById("search")

.addEventListener("keyup",function(){



let value=this.value.toLowerCase();



let rows=document.querySelectorAll(".studentRow");



rows.forEach(function(row){


let name=row.querySelector(".studentName")

.innerText

.toLowerCase();



if(name.includes(value)){


row.style.display="";


}

else{


row.style.display="none";


}



});



});



</script>




</body>


</html>