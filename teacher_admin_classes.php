<?php

session_start();

require_once __DIR__ . '/config/db.php';


/*
|--------------------------------------------------------------------------
| TEACHER LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['teacher_id']) ||
    empty($_SESSION['teacher_id'])
) {

    header("Location: login.php");
    exit();

}

$teacher_id = (int) $_SESSION['teacher_id'];


/*
|--------------------------------------------------------------------------
| TEACHER CLASS ID
|--------------------------------------------------------------------------
|
| Ye wahi class hai jisme Admin ke students add honge.
|
*/

if (
    !isset($_GET['teacher_class_id']) ||
    !is_numeric($_GET['teacher_class_id'])
) {

    die("Teacher Class ID is missing.");

}

$teacher_class_id =
    (int) $_GET['teacher_class_id'];


/*
|--------------------------------------------------------------------------
| VERIFY TEACHER CLASS
|--------------------------------------------------------------------------
*/

$class_stmt = mysqli_prepare(
    $conn,

    "SELECT
        id,
        class_name,
        subject,
        teacher_id

     FROM classes

     WHERE id = ?
     AND teacher_id = ?

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
    $teacher_class_id,
    $teacher_id
);


mysqli_stmt_execute($class_stmt);


$class_result =
    mysqli_stmt_get_result($class_stmt);


if (
    !$class_result ||
    mysqli_num_rows($class_result) === 0
) {

    mysqli_stmt_close($class_stmt);

    die(
        "Teacher class not found or you do not have permission to access it."
    );

}


$teacher_class =
    mysqli_fetch_assoc($class_result);


mysqli_stmt_close($class_stmt);


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";

$admin = null;

$admin_classes = [];


/*
|--------------------------------------------------------------------------
| SEARCH ADMIN
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['search_admin'])
) {


    $admin_code =
        strtoupper(
            trim(
                $_POST['admin_code'] ?? ''
            )
        );


    if ($admin_code === '') {

        $message =
            "Please enter Admin Unique Code.";

        $message_type =
            "danger";

    }

    else {


        /*
        |--------------------------------------------------------------------------
        | FIND ADMIN
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $conn,

            "SELECT
                id,
                full_name,
                email,
                admin_code

             FROM teachers

             WHERE admin_code = ?

             AND role = 'admin'

             LIMIT 1"
        );


        if (!$stmt) {

            $message =
                "Database Error: " .
                mysqli_error($conn);

            $message_type =
                "danger";

        }

        else {


            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $admin_code
            );


            mysqli_stmt_execute($stmt);


            $result =
                mysqli_stmt_get_result($stmt);


            if (
                !$result ||
                mysqli_num_rows($result) === 0
            ) {

                $message =
                    "Invalid Admin Unique Code.";

                $message_type =
                    "danger";

            }

            else {


                $admin =
                    mysqli_fetch_assoc($result);


                $admin_id =
                    (int) $admin['id'];


                /*
                |--------------------------------------------------------------------------
                | GET ADMIN CLASSES
                |--------------------------------------------------------------------------
                */

                $stmt2 = mysqli_prepare(
                    $conn,

                    "SELECT

                        c.id,
                        c.class_name,
                        c.academic_year,

                        (
                            SELECT COUNT(*)
                            FROM students s
                            WHERE s.class_id = c.id
                        ) AS total_students

                     FROM classes c

                     WHERE c.admin_id = ?

                     ORDER BY c.id DESC"
                );


                if ($stmt2) {


                    mysqli_stmt_bind_param(
                        $stmt2,
                        "i",
                        $admin_id
                    );


                    mysqli_stmt_execute($stmt2);


                    $class_result =
                        mysqli_stmt_get_result($stmt2);


                    while (
                        $class =
                        mysqli_fetch_assoc($class_result)
                    ) {

                        $admin_classes[] =
                            $class;

                    }


                    mysqli_stmt_close($stmt2);

                }

            }


            mysqli_stmt_close($stmt);

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


<title>Add Students From Admin</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>


<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
rel="stylesheet"
>


<style>


* {
    box-sizing: border-box;
}


body {

    margin: 0;

    background: #f5f7fb;

    font-family: Arial, Helvetica, sans-serif;

}


/*
|--------------------------------------------------------------------------
| MAIN
|--------------------------------------------------------------------------
*/

.container-main {

    width: 94%;

    max-width: 1200px;

    margin: 35px auto;

}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

.header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    margin-bottom: 25px;

}


.title {

    font-size: 28px;

    font-weight: 700;

}


.subtitle {

    color: #777;

    margin-top: 5px;

}


/*
|--------------------------------------------------------------------------
| TEACHER CLASS
|--------------------------------------------------------------------------
*/

.teacher-class-box {

    background: white;

    border-radius: 16px;

    padding: 20px;

    margin-bottom: 25px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.08);

}


.teacher-class-icon {

    width: 55px;

    height: 55px;

    border-radius: 14px;

    background: #e8f1ff;

    color: #0d6efd;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

}


.teacher-class-name {

    font-size: 21px;

    font-weight: 700;

}


/*
|--------------------------------------------------------------------------
| SEARCH CARD
|--------------------------------------------------------------------------
*/

.search-card {

    background: white;

    padding: 30px;

    border-radius: 18px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.08);

}


.form-control {

    height: 50px;

    border-radius: 9px;

}


.btn {

    min-height: 48px;

    border-radius: 9px;

    font-weight: 600;

}


/*
|--------------------------------------------------------------------------
| ADMIN INFO
|--------------------------------------------------------------------------
*/

.admin-info {

    background: #eaf3ff;

    border: 1px solid #cfe2ff;

    border-radius: 16px;

    padding: 20px;

    margin-top: 25px;

}


