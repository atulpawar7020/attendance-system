<?php
// =============================================
// INDEX.PHP – Smart Attendance System
// Works on Apache, PHP Built-in Server, and Pxxl
// =============================================

// 🔥 Enable error reporting for debugging (remove when live)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// =============================================
// ✅ ROUTER for PHP Built-in Server (if you need URL rewriting)
// This handles requests like /join_class.php?code=... directly
// =============================================
$request_uri = $_SERVER['REQUEST_URI'];
$request_path = parse_url($request_uri, PHP_URL_PATH);
$request_path = ltrim($request_path, '/');

// If it's a real file (CSS, JS, images, etc.), serve it directly
if ($request_path && file_exists($request_path) && is_file($request_path)) {
    return false; // Let the server serve the file
}

// If the request is for a specific PHP file that exists, include it
if ($request_path && file_exists($request_path) && pathinfo($request_path, PATHINFO_EXTENSION) === 'php') {
    include $request_path;
    exit;
}

// =============================================
// 🔐 SESSION-BASED REDIRECT (Original Logic)
// =============================================

// If teacher is logged in, go to classes.php
if (isset($_SESSION['teacher_id'])) {
    header("Location: classes.php");
    exit();
}

// If admin is logged in, go to admin dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: Admin/admin_classes.php");
    exit();
}

// If no one is logged in, go to login page
header("Location: login.php");
exit();
?>