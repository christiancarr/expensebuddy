<?php
session_start();
if (!isset($_SESSION['uID']) && !isset($_COOKIE['uID'])) {
    // Redirect to login page if user is not logged in
    header("Location: index.php");
    exit();
}

// Optional: Refresh session with cookie if it exists
if (!isset($_SESSION['uID']) && isset($_COOKIE['uID'])) {
    $_SESSION['uID'] = $_COOKIE['uID'];
}
?>