<?php
// Retrieve database credentials from environment variables (for Render deployment)
// If environment variables are not set, fallback to default local XAMPP settings
$host     = getenv('DB_HOST')     ?: 'eregistrar-db-juliefelmalusay-0dad.f.aivencloud.com';
$user     = getenv('DB_USER')     ?: 'avnadmin';
$pass     = getenv('DB_PASS')     ?: 'AVNS_b6hfjrZX9LKY9nb49ja';
$dbname   = getenv('DB_NAME')     ?: 'defaultdb';
$port     = getenv('DB_PORT')     ?: 17569;

// Create database connection
$conn = mysqli_connect($host, $user, $pass, $dbname, (int)$port);

// Check connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>