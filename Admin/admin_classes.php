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


$admin_id = (int) $_SESSION['admin_id'];


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
        profile_photo
     FROM teachers
     WHERE id = ?
     AND role = 'admin'
     LIMIT 1"
);


if (!$stmt) {

    die(
        "Database error: " .
        mysqli_error($conn)
    );

}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $admin_id
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


$admin =
    mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


if (!$admin) {

    $_SESSION = [];

    session_destroy();

    header("Location: ../login.php");

    exit();

}


/*
|--------------------------------------------------------------------------
| PROFILE PHOTO
|--------------------------------------------------------------------------
|
| Database:
| profile_photo = filename
|
| Actual folder:
| uploads/profile/
|
*/

$profilePhoto =
    trim(
        $admin['profile_photo'] ?? ''
    );


$profilePhotoUrl = '';


if ($profilePhoto !== '') {

    $safePhoto =
        basename($profilePhoto);


    $photoFile =
        __DIR__ .
        '/../uploads/profile/' .
        $safePhoto;


    if (is_file($photoFile)) {

        $profilePhotoUrl =
            '../uploads/profile/' .
            rawurlencode($safePhoto) .
            '?v=' .
            filemtime($photoFile);

    }

}


/*
|--------------------------------------------------------------------------
| DELETE CLASS
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['delete_class'])
) {

    $class_id =
        (int) $_GET['delete_class'];


    /*
    |--------------------------------------------------------------------------
    | CHECK CLASS BELONGS TO ADMIN
    |--------------------------------------------------------------------------
    */

    $check =
        mysqli_prepare(
            $conn,

            "SELECT id
             FROM classes
             WHERE id = ?
             AND admin_id = ?
             LIMIT 1"
        );


    mysqli_stmt_bind_param(
        $check,
        "ii",
        $class_id,
        $admin_id
    );


    mysqli_stmt_execute($check);


    $checkResult =
        mysqli_stmt_get_result($check);


    if (
        mysqli_num_rows(
            $checkResult
        ) > 0
    ) {


        /*
        |--------------------------------------------------------------------------
        | DELETE STUDENTS
        |--------------------------------------------------------------------------
        */

        $deleteStudents =
            mysqli_prepare(
                $conn,

                "DELETE FROM students
                 WHERE class_id = ?"
            );


        if ($deleteStudents) {

            mysqli_stmt_bind_param(
                $deleteStudents,
                "i",
                $class_id
            );


            mysqli_stmt_execute(
                $deleteStudents
            );


            mysqli_stmt_close(
                $deleteStudents
            );

        }


        /*
        |--------------------------------------------------------------------------
        | DELETE CLASS
        |--------------------------------------------------------------------------
        */

        $deleteClass =
            mysqli_prepare(
                $conn,

                "DELETE FROM classes
                 WHERE id = ?
                 AND admin_id = ?"
            );


        if ($deleteClass) {

            mysqli_stmt_bind_param(
                $deleteClass,
                "ii",
                $class_id,
                $admin_id
            );


            mysqli_stmt_execute(
                $deleteClass
            );


            mysqli_stmt_close(
                $deleteClass
            );

        }

    }


    header(
        "Location: admin_classes.php"
    );

    exit();

}


/*
|--------------------------------------------------------------------------
| GET ADMIN CLASSES
|--------------------------------------------------------------------------
*/

$classQuery = mysqli_prepare(
    $conn,

    "SELECT
        c.id,
        c.class_name,
        c.academic_year,
        COUNT(s.id) AS total_students

     FROM classes c

     LEFT JOIN students s
     ON s.class_id = c.id

     WHERE c.admin_id = ?

     GROUP BY
        c.id,
        c.class_name,
        c.academic_year

     ORDER BY c.id DESC"
);


if (!$classQuery) {

    die(
        "Database error: " .
        mysqli_error($conn)
    );

}


mysqli_stmt_bind_param(
    $classQuery,
    "i",
    $admin_id
);


mysqli_stmt_execute(
    $classQuery
);


