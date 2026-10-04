<?php
// Retrieve database credentials from environment variables (for Render deployment)
// If environment variables are not set, fallback to default local XAMPP settings
$host     = getenv('DB_HOST')     ?: 'localhost';
$user     = getenv('DB_USER')     ?: 'root';
$pass     = getenv('DB_PASS')     ?: '';
$dbname   = getenv('DB_NAME')     ?: 'eregistrar';
$port     = getenv('DB_PORT')     ?: 3306;

// Create database connection
$conn = mysqli_connect($host, $user, $pass, $dbname, (int)$port);

// Check connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>