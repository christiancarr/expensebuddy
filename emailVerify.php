<?php
include("master_inc.php");
session_start();

// Enable error reporting for debugging (remove this in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to display a Bootstrap-styled message
function displayMessage($type, $title, $message, $buttonText = 'Continue', $buttonLink = 'index.php') {
    echo "
    <div class='container mt-5'>
        <div class='text-center'>
            <div class='card border-0 shadow' style='background-color: #ffffff;'>
                <div class='card-body'>
                    <h4 class='card-title text-$type'>$title</h4>
                    <p class='card-text'>$message</p>
                    <a href='$buttonLink' class='btn btn-success mt-3'>$buttonText</a>
                </div>
            </div>
        </div>
    </div>
    ";
}

// Start of HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #ffffff; /* Make the entire page background white */
        }
        .card {
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body>

<?php
// Check if the token is provided in the URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    displayMessage('danger', 'Error', 'Invalid request. No token provided.');
    exit();
}

$token = $_GET['token'];
$token = mysqli_real_escape_string($conn, $token);

// Check if the token exists in the database
$sql = "SELECT * FROM users WHERE reset_token = '$token'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    // Token found, update the user's status to verified
    $user = $result->fetch_assoc();
    $updateSql = "UPDATE users SET status = '1', reset_token = NULL WHERE uID = " . intval($user['uID']);
    
    if ($conn->query($updateSql) === TRUE) {
        displayMessage(
            'success',
            'Email Verified',
            'Your email has been successfully verified!',
            'Continue',
            'index.php'
        );
    } else {
        displayMessage(
            'danger',
            'Database Error',
            'There was an error updating your account. Please try again later.'
        );
    }
} else {
    // Token not found or expired
    displayMessage('danger', 'Invalid Token', 'The token is invalid or has expired. Please try registering again.');
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
