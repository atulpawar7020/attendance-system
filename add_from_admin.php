<?php

session_start();

require_once __DIR__ . "/config/db.php";


// --------------------------------------------------
// CHECK TEACHER LOGIN
// --------------------------------------------------

if (!isset($_SESSION['teacher_id'])) {

    header("Location: login.php");
    exit;
}

$teacher_id = (int)$_SESSION['teacher_id'];


// --------------------------------------------------
// VARIABLES
// --------------------------------------------------

$message = "";
$error = "";

$admin_classes = [];


// Teacher class ID
$teacher_class_id = isset($_GET['class_id'])
    ? (int)$_GET['class_id']
    : 0;


// --------------------------------------------------
// VERIFY TEACHER CLASS
// --------------------------------------------------

if ($teacher_class_id > 0) {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, class_name, academic_year
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

    if (!$teacher_class) {

        $error = "Invalid teacher class.";

        $teacher_class_id = 0;
    }

}


// --------------------------------------------------
// ADMIN CODE SUBMIT
// --------------------------------------------------

if (isset($_POST['find_admin'])) {

    $admin_code = trim($_POST['admin_code'] ?? "");

    if ($admin_code == "") {

        $error = "Please enter Admin Unique Code.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, full_name, email
             FROM teachers
             WHERE unique_code = ?
             AND role = 'admin'
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $admin_code
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $admin = mysqli_fetch_assoc($result);


        if (!$admin) {

            $error = "Invalid Admin Unique Code.";

        } else {

            $admin_id = (int)$admin['id'];


            // Get admin classes
            $stmt2 = mysqli_prepare(
                $conn,
                "SELECT
                    c.id,
                    c.class_name,
                    c.academic_year,
                    COUNT(s.id) AS total_students
                 FROM classes c
                 LEFT JOIN students s
                    ON s.class_id = c.id
                 WHERE c.teacher_id = ?
                 GROUP BY
                    c.id,
                    c.class_name,
                    c.academic_year
                 ORDER BY c.class_name ASC"
            );

            mysqli_stmt_bind_param(
                $stmt2,
                "i",
                $admin_id
            );

            mysqli_stmt_execute($stmt2);

            $result2 = mysqli_stmt_get_result($stmt2);

            while ($row = mysqli_fetch_assoc($result2)) {

                $admin_classes[] = $row;
            }


            if (count($admin_classes) == 0) {

                $error =
                    "This Admin has not created any classes yet.";

            }

            $_SESSION['admin_import_id'] = $admin_id;
            $_SESSION['admin_import_code'] = $admin_code;
        }
    }
}


// --------------------------------------------------
// ADD STUDENTS FROM ADMIN CLASS
// --------------------------------------------------

if (isset($_POST['import_students'])) {

    $admin_class_id = (int)($_POST['admin_class_id'] ?? 0);

    $target_class_id = (int)($_POST['target_class_id'] ?? 0);


    if ($admin_class_id <= 0 || $target_class_id <= 0) {

        $error = "Please select both classes.";

    } else {


        // --------------------------------------------------
        // Verify target class belongs to logged-in teacher
        // --------------------------------------------------

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id
             FROM classes
             WHERE id = ?
             AND teacher_id = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $target_class_id,
            $teacher_id
        );

        mysqli_stmt_execute($stmt);

        $target_result = mysqli_stmt_get_result($stmt);

        $target_class = mysqli_fetch_assoc($target_result);


        if (!$target_class) {

            $error = "Invalid target class.";

        } else {


            // --------------------------------------------------
            // Get admin class owner
            // --------------------------------------------------

            $stmt = mysqli_prepare(
                $conn,
                "SELECT
                    c.id,
                    c.teacher_id,
                    c.class_name
                 FROM classes c
                 INNER JOIN teachers t
                    ON t.id = c.teacher_id
                 WHERE c.id = ?
                 AND t.role = 'admin'
                 LIMIT 1"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $admin_class_id
            );

            mysqli_stmt_execute($stmt);

            $admin_result = mysqli_stmt_get_result($stmt);

            $admin_class = mysqli_fetch_assoc($admin_result);


            if (!$admin_class) {

                $error = "Invalid Admin class.";

            } else {


                $admin_id = (int)$admin_class['teacher_id'];


                // --------------------------------------------------
                // Import students
                // --------------------------------------------------

                $students_query = mysqli_query(
                    $conn,
                    "SELECT
                        roll_no,
                        full_name,
                        email,
                        mobile
                     FROM students
                     WHERE class_id = $admin_class_id
                     ORDER BY roll_no ASC"
                );


                if (!$students_query) {

                    $error =
                        "Student query failed: "
                        . mysqli_error($conn);

                } else {


                    $added = 0;
                    $skipped = 0;


                    while ($student = mysqli_fetch_assoc($students_query)) {


                        $roll_no = (int)$student['roll_no'];

                        $full_name = $student['full_name'];

                        $email = $student['email'];

                        $mobile = $student['mobile'];


                        // --------------------------------------------------
                        // Check duplicate roll number
                        // --------------------------------------------------

                        $check = mysqli_prepare(
                            $conn,
                            "SELECT id
                             FROM students
                             WHERE class_id = ?
                             AND roll_no = ?
                             LIMIT 1"
                        );

                        mysqli_stmt_bind_param(
                            $check,
                            "ii",
                            $target_class_id,
                            $roll_no
                        );

                        mysqli_stmt_execute($check);

                        $check_result =
                            mysqli_stmt_get_result($check);


                        if (mysqli_num_rows($check_result) > 0) {

                            $skipped++;

                            continue;
                        }


                        // --------------------------------------------------
                        // Insert student
                        // --------------------------------------------------

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
                            (?, ?, ?, ?, ?)"
                        );


                        mysqli_stmt_bind_param(
                            $insert,
                            "iisss",
                            $target_class_id,
                            $roll_no,
                            $full_name,
                            $mobile,
                            $email
                        );


                        if (mysqli_stmt_execute($insert)) {

                            $added++;

                        } else {

                            $skipped++;
                        }
                    }


                    $message =
                        "$added students added successfully.";

                    if ($skipped > 0) {

                        $message .=
                            " $skipped students skipped because their roll number already exists.";
                    }
                }
            }
        }
    }
}

