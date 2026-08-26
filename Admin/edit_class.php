<?php

session_start();

require_once __DIR__ . '/../config/db.php';


/*
|--------------------------------------------------------------------------
| ADMIN LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['admin_id']) ||
    empty($_SESSION['admin_id'])
) {

    header("Location: ../login.php");
    exit();

}


$admin_id =
    (int) $_SESSION['admin_id'];


/*
|--------------------------------------------------------------------------
| CLASS ID
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {

    die("Invalid Class ID.");

}


$class_id =
    (int) $_GET['id'];


/*
|--------------------------------------------------------------------------
| GET CLASS
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Use admin_id, NOT teacher_id.
|
*/

$class_stmt =
    mysqli_prepare(
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

    mysqli_stmt_close(
        $class_stmt
    );

    die(
        "Class not found or you do not have permission to edit this class."
    );

}


$class =
    mysqli_fetch_assoc(
        $class_result
    );


mysqli_stmt_close(
    $class_stmt
);


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$message = "";


/*
|--------------------------------------------------------------------------
| UPDATE CLASS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_class'])
) {


    $class_name =
        trim(
            $_POST['class_name'] ?? ''
        );


    $academic_year =
        trim(
            $_POST['academic_year'] ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $class_name === '' ||
        $academic_year === ''
    ) {

        $message = "
            <div class='alert alert-danger'>
                <i class='fa-solid fa-circle-exclamation'></i>
                Class Name and Academic Year are required.
            </div>
        ";

    }

    else {


        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        $update_stmt =
            mysqli_prepare(
                $conn,

                "UPDATE classes

                 SET
                    class_name = ?,
                    academic_year = ?

                 WHERE id = ?
                 AND admin_id = ?"
            );


        if (!$update_stmt) {

            $message = "
                <div class='alert alert-danger'>
                    Database Error:
                    " .
                    htmlspecialchars(
                        mysqli_error($conn),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    .
                "
                </div>
            ";

        }

        else {


            mysqli_stmt_bind_param(
                $update_stmt,
                "ssii",
                $class_name,
                $academic_year,
                $class_id,
                $admin_id
            );


            if (
                mysqli_stmt_execute(
                    $update_stmt
                )
            ) {


                mysqli_stmt_close(
                    $update_stmt
                );


                /*
                |--------------------------------------------------------------------------
                | SUCCESS → BACK TO ADMIN CLASSES
                |--------------------------------------------------------------------------
                */

                header(
                    "Location: admin_classes.php?updated=1"
                );

                exit();

            }

            else {

                $message = "
                    <div class='alert alert-danger'>

                        <i class='fa-solid fa-circle-exclamation'></i>

                        Database Error:

                        " .
                        htmlspecialchars(
                            mysqli_stmt_error(
                                $update_stmt
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        .

                    "
                    </div>
                ";

            }


            mysqli_stmt_close(
                $update_stmt
            );

        }

    }

}


/*
|--------------------------------------------------------------------------
| ESCAPE FUNCTION
|--------------------------------------------------------------------------
*/

function e($value)
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

<title>Edit Class</title>


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


.edit-card {

    max-width: 650px;

    margin:
        60px auto;

    background: #ffffff;

    border: none;

    border-radius: 18px;

    overflow: hidden;

    box-shadow:
        0 5px 25px
        rgba(0,0,0,.10);

}


.header {

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color: white;

    padding: 28px;

}


.header h3 {

    margin: 0;

    font-weight: 700;

}


.header p {

    margin:
        7px 0 0;

    opacity: .9;

}


.card-body {

    padding: 30px;

}


.form-label {

    font-weight: 600;

}


.form-control {

    height: 50px;

    border-radius: 10px;

}


.form-control:focus {

    border-color: #0d6efd;

    box-shadow:
        0 0 0 .2rem
        rgba(13,110,253,.15);

}


.btn {

    height: 48px;

    border-radius: 10px;

    font-weight: 600;

}


.info-box {

    background: #f8f9fa;

    border-radius: 10px;

    padding: 12px 15px;

    margin-bottom: 20px;

    color: #666;

}

</style>

</head>


<body>


<div class="edit-card">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="header">

<h3>

<i class="fa-solid fa-pen-to-square"></i>

Edit Class

</h3>


<p>

Update your class information

</p>

</div>



<!-- =====================================================
     BODY
===================================================== -->

<div class="card-body">


<!-- MESSAGE -->

<?= $message ?>



<!-- CLASS INFORMATION -->

<div class="info-box">

<i class="fa-solid fa-graduation-cap text-primary"></i>

<strong> Class ID:</strong>

#<?= e($class['id']) ?>

</div>



<form
    method="POST"
    action="edit_class.php?id=<?= $class_id ?>"
>


<!-- CLASS NAME -->

<div class="mb-4">

<label class="form-label">

Class Name

</label>


<input
    type="text"
    name="class_name"
    class="form-control"
    value="<?= e($class['class_name']) ?>"
    placeholder="Enter class name"
    required
>

</div>



<!-- ACADEMIC YEAR -->

<div class="mb-4">

<label class="form-label">

Academic Year

</label>


<input
    type="text"
    name="academic_year"
    class="form-control"
    value="<?= e($class['academic_year']) ?>"
    placeholder="Example: 2026-27"
    required
>

</div>



<!-- SAVE -->

<button
    type="submit"
    name="update_class"
    class="
        btn
        btn-primary
        w-100
    "
>

<i class="fa-solid fa-floppy-disk"></i>

Save Changes

</button>



<!-- CANCEL -->

<a
    href="admin_classes.php"
    class="
        btn
        btn-secondary
        w-100
        mt-3
        d-flex
        align-items-center
        justify-content-center
    "
>

<i class="fa-solid fa-arrow-left me-2"></i>

Cancel

</a>


</form>


</div>

</div>


</body>

</html>