<?php
// Database connection credentials
$host = 'localhost';
$user = 'YOUR USERNAME';
$pass = 'YOUR PASSWORD';
$dbName = 'YOUR DATABASE NAME';

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbName);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