?>

<!DOCTYPE html>

<html>

<head>

<title>Add Students From Admin</title>

<meta name="viewport"
content="width=device-width, initial-scale=1">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>

body{
    background:#f4f6f9;
}

.container-box{
    max-width:900px;
    margin:50px auto;
}

.card{
    border:none;
    border-radius:18px;
    box-shadow:0 8px 25px rgba(0,0,0,.10);
}

.admin-card{
    border:2px solid #0d6efd;
}

</style>

</head>

<body>

<div class="container container-box">

<div class="card p-4">

<h2 class="mb-4">

<i class="bi bi-person-plus"></i>

Add Students From Admin

</h2>


<?php if ($error != "") { ?>

<div class="alert alert-danger">

<?= htmlspecialchars($error) ?>

</div>

<?php } ?>


<?php if ($message != "") { ?>

<div class="alert alert-success">

<?= htmlspecialchars($message) ?>

</div>

<?php } ?>


<!-- --------------------------------------------------
     ADMIN CODE
--------------------------------------------------- -->

<form method="POST">

<label class="form-label">

<strong>Admin Unique Code</strong>

</label>

<div class="input-group mb-3">

<input
type="text"
name="admin_code"
class="form-control"
placeholder="Example: ADM-10001"
required>

<button
type="submit"
name="find_admin"
class="btn btn-primary">

Find Admin

</button>

</div>

</form>


<?php if (count($admin_classes) > 0) { ?>


<hr>


<!-- --------------------------------------------------
     IMPORT FORM
--------------------------------------------------- -->

<form method="POST">


<div class="mb-3">

<label class="form-label">

<strong>Your Teacher Class</strong>

</label>


<select
name="target_class_id"
class="form-select"
required>

<option value="">

Select Your Class

</option>


<?php

$my_classes_query = mysqli_query(
    $conn,
    "SELECT id, class_name, academic_year
     FROM classes
     WHERE teacher_id = $teacher_id
     ORDER BY class_name ASC"
);

while ($myclass = mysqli_fetch_assoc($my_classes_query)) {

?>

<option
value="<?= (int)$myclass['id'] ?>"
<?= $teacher_class_id == $myclass['id']
    ? 'selected'
    : '' ?>>

<?= htmlspecialchars($myclass['class_name']) ?>

-
<?= htmlspecialchars($myclass['academic_year']) ?>

</option>

<?php } ?>

</select>

</div>


<div class="mb-3">

<label class="form-label">

<strong>Admin Class</strong>

</label>


<select
name="admin_class_id"
class="form-select"
required>

<option value="">

Select Admin Class

</option>


<?php foreach ($admin_classes as $class) { ?>

<option
value="<?= (int)$class['id'] ?>">

<?= htmlspecialchars($class['class_name']) ?>

-

<?= htmlspecialchars($class['academic_year']) ?>

(<?= (int)$class['total_students'] ?> Students)

</option>

<?php } ?>

</select>

</div>


<button
type="submit"
name="import_students"
class="btn btn-success w-100">

Add Students To My Class

</button>


</form>


<?php } ?>


<br>

<a
href="classes.php"
class="btn btn-secondary">

Back To Classes

</a>


</div>

</div>

</body>

</html>