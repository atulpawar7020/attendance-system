<?php

session_start();

require_once __DIR__ . '/../config/db.php';


// =====================================
// ADMIN LOGIN CHECK
// =====================================

if (
    !isset($_SESSION['admin_id']) ||
    empty($_SESSION['admin_id'])
) {

    header("Location: admin_login.php");
    exit();

}


$admin_id = (int)$_SESSION['admin_id'];

$message = "";
$message_type = "";


// =====================================
// CREATE CLASS
// =====================================

if (isset($_POST['create_class'])) {


    $class_name =
        trim($_POST['class_name']);

    $academic_year =
        trim($_POST['academic_year']);


    if (
        $class_name == "" ||
        $academic_year == ""
    ) {

        $message =
            "Please fill all fields.";

        $message_type =
            "danger";

    }

    else {


        // =====================================
        // CHECK DUPLICATE ADMIN CLASS
        // =====================================

        $stmt = mysqli_prepare(
            $conn,

            "SELECT id

             FROM classes

             WHERE admin_id = ?
             AND class_name = ?
             AND academic_year = ?

             LIMIT 1"
        );


        mysqli_stmt_bind_param(
            $stmt,
            "iss",

            $admin_id,
            $class_name,
            $academic_year
        );


        mysqli_stmt_execute($stmt);


        $result =
            mysqli_stmt_get_result($stmt);


        if (
            mysqli_num_rows($result) > 0
        ) {

            $message =
                "This class already exists.";

            $message_type =
                "warning";

        }

        else {


            // =====================================
            // INSERT ADMIN CLASS
            // =====================================

            $stmt2 = mysqli_prepare(
                $conn,

                "INSERT INTO classes

                (
                    teacher_id,
                    admin_id,
                    class_name,
                    subject,
                    semester,
                    department,
                    academic_year
                )

                VALUES

                (
                    NULL,
                    ?,
                    ?,
                    '',
                    '',
                    '',
                    ?
                )"
            );


            mysqli_stmt_bind_param(
                $stmt2,
                "iss",

                $admin_id,
                $class_name,
                $academic_year
            );


            if (
                mysqli_stmt_execute($stmt2)
            ) {

                $message =
                    "Class created successfully.";

                $message_type =
                    "success";

            }

            else {

                $message =
                    "Database Error: " .
                    mysqli_error($conn);

                $message_type =
                    "danger";

            }


            mysqli_stmt_close($stmt2);

        }


        mysqli_stmt_close($stmt);

    }

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

<title>Create Class</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>


<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
rel="stylesheet"
>


<style>

body{

    background:#f5f7fb;

    font-family:Arial,sans-serif;

}


.container-box{

    width:95%;

    max-width:650px;

    margin:60px auto;

}


.card{

    border:none;

    border-radius:20px;

    box-shadow:
    0 8px 25px rgba(0,0,0,.08);

}


.card-header{

    background:#0d6efd;

    color:white;

    padding:22px;

    border-radius:
    20px 20px 0 0 !important;

}


.form-control{

    height:50px;

    border-radius:10px;

}


.btn{

    height:50px;

    border-radius:10px;

}


label{

    font-weight:600;

    margin-bottom:8px;

}

</style>

</head>


<body>


<div class="container-box">


<div class="card">


<div class="card-header">

<h3 class="mb-0">

<i class="fa-solid fa-school"></i>

Create Class

</h3>

</div>


<div class="card-body p-4">


<?php if ($message != "") { ?>


<div class="alert alert-<?php

echo $message_type;

?>">

<?php

echo htmlspecialchars($message);

?>

</div>


<?php } ?>


<form method="POST">


<div class="mb-4">


<label>

Class Name

</label>


<input
type="text"
name="class_name"
class="form-control"
placeholder="Example: FYBSC"
required
>


</div>



<div class="mb-4">


<label>

Academic Year

</label>


<input
type="text"
name="academic_year"
class="form-control"
placeholder="Example: 2026-27"
required
>


</div>



<button
type="submit"
name="create_class"
class="btn btn-primary w-100"
>

<i class="fa fa-plus"></i>

Create Class

</button>


</form>


</div>


</div>


</div>


</body>

</html>