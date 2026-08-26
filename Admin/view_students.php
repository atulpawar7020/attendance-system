<?php

session_start();

require_once __DIR__ . '/../config/db.php';


/*
|--------------------------------------------------------------------------
| ADMIN LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {

    header("Location: ../login.php");
    exit();

}

$admin_id = (int)$_SESSION['admin_id'];


/*
|--------------------------------------------------------------------------
| CLASS ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET['class_id']) || !is_numeric($_GET['class_id'])) {

    die("Invalid Class ID.");

}

$class_id = (int)$_GET['class_id'];

if ($class_id <= 0) {

    die("Invalid Class ID.");

}


/*
|--------------------------------------------------------------------------
| DELETE STUDENT
|--------------------------------------------------------------------------
| Delete only if student belongs to admin's class
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['delete_student']) &&
    is_numeric($_GET['delete_student'])
) {

    $student_id = (int)$_GET['delete_student'];

    if ($student_id > 0) {

        /*
        | Verify student belongs to this admin's class
        */

        $deleteStmt = mysqli_prepare(
            $conn,

            "DELETE s
             FROM students s
             INNER JOIN classes c
                ON c.id = s.class_id
             WHERE s.id = ?
             AND s.class_id = ?
             AND c.admin_id = ?"
        );

        if (!$deleteStmt) {

            die(
                "Database Error: " .
                mysqli_error($conn)
            );

        }

        mysqli_stmt_bind_param(
            $deleteStmt,
            "iii",
            $student_id,
            $class_id,
            $admin_id
        );

        mysqli_stmt_execute($deleteStmt);

        mysqli_stmt_close($deleteStmt);

    }


    /*
    | Go back to clean URL
    */

    header(
        "Location: view_students.php?class_id=" .
        $class_id
    );

    exit();

}


/*
|--------------------------------------------------------------------------
| GET CLASS
|--------------------------------------------------------------------------
*/

$classStmt = mysqli_prepare(
    $conn,

    "SELECT
        id,
        class_name,
        academic_year,
        admin_id
     FROM classes
     WHERE id = ?
     AND admin_id = ?
     LIMIT 1"
);

if (!$classStmt) {

    die(
        "Database Error: " .
        mysqli_error($conn)
    );

}

mysqli_stmt_bind_param(
    $classStmt,
    "ii",
    $class_id,
    $admin_id
);

mysqli_stmt_execute($classStmt);

$classResult =
    mysqli_stmt_get_result($classStmt);


if (
    !$classResult ||
    mysqli_num_rows($classResult) == 0
) {

    die("
        <div style='
            font-family:Arial;
            text-align:center;
            margin-top:100px;
        '>

            <h2>Class Not Found</h2>

            <p>
                This class does not belong to the
                logged-in admin.
            </p>

            <a href='admin_classes.php'>
                Back to Classes
            </a>

        </div>
    ");

}


$class =
    mysqli_fetch_assoc($classResult);


mysqli_stmt_close($classStmt);


/*
|--------------------------------------------------------------------------
| GET STUDENTS
|--------------------------------------------------------------------------
*/

$studentStmt = mysqli_prepare(
    $conn,

    "SELECT
        id,
        roll_no,
        full_name,
        mobile,
        email
     FROM students
     WHERE class_id = ?
     ORDER BY
        CAST(roll_no AS UNSIGNED) ASC,
        roll_no ASC"
);

if (!$studentStmt) {

    die(
        "Database Error: " .
        mysqli_error($conn)
    );

}

mysqli_stmt_bind_param(
    $studentStmt,
    "i",
    $class_id
);

mysqli_stmt_execute($studentStmt);

$studentResult =
    mysqli_stmt_get_result($studentStmt);

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    <?php
    echo htmlspecialchars(
        $class['class_name']
    );
    ?>
    - Students
</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
rel="stylesheet">


<style>

body {

    background:#f5f7fb;

    font-family:Arial,sans-serif;

}


.navbar {

    background:white;

    box-shadow:0 2px 10px rgba(0,0,0,.08);

}


.card {

    border:none;

    border-radius:18px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.08);

}


.student-table {

    background:white;

    border-radius:18px;

    overflow:hidden;

    box-shadow:
        0 5px 20px rgba(0,0,0,.08);

}


.table {

    margin-bottom:0;

}


.table thead th {

    background:#0d6efd;

    color:white;

    border:none;

    padding:15px;

    white-space:nowrap;

}


.table tbody td {

    padding:14px;

    vertical-align:middle;

}


.student-avatar {

    width:42px;

    height:42px;

    min-width:42px;

    border-radius:50%;

    background:#e8f1ff;

    color:#0d6efd;

    display:flex;

    align-items:center;

    justify-content:center;

    font-weight:bold;

}


.action-buttons {

    display:flex;

    gap:7px;

    white-space:nowrap;

}


.action-buttons .btn {

    border-radius:8px;

}


