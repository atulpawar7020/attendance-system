<?php

ob_start();

error_reporting(0);

include("config/db.php");

require_once 'vendor/autoload.php';


use Dompdf\Dompdf;
use Dompdf\Options;



// ==========================
// CLASS ID CHECK
// ==========================


if(!isset($_GET['class_id']))
{
    die("Class ID Missing");
}


$class_id = intval($_GET['class_id']);




// ==========================
// OPTIONAL MONTH YEAR
// ==========================


$month = isset($_GET['month']) && $_GET['month']!=""
? intval($_GET['month'])
: "";


$year = isset($_GET['year']) && $_GET['year']!=""
? intval($_GET['year'])
: "";








// ==========================
// CLASS DETAILS
// ==========================


$classQuery=mysqli_query($conn,

"
SELECT *

FROM classes

WHERE id='$class_id'

"

);


$class=mysqli_fetch_assoc($classQuery);



if(!$class)
{
    die("Class Not Found");
}









// ==========================
// DOMPDF SETUP
// ==========================


$options=new Options();


$options->set(
'isHtml5ParserEnabled',
true
);


$options->set(
'isRemoteEnabled',
true
);



$dompdf=new Dompdf($options);









// ==========================
// TITLE
// ==========================


if($month!="" && $year!="")
{


$title =
$class['class_name'].
" Attendance Sheet - ".
date(
"F Y",
mktime(0,0,0,$month,1,$year)
);


}
else
{


$title =
$class['class_name'].
" Overall Attendance Sheet";


}










$html="

<style>

body{

font-family:Arial;

font-size:12px;

}


h2{

text-align:center;

color:#2F5496;

}


table{

width:100%;

border-collapse:collapse;

}


th{

background:#0D6EFD;

color:white;

padding:6px;

border:1px solid #555;

text-align:center;

}


td{

padding:5px;

border:1px solid #999;

text-align:center;

}


.name{

text-align:left;

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


<h2>
$title
</h2>


<table>

<tr>

<th>
Roll No
</th>


<th>
Student Name
</th>";









// ==========================
// GET DATES
// ==========================


if($month!="" && $year!="")
{


$dateQuery=mysqli_query($conn,


"

SELECT DISTINCT attendance_date

FROM attendance

WHERE class_id='$class_id'

AND MONTH(attendance_date)='$month'

AND YEAR(attendance_date)='$year'

ORDER BY attendance_date ASC

"


);



}
else
{


$dateQuery=mysqli_query($conn,


"

SELECT DISTINCT attendance_date

FROM attendance

WHERE class_id='$class_id'

ORDER BY attendance_date ASC

"


);



}



$dates=[];



while($date=mysqli_fetch_assoc($dateQuery))
{


$dates[]=$date['attendance_date'];


$html .= "

<th>

".date(
"d-m-Y",
strtotime($date['attendance_date'])
)."

</th>";



}



$html .= "

<th>
Present
</th>

<th>
Absent
</th>


<th>
Attendance %
</th>


</tr>";








// ==========================
// STUDENT DATA
// ==========================


$studentQuery=mysqli_query($conn,


"

SELECT *

FROM students

WHERE class_id='$class_id'

ORDER BY roll_no ASC


"

);





while($student=mysqli_fetch_assoc($studentQuery))
{


$student_id=$student['id'];


$present=0;

$absent=0;



$html.="

<tr>


<td>
".$student['roll_no']."
</td>


<td class='name'>
".$student['full_name']."
</td>


";






foreach($dates as $date)
{


$att=mysqli_query($conn,


"

SELECT status

FROM attendance

WHERE class_id='$class_id'

AND student_id='$student_id'

AND attendance_date='$date'

"

);



$status="";



if(mysqli_num_rows($att)>0)
{


$row=mysqli_fetch_assoc($att);


$status=$row['status'];


}







if($status=="Present")
{


$html.="

<td class='present'>
P
</td>

";


$present++;


}

elseif($status=="Absent")
{


$html.="

<td class='absent'>
A
</td>

";


$absent++;


}

else
{


$html.="

<td>
-
</td>


";


}



}





$total=$present+$absent;


$percentage=0;



if($total>0)
{

$percentage=round(
($present/$total)*100
);

}





$html.="


<td>
$present
</td>


<td>
$absent
</td>


<td>
$percentage%
</td>



</tr>


";



}






$html.="

</table>

";






// ==========================
// CREATE PDF
// ==========================


$dompdf->loadHtml($html);


$dompdf->setPaper(
'A4',
'landscape'
);


$dompdf->render();








// ==========================
// FILE NAME
// ==========================


if($month!="" && $year!="")
{


$fileName =

$class['class_name'].

"_Attendance_".

date(
"M-Y",
mktime(0,0,0,$month,1,$year)
).

".pdf";


}
else
{


$fileName =

$class['class_name'].

"_Overall_Attendance.pdf";


}







if(ob_get_length())
{

ob_end_clean();

}



$dompdf->stream(

$fileName,

[
"Attachment"=>true
]

);


exit;


?>