$classResult =
    mysqli_stmt_get_result(
        $classQuery
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Admin Classes</title>


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

/* =====================================================
   BODY
===================================================== */

body {

    margin: 0;

    background: #f5f7fb;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}


/* =====================================================
   NAVBAR
===================================================== */

.navbar {

    background: #ffffff;

    box-shadow:
        0 2px 10px
        rgba(0, 0, 0, 0.08);

}


.logo {

    font-size: 22px;

    font-weight: 700;

}


/* =====================================================
   PROFILE DROPDOWN BUTTON
===================================================== */

.profile-button {

    width: 46px;

    height: 46px;

    padding: 0;

    border: 2px solid #0d6efd;

    border-radius: 50%;

    background: #ffffff;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: center;

    position: relative;

}


/*
   Bootstrap dropdown arrow hide
*/

.profile-button::after {

    display: none !important;

}


/* =====================================================
   PROFILE PHOTO
===================================================== */

.profile-button img {

    width: 100%;

    height: 100%;

    display: block;

    object-fit: cover;

    object-position: center center;

    border-radius: 50%;

}


/* =====================================================
   DEFAULT PROFILE ICON
===================================================== */

.default-profile {

    width: 100%;

    height: 100%;

    border-radius: 50%;

    background: #eaf3ff;

    color: #0d6efd;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

}


/* =====================================================
   DROPDOWN
===================================================== */

.dropdown-menu {

    border: none;

    border-radius: 12px;

    box-shadow:
        0 8px 25px
        rgba(0, 0, 0, 0.12);

    min-width: 180px;

}


.dropdown-item {

    padding:
        10px 15px;

}


.dropdown-item i {

    width: 22px;

}


/* =====================================================
   CLASS CARD
===================================================== */

.class-card {

    border: none;

    border-radius: 18px;

    background: #ffffff;

    box-shadow:
        0 5px 18px
        rgba(0, 0, 0, 0.10);

    transition: 0.3s;

    height: 100%;

}


.class-card:hover {

    transform:
        translateY(-5px);

    box-shadow:
        0 10px 25px
        rgba(0, 0, 0, 0.15);

}


/* =====================================================
   CLASS ICON
===================================================== */

.class-icon {

    width: 60px;

    height: 60px;

    border-radius: 15px;

    background: #e8f0ff;

    color: #0d6efd;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 26px;

}


/* =====================================================
   CLASS NAME
===================================================== */

.class-name {

    font-size: 21px;

    font-weight: 700;

}


.academic-year {

    color: #777;

}


.student-count {

    font-weight: 600;

}


/* =====================================================
   MOBILE
===================================================== */

@media (
    max-width: 576px
) {

    .logo {

        font-size: 18px;

    }


    .navbar {

        padding-top: 10px;

        padding-bottom: 10px;

    }


    .navbar .btn {

        font-size: 13px;

        padding:
            8px 10px;

    }


    .profile-button {

        width: 42px;

        height: 42px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar">

<div
    class="
        container-fluid
        px-4
    "
>


<!-- =================================================
     LOGO
================================================= -->

<div class="logo">

<i
    class="
        fa-solid
        fa-user-shield
        text-primary
    "
></i>

Admin Panel

</div>



<!-- =================================================
     RIGHT SIDE
================================================= -->

<div
    class="
        d-flex
        align-items-center
        gap-3
    "
>


<!-- CREATE CLASS -->

<a
    href="create_class.php"
    class="btn btn-primary"
>

<i class="fa-solid fa-plus"></i>

Create Class

</a>



<!-- =================================================
     PROFILE
================================================= -->

<div class="dropdown">


<button
    type="button"
    class="profile-button"
    data-bs-toggle="dropdown"
    aria-expanded="false"
>


<?php if (
    $profilePhotoUrl !== ''
): ?>

<img
    src="<?= htmlspecialchars(
        $profilePhotoUrl,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    alt="Admin Profile"
>


<?php else: ?>

<div class="default-profile">

<i
    class="
        fa-solid
        fa-user-shield
    "
></i>

</div>

<?php endif; ?>


</button>



<!-- =================================================
     PROFILE MENU
================================================= -->

<ul
    class="
        dropdown-menu
        dropdown-menu-end
    "
>


<!-- PROFILE -->

<li>

<a
    class="dropdown-item"
    href="admin_profile.php"
>

<i
    class="
        fa-solid
        fa-user
        text-primary
    "
></i>

Profile

</a>

</li>



<li>

<hr class="dropdown-divider">

</li>



<!-- LOGOUT -->

<li>

<a
    class="
        dropdown-item
        text-danger
    "
    href="admin_logout.php"
    onclick="
        return confirm(
            'Are you sure you want to logout?'
        );
    "
>

<i
    class="
        fa-solid
        fa-right-from-bracket
    "
></i>

Logout

</a>

</li>


</ul>


</div>


</div>


</div>

</nav>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<div class="container py-4">


<div
    class="
        d-flex
        justify-content-between
        align-items-center
        mb-4
    "
>


<div>

<h3 class="fw-bold mb-1">

My Classes

</h3>


<p class="text-muted mb-0">

Classes created by you

</p>

</div>


</div>



<!-- =====================================================
     CLASS LIST
===================================================== -->

<div class="row g-4">


<?php

if (
    mysqli_num_rows(
        $classResult
    ) === 0
) {

?>


<!-- NO CLASS -->

<div class="col-12">

<div
    class="
        text-center
        py-5
    "
>


<i
    class="
        fa-solid
        fa-folder-open
        text-muted
    "
    style="font-size:60px;"
></i>


<h4 class="mt-3">

No Classes Created

</h4>


<p class="text-muted">

Create your first class to add students.

</p>


<a
    href="create_class.php"
    class="btn btn-primary"
>

<i class="fa fa-plus"></i>

Create Class

</a>


</div>

</div>


<?php

}


/*
|--------------------------------------------------------------------------
| LOOP CLASSES
|--------------------------------------------------------------------------
*/

while (
    $class =
    mysqli_fetch_assoc(
        $classResult
    )
) {


$class_id =
    (int) $class['id'];


$class_name =
    $class['class_name'];


$academic_year =
    $class['academic_year'];


$total_students =
    (int) $class['total_students'];

?>


<!-- =====================================================
     CLASS CARD
===================================================== -->

<div
    class="
        col-md-6
        col-lg-4
    "
>


<div class="class-card p-4">


<!-- TOP -->

<div
    class="
        d-flex
        justify-content-between
    "
>


<!-- CLASS ICON -->

<div class="class-icon">

<i
    class="
        fa-solid
        fa-graduation-cap
    "
></i>

</div>



<!-- CLASS OPTIONS -->

<div class="dropdown">


<button
    type="button"
    class="
        btn
        btn-light
        rounded-circle
    "
    data-bs-toggle="dropdown"
>

<i
    class="
        fa-solid
        fa-ellipsis-vertical
    "
></i>

</button>



<ul
    class="
        dropdown-menu
        dropdown-menu-end
    "
>


<!-- ADD STUDENT -->

<li>

<a
    class="dropdown-item"
    href="
        add_student.php?class_id=
        <?= $class_id ?>
    "
>

<i
    class="
        fa-solid
        fa-user-plus
        text-primary
    "
></i>

Add Student

</a>

</li>



<!-- EDIT CLASS -->

<li>

<a
    class="dropdown-item"
    href="
        edit_class.php?id=
        <?= $class_id ?>
    "
>

<i
    class="
        fa-solid
        fa-pen
        text-warning
    "
></i>

Edit Class

</a>

</li>



<li>

<hr class="dropdown-divider">

</li>



<!-- DELETE -->

<li>

<a
    class="
        dropdown-item
        text-danger
    "
    href="
        admin_classes.php?delete_class=
        <?= $class_id ?>
    "
    onclick="
        return confirm(
            'Are you sure you want to delete this class? All students in this class will also be deleted.'
        );
    "
>

<i
    class="
        fa-solid
        fa-trash
    "
></i>

Delete Class

</a>

</li>


</ul>


</div>


</div>



<!-- =================================================
     CLASS DETAILS
================================================= -->

<div class="mt-3">


<div class="class-name">

<?= htmlspecialchars(
    $class_name,
    ENT_QUOTES,
    'UTF-8'
) ?>

</div>


<div class="academic-year mt-1">

<i class="fa fa-calendar"></i>

Academic Year:

<?= htmlspecialchars(
    $academic_year,
    ENT_QUOTES,
    'UTF-8'
) ?>

</div>


<div class="student-count mt-3">

<i
    class="
        fa fa-users
        text-primary
    "
></i>

<?= $total_students ?>

Students

</div>


</div>



<!-- =================================================
     VIEW STUDENTS
================================================= -->

<div class="mt-4">

<a
    href="view_students.php?class_id=<?php echo $class_id; ?>"
    class="btn btn-primary w-100"
>
    <i class="fa fa-users"></i>
    View Students
</a>

</div>


</div>

</div>


<?php

}

?>


</div>

</div>



<!-- =====================================================
     BOOTSTRAP JS
===================================================== -->

<script
    src="
        https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js
    "
></script>


</body>

</html>