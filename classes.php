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


/* =========================================
   BODY
========================================= */

body {
    background: #edeaed;
    font-family: Arial, Helvetica, sans-serif;
    margin: 0;
    padding: 0;
}


/* =========================================
   COMMON CARD
========================================= */

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.66);
}


/* =========================================
   CLASS CARD
========================================= */

.class-card {
    position: relative;

    border: none;
    border-radius: 15px;

    background: #ffffff;

    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.66);

    transition: 0.4s;

    /* IMPORTANT:
       Dropdown card ke bahar bhi dikhega
    */
    overflow: visible !important;

    z-index: 1;
}


.class-card:hover {
    transform: translateY(-5px);

    z-index: 100;
}


/* =========================================
   CLASS GRID / CONTAINER
========================================= */

.class-grid,
.classes-container,
.row {
    overflow: visible !important;
}


/* =========================================
   PROFILE IMAGE
========================================= */

.profile-img {
    width: 55px;
    height: 55px;

    border-radius: 50%;

    object-fit: cover;

    display: block;
}


/* =========================================
   BIG PROFILE
========================================= */

.big-profile {
    width: 90px;
    height: 90px;

    border-radius: 50%;

    object-fit: cover;

    display: block;
}


/* =========================================
   PROFILE BUTTON
========================================= */

.profile-button {
    width: 45px;
    height: 45px;

    padding: 0;

    border-radius: 50%;

    overflow: hidden;

    border: 1px solid #ddd;

    background: white;

    display: flex;

    align-items: center;

    justify-content: center;

    cursor: pointer;
}


.profile-button:hover {
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
}


/* =========================================
   THREE DOT MENU CONTAINER
========================================= */

.class-menu {
    position: relative;

    z-index: 9999;
}


/* =========================================
   THREE DOT BUTTON
========================================= */

.menu-btn {
    width: 42px;
    height: 42px;

    padding: 0;

    border: none;

    border-radius: 10px;

    background: #f5f5f5;

    color: #222;

    font-size: 24px;

    cursor: pointer;

    display: flex;

    align-items: center;

    justify-content: center;

    transition: 0.2s;
}


.menu-btn:hover {
    background: #e5e5e5;
}


.menu-btn:focus {
    outline: none;

    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
}


/* =========================================
   DROPDOWN MENU
========================================= */

.class-dropdown {
    position: absolute;

    top: 48px;

    right: 0;

    width: 230px;

    background: #ffffff;

    border-radius: 10px;

    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);

    z-index: 999999;

    overflow: visible !important;

    padding: 6px 0;

    display: none;
}


/* =========================================
   DROPDOWN LINKS
========================================= */

.class-dropdown a {
    display: flex;

    align-items: center;

    gap: 10px;

    width: 100%;

    box-sizing: border-box;

    padding: 12px 16px;

    color: #333;

    text-decoration: none;

    font-size: 15px;

    background: #ffffff;

    white-space: nowrap;

    transition: 0.2s;
}


.class-dropdown a:hover {
    background: #f1f3f5;

    color: #0d6efd;
}


/* =========================================
   DELETE CLASS
========================================= */

.class-dropdown a.delete-class {
    color: #dc3545;
}


.class-dropdown a.delete-class:hover {
    background: #ffe5e5;

    color: #dc3545;
}


/* =========================================
   START ATTENDANCE BUTTON
========================================= */

.start-btn {
    width: 100%;

    border: none;

    border-radius: 8px;

    padding: 12px;

    background: #198754;

    color: white;

    font-size: 16px;

    cursor: pointer;

    transition: 0.2s;
}


.start-btn:hover {
    background: #157347;
}


/* =========================================
   CREATE CLASS BUTTON
========================================= */

.create-class-btn {
    border: none;

    border-radius: 8px;

    background: #0d6efd;

    color: white;

    padding: 10px 18px;

    font-size: 16px;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    gap: 7px;
}


.create-class-btn:hover {
    background: #0b5ed7;

    color: white;
}


/* =========================================
   CLASS TITLE
========================================= */

.class-title {
    font-size: 24px;

    font-weight: 600;

    color: #222;

    margin-bottom: 10px;
}


/* =========================================
   SUBJECT
========================================= */

.class-subject {
    color: #666;

    font-size: 16px;

    margin-bottom: 12px;
}


/* =========================================
   STUDENT COUNT
========================================= */

.student-count {
    font-size: 16px;

    color: #333;

    margin-bottom: 15px;
}


.student-count strong {
    font-weight: 700;

    color: #222;
}


/* =========================================
   PROFILE DROPDOWN
========================================= */

.profile-dropdown {
    position: absolute;

    top: 55px;

    right: 0;

    width: 220px;

    background: white;

    border-radius: 12px;

    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.20);

    z-index: 999999;

    overflow: hidden;
}


.profile-dropdown a {
    display: block;

    padding: 12px 15px;

    color: #333;

    text-decoration: none;
}


.profile-dropdown a:hover {
    background: #f2f4f7;
}


/* =========================================
   DELETE / LOGOUT
========================================= */

.delete-link,
.logout-link {
    color: #dc3545 !important;
}


.delete-link:hover,
.logout-link:hover {
    background: #ffe5e5 !important;
}


/* =========================================
   MOBILE RESPONSIVE
========================================= */

@media (max-width: 768px) {

    .class-dropdown {

        right: 0;

        width: 210px;

    }


    .class-title {

        font-size: 20px;

    }


    .profile-img {

        width: 45px;

        height: 45px;

    }


    .class-card {

        margin-bottom: 20px;

    }

}


/* =========================================
   VERY SMALL SCREEN
========================================= */

@media (max-width: 480px) {

    .class-dropdown {

        width: 200px;

    }


    .menu-btn {

        width: 38px;

        height: 38px;

        font-size: 21px;

    }

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

    <a
        class="dropdown-item"
        href="teacher_admin_classes.php?teacher_class_id=<?php echo (int)$class_id; ?>"
    >

        <i class="fa fa-user-plus"></i>

        Add From Admin

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