<?php
session_start();

if (isset($_SESSION['teacher_id'])) {
    header("Location: classes.php");
    exit();
}

if (isset($_SESSION['admin_id'])) {
    header("Location: Admin/admin_classes.php");
    exit();
}

header("Location: login.php");
exit();
?>