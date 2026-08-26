<?php

session_start();

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['admin_id'])) {

    header("Location: admin_login.php");
    exit();

}

$admin_id = (int)$_SESSION['admin_id'];


if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {

    die("Invalid Class ID.");

}

$class_id = (int)$_GET['id'];


// Check class

$check = mysqli_query(
    $conn,

    "SELECT id
     FROM classes
     WHERE id = $class_id
     AND teacher_id = $admin_id
     LIMIT 1"
);


if (
    !$check ||
    mysqli_num_rows($check) == 0
) {

    die("Class not found or permission denied.");

}


// Delete students first

$delete_students = mysqli_query(
    $conn,

    "DELETE FROM students
     WHERE class_id = $class_id"
);


if (!$delete_students) {

    die(
        "Unable to delete students: " .
        htmlspecialchars(
            mysqli_error($conn)
        )
    );

}


// Delete class

$delete_class = mysqli_query(
    $conn,

    "DELETE FROM classes
     WHERE id = $class_id
     AND teacher_id = $admin_id"
);


if (!$delete_class) {

    die(
        "Unable to delete class: " .
        htmlspecialchars(
            mysqli_error($conn)
        )
    );

}


header(
    "Location: admin_classes.php"
);

exit();

?>