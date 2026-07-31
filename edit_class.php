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


$id = intval($_GET['id']);



// Update Class

if(isset($_POST['update'])){


    $class_name = $_POST['class_name'];

    $subject = $_POST['subject'];



    mysqli_query($conn,

    "UPDATE classes SET

    class_name='$class_name',

    subject='$subject'

    WHERE id='$id'"

    );



    echo "

    <script>

    alert('Class Updated Successfully');

    window.location='classes.php';

    </script>

    ";

}





// Get Class Data

$classQuery = mysqli_query($conn,

"SELECT * FROM classes WHERE id='$id'"

);


$classData = mysqli_fetch_assoc($classQuery);



if(!$classData){

    die("Class Not Found");

}



?>


<!DOCTYPE html>

<html>

<head>

<title>Edit Class</title>


<meta name="viewport" content="width=device-width, initial-scale=1">


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">


<style>

body{

background:#f5f7fb;

}


.card{

border:none;

border-radius:15px;

box-shadow:0 5px 15px rgba(0,0,0,.1);

}


</style>


</head>


<body>


<div class="container mt-5">


<div class="card">


<div class="card-body">


<h3 class="mb-4">

<i class="fa fa-edit"></i>

Edit Class

</h3>



<form method="POST">


<div class="mb-3">


<label class="form-label">

Class Name

</label>


<input type="text"

name="class_name"

class="form-control"

value="<?php echo $classData['class_name']; ?>"

required>


</div>




<div class="mb-3">


<label class="form-label">

Subject

</label>


<input type="text"

name="subject"

class="form-control"

value="<?php echo $classData['subject']; ?>"

required>


</div>



<button name="update"

class="btn btn-success">

<i class="fa fa-save"></i>

Update Class

</button>



<a href="classes.php"

class="btn btn-secondary">

Back

</a>



</form>



<hr>



<h4>

Students List

</h4>



<table class="table table-bordered mt-3">


<tr class="table-primary">


<th width="120">

Roll No

</th>


<th>

Student Name

</th>


<th width="150">

Action

</th>


</tr>





<?php


$studentQuery = mysqli_query($conn,


"SELECT * FROM students

WHERE class_id='$id'

ORDER BY roll_no ASC"

);



if(mysqli_num_rows($studentQuery)>0){



while($student=mysqli_fetch_assoc($studentQuery)){



?>



<tr>


<td>

<?php echo $student['roll_no']; ?>

</td>



<td>

<?php echo $student['full_name']; ?>

</td>



<td>


<a href="remove_student.php?id=<?php echo $student['id']; ?>&class_id=<?php echo $id; ?>"

class="btn btn-danger btn-sm"

onclick="return confirm('Remove this student?')">


<i class="fa fa-trash"></i>

Remove


</a>



</td>


</tr>



<?php

}


}

else{


?>


<tr>

<td colspan="3" class="text-center">

No Students Added

</td>

</tr>


<?php

}

?>



</table>



<a href="add_student.php?class_id=<?php echo $id; ?>"

class="btn btn-primary">

<i class="fa fa-user-plus"></i>

Add Student

</a>



</div>

</div>

</div>



</body>

</html>