<?php

session_start();

include("config/db.php");


if(!isset($_SESSION['teacher_id']))
{
    header("Location: login.php");
    exit();
}



$class_id=$_POST['class_id'];


$roll=$_POST['roll_no'];

$name=$_POST['name'];

$mobile=$_POST['mobile'];

$email=$_POST['email'];





for($i=0;$i<count($roll);$i++)
{


if(trim($name[$i])=="")
{
    continue;
}



$roll_no=mysqli_real_escape_string($conn,$roll[$i]);

$full_name=mysqli_real_escape_string($conn,$name[$i]);

$mob=mysqli_real_escape_string($conn,$mobile[$i]);

$mail=mysqli_real_escape_string($conn,$email[$i]);




// check student exists


$check=mysqli_query($conn,

"
SELECT id

FROM students

WHERE class_id='$class_id'

AND roll_no='$roll_no'

"

);



if(mysqli_num_rows($check)>0)
{


// UPDATE


mysqli_query($conn,


"

UPDATE students

SET

full_name='$full_name',

mobile='$mob',

email='$mail'


WHERE class_id='$class_id'

AND roll_no='$roll_no'


"


);



}

else
{


// INSERT


mysqli_query($conn,


"

INSERT INTO students

(
class_id,
roll_no,
full_name,
mobile,
email
)

VALUES

(
'$class_id',
'$roll_no',
'$full_name',
'$mob',
'$mail'
)


"


);



}



}





echo "

<script>

alert('Students Updated Successfully');

window.location='manage_students_excel.php?class_id=$class_id';

</script>

";



?>