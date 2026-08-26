<?php

session_start();

require_once __DIR__ . '/../config/db.php';


/*
|--------------------------------------------------------------------------
| ADMIN LOGIN CHECK
|--------------------------------------------------------------------------
|
| Common login page is outside Admin folder:
|
| attendance-system/login.php
|
*/

if (
    !isset($_SESSION['admin_id']) ||
    empty($_SESSION['admin_id'])
) {

    header("Location: ../login.php");
    exit();

}

$admin_id = (int) $_SESSION['admin_id'];


/*
|--------------------------------------------------------------------------
| CLASS ID CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['class_id']) ||
    !is_numeric($_GET['class_id'])
) {

    die("Class ID is missing.");

}

$class_id = (int) $_GET['class_id'];


/*
|--------------------------------------------------------------------------
| GET CLASS
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Admin classes are connected using admin_id.
|
*/

$class_stmt = mysqli_prepare(
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


if (!$class_stmt) {

    die(
        "Database Error: " .
        mysqli_error($conn)
    );

}


mysqli_stmt_bind_param(
    $class_stmt,
    "ii",
    $class_id,
    $admin_id
);


mysqli_stmt_execute(
    $class_stmt
);


$class_result =
    mysqli_stmt_get_result(
        $class_stmt
    );


if (
    !$class_result ||
    mysqli_num_rows($class_result) === 0
) {

    mysqli_stmt_close($class_stmt);

    die(
        "Class not found or you do not have permission to access this class."
    );

}


$class =
    mysqli_fetch_assoc(
        $class_result
    );


mysqli_stmt_close($class_stmt);


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$message = "";

$messageType = "";


/*
|--------------------------------------------------------------------------
| ADD STUDENT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_student'])
) {


    $roll_no =
        trim(
            $_POST['roll_no'] ?? ''
        );


    $full_name =
        trim(
            $_POST['full_name'] ?? ''
        );


    $email =
        trim(
            $_POST['email'] ?? ''
        );


    $mobile =
        trim(
            $_POST['mobile'] ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $roll_no === '' ||
        $full_name === ''
    ) {

        $message =
            "Roll Number and Student Name are required.";

        $messageType =
            "danger";

    }

    elseif (
        $email !== '' &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            "Please enter a valid email address.";

        $messageType =
            "danger";

    }

    elseif (
        $mobile !== '' &&
        !preg_match(
            '/^[0-9]{10}$/',
            $mobile
        )
    ) {

        $message =
            "Please enter a valid 10-digit mobile number.";

        $messageType =
            "danger";

    }

    else {


        /*
        |--------------------------------------------------------------------------
        | CHECK DUPLICATE ROLL NUMBER
        |--------------------------------------------------------------------------
        */

        $check_stmt =
            mysqli_prepare(
                $conn,

                "SELECT id
                 FROM students
                 WHERE class_id = ?
                 AND roll_no = ?
                 LIMIT 1"
            );


        if (!$check_stmt) {

            $message =
                "Database error.";

            $messageType =
                "danger";

        }

        else {


            mysqli_stmt_bind_param(
                $check_stmt,
                "is",
                $class_id,
                $roll_no
            );


            mysqli_stmt_execute(
                $check_stmt
            );


            $check_result =
                mysqli_stmt_get_result(
                    $check_stmt
                );


            if (
                mysqli_num_rows(
                    $check_result
                ) > 0
            ) {

                $message =
                    "This roll number already exists in this class.";

                $messageType =
                    "danger";

            }

            else {


                /*
                |--------------------------------------------------------------------------
                | INSERT STUDENT
                |--------------------------------------------------------------------------
                */

                $insert_stmt =
                    mysqli_prepare(
                        $conn,

                        "INSERT INTO students
                        (
                            class_id,
                            roll_no,
                            full_name,
                            mobile,
                            email
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?
                        )"
                    );


                if (!$insert_stmt) {

                    $message =
                        "Database error while adding student.";

                    $messageType =
                        "danger";

                }

                else {


                    mysqli_stmt_bind_param(
                        $insert_stmt,
                        "issss",
                        $class_id,
                        $roll_no,
                        $full_name,
                        $mobile,
                        $email
                    );


                    if (
                        mysqli_stmt_execute(
                            $insert_stmt
                        )
                    ) {

                        /*
                        |--------------------------------------------------------------------------
                        | SUCCESS
                        |--------------------------------------------------------------------------
                        */

                        mysqli_stmt_close(
                            $insert_stmt
                        );

                        mysqli_stmt_close(
                            $check_stmt
                        );


                        header(
                            "Location: add_student.php?class_id=" .
                            $class_id .
                            "&success=1"
                        );

                        exit();

                    }

                    else {

                        $message =
                            "Database Error: " .
                            mysqli_stmt_error(
                                $insert_stmt
                            );

                        $messageType =
                            "danger";

                    }


                    mysqli_stmt_close(
                        $insert_stmt
                    );

                }

            }


            mysqli_stmt_close(
                $check_stmt
            );

        }

    }

}


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE AFTER REDIRECT
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['success']) &&
    $_GET['success'] == '1'
) {

    $message =
        "Student added successfully.";

    $messageType =
        "success";

}