.empty-box {

    background:white;

    border-radius:18px;

    padding:60px 20px;

    text-align:center;

    box-shadow:
        0 5px 20px rgba(0,0,0,.08);

}


@media(max-width:768px){

    .action-buttons {

        flex-direction:column;

    }

    .action-buttons .btn {

        width:100%;

    }

}

</style>

</head>


<body>


<!-- =========================================
     NAVBAR
========================================= -->

<nav class="navbar py-3">

<div class="container-fluid px-4">


<div>

<h4 class="mb-0 fw-bold">

<i class="fa-solid fa-users text-primary"></i>

Students

</h4>

</div>


<div class="d-flex gap-2">


<a
href="add_student.php?class_id=<?php echo $class_id; ?>"
class="btn btn-primary">

<i class="fa-solid fa-user-plus"></i>

Add Student

</a>


<a
href="admin_classes.php"
class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back

</a>


</div>


</div>

</nav>



<!-- =========================================
     MAIN
========================================= -->

<div class="container py-4">


<!-- CLASS INFORMATION -->

<div class="card p-4 mb-4">


<div class="row align-items-center">


<div class="col-md-8">


<h2 class="fw-bold mb-2">

<?php

echo htmlspecialchars(
    $class['class_name']
);

?>

</h2>


<p class="text-muted mb-0">

<i class="fa-solid fa-calendar"></i>

Academic Year:

<strong>

<?php

echo htmlspecialchars(
    $class['academic_year']
);

?>

</strong>

</p>


</div>


<div class="col-md-4 text-md-end mt-3 mt-md-0">


<span class="badge bg-primary fs-6 px-3 py-2">

<i class="fa-solid fa-users"></i>

<?php

echo mysqli_num_rows(
    $studentResult
);

?>

 Students

</span>


</div>


</div>

</div>



<?php

if (
    mysqli_num_rows($studentResult) == 0
) {

?>


<!-- =========================================
     NO STUDENTS
========================================= -->

<div class="empty-box">


<i
class="fa-solid fa-user-slash text-muted"
style="font-size:60px;">
</i>


<h4 class="mt-3">

No Students Found

</h4>


<p class="text-muted">

This class does not have any students yet.

</p>


<a
href="add_student.php?class_id=<?php echo $class_id; ?>"
class="btn btn-primary">

<i class="fa fa-user-plus"></i>

Add Student

</a>


</div>


<?php

} else {

?>


<!-- =========================================
     STUDENT TABLE
========================================= -->

<div class="student-table">


<div class="table-responsive">


<table class="table table-hover">


<thead>

<tr>

<th>#</th>

<th>Roll No</th>

<th>Student Name</th>

<th>Mobile</th>

<th>Email</th>

<th>Action</th>

</tr>

</thead>


<tbody>


<?php

$count = 1;


while (
    $student =
    mysqli_fetch_assoc($studentResult)
) {

$student_id =
    (int)$student['id'];

?>


<tr>


<!-- NUMBER -->

<td>

<?php

echo $count++;

?>

</td>


<!-- ROLL NUMBER -->

<td>

<strong>

<?php

echo htmlspecialchars(
    $student['roll_no']
);

?>

</strong>

</td>


<!-- NAME -->

<td>


<div class="d-flex align-items-center gap-3">


<div class="student-avatar">

<?php

$name =
    trim(
        $student['full_name']
    );

$firstLetter =
    !empty($name)
    ? substr($name,0,1)
    : '?';

echo htmlspecialchars(
    strtoupper($firstLetter)
);

?>

</div>


<strong>

<?php

echo htmlspecialchars(
    $student['full_name']
);

?>

</strong>


</div>


</td>


<!-- MOBILE -->

<td>

<?php

if (
    !empty($student['mobile'])
) {

echo htmlspecialchars(
    $student['mobile']
);

} else {

?>

<span class="text-muted">

Not provided

</span>

<?php

}

?>

</td>


<!-- EMAIL -->

<td>

<?php

if (
    !empty($student['email'])
) {

echo htmlspecialchars(
    $student['email']
);

} else {

?>

<span class="text-muted">

Not provided

</span>

<?php

}

?>

</td>


<!-- ACTION -->

<td>


<div class="action-buttons">


<!-- EDIT -->

<a
href="edit_student.php?id=<?php echo $student_id; ?>&class_id=<?php echo $class_id; ?>"
class="btn btn-warning btn-sm">

<i class="fa-solid fa-pen"></i>

Edit

</a>


<!-- REMOVE -->

<a
href="view_students.php?class_id=<?php echo $class_id; ?>&delete_student=<?php echo $student_id; ?>"
class="btn btn-danger btn-sm"

onclick="return confirm(
    'Are you sure you want to remove this student?'
);">

<i class="fa-solid fa-trash"></i>

Remove

</a>


</div>


</td>


</tr>


<?php

}

?>


</tbody>

</table>


</div>

</div>


<?php

}

?>


</div>


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>


<?php

mysqli_stmt_close($studentStmt);

?>