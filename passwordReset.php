<?php
include("master_inc.php");
session_start();

$message = "";
$modalType = "";
$title = "";
$buttonText = "Continue";
$buttonLink = "index.php";

// Function to set a Bootstrap-styled modal message
function setModalMessage($type, $title, $message, $buttonText = 'Continue', $buttonLink = 'index.php') {
    global $modalType, $messageContent, $titleContent, $buttonLinkContent, $buttonTextContent;
    $modalType = $type;
    $messageContent = $message;
    $titleContent = $title;
    $buttonTextContent = $buttonText;
    $buttonLinkContent = $buttonLink;
}

// Check if the token is provided in the URL
if (!isset($_GET['token'])) {
    setModalMessage('danger', 'Error', 'Invalid request. No token provided.', 'Back to Home', 'index.php');
} else {
    $token = $_GET['token'];
    $token = mysqli_real_escape_string($conn, $token);

    // Check if the token exists in the database
    $sql = "SELECT * FROM users WHERE reset_token = '$token'";
    $result = $conn->query($sql);

    if ($result->num_rows == 0) {
        setModalMessage('danger', 'Error', 'Invalid or expired token.', 'Back to Home', 'index.php');
    } else {
        $user = $result->fetch_assoc();

        // Update the user's status to 1 (verified)
        $updateStatusSql = "UPDATE users SET status = 1 WHERE uID = " . intval($user['uID']);
        $conn->query($updateStatusSql);

        // Function to hash passwords using bcrypt (compatible with PHP 5.4)
        function bcrypt_hash($password) {
            $salt = substr(str_replace('+', '.', base64_encode(openssl_random_pseudo_bytes(17))), 0, 22);
            return crypt($password, '$2y$10$' . $salt);
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'])) {
            $password = trim($_POST['password']);
            $confirmPassword = trim($_POST['confirmPassword']);

            // Validate password requirements
            if (strlen($password) < 8 || 
                !preg_match('/[A-Z]/', $password) || 
                !preg_match('/[a-z]/', $password) || 
                !preg_match('/[0-9]/', $password)) {
                setModalMessage('danger', 'Error', 'Password must be at least 8 characters long and include one uppercase letter, one lowercase letter, and one number.', 'Try Again');
            } elseif ($password !== $confirmPassword) {
                setModalMessage('danger', 'Error', 'Passwords do not match.', 'Try Again');
            } else {
                // Hash the password using bcrypt
                $hashedPassword = bcrypt_hash($password);

                // Update the password in the database and clear the token
                $updateSql = "UPDATE users SET password = '$hashedPassword', reset_token = NULL WHERE uID = " . intval($user['uID']);
                if ($conn->query($updateSql) === TRUE) {
                    setModalMessage('success', 'Success', 'Your password has been successfully updated.', 'Continue', 'index.php');
                } else {
                    setModalMessage('danger', 'Error', 'Error updating password: ' . htmlspecialchars($conn->error), 'Back to Home');
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
	 <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center">Reset Your Password</h2>
    <form method="POST" action="" class="mt-4">
        <div class="mb-3">
            <label for="password" class="form-label">New Password:</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password" required>
        </div>
        <div class="mb-3">
            <label for="confirmPassword" class="form-label">Confirm New Password:</label>
            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm new password" required>
        </div>
        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-success">Reset Password</button>
            <a href="index.php" class="btn btn-warning">Back</a>
        </div>
    </form>
</div>

<!-- Modal for displaying messages -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-<?php echo $modalType; ?>" id="feedbackModalLabel"><?php echo $titleContent; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p><?php echo $messageContent; ?></p>
            </div>
            <div class="modal-footer">
                <a href="<?php echo $buttonLinkContent; ?>" class="btn btn-success"><?php echo $buttonTextContent; ?></a>
            </div>
        </div>
    </div>
</div>

<!-- Trigger the modal if there is a message -->
<?php if (!empty($messageContent)) : ?>
<script>
    var myModal = new bootstrap.Modal(document.getElementById('feedbackModal'));
    myModal.show();
</script>
<?php endif; ?>
</body>
</html>




