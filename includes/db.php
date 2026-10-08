<?php
// Retrieve database credentials from environment variables (for Render deployment)
// If environment variables are not set, fallback to default local XAMPP settings
$host     = getenv('DB_HOST')     ?: 'mysql-eregistrar-juliefelmalusay-0dad.g.aivencloud.com';
$user     = getenv('DB_USER')     ?: 'avnadmin';
$pass     = getenv('DB_PASS')     ?: 'AVNS_DwLisUTSX3JjP5Pz-z5';
$dbname   = getenv('DB_NAME')     ?: 'defaultdb';
$port     = getenv('DB_PORT')     ?: 3306;

// Create database connection
$conn = mysqli_connect($host, $user, $pass, $dbname, (int)$port);

// Check connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>