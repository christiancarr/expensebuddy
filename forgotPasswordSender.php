<?php
include("master_inc.php");

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

/* EMAIL SENDER. I HAPPEN TO BE USING SOCKETLABS BUT YOU CAN ALSO USE SENDGRID, MAILGUN OR ANYTHING ELSE YOU CHOSE.  FOR SOCKET LABS JUST RELACE WITH YOUR USERNAME AND PASSWORD */

// Function to display a Bootstrap-styled message
function displayMessage($type, $title, $message, $buttonText = 'Continue', $buttonLink = 'index.php') {
    echo "
    <div class='container mt-5'>
        <div class='card shadow-lg'>
            <div class='card-body text-center'>
                <h4 class='card-title text-$type'>$title</h4>
                <p class='card-text'>$message</p>
                <a href='$buttonLink' class='btn btn-success mt-3'>$buttonText</a>
            </div>
        </div>
    </div>
    ";
}

if (isset($_REQUEST['email'])) {
    $email = trim($_REQUEST['email']);
    $email = mysqli_real_escape_string($conn, $email);

    // Check if the email exists in the database
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // User found; generate a reset token
        $token = bin2hex(openssl_random_pseudo_bytes(16));

        // Store the reset token in the database for this user
        $updateSql = "UPDATE users SET reset_token = '$token' WHERE email = '$email'";
        if ($conn->query($updateSql) === TRUE) {
            // Create the password reset link
            $resetLink = "https://expensebuddy.io/passwordReset.php?token=" . urlencode($token);

            // Initialize PHPMailer
            $mail = new PHPMailer();
            $mail->IsSMTP();
            $mail->Host = 'smtp.socketlabs.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'YOUR SOCKETLABS USERNAME HERE';
            $mail->Password = 'YOUR SOCKETLABS PASSWORD HERE ';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->SetFrom('no-reply@expensebuddy.io', 'ExpenseBuddy.io Support');
            $mail->AddAddress($email);
            $mail->IsHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "<p>Please click the link below to reset your password:</p><p><a href='$resetLink'>$resetLink</a></p>";
            $mail->AltBody = "Please click the link below to reset your password:\n\n$resetLink";

            if ($mail->Send()) {
                displayMessage(
                    'success',
                    'Password Reset Email Sent',
                    'A password reset link has been sent to your email address. Please check your inbox.',
                    'Continue',
                    'index.php'
                );
            } else {
                displayMessage(
                    'danger',
                    'Failed to Send Email',
                    'There was an error sending the password reset email. Please try again later.',
                    'Back to Home',
                    'index.php'
                );
            }
        } else {
            displayMessage(
                'danger',
                'Database Error',
                'There was an error updating the reset token. Please try again later.',
                'Back to Home',
                'index.php'
            );
        }
    } else {
        displayMessage(
            'warning',
            'User Not Found',
            'The email address you entered does not exist in our system.',
            'Back to Home',
            'index.php'
        );
    }
} else {
    displayMessage(
        'danger',
        'Invalid Request',
        'No email address provided. Please try again.',
        'Back to Home',
        'index.php'
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
	 <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
</body>
</html>
