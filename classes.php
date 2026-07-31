<?php

session_start();

include("config/db.php");


if(!isset($_SESSION['teacher_id'])){

    header("Location: login.php");
    exit();

}


$teacher_id = $_SESSION['teacher_id'];


// Teacher Profile Data

$teacher_query = mysqli_query($conn,

"SELECT * FROM teachers WHERE id='$teacher_id'"

);


$teacher = mysqli_fetch_assoc($teacher_query);



// Get Classes Only For Logged In Teacher

$classes = mysqli_query($conn,

"SELECT * FROM classes 
WHERE teacher_id='$teacher_id'
ORDER BY id DESC"

);

?>


<!DOCTYPE html>

<html>

<head>

<title>Classes</title>


<meta name="viewport" content="width=device-width, initial-scale=1">


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">



<style>


body{

background: #edeaed;

}



.card{

border:none;

border-radius:15px;

box-shadow:0 5px 15px rgba(0, 0, 0, 0.66);

}



.class-card{

transition:.4s;

}



.class-card:hover{

transform:translateY(-5px);

}



.profile-img{

width:30px;

height:30px;

border-radius:50%;

object-fit:cover;

}



.big-profile{

width:90px;

height:90px;

border-radius:50%;

object-fit:cover;

}

.profile-button{

width:45px;
height:45px;
padding:0;
border-radius:50%;
overflow:hidden;
border:1px solid #ddd;
background:white;
display:flex;
align-items:center;
justify-content:center;

}


.profile-img{

width:55px;
height:55px;
border-radius:50%;
object-fit:cover;

}


</style>


</head>


<body>



<div class="container mt-4">



<div class="d-flex justify-content-between align-items-center mb-4">



<h2>


My Classes

</h2>




<div class="d-flex align-items-center">



<a href="create_class.php"

class="btn btn-primary me-3">


<i class="fa fa-plus"></i>

Create Class


</a>





<!-- PROFILE DROPDOWN -->


<div class="dropdown">



<button class="profile-button"

data-bs-toggle="dropdown">



<?php

if(!empty($teacher['profile_photo']) && file_exists("uploads/".$teacher['profile_photo'])){


?>


<img src="uploads/<?php echo $teacher['profile_photo']; ?>"

class="profile-img">


<?php


}

else{


?>


<i class="fa fa-user fa-2x"></i>


<?php


}


?>



</button>





<div class="dropdown-menu dropdown-menu-end p-3"

style="width:330px;">





<div class="text-center">



<?php

if(!empty($teacher['profile_photo']) && file_exists("uploads/".$teacher['profile_photo'])){


?>

<img src="uploads/<?php echo $teacher['profile_photo']; ?>"

class="big-profile">


<?php

}

else{

?>

<i class="fa fa-user-circle fa-5x"></i>


<?php

}

?>




<h5 class="mt-2">

<?php echo $teacher['full_name']; ?>

</h5>


<p class="text-muted">

<?php echo $teacher['email']; ?>

</p>



</div>




<hr>



<p>

<b>
Mobile:
</b>

<?php echo $teacher['mobile']; ?>

</p>



<p>

<b>
College:
</b>

<?php echo $teacher['college_name']; ?>

</p>



<p>

<b>
Department:
</b>

<?php echo $teacher['department']; ?>

</p>



<p>

<b>
Designation:
</b>

<?php echo $teacher['designation']; ?>

</p>




<hr>



<a href="teacher_profile.php"

class="btn btn-primary w-100 mb-2">


<i class="fa fa-user-edit"></i>

Edit Profile


</a>




<a href="logout.php"

class="btn btn-danger w-100"

onclick="return confirm('Are you sure you want to logout?')">


<i class="fa fa-sign-out-alt"></i>

Logout


</a>




</div>


</div>


</div>


</div>






<div class="row">



<?php


if(mysqli_num_rows($classes)>0){



while($row=mysqli_fetch_assoc($classes)){



$class_id=$row['id'];



$countQuery=mysqli_query($conn,


"SELECT COUNT(*) as total

FROM students

WHERE class_id='$class_id'"

);



$count=mysqli_fetch_assoc($countQuery);



?>



<div class="col-md-4 mb-4">



<div class="card class-card">



<div class="card-body">



<div class="d-flex justify-content-between">



<div>


<h4>

<?php echo $row['class_name']; ?>

</h4>



<p class="text-muted">

Subject:

<b>

<?php echo $row['subject']; ?>

</b>

</p>



<p>

Students:

<b>

<?php echo $count['total']; ?>

</b>

</p>


</div>




<!-- THREE DOT -->


<div class="dropdown">


<button class="btn btn-light"

data-bs-toggle="dropdown">


<i class="fa fa-ellipsis-v"></i>


</button>



<ul class="dropdown-menu dropdown-menu-end">



<li>

<a class="dropdown-item"

href="open-class.php?class_id=<?php echo $class_id; ?>">


<i class="fa fa-door-open"></i>

Open Class


</a>

</li>



<li>

<a class="dropdown-item"

href="attendance_sheet.php?class_id=<?php echo $class_id; ?>">


<i class="fa fa-calendar-check"></i>

Attendance Sheet


</a>

</li>



<!--  <li>

<a class="dropdown-item"

href="monthly_attendance.php?class_id=<?php echo $class_id; ?>">


<i class="fa fa-file-lines"></i>

Monthly Attendance


</a>

</li>  -->



<li>

<a class="dropdown-item"

href="add_student.php?class_id=<?php echo $class_id; ?>">


<i class="fa fa-user-plus"></i>

Add Student


</a>

</li>



<li>

<hr class="dropdown-divider">

</li>



<li>

<a class="dropdown-item"

href="edit_class.php?id=<?php echo $class_id; ?>">


<i class="fa fa-edit"></i>

Edit Class


</a>

</li>




<li>


<a class="dropdown-item text-danger"

href="delete_class.php?id=<?php echo $class_id; ?>"

onclick="return confirm('Delete this class?')">


<i class="fa fa-trash"></i>

Delete Class


</a>


</li>



</ul>


</div>



</div>



<hr>




<a href="open-class.php?class_id=<?php echo $class_id; ?>"

class="btn btn-success w-100">


<i class="fa fa-play"></i>

Start Attendance


</a>




</div>

</div>


</div>



<?php

}

}

else{


?>

<h4 class="text-center">

No Classes Found

</h4>


<?php

}

?>


</div>



</div>





<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


</body>

</html>