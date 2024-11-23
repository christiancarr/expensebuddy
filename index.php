<?php

session_start();

// Include necessary files and libraries
include("master_inc.php");

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

$successPage = 'expenses.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Custom bcrypt hash function (compatible with PHP 5.4)
function bcrypt_hash($password) {
    $salt = substr(str_replace('+', '.', base64_encode(openssl_random_pseudo_bytes(17))), 0, 22);
    return crypt($password, '$2y$10$' . $salt);
}

// Custom bcrypt verify function (compatible with PHP 5.4)
function bcrypt_verify($password, $hashedPassword) {
    return crypt($password, $hashedPassword) === $hashedPassword;
}

/* EMAIL SENDER. I HAPPEN TO BE USING SOCKETLABS BUT YOU CAN ALSO USE SENDGRID, MAILGUN OR ANYTHING ELSE YOU CHOSE.  FOR SOCKET LABS JUST RELACE WITH YOUR USERNAME AND PASSWORD */
/* Function to send verification email using PHPMailer with SocketLabs SMTP
function sendVerificationEmail($email, $token, $firstName) {
    $resetLink = "https://expensebuddy.io/emailVerify.php?token=" . urlencode($token);
    
    // Initialize PHPMailer
    $mail = new PHPMailer();
    $mail->IsSMTP();
    //$mail->SMTPDebug = 2; // Enable verbose debug output for debugging 
    $mail->Host = 'smtp.socketlabs.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'YOUR SOCKETLABS USERNAME HERE';
    $mail->Password = 'YOUR SOCKETLABS PASSWORD HERE ';
    $mail->SMTPSecure = 'tls'; // Use 'tls' for port 587
    $mail->Port = 587;

    // Set email details
    $mail->SetFrom('no-reply@expensebuddy.io', 'ExpenseBuddy.io Support');
    $mail->AddAddress($email);
    $mail->IsHTML(true);
    $mail->Subject = 'Email Verification';
    $mail->Body = "
        <h2>Hello, $firstName!</h2>
        <p>Thank you for registering. Please verify your email by clicking the link below:</p>
        <a href='$resetLink'>$resetLink</a>
        <p>If you did not register, please ignore this email.</p>";
    $mail->AltBody = "Please verify your email by clicking the link: $resetLink";

    // Send email and return status
    if ($mail->Send()) {
        return true;
    } else {
        echo 'Mailer Error: ' . $mail->ErrorInfo;
        return false;
    }
}

$response = "";
*/

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    ob_start();

    // Registration Process
    if (isset($_POST['register'])) {
        $email = $_POST['email'];
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $response = "Passwords do not match!";
        } else {
            // Check if the user already exists
            $sql = "SELECT * FROM users WHERE email = '$email'";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                $response = "User already exists!";
            } else {
                // Generate a unique token
                $token = bin2hex(openssl_random_pseudo_bytes(16));
                $hashed_password = bcrypt_hash($password);
                
                $accession = date("YmdHis");

                // Insert the new user into the database
				//WHEN TURNING ON EMAIL VERIFICATION, SET STATUS TO 0 INSTEAD OF 1.  THIS WILL REQUIRE NEW USER TO VERIFY THE EMAIL THAT WAS SENT TO THEM
                $sql = "INSERT INTO users (accession, email, firstName, lastName, phone, status, password, reset_token) 
                        VALUES ('$accession','$email', '$firstName', '$lastName', '$phone', '1', '$hashed_password', '$token')";
                
                if ($conn->query($sql) === TRUE) {
                    // Send the verification email
                    if (sendVerificationEmail($email, $token, $firstName)) {
                        $response = "User registered successfully! A verification email has been sent.";

                        // Find the new user and set fID = uID
                        $query = "SELECT * FROM users WHERE `accession` = '$accession'";
                        $result = $conn->query($query);

                        while ($row = $result->fetch_assoc()) {
                            $uID = $row["uID"];
                            
                            // Update fID
                            $query2 = $conn->query("UPDATE `users` SET `fID`='$uID' WHERE `uID` = '$uID'");

                            if ($query2) {
                                // Insert default categories - ADJUST HOWEVER YOU WANT
                                $defaultCategories = [
                                    ['Rent', 2500],
                                    ['Home Maintenance', 175],
                                    ['Electricity, Heat, Natural Gas', 500],
                                    ['Water, Trash', 175],
                                    ['Cell / Internet', 400],
                                    ['Groceries', 1200],
                                    ['Clothing', 250],
                                    ['Medical / Dental', 350],
                                    ['Fuel', 500],
                                    ['Entertainment / Eating Out', 500],
                                    ['Charitable Contributions', 500],
                                    ['Life Insurance', 100],
                                    ['Health Insurance', 250],
                                    ['Vehicle Insurance', 500],
                                    ['Car Payments', 600],
                                    ['Personal Care', 200],
                                    ['Vehicle Maintenance', 150],
                                    ['Gifts', 100],
                                ];

                                foreach ($defaultCategories as $category) {
                                    $catName = $category[0];
                                    $catBudget = $category[1];
                                    $insertCategory = $conn->query("INSERT INTO `categories` (catName, catBudget, fID) 
                                        VALUES ('$catName', '$catBudget', '$uID')");
                                    
                                    if (!$insertCategory) {
                                        echo 'Category Insert Error: (' . $conn->errno . ') ' . $conn->error;
                                    }
                                }
                            } else {
                                echo 'fID UPDATE Error: (' . $conn->errno . ') ' . $conn->error;
                            }
                        }
                    } else {
                        $response = "User registered, but failed to send verification email.";
                    }
                } else {
                    $response = "Database error: " . $conn->error;
                }
            }
        }
    }

    // Login Process
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (bcrypt_verify($password, $user['password'])) {
                if ($user['status'] == '1') {
                    session_start();
                    $_SESSION['fID'] = $user['fID'];
                    $_SESSION['uID'] = $user['uID'];
                    setcookie('uID', $user['uID'], time() + (86400 * 30), "/");
                    header("Location: $successPage");
                    exit();
                } else {
                    $response = "Please verify your email first!";
                }
            } else {
                $response = "Incorrect password!";
            }
        } else {
            $response = "No user found with this email!";
        }
    }

    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            var responseModal = new bootstrap.Modal(document.getElementById('responseModal'));
            document.getElementById('responseMessage').textContent = '$response';
            responseModal.show();
        });
    </script>";
    ob_end_flush();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExpenseBuddy.io</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .logo {
            font-weight: bold;
            font-size: 1.5rem;
            color: #007bff;
        }
        .hero {
            background-color: #f8f9fa;
            padding: 50px 0;
            text-align: center;
        }
        .btn-primary {
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand logo" href="#"><img src="images/expenseBuddyLogoLG.png" width="300"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 class="display-4">ExpenseBuddy.io</h1>
            <p class="lead">A free, open-source personal expense manager to help you stay on budget and track your spending.</p>
            
            <!-- Login & Signup Buttons -->
            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#signupModal">Create Account</button>
        </div>
    </section>

    <!-- Features Section -->
    <div class="container my-5">
        <div class="row text-center">
            <div class="col-md-4">
                <h3>Easy to Use</h3>
                <p>Simple and intuitive interface designed for everyone. No complicated setups or learning curves.</p>
            </div>
            <div class="col-md-4">
                <h3>Completely Free</h3>
                <p>No hidden fees or subscription plans. 100% free and open-source for personal use.</p>
            </div>
            <div class="col-md-4">
                <h3>Open Source</h3>
                <p>Contribute to our codebase or customize it for your needs. Available on GitHub.</p>
            </div>
        </div>

        <!-- Download Button -->
     
        <div class="text-center mt-4">
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#downloadModal">Download Source Code</button>
        </div>
    </div>
	
	<!-- Download Modal -->
    <div class="modal fade" id="downloadModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="downloadModalLabel">Download Source Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Will publish to GitHub soon.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Login</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
						<!-- TURN THIS ON IF YOU HAVE AN EMAIL SENDER SET UP IN forgotPassword.php -->
                       <!-- <a href="forgotPassword.php" class="btn btn-link">Forgot Password?</a>-->
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="login" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Signup Modal -->
    <div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Create Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" required>
                        </div>
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="register" class="btn btn-secondary">Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
	
	<!-- Response Modal -->
    <div class="modal fade" id="responseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="responseMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Bootstrap Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>

