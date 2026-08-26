<?php

session_start();

require_once __DIR__ . '/../config/db.php';


if (!isset($_SESSION['admin_id'])) {

    header("Location: admin_login.php");
    exit();

}


$admin_id = (int)$_SESSION['admin_id'];


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    die("Invalid Student ID.");

}


$student_id = (int)$_GET['id'];


// ===============================
// FIND STUDENT + CHECK ADMIN
// ===============================

$query = mysqli_query(
    $conn,

    "SELECT
        s.id,
        s.class_id
     FROM students s

     INNER JOIN classes c
        ON c.id = s.class_id

     WHERE s.id = $student_id
     AND c.teacher_id = $admin_id

     LIMIT 1"
);


if (!$query || mysqli_num_rows($query) == 0) {

    die("Student not found or permission denied.");

}


$student = mysqli_fetch_assoc($query);


$class_id = (int)$student['class_id'];


// ===============================
// DELETE
// ===============================

$delete = mysqli_query(
    $conn,

    "DELETE FROM students
     WHERE id = $student_id
     AND class_id = $class_id"
);


if (!$delete) {

    die(
        "Delete Error: " .
        htmlspecialchars(
            mysqli_error($conn)
        )
    );

}


// ===============================
// BACK
// ===============================

header(
    "Location: view_students.php?class_id=$class_id"
);

exit();

?>