<?php
// Database connection credentials
$host = 'localhost';
$user = 'expeybnv_prime';
$pass = 'Sheri4316!';
$dbName = 'expeybnv_db1';

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbName);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>