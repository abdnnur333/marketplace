<?php
// DATABASE CONFIGURATION
// Set database connection credentials
$host = 'localhost';
$dbname = 'university_marketplace';
$username = 'root';  // Change if needed
$password = '';      // Change if needed

// CREATE DATABASE CONNECTION
// Establish connection to MySQL database using mysqli
$conn = mysqli_connect($host, $username, $password, $dbname);

// VALIDATE CONNECTION
// Check if connection was successful, die if it fails
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>