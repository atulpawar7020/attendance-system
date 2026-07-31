<?php

session_start();

include("config/db.php");


if(!isset($_SESSION['teacher_id'])){

header("Location: login.php");

exit();

}


$student_id=$_GET['id'];

$class_id=$_GET['class_id'];



// Delete attendance of this student

mysqli_query($conn,

"DELETE FROM attendance

WHERE student_id='$student_id'

AND class_id='$class_id'"

);



// Remove student from class

mysqli_query($conn,


"DELETE FROM students

WHERE id='$student_id'

AND class_id='$class_id'"

);



echo "

<script>

alert('Student Removed');

window.location='edit_class.php?id=$class_id';

</script>

";


?>