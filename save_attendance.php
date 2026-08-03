<?php

session_start();

include("config/db.php");

date_default_timezone_set("Asia/Kolkata");



if(!isset($_SESSION['teacher_id'])){

    header("Location: login.php");
    exit();

}




if(isset($_POST['student_id'])){


$class_id = $_POST['class_id'];

$students = $_POST['student_id'];

$status = $_POST['status'];


// Selected date from open-class.php

$date = $_POST['attendance_date'];





foreach($students as $key=>$student_id){



$student_id = intval($student_id);

$att_status = $status[$key];





// Check attendance already exists

$check = mysqli_query($conn,

"
SELECT id FROM attendance

WHERE class_id='$class_id'

AND student_id='$student_id'

AND attendance_date='$date'

"

);






if(mysqli_num_rows($check)>0){



// UPDATE existing attendance


mysqli_query($conn,

"
UPDATE attendance

SET status='$att_status'

WHERE class_id='$class_id'

AND student_id='$student_id'

AND attendance_date='$date'

"

);



}

else{



// INSERT new attendance


mysqli_query($conn,

"
INSERT INTO attendance

(
class_id,
student_id,
attendance_date,
status
)

VALUES

(
'$class_id',
'$student_id',
'$date',
'$att_status'
)

"

);



}



}






echo "

<script>

alert('Attendance Saved Successfully');

window.location='open-class.php?class_id=$class_id&date=$date';

</script>

";




}

else{


echo "

<script>

alert('No Student Found');

window.history.back();

</script>

";


}



?>