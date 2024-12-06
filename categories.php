<?php

include"userAuth.php";

session_start();

include "master_inc.php";

$fID = $_SESSION['fID'];

// Handle add, edit, and delete actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == 'add') {
        $catName = $_POST['catName'];
        $catBudget = $_POST['catBudget'];
        $notes = $_POST['notes'];
        $stmt = $conn->prepare("INSERT INTO categories (catName, catBudget, notes, fID) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $catName, $catBudget, $notes, $fID);
            $stmt->execute();
            $stmt->close();
        } else {
            die("Error preparing statement: " . $conn->error);
        }
    } elseif ($action == 'edit') {
        $cID = $_POST['cID'];
        $catName = $_POST['catName'];
        $catBudget = $_POST['catBudget'];
        $notes = $_POST['notes'];
        $stmt = $conn->prepare("UPDATE categories SET catName = ?, catBudget = ?, notes = ? WHERE cID = ?");
        if ($stmt) {
            $stmt->bind_param("sssi", $catName, $catBudget, $notes, $cID);
            $stmt->execute();
            $stmt->close();
        } else {
            die("Error preparing statement: " . $conn->error);
        }
    } elseif ($action == 'delete') {
        $cID = $_POST['cID'];
        $stmt = $conn->prepare("DELETE FROM categories WHERE cID = ?");
        if ($stmt) {
            $stmt->bind_param("i", $cID);
            $stmt->execute();
            $stmt->close();
        } else {
            die("Error preparing statement: " . $conn->error);
        }
    }
}

// Fetch categories from the database
$result = $conn->query("SELECT * FROM categories WHERE `fID` = '$fID' ORDER BY cID ASC");

if ($result === false) {
    die("Error executing query: " . $conn->error);
}

$categories = array();
$totalBudget = 0.00;

while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
    $totalBudget += (float) $row['catBudget'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Manager</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Ensure the table is horizontally scrollable on small screens */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
    <script>
        // Function to ensure only numbers and a single dot are allowed in the Budget field
        function validateBudgetInput(event) {
            const char = String.fromCharCode(event.which);
            if (!/[0-9.]|\./.test(char)) {
                event.preventDefault();
            }
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <div class="text-center">
            <h2><a class="navbar-brand logo" href="#"><img src="images/expenseBuddyLogoLG.png" width="300"></a></h2>
            <h2>Category Manager</h2>
        </div>
        <div class="text-center mt-4">
            <a href="expenses.php" class="btn btn-success">Manage Expenses</a>
            <a href="report.php" class="btn btn-primary">Report</a>
            <a href="logout.php" class="btn btn-warning">Logout</a>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                <!-- Add Category Form -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="catName">Category Name</label>
                        <input type="text" class="form-control" id="catName" name="catName" required>
                    </div>
                    <div class="form-group">
                        <label for="catBudget">Budget</label>
                        <input type="text" class="form-control" id="catBudget" name="catBudget" required onkeypress="validateBudgetInput(event)">
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Add Category</button>
                </form>
            </div>
        </div>

        <!-- Category List -->
        <div class="mt-5">
            <h4>Existing Categories</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Edit</th>
                            <th>Category Name</th>
                            <th>Budget</th>
                            <th>Notes</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <!-- Edit Button triggers modal -->
                                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#editModal<?php echo $category['cID']; ?>">Edit</button>
                                </td>
                                <td><?php echo htmlspecialchars($category['catName'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo "$" . number_format($category['catBudget'], 2); ?></td>
                                <td><?php echo htmlspecialchars($category['notes'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <!-- Delete Form -->
                                    <form method="POST" action="" style="display:inline-block;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="cID" value="<?php echo $category['cID']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this category?');">Delete</button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $category['cID']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $category['cID']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel<?php echo $category['cID']; ?>">Edit Category</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="cID" value="<?php echo $category['cID']; ?>">
                                                <div class="form-group">
                                                    <label for="catName">Category Name</label>
                                                    <input type="text" class="form-control" id="catName" name="catName" value="<?php echo htmlspecialchars($category['catName'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="catBudget">Budget</label>
                                                    <input type="text" class="form-control" id="catBudget" name="catBudget" value="<?php echo htmlspecialchars($category['catBudget'], ENT_QUOTES, 'UTF-8'); ?>" required onkeypress="validateBudgetInput(event)">
                                                </div>
                                                <div class="form-group">
                                                    <label for="notes">Notes</label>
                                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($category['notes'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">Total Budget</th>
                            <th colspan="3"><?php echo "$" . number_format($totalBudget, 2); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

