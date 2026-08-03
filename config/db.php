<?php

if($_SERVER['SERVER_NAME']=="localhost"){

    $conn=mysqli_connect(
        "localhost",
        "root",
        "",
        "attendance_db"
    );

}else{

    $conn=mysqli_connect(
        "sql203.infinityfree.com",
        "if0_42566807",
        "YOUR_PASSWORD",
        "if0_42566807_attendance"
    );

}

if(!$conn){
    die(mysqli_connect_error());
}

?>