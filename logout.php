<?php
session_start();

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Delete the user ID cookie
setcookie('uID', '', time() - 3600, "/"); // Set to a past time to delete the cookie

// Logout confirmation with button to go back to index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container text-center mt-5">
        <h2>Logout Successful</h2>
        <p>You have been logged out.</p>
        <a href="index.php" class="btn btn-success">Return to Home</a>
    </div>
</body>
</html>
