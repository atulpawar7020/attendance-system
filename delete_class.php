<?php

session_start();

include("config/db.php");


if(!isset($_SESSION['teacher_id'])){

header("Location: login.php");
exit();

}



if(!isset($_GET['id'])){

die("Class ID Missing");

}



$id=$_GET['id'];



// Delete attendance first

mysqli_query($conn,

"DELETE FROM attendance WHERE class_id='$id'"

);



// Delete students

mysqli_query($conn,

"DELETE FROM students WHERE class_id='$id'"

);



// Delete class

mysqli_query($conn,

"DELETE FROM classes WHERE id='$id'"

);



echo "

<script>

alert('Class Deleted Successfully');

window.location='classes.php';

</script>

";

?>