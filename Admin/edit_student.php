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
| STUDENT ID
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {

    die("Invalid Student ID.");

}

$student_id = (int)$_GET['id'];


/*
|--------------------------------------------------------------------------
| CLASS ID
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['class_id']) ||
    !is_numeric($_GET['class_id'])
) {

    die("Invalid Class ID.");

}

$class_id = (int)$_GET['class_id'];


/*
|--------------------------------------------------------------------------
| GET STUDENT
|--------------------------------------------------------------------------
| Student must belong to Admin's class.
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,

    "SELECT
        s.id,
        s.class_id,
        s.roll_no,
        s.full_name,
        s.mobile,
        s.email,

        c.class_name,
        c.academic_year

     FROM students s

     INNER JOIN classes c
        ON c.id = s.class_id

     WHERE s.id = ?
     AND s.class_id = ?
     AND c.admin_id = ?

     LIMIT 1"
);

if (!$stmt) {

    die(
        "Database Error: " .
        mysqli_error($conn)
    );

}

mysqli_stmt_bind_param(
    $stmt,
    "iii",
    $student_id,
    $class_id,
    $admin_id
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);


if (
    !$result ||
    mysqli_num_rows($result) == 0
) {

    die("
        <div style='
            font-family:Arial;
            text-align:center;
            margin-top:100px;
        '>

            <h2>Student Not Found</h2>

            <p>
                You don't have permission to edit this student.
            </p>

            <a href='admin_classes.php'>
                Back to Classes
            </a>

        </div>
    ");

}


$student =
    mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| UPDATE STUDENT
|--------------------------------------------------------------------------
*/

$message = "";

$message_type = "";


if (
    isset($_POST['update_student'])
) {


    $roll_no =
        trim($_POST['roll_no'] ?? '');


    $full_name =
        trim($_POST['full_name'] ?? '');


    $mobile =
        trim($_POST['mobile'] ?? '');


    $email =
        trim($_POST['email'] ?? '');


    /*
    | Required fields
    */

    if (
        $roll_no === "" ||
        $full_name === ""
    ) {

        $message =
            "Roll Number and Student Name are required.";

        $message_type =
            "danger";

    } else {


        /*
        | Check duplicate roll number
        | inside same class
        */

        $checkStmt = mysqli_prepare(
            $conn,

            "SELECT id
             FROM students
             WHERE class_id = ?
             AND roll_no = ?
             AND id != ?
             LIMIT 1"
        );


        mysqli_stmt_bind_param(
            $checkStmt,
            "isi",
            $class_id,
            $roll_no,
            $student_id
        );


        mysqli_stmt_execute(
            $checkStmt
        );


        $checkResult =
            mysqli_stmt_get_result(
                $checkStmt
            );


        if (
            mysqli_num_rows(
                $checkResult
            ) > 0
        ) {

            $message =
                "This roll number already exists in this class.";

            $message_type =
                "danger";

        } else {


            /*
            | UPDATE
            */

            $updateStmt = mysqli_prepare(
                $conn,

                "UPDATE students s

                 INNER JOIN classes c
                    ON c.id = s.class_id

                 SET
                    s.roll_no = ?,
                    s.full_name = ?,
                    s.mobile = ?,
                    s.email = ?

                 WHERE s.id = ?
                 AND s.class_id = ?
                 AND c.admin_id = ?"
            );


            if (!$updateStmt) {

                $message =
                    "Database Error: " .
                    mysqli_error($conn);

                $message_type =
                    "danger";

            } else {


                mysqli_stmt_bind_param(
                    $updateStmt,
                    "ssssiii",
                    $roll_no,
                    $full_name,
                    $mobile,
                    $email,
                    $student_id,
                    $class_id,
                    $admin_id
                );


                if (
                    mysqli_stmt_execute(
                        $updateStmt
                    )
                ) {


                    mysqli_stmt_close(
                        $updateStmt
                    );


                    header(
                        "Location: view_students.php?class_id=" .
                        $class_id
                    );

                    exit();


                } else {


                    $message =
                        "Update failed: " .
                        mysqli_error($conn);

                    $message_type =
                        "danger";

                    mysqli_stmt_close(
                        $updateStmt
                    );

                }

            }

        }


        mysqli_stmt_close(
            $checkStmt
        );

    }

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Edit Student</title>


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


.edit-card {

    max-width:700px;

    margin:60px auto;

    background:white;

    border-radius:20px;

    box-shadow:
        0 5px 25px rgba(0,0,0,.10);

    overflow:hidden;

}


.card-header-custom {

    background:#0d6efd;

    color:white;

    padding:25px;

}


.card-body {

    padding:30px;

}


.form-control {

    height:48px;

    border-radius:8px;

}


.btn {

    border-radius:8px;

    min-height:45px;

}

</style>

</head>


<body>


<div class="container">


<div class="edit-card">


<!-- HEADER -->

<div class="card-header-custom">


<h3 class="mb-1">

<i class="fa-solid fa-user-pen"></i>

Edit Student

</h3>


<div>

<?php

echo htmlspecialchars(
    $student['class_name']
);

?>

|

<?php

echo htmlspecialchars(
    $student['academic_year']
);

?>

</div>


</div>


<!-- BODY -->

<div class="card-body">


<?php

if ($message !== "") {

?>


<div class="alert alert-<?php echo $message_type; ?>">

<?php

echo htmlspecialchars(
    $message
);

?>

</div>


<?php

}

?>


<form method="POST">


<!-- ROLL -->

<div class="mb-3">


<label class="form-label fw-bold">

Roll Number *

</label>


<input
type="text"
name="roll_no"
class="form-control"
value="<?php

echo htmlspecialchars(
    $student['roll_no']
);

?>"
required>


</div>


<!-- NAME -->

<div class="mb-3">


<label class="form-label fw-bold">

Student Name *

</label>


<input
type="text"
name="full_name"
class="form-control"
value="<?php

echo htmlspecialchars(
    $student['full_name']
);

?>"
required>


</div>


<!-- EMAIL -->

<div class="mb-3">


<label class="form-label fw-bold">

Email

</label>


<input
type="email"
name="email"
class="form-control"
value="<?php

echo htmlspecialchars(
    $student['email'] ?? ''
);

?>">


</div>


<!-- MOBILE -->

<div class="mb-4">


<label class="form-label fw-bold">

Contact Number

</label>


<input
type="text"
name="mobile"
class="form-control"
value="<?php

echo htmlspecialchars(
    $student['mobile'] ?? ''
);

?>">


</div>


<!-- UPDATE -->

<button
type="submit"
name="update_student"
class="btn btn-primary w-100">

<i class="fa-solid fa-save"></i>

Save Changes

</button>


<!-- CANCEL -->

<a
href="view_students.php?class_id=<?php echo $class_id; ?>"
class="btn btn-secondary w-100 mt-3">

<i class="fa-solid fa-arrow-left"></i>

Cancel

</a>


</form>


</div>

</div>

</div>


</body>

</html>