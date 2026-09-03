<?php

if ($_SERVER['SERVER_NAME'] == "localhost") {

    $conn = mysqli_connect(
        "localhost",
        "root",
        "",
        "attendance_db"
    );

} else {

    $conn = mysqli_connect(
        "sql203.infinityfree.com",
        "if0_42566807",
        "Atul9940",
        "if0_42566807_attendance_db"
    );
}

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

?>