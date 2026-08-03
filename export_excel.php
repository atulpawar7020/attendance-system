<?php

ob_start();

error_reporting(0);

require 'vendor/autoload.php';

include("config/db.php");


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;



// =======================
// CLASS ID
// =======================

if(!isset($_GET['class_id']))
{
    die("Class ID Missing");
}


$class_id = intval($_GET['class_id']);



// =======================
// MONTH YEAR OPTIONAL
// =======================


$month = isset($_GET['month']) && $_GET['month']!=""
? intval($_GET['month'])
: "";


$year = isset($_GET['year']) && $_GET['year']!=""
? intval($_GET['year'])
: "";






// =======================
// CLASS DETAILS
// =======================


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







// =======================
// CREATE EXCEL
// =======================


$spreadsheet=new Spreadsheet();

$sheet=$spreadsheet->getActiveSheet();


$sheet->setTitle("Attendance");








// =======================
// GET DATES
// =======================



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



while($row=mysqli_fetch_assoc($dateQuery))
{

    $dates[]=$row['attendance_date'];

}








// =======================
// TITLE
// =======================



if($month!="" && $year!="")
{

$title =
$class['class_name'].
" Attendance Sheet - ".
date("F Y",mktime(0,0,0,$month,1,$year));


}
else
{


$title =
$class['class_name'].
" Overall Attendance Sheet";


}




$totalColumns =
2 + count($dates)+3;


$lastColumn =
Coordinate::stringFromColumnIndex($totalColumns);



$sheet->setCellValue(
"A1",
$title
);



$sheet->mergeCells(
"A1:".$lastColumn."1"
);




$sheet->getStyle("A1")
->getFont()
->setBold(true)
->setSize(16);







// =======================
// HEADER
// =======================


$row=3;

$col=1;



$headers=[

"Roll No",

"Student Name"

];




foreach($dates as $date)
{

$headers[]=date(
"d-M-Y",
strtotime($date)
);


}




$headers[]="Present";

$headers[]="Absent";

$headers[]="Attendance %";







foreach($headers as $head)
{


$sheet->setCellValue(

[$col,$row],

$head

);


$col++;


}





$sheet->getStyle(

"A3:".$lastColumn."3"

)

->getFont()

->setBold(true)

->getColor()

->setRGB("FFFFFF");



$sheet->getStyle(

"A3:".$lastColumn."3"

)

->getFill()

->setFillType(Fill::FILL_SOLID)

->getStartColor()

->setRGB("0D6EFD");





// =======================
// STUDENTS START
// =======================


$row=4;


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


$col=1;


$student_id=$student['id'];



$sheet->setCellValue(
[$col++,$row],
$student['roll_no']
);



$sheet->setCellValue(
[$col++,$row],
$student['full_name']
);



$present=0;

$absent=0;



foreach($dates as $date)
{


$statusQuery=mysqli_query($conn,


"
SELECT status

FROM attendance

WHERE class_id='$class_id'

AND student_id='$student_id'

AND attendance_date='$date'

"


);


$status="";



if(mysqli_num_rows($statusQuery)>0)
{

$data=mysqli_fetch_assoc($statusQuery);

$status=$data['status'];

}



$cell=
Coordinate::stringFromColumnIndex($col).
$row;



if($status=="Present")
{


$sheet->setCellValue(
[$col,$row],
"P"
);


$present++;


$sheet->getStyle($cell)
->getFont()
->setBold(true)
->getColor()
->setRGB("008000");


}

elseif($status=="Absent")
{


$sheet->setCellValue(
[$col,$row],
"A"
);


$absent++;


$sheet->getStyle($cell)
->getFont()
->setBold(true)
->getColor()
->setRGB("FF0000");


}

else
{

$sheet->setCellValue(
[$col,$row],
"-"
);


}



$col++;


}

// =======================
// PRESENT ABSENT PERCENTAGE
// =======================


$total = $present + $absent;


$percentage = 0;


if($total > 0)
{

    $percentage = round(($present/$total)*100);

}




$sheet->setCellValue(
[$col++,$row],
$present
);



$sheet->setCellValue(
[$col++,$row],
$absent
);



$sheet->setCellValue(
[$col,$row],
$percentage."%"
);



$row++;


}







// =======================
// COLUMN AUTO SIZE
// =======================


for($i=1;$i<=$totalColumns;$i++)
{


$letter =
Coordinate::stringFromColumnIndex($i);



$sheet->getColumnDimension($letter)
->setAutoSize(true);


}







// =======================
// BORDER
// =======================


$sheet->getStyle(

"A3:".$lastColumn.($row-1)

)

->getBorders()

->getAllBorders()

->setBorderStyle(

Border::BORDER_THIN

);








// =======================
// FILE NAME
// =======================


if($month!="" && $year!="")
{


$fileName =

$class['class_name'].

"_Attendance_".

date(
"M-Y",
mktime(0,0,0,$month,1,$year)
).

".xlsx";


}
else
{


$fileName =

$class['class_name'].

"_Overall_Attendance.xlsx";


}








// =======================
// DOWNLOAD
// =======================


if(ob_get_length())
{

ob_end_clean();

}



header(
'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);



header(
'Content-Disposition: attachment;filename="'.$fileName.'"'
);



header(
'Cache-Control: max-age=0'
);



$writer=new Xlsx($spreadsheet);



$writer->save("php://output");



exit;


?>