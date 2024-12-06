<?php
include("master_inc.php");
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
	 <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container mt-5">
  <h2 class="text-center"><a class="navbar-brand logo" href="#"><img src="images/expenseBuddyLogoLG.png" width="300"></a></h2>
  <h2 class="text-center">Forgot Password</h2>
    <form id="forgotPasswordForm" method="POST" action="">
        <div class="mb-3">
            <label for="email" class="form-label">Enter your email:</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
        </div>
        <div class="d-flex justify-content-between">
            <!-- Submit Button -->
            <button type="submit" class="btn btn-success">Submit</button>
            
            <!-- Back Button -->
            <a href="index.php" class="btn btn-warning">Back</a>
        </div>
    </form>
</div>

<!-- Modal for "User Not Found" -->
<div class="modal fade" id="userNotFoundModal" tabindex="-1" aria-labelledby="userNotFoundModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userNotFoundModalLabel">User Not Found</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                The email you entered does not exist in our system.
            </div>
            <div class="modal-footer">
                <a href="index.php" class="btn btn-primary">Continue</a>
            </div>
        </div>
    </div>
</div>

<?php
// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $email = mysqli_real_escape_string($conn, $email);

    // Check if the email exists in the database
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Email exists, redirect to forgotPasswordSender.php
        echo "<form id='redirectForm' method='POST' action='forgotPasswordSender.php'>";
        echo "<input type='hidden' name='email' value='" . htmlspecialchars($email) . "'>";
        echo "</form>";
        echo "<script>document.getElementById('redirectForm').submit();</script>";
    } else {
        // Email not found, show modal
        echo "<script>
                var myModal = new bootstrap.Modal(document.getElementById('userNotFoundModal'));
                myModal.show();
              </script>";
    }
}
?>
</body>
</html>
