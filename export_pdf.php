<?php

ob_start();

error_reporting(E_ALL);
ini_set('display_errors','0');


include("config/db.php");

require_once 'vendor/autoload.php';


use Dompdf\Dompdf;
use Dompdf\Options;



if(!isset($_GET['class_id']))
{
    die("Class ID Missing");
}



$class_id = intval($_GET['class_id']);



$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');





// CLASS DATA

$classQuery=mysqli_query($conn,

"SELECT * FROM classes WHERE id='$class_id'"

);


$class=mysqli_fetch_assoc($classQuery);



if(!$class)
{
    die("Class Not Found");
}







// PDF SETUP


$options=new Options();

$options->set('isHtml5ParserEnabled',true);

$options->set('isRemoteEnabled',true);


$dompdf=new Dompdf($options);







$html='';


$html.="

<style>

body{

font-family: Arial;

}


h2{

text-align:center;

color:#2F5496;

}



table{

border-collapse:collapse;

width:100%;

font-size:11px;

}



th{

background:#0d6efd;

color:white;

padding:6px;

border:1px solid #999;

}



td{

padding:5px;

border:1px solid #999;

text-align:center;

}



.name{

text-align:left;

}



.p{

color:green;

font-weight:bold;

}



.a{

color:red;

font-weight:bold;

}



</style>

";





$html.="<h2>

".$class['class_name']." Attendance Sheet

</h2>";



$html.="<h4>

".date("F Y",mktime(0,0,0,$month,1,$year))."

</h4>";





$html.="

<table>

<tr>

<th>Roll</th>

<th>Student Name</th>

";






// DATE COLUMNS


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



$dates=[];



while($d=mysqli_fetch_assoc($dateQuery))
{

$dates[]=$d['attendance_date'];


$html.="

<th>

".date("d-M",strtotime($d['attendance_date']))."

</th>

";

}




$html.="

<th>Present</th>

<th>Absent</th>

<th>Attendance %</th>

</tr>";







// STUDENTS


$students=mysqli_query($conn,


"

SELECT * FROM students

WHERE class_id='$class_id'

ORDER BY roll_no ASC

"

);







while($student=mysqli_fetch_assoc($students))
{


$html.="

<tr>

<td>

".$student['roll_no']."

</td>


<td class='name'>

".$student['full_name']."

</td>

";



$present=0;

$absent=0;





foreach($dates as $date)
{


$att=mysqli_query($conn,


"

SELECT status FROM attendance

WHERE class_id='$class_id'

AND student_id='".$student['id']."'

AND attendance_date='$date'

"

);



if(mysqli_num_rows($att)>0)
{


$data=mysqli_fetch_assoc($att);



if($data['status']=="Present")
{

$html.="

<td class='p'>P</td>

";


$present++;


}

else
{


$html.="

<td class='a'>A</td>

";


$absent++;


}



}

else
{


$html.="

<td>-</td>

";

}



}




$total=$present+$absent;



$percentage=0;


if($total>0)
{

$percentage=round(($present/$total)*100);

}




$html.="


<td>

".$present."

</td>



<td>

".$absent."

</td>



<td>

".$percentage."%

</td>



</tr>";



}



$html.="</table>";






$dompdf->loadHtml($html);


$dompdf->setPaper('A4','landscape');


$dompdf->render();






$file=$class['class_name']."_Attendance.pdf";



if(ob_get_length())
{

ob_end_clean();

}



$dompdf->stream($file,

[
"Attachment"=>true
]

);


exit();


?>