.admin-icon {

    width: 60px;

    height: 60px;

    border-radius: 50%;

    background: #0d6efd;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

}


/*
|--------------------------------------------------------------------------
| CLASS GRID
|--------------------------------------------------------------------------
*/

.class-grid {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(280px, 1fr)
        );

    gap: 25px;

    margin-top: 25px;

}


/*
|--------------------------------------------------------------------------
| CLASS CARD
|--------------------------------------------------------------------------
*/

.class-card {

    background: white;

    padding: 25px;

    border-radius: 18px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.08);

    transition: .2s;

}


.class-card:hover {

    transform: translateY(-4px);

}


.class-icon {

    width: 60px;

    height: 60px;

    border-radius: 15px;

    background: #e8f1ff;

    color: #0d6efd;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 27px;

}


.class-name {

    font-size: 22px;

    font-weight: 700;

}


.year {

    color: #777;

    margin-top: 8px;

}


.students {

    margin-top: 15px;

    color: #555;

}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media(max-width:768px) {

    .header {

        flex-direction: column;

        align-items: flex-start;

    }

}

</style>


</head>


<body>


<div class="container-main">


<!-- ==================================================
     HEADER
=================================================== -->

<div class="header">


    <div>

        <div class="title">

            <i class="fa-solid fa-user-group text-primary"></i>

            Add Students From Admin

        </div>


        <div class="subtitle">

            Import students from an Admin class

        </div>

    </div>


    <a
        href="classes.php"
        class="btn btn-secondary px-4"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Classes

    </a>


</div>



<!-- ==================================================
     SELECTED TEACHER CLASS
=================================================== -->

<div class="teacher-class-box">


    <div class="d-flex align-items-center gap-3">


        <div class="teacher-class-icon">

            <i class="fa-solid fa-school"></i>

        </div>


        <div>

            <div class="text-muted small">

                Students will be added to:

            </div>


            <div class="teacher-class-name">

                <?= e($teacher_class['class_name']) ?>

            </div>


            <?php if (!empty($teacher_class['subject'])): ?>

                <div class="text-muted">

                    Subject:

                    <?= e($teacher_class['subject']) ?>

                </div>

            <?php endif; ?>


        </div>


    </div>


</div>



<!-- ==================================================
     SEARCH ADMIN
=================================================== -->

<div class="search-card">


    <h4>

        <i class="fa-solid fa-key text-primary"></i>

        Enter Admin Unique Code

    </h4>


    <p class="text-muted">

        Enter the unique code provided by the Admin.

    </p>


    <form method="POST">


        <div class="row g-3">


            <div class="col-md-9">


                <input
                    type="text"
                    name="admin_code"
                    class="form-control"
                    placeholder="Example: ADMIN-A1B2C3D4"
                    value="<?=

                        isset($_POST['admin_code'])

                        ? e($_POST['admin_code'])

                        : ''

                    ?>"
                    required
                >


            </div>


            <div class="col-md-3">


                <button
                    type="submit"
                    name="search_admin"
                    class="btn btn-primary w-100"
                >

                    <i class="fa-solid fa-search"></i>

                    Find Admin

                </button>


            </div>


        </div>


    </form>



    <?php if ($message !== ''): ?>


        <div
            class="alert alert-<?= e($message_type) ?> mt-4"
        >

            <?php if ($message_type === 'danger'): ?>

                <i class="fa-solid fa-circle-exclamation"></i>

            <?php else: ?>

                <i class="fa-solid fa-circle-check"></i>

            <?php endif; ?>


            <?= e($message) ?>


        </div>


    <?php endif; ?>


</div>



<!-- ==================================================
     ADMIN INFORMATION
=================================================== -->

<?php if ($admin !== null): ?>


<div class="admin-info">


    <div class="d-flex align-items-center gap-3">


        <div class="admin-icon">

            <i class="fa-solid fa-user-shield"></i>

        </div>


        <div>


            <h5 class="mb-1">

                <?= e($admin['full_name']) ?>

            </h5>


            <div class="text-muted">

                <?= e($admin['email']) ?>

            </div>


            <div class="mt-1">

                Admin Code:

                <strong>

                    <?= e($admin['admin_code']) ?>

                </strong>

            </div>


        </div>


    </div>


</div>



<!-- ==================================================
     ADMIN CLASSES
=================================================== -->

<h3 class="mt-4">

    <i class="fa-solid fa-school text-primary"></i>

    Admin Classes

</h3>


<?php if (count($admin_classes) === 0): ?>


    <div class="alert alert-info mt-3">

        <i class="fa-solid fa-circle-info"></i>

        This Admin has not created any classes yet.

    </div>


<?php else: ?>


<div class="class-grid">


<?php foreach ($admin_classes as $class): ?>


    <div class="class-card">


        <div class="class-icon">

            <i class="fa-solid fa-graduation-cap"></i>

        </div>


        <div class="class-name mt-3">

            <?= e($class['class_name']) ?>

        </div>


        <div class="year">

            <i class="fa-solid fa-calendar"></i>

            Academic Year:

            <strong>

                <?= e($class['academic_year']) ?>

            </strong>

        </div>


        <div class="students">

            <i class="fa-solid fa-users text-primary"></i>

            <strong>

                <?= (int)$class['total_students'] ?>

            </strong>

            Students

        </div>


        <!-- ==========================================
             IMPORTANT BUTTON
        =========================================== -->
<a
    href="import_admin_students.php?admin_class_id=<?php echo (int)$class['id']; ?>&teacher_class_id=<?php echo (int)$teacher_class_id; ?>"
    class="btn btn-success w-100 mt-4"
>
    <i class="fa-solid fa-user-plus"></i>
    Add Students
</a>


    </div>


<?php endforeach; ?>


</div>


<?php endif; ?>


<?php endif; ?>


</div>


</body>

</html>