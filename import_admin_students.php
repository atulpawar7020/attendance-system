<?php

session_start();

require_once __DIR__ . '/config/db.php';

/*
|--------------------------------------------------------------------------
| TEACHER LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['teacher_id']) || empty($_SESSION['teacher_id'])) {

    header("Location: login.php");
    exit();

}

$teacher_id = (int) $_SESSION['teacher_id'];


/*
|--------------------------------------------------------------------------
| GET IDs
|--------------------------------------------------------------------------
*/

$admin_class_id = isset($_GET['admin_class_id'])
    ? (int) $_GET['admin_class_id']
    : 0;

$teacher_class_id = isset($_GET['teacher_class_id'])
    ? (int) $_GET['teacher_class_id']
    : 0;


/*
|--------------------------------------------------------------------------
| VALIDATE IDS
|--------------------------------------------------------------------------
*/

if ($admin_class_id <= 0 || $teacher_class_id <= 0) {

    die("
        <div style='font-family:Arial;padding:40px'>
            <h2 style='color:#dc3545'>Invalid Class ID</h2>
            <p>Admin class or teacher class ID is missing.</p>
            <a href='classes.php'>Back to Classes</a>
        </div>
    ");

}


/*
|--------------------------------------------------------------------------
| GET ADMIN CLASS
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,

    "SELECT
        id,
        class_name,
        academic_year,
        admin_id

     FROM classes

     WHERE id = ?

     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $admin_class_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$admin_class = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$admin_class) {

    die("
        <div style='font-family:Arial;padding:40px'>
            <h2 style='color:#dc3545'>Admin Class Not Found</h2>
            <a href='classes.php'>Back to Classes</a>
        </div>
    ");

}


/*
|--------------------------------------------------------------------------
| CHECK THAT ADMIN CLASS REALLY BELONGS TO AN ADMIN
|--------------------------------------------------------------------------
*/

$admin_id = (int) $admin_class['admin_id'];

if ($admin_id <= 0) {

    die("
        <div style='font-family:Arial;padding:40px'>
            <h2 style='color:#dc3545'>Invalid Admin Class</h2>
            <p>This class is not connected to an Admin.</p>
            <a href='classes.php'>Back to Classes</a>
        </div>
    ");

}


/*
|--------------------------------------------------------------------------
| GET TEACHER CLASS
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,

    "SELECT
        id,
        class_name,
        academic_year,
        teacher_id

     FROM classes

     WHERE id = ?
     AND teacher_id = ?

     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $teacher_class_id,
    $teacher_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$teacher_class = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$teacher_class) {

    die("
        <div style='font-family:Arial;padding:40px'>
            <h2 style='color:#dc3545'>Teacher Class Not Found</h2>

            <p>
                The selected class does not belong to the logged-in teacher.
            </p>

            <a href='classes.php'>Back to Classes</a>
        </div>
    ");

}


/*
|--------------------------------------------------------------------------
| GET ADMIN INFORMATION
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

     WHERE id = ?
     AND role = 'admin'

     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $admin_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$admin = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$admin) {

    die("
        <div style='font-family:Arial;padding:40px'>
            <h2 style='color:#dc3545'>Admin Not Found</h2>
            <a href='classes.php'>Back to Classes</a>
        </div>
    ");

}


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";


/*
|--------------------------------------------------------------------------
| IMPORT STUDENTS
|--------------------------------------------------------------------------
*/

if (isset($_POST['import_students'])) {

    if (
        !isset($_POST['student_ids']) ||
        !is_array($_POST['student_ids']) ||
        count($_POST['student_ids']) == 0
    ) {

        $message = "Please select at least one student.";
        $message_type = "danger";

    } else {

        $selected_students = $_POST['student_ids'];

        $added_count = 0;
        $skipped_count = 0;
        $error_count = 0;


        /*
        |--------------------------------------------------------------------------
        | START TRANSACTION
        |--------------------------------------------------------------------------
        */

        mysqli_begin_transaction($conn);

        try {

            foreach ($selected_students as $student_id) {

                $student_id = (int) $student_id;

                if ($student_id <= 0) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | GET STUDENT FROM ADMIN CLASS
                |--------------------------------------------------------------------------
                */

                $stmt = mysqli_prepare(
                    $conn,

                    "SELECT
                        id,
                        roll_no,
                        full_name,
                        mobile,
                        email

                     FROM students

                     WHERE id = ?
                     AND class_id = ?

                     LIMIT 1"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ii",
                    $student_id,
                    $admin_class_id
                );

                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);

                $student = mysqli_fetch_assoc($result);

                mysqli_stmt_close($stmt);


                /*
                |--------------------------------------------------------------------------
                | STUDENT NOT FOUND
                |--------------------------------------------------------------------------
                */

                if (!$student) {

                    $error_count++;

                    continue;

                }


                $roll_no   = trim((string)$student['roll_no']);
                $full_name = trim((string)$student['full_name']);
                $mobile    = trim((string)$student['mobile']);
                $email     = trim((string)$student['email']);


                /*
                |--------------------------------------------------------------------------
                | CHECK IF SAME STUDENT ALREADY EXISTS
                |--------------------------------------------------------------------------
                |
                | First email check.
                |
                */

                $already_exists = false;


                if ($email !== "") {

                    $stmt = mysqli_prepare(
                        $conn,

                        "SELECT id

                         FROM students

                         WHERE class_id = ?
                         AND email = ?

                         LIMIT 1"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "is",
                        $teacher_class_id,
                        $email
                    );

                    mysqli_stmt_execute($stmt);

                    $result = mysqli_stmt_get_result($stmt);

                    if (mysqli_num_rows($result) > 0) {

                        $already_exists = true;

                    }

                    mysqli_stmt_close($stmt);

                }


                /*
                |--------------------------------------------------------------------------
                | CHECK NAME + MOBILE
                |--------------------------------------------------------------------------
                */

                if (!$already_exists && $mobile !== "") {

                    $stmt = mysqli_prepare(
                        $conn,

                        "SELECT id

                         FROM students

                         WHERE class_id = ?
                         AND full_name = ?
                         AND mobile = ?

                         LIMIT 1"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "iss",
                        $teacher_class_id,
                        $full_name,
                        $mobile
                    );

                    mysqli_stmt_execute($stmt);

                    $result = mysqli_stmt_get_result($stmt);

                    if (mysqli_num_rows($result) > 0) {

                        $already_exists = true;

                    }

                    mysqli_stmt_close($stmt);

                }


                /*
                |--------------------------------------------------------------------------
                | IF ALREADY EXISTS
                |--------------------------------------------------------------------------
                */

                if ($already_exists) {

                    $skipped_count++;

                    continue;

                }


                /*
                |--------------------------------------------------------------------------
                | CREATE ROLL NUMBER FOR TEACHER CLASS
                |--------------------------------------------------------------------------
                |
                | Same roll number can exist in another class.
                |
                | But if the same roll number already exists
                | in teacher class, create a new safe roll number.
                |
                */

                $new_roll_no = $roll_no;


                $roll_check = mysqli_prepare(
                    $conn,

                    "SELECT id

                     FROM students

                     WHERE class_id = ?
                     AND roll_no = ?

                     LIMIT 1"
                );

                mysqli_stmt_bind_param(
                    $roll_check,
                    "is",
                    $teacher_class_id,
                    $new_roll_no
                );

                mysqli_stmt_execute($roll_check);

                $roll_result =
                    mysqli_stmt_get_result($roll_check);

                mysqli_stmt_close($roll_check);


                /*
                |--------------------------------------------------------------------------
                | IF ROLL NUMBER EXISTS
                |--------------------------------------------------------------------------
                */

                if (mysqli_num_rows($roll_result) > 0) {

                    $counter = 1;

                    while (true) {

                        $new_roll_no =
                            $roll_no . "-IMP" . $counter;


                        $check_roll = mysqli_prepare(
                            $conn,

                            "SELECT id

                             FROM students

                             WHERE class_id = ?
                             AND roll_no = ?

                             LIMIT 1"
                        );

                        mysqli_stmt_bind_param(
                            $check_roll,
                            "is",
                            $teacher_class_id,
                            $new_roll_no
                        );

                        mysqli_stmt_execute($check_roll);

                        $check_result =
                            mysqli_stmt_get_result($check_roll);

                        mysqli_stmt_close($check_roll);


                        if (mysqli_num_rows($check_result) == 0) {

                            break;

                        }

                        $counter++;

                    }

                }


                /*
                |--------------------------------------------------------------------------
                | INSERT STUDENT
                |--------------------------------------------------------------------------
                */

                $insert = mysqli_prepare(
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

                mysqli_stmt_bind_param(
                    $insert,
                    "issss",
                    $teacher_class_id,
                    $new_roll_no,
                    $full_name,
                    $mobile,
                    $email
                );


                if (mysqli_stmt_execute($insert)) {

                    $added_count++;

                } else {

                    $error_count++;

                }


                mysqli_stmt_close($insert);

            }


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            mysqli_commit($conn);


            /*
            |--------------------------------------------------------------------------
            | SUCCESS MESSAGE
            |--------------------------------------------------------------------------
            */

            $message =
                "Import completed. " .
                $added_count .
                " student(s) added.";

            if ($skipped_count > 0) {

                $message .=
                    " " .
                    $skipped_count .
                    " duplicate student(s) skipped.";

            }

            if ($error_count > 0) {

                $message .=
                    " " .
                    $error_count .
                    " student(s) could not be added.";

            }


            $message_type =
                ($error_count > 0)
                ? "warning"
                : "success";


        } catch (Exception $e) {

            mysqli_rollback($conn);

            $message =
                "Import failed: " .
                $e->getMessage();

            $message_type = "danger";

        }

    }

}


/*
|--------------------------------------------------------------------------
| GET ADMIN CLASS STUDENTS
|--------------------------------------------------------------------------
*/

$students = [];

$stmt = mysqli_prepare(
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
        CAST(roll_no AS UNSIGNED),
        roll_no,
        full_name"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $admin_class_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


while ($row = mysqli_fetch_assoc($result)) {

    $students[] = $row;

}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| COUNT TEACHER CLASS STUDENTS
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,

    "SELECT COUNT(*) AS total

     FROM students

     WHERE class_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $teacher_class_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$count_data = mysqli_fetch_assoc($result);

$teacher_student_count =
    (int)$count_data['total'];

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Import Students From Admin</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
rel="stylesheet">


<style>

body {

    background: #f5f7fb;

    font-family: Arial, Helvetica, sans-serif;

}


.page-container {

    width: 94%;

    max-width: 1200px;

    margin: 35px auto;

}


.top-card {

    background: white;

    border-radius: 18px;

    padding: 25px;

    box-shadow: 0 5px 20px rgba(0,0,0,.08);

    margin-bottom: 25px;

}


.info-box {

    border-radius: 14px;

    padding: 18px;

    background: #eef6ff;

}


.info-box.admin {

    background: #f0f7ff;

}


.info-box.teacher {

    background: #f1fff6;

}


.student-card {

    background: white;

    border-radius: 15px;

    padding: 18px;

    box-shadow: 0 4px 15px rgba(0,0,0,.07);

    margin-bottom: 12px;

    transition: .2s;

}


.student-card:hover {

    transform: translateY(-2px);

    box-shadow: 0 7px 20px rgba(0,0,0,.10);

}


.student-name {

    font-size: 18px;

    font-weight: 600;

}


.roll {

    color: #0d6efd;

    font-weight: 600;

}


.empty-box {

    background: white;

    border-radius: 18px;

    padding: 60px 20px;

    text-align: center;

    box-shadow: 0 5px 20px rgba(0,0,0,.07);

}


.select-all-box {

    background: white;

    padding: 18px;

    border-radius: 15px;

    box-shadow: 0 4px 15px rgba(0,0,0,.06);

    margin-bottom: 15px;

}


.form-check-input {

    width: 20px;

    height: 20px;

    cursor: pointer;

}


.form-check-label {

    cursor: pointer;

}


.import-btn {

    position: sticky;

    bottom: 15px;

    z-index: 100;

    background: white;

    padding: 15px;

    border-radius: 15px;

    box-shadow: 0 -4px 20px rgba(0,0,0,.12);

}


</style>

</head>


<body>


<div class="page-container">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="top-card">


<div class="d-flex justify-content-between
            align-items-center flex-wrap gap-3">


<div>

<h2 class="mb-1">

<i class="fa-solid fa-user-plus text-success"></i>

Import Students

</h2>


<p class="text-muted mb-0">

Copy students from Admin class to your class.

</p>

</div>


<a
href="classes.php"
class="btn btn-secondary">


<i class="fa fa-arrow-left"></i>

Back to Classes


</a>


</div>


</div>



<!-- =====================================================
     ADMIN / TEACHER CLASS INFO
===================================================== -->

<div class="row g-4 mb-4">


<div class="col-md-6">


<div class="info-box admin">


<h5>

<i class="fa-solid fa-user-shield text-primary"></i>

Admin Class

</h5>


<h4 class="mb-1">

<?php

echo htmlspecialchars(
    $admin_class['class_name']
);

?>

</h4>


<p class="mb-1">

Academic Year:

<strong>

<?php

echo htmlspecialchars(
    $admin_class['academic_year']
);

?>

</strong>

</p>


<p class="mb-0">

Admin:

<strong>

<?php

echo htmlspecialchars(
    $admin['full_name']
);

?>

</strong>

</p>


</div>

</div>



<div class="col-md-6">


<div class="info-box teacher">


<h5>

<i class="fa-solid fa-chalkboard-user text-success"></i>

Your Class

</h5>


<h4 class="mb-1">

<?php

echo htmlspecialchars(
    $teacher_class['class_name']
);

?>

</h4>


<p class="mb-1">

Academic Year:

<strong>

<?php

echo htmlspecialchars(
    $teacher_class['academic_year']
);

?>

</strong>

</p>


<p class="mb-0">

Current Students:

<strong>

<?php

echo $teacher_student_count;

?>

</strong>

</p>


</div>

</div>


</div>



<!-- =====================================================
     MESSAGE
===================================================== -->

<?php if ($message !== "") { ?>


<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">

<?php

echo htmlspecialchars($message);

?>


<button
type="button"
class="btn-close"
data-bs-dismiss="alert">
</button>


</div>


<?php } ?>



<!-- =====================================================
     STUDENTS
===================================================== -->

<?php if (count($students) == 0) { ?>


<div class="empty-box">


<i
class="fa-solid fa-users-slash text-muted"
style="font-size:60px;">
</i>


<h3 class="mt-3">

No Students Found

</h3>


<p class="text-muted">

There are no students in this Admin class.

</p>


<a
href="classes.php"
class="btn btn-primary">

Back to Classes

</a>


</div>


<?php } else { ?>



<form method="POST"
      id="importForm">


<!-- =====================================================
     SELECT ALL
===================================================== -->

<div class="select-all-box">


<div class="d-flex justify-content-between
            align-items-center flex-wrap gap-3">


<div class="form-check">


<input
type="checkbox"
class="form-check-input"
id="selectAll">


<label
for="selectAll"
class="form-check-label fw-bold ms-2">

Select All Students

</label>


</div>


<div>

<span class="badge bg-primary">

<?php echo count($students); ?>

Students Available

</span>


</div>


</div>


</div>



<!-- =====================================================
     STUDENT LIST
===================================================== -->

<div class="row">


<?php foreach ($students as $student) { ?>


<div class="col-md-6">


<div class="student-card">


<div class="d-flex
            align-items-center
            gap-3">


<div class="form-check">


<input
type="checkbox"
class="form-check-input student-checkbox"
name="student_ids[]"
value="<?php echo (int)$student['id']; ?>">


</div>


<div class="flex-grow-1">


<div class="student-name">

<?php

echo htmlspecialchars(
    $student['full_name']
);

?>

</div>


<div class="roll">

Roll No:

<?php

echo htmlspecialchars(
    $student['roll_no']
);

?>

</div>


<?php if (!empty($student['email'])) { ?>


<div class="text-muted small">

<i class="fa fa-envelope"></i>

<?php

echo htmlspecialchars(
    $student['email']
);

?>

</div>


<?php } ?>


<?php if (!empty($student['mobile'])) { ?>


<div class="text-muted small">

<i class="fa fa-phone"></i>

<?php

echo htmlspecialchars(
    $student['mobile']
);

?>

</div>


<?php } ?>


</div>


</div>


</div>


</div>


<?php } ?>


</div>



<!-- =====================================================
     IMPORT BUTTON
===================================================== -->

<div class="import-btn mt-4">


<div class="d-flex
            justify-content-between
            align-items-center
            flex-wrap gap-3">


<div>

<strong>

Selected:

<span id="selectedCount">0</span>

</strong>

students

</div>


<button
type="submit"
name="import_students"
class="btn btn-success btn-lg px-5"
id="importButton"
disabled>


<i class="fa-solid fa-user-plus"></i>

Add Selected Students


</button>


</div>


</div>


</form>


<?php } ?>


</div>



<script>

const selectAll =
    document.getElementById("selectAll");

const checkboxes =
    document.querySelectorAll(".student-checkbox");

const selectedCount =
    document.getElementById("selectedCount");

const importButton =
    document.getElementById("importButton");


function updateSelectedCount() {

    let selected = 0;

    checkboxes.forEach(function(checkbox) {

        if (checkbox.checked) {

            selected++;

        }

    });


    selectedCount.textContent = selected;


    if (importButton) {

        importButton.disabled =
            selected === 0;

    }


    if (selectAll) {

        if (
            selected === checkboxes.length &&
            checkboxes.length > 0
        ) {

            selectAll.checked = true;

        } else {

            selectAll.checked = false;

        }

    }

}


if (selectAll) {

    selectAll.addEventListener(
        "change",
        function() {

            checkboxes.forEach(
                function(checkbox) {

                    checkbox.checked =
                        selectAll.checked;

                }
            );

            updateSelectedCount();

        }
    );

}


checkboxes.forEach(
    function(checkbox) {

        checkbox.addEventListener(
            "change",
            updateSelectedCount
        );

    }
);


updateSelectedCount();


document.getElementById("importForm")
    ?.addEventListener(
        "submit",
        function(event) {

            let selected = 0;

            checkboxes.forEach(
                function(checkbox) {

                    if (checkbox.checked) {

                        selected++;

                    }

                }
            );


            if (selected === 0) {

                event.preventDefault();

                alert(
                    "Please select at least one student."
                );

                return;

            }


            const confirmed =
                confirm(
                    "Are you sure you want to add " +
                    selected +
                    " student(s) to your class?"
                );


            if (!confirmed) {

                event.preventDefault();

            }

        }
    );

</script>


</body>

</html>