/*
|--------------------------------------------------------------------------
| ESCAPE FUNCTION
|--------------------------------------------------------------------------
*/

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Add Student</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- Font Awesome -->

<link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    rel="stylesheet"
>


<style>

body {

    margin: 0;

    background: #f5f7fb;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}


.header {

    background: #ffffff;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,.08);

    padding: 20px;

}


.card {

    border: none;

    border-radius: 18px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.10);

}


.form-control {

    height: 45px;

    border-radius: 9px;

}


.btn {

    border-radius: 9px;

    font-weight: 600;

}


.class-badge {

    background: #eaf3ff;

    color: #0d6efd;

    border-radius: 10px;

    padding: 8px 12px;

    display: inline-block;

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<div class="header">

<div class="container">

<div
    class="
        d-flex
        justify-content-between
        align-items-center
        gap-3
    "
>


<div>

<h4 class="mb-1">

<i
    class="
        fa-solid
        fa-user-plus
        text-primary
    "
></i>

Add Student

</h4>


<div class="text-muted">

<?= e($class['class_name']) ?>

&nbsp; | &nbsp;

<?= e($class['academic_year']) ?>

</div>

</div>


<a
    href="admin_classes.php"
    class="btn btn-secondary"
>

<i class="fa-solid fa-arrow-left"></i>

Back

</a>


</div>

</div>

</div>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="container py-4">


<div class="row justify-content-center">


<div class="col-md-8">


<!-- MESSAGE -->

<?php if ($message !== ''): ?>

<div
    class="
        alert
        alert-<?= e($messageType) ?>
        alert-dismissible
        fade
        show
    "
>

<?php if ($messageType === 'success'): ?>

<i class="fa-solid fa-circle-check"></i>

<?php else: ?>

<i class="fa-solid fa-circle-exclamation"></i>

<?php endif; ?>


<?= e($message) ?>


<button
    type="button"
    class="btn-close"
    data-bs-dismiss="alert"
></button>

</div>

<?php endif; ?>



<!-- =====================================================
     ADD STUDENT CARD
===================================================== -->

<div class="card p-4">


<h5 class="mb-4">

<i
    class="
        fa-solid
        fa-user-plus
        text-primary
    "
></i>

Add Student Manually

</h5>


<div class="mb-4">

<span class="class-badge">

<i class="fa-solid fa-graduation-cap"></i>

<?= e($class['class_name']) ?>

</span>

</div>



<form
    method="POST"
    action="add_student.php?class_id=<?= $class_id ?>"
>


<div class="row">


<!-- ROLL NUMBER -->

<div class="col-md-6 mb-3">

<label class="form-label fw-bold">

Roll Number *

</label>


<input
    type="text"
    name="roll_no"
    class="form-control"
    placeholder="Enter roll number"
    required
>

</div>



<!-- FULL NAME -->

<div class="col-md-6 mb-3">

<label class="form-label fw-bold">

Student Name *

</label>


<input
    type="text"
    name="full_name"
    class="form-control"
    placeholder="Enter student name"
    required
>

</div>



<!-- EMAIL -->

<div class="col-md-6 mb-3">

<label class="form-label fw-bold">

Email

</label>


<input
    type="email"
    name="email"
    class="form-control"
    placeholder="student@example.com"
>

</div>



<!-- MOBILE -->

<div class="col-md-6 mb-3">

<label class="form-label fw-bold">

Contact Number

</label>


<input
    type="text"
    name="mobile"
    class="form-control"
    maxlength="10"
    pattern="[0-9]{10}"
    placeholder="10 digit mobile number"
>

</div>


</div>



<button
    type="submit"
    name="add_student"
    class="
        btn
        btn-primary
        w-100
    "
>

<i class="fa-solid fa-plus"></i>

Add Student

</button>


</form>



<hr class="my-4">



<!-- EXCEL -->

<h5>

<i
    class="
        fa-solid
        fa-file-excel
        text-success
    "
></i>

Upload Students

</h5>


<p class="text-muted">

Upload Excel sheet to add multiple students.

</p>


<a
    href="upload_students.php?class_id=<?= $class_id ?>"
    class="
        btn
        btn-success
    "
>

<i class="fa-solid fa-file-excel"></i>

Upload Excel Sheet

</a>


</div>

</div>

</div>

</div>



</body>

</html>