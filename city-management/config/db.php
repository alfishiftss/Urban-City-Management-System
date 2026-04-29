<?php
// config/db.php

$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$dbname = 'city_management';

// Create connection using mysqli
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    // Show error if connection fails
    die("Connection failed: " . $conn->connect_error);
}
?>
