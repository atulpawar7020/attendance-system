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


$class_id=$_GET['class_id'];



// Default ALL Attendance

$month="";
$year="";


// Filter Apply

if(isset($_GET['month']) && $_GET['month']!=""){

    $month=$_GET['month'];

}


if(isset($_GET['year']) && $_GET['year']!=""){

    $year=$_GET['year'];

}




// Class Information

$classQuery=mysqli_query($conn,

"SELECT * FROM classes WHERE id='$class_id'");


$class=mysqli_fetch_assoc($classQuery);




// Students List

$students=mysqli_query($conn,

"SELECT * FROM students

WHERE class_id='$class_id'

ORDER BY roll_no ASC");





// Attendance Date Query

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


}



.table td{


text-align:center;

vertical-align:middle;


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


</style>


</head>



<body>



<div class="container mt-4">



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




<!-- FILTER -->

<form method="GET" class="row mb-3">


<input type="hidden"
name="class_id"
value="<?php echo $class_id; ?>">



<div class="col-md-5">


<select name="month" class="form-control">


<option value="">

select Month

</option>



<?php


for($i=1;$i<=12;$i++){


$selected=($month==$i)?"selected":"";


echo "

<option value='$i' $selected>

".date("F",mktime(0,0,0,$i,1))."

</option>";

}


?>


</select>


</div>





<div class="col-md-5">


<select name="year" class="form-control">


<option value="">

select Year 

</option>



<?php


for($y=2025;$y<=2035;$y++){


$selected=($year==$y)?"selected":"";


echo "

<option value='$y' $selected>

$y

</option>";

}


?>


</select>


</div>




<div class="col-md-2">


<button class="btn btn-primary">

View

</button>


</div>



</form>





<input type="text"

id="search"

class="form-control mb-3"

placeholder="Search Student Name...">


<br>


<div class="table-responsive">


<table class="table table-bordered"

id="attendanceTable">



<tr>


<th>
Roll
</th>


<th>
Student Name
</th>



<?php


foreach($dates as $date){


echo "

<th>

".date("d M",strtotime($date))."

</th>";


}



?>


<th>
Present
</th>


<th>
Absent
</th>


<th>
Attendance %
</th>


</tr>








<?php


mysqli_data_seek($students,0);



while($student=mysqli_fetch_assoc($students)){


$present=0;

$absent=0;


?>



<tr class="studentRow">



<td>

<?php echo $student['roll_no']; ?>

</td>



<td class="studentName">

<?php echo $student['full_name']; ?>

</td>



<?php



foreach($dates as $date){



$attQuery=mysqli_query($conn,


"SELECT status FROM attendance

WHERE student_id='".$student['id']."'

AND class_id='$class_id'

AND attendance_date='$date'"

);



if(mysqli_num_rows($attQuery)>0){



$att=mysqli_fetch_assoc($attQuery);



if($att['status']=="Present"){


$present++;



echo "

<td class='present'>

P

</td>";



}

else{


$absent++;



echo "

<td class='absent'>

A

</td>";



}



}

else{


echo "

<td>

-

</td>";

}


}




$total=$present+$absent;



if($total>0){


$percentage=round(($present/$total)*100);


}

else{


$percentage=0;


}



?>



<td>

<?php echo $present; ?>

</td>




<td>

<?php echo $absent; ?>

</td>



<td class="percent">

<?php echo $percentage; ?>%

</td>



</tr>



<?php

}

?>



</table>


</div>




<br>



<div class="text-center">


<a href="export_excel.php?class_id=<?php echo $class_id; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>"
class="btn btn-success">

<i class="fa fa-file-excel"></i>
Download Excel

</a>




<a href="export_pdf.php?class_id=<?php echo $class_id;?>&month=<?php echo $month;?>&year=<?php echo $year;?>"

class="btn btn-danger">


<i class="fa fa-file-pdf"></i>

Export PDF


</a>


</div>




<br>



<a href="classes.php"

class="btn btn-secondary">

Back

</a>



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