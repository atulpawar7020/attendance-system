<?php
// =============================================
// ✅ DEBUG MODE ON – (बाद में इसे बंद कर देना)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get database credentials from environment variables
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$db   = getenv('DB_NAME') ?: 'attendance_db';
$port = getenv('DB_PORT') ?: 3306;

// Create connection
$conn = mysqli_connect($host, $user, $pass, $db, $port);

// Check connection
if (!$conn) {
    die("❌ Database Connection Failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");
?>