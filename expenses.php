<?php

include"userAuth.php";

session_start();

include "master_inc.php";

$fID = $_SESSION['fID'];
$uID = $_SESSION['uID'];

// Reformat the current date to match the database format for comparisons
$currentMonthStart = date('m/01/Y');
$currentMonthEnd = date('m/t/Y');

// Fetch categories for the category dropdown
$categoryResult = $conn->query("SELECT catName FROM categories WHERE `fID` = '$fID' ORDER BY catName ASC");
if ($categoryResult === false) {
    die("Error fetching categories: " . $conn->error);
}
$categories = array();
while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row['catName'];
}

// Handle add, edit, delete, and search actions
$searchQuery = '';
$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : $currentMonthStart;
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : $currentMonthEnd;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'add') {
		
		//echo"<br />uID: $uID<br />";
		
        $date = $_POST['date'];
        $category = $_POST['category'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $notes = $_POST['notes'];
        $stmt = $conn->prepare("INSERT INTO expenses (date, category, amount, description, notes, uID, fID) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssssss", $date, $category, $amount, $description, $notes, $uID, $fID);
            $stmt->execute();
            $stmt->close();
        } else {
            die("Error preparing statement: " . $conn->error);
        }
    } elseif ($action == 'edit') {
        $eID = $_POST['eID'];
        $date = $_POST['date'];
        $category = $_POST['category'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $notes = $_POST['notes'];
        $stmt = $conn->prepare("UPDATE expenses SET date = ?, category = ?, amount = ?, description = ?, notes = ? WHERE eID = ?");
        if ($stmt) {
            $stmt->bind_param("sssssi", $date, $category, $amount, $description, $notes, $eID);
            $stmt->execute();
            $stmt->close();
        } else {
            die("Error preparing statement: " . $conn->error);
        }
    } elseif ($action == 'delete') {
        $eID = $_POST['eID'];
        $stmt = $conn->prepare("DELETE FROM expenses WHERE eID = ?");
        if ($stmt) {
            $stmt->bind_param("i", $eID);
            $stmt->execute();
            $stmt->close();
        } else {
            die("Error preparing statement: " . $conn->error);
        }
    } elseif ($action == 'search') {
        $searchQuery = $_POST['search'];
    }
}

// Fetch expenses from the database with optional search and date filtering
$sql = "SELECT eID, date, category, amount, description, notes FROM expenses WHERE `fID` = '$fID' AND (STR_TO_DATE(date, '%m/%d/%Y') BETWEEN STR_TO_DATE(?, '%m/%d/%Y') AND STR_TO_DATE(?, '%m/%d/%Y'))";
$params = array($startDate, $endDate);

if ($searchQuery) {
    $sql .= " AND (category LIKE ? OR description LIKE ?)";
    $likeQuery = '%' . $searchQuery . '%';
    $params[] = $likeQuery;
    $params[] = $likeQuery;
}

$sql .= " ORDER BY STR_TO_DATE(date, '%m/%d/%Y') DESC";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$paramTypes = str_repeat('s', count($params));
$refs = array();
foreach ($params as $key => $value) {
    $refs[$key] = &$params[$key];
}
call_user_func_array(array($stmt, 'bind_param'), array_merge(array($paramTypes), $refs));

$stmt->execute();
$stmt->bind_result($eID, $date, $category, $amount, $description, $notes);

$expenses = array();
$totalAmount = 0.00;
while ($stmt->fetch()) {
    $expenses[] = array(
        'eID' => $eID,
        'date' => $date,
        'category' => $category,
        'amount' => $amount,
        'description' => $description,
        'notes' => $notes
    );
    $totalAmount += (float)$amount;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Manager</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet">
	<!-- Using Flatpickr because bootstrap datepicker in the edit modal was blanking out the rest of the modal fields on initiation -->
	<!-- Flatpickr CSS -->
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <!-- Jquery before flatpicker -->
	<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
	
</head>
<body>
<div class="container mt-5">
    <div class="text-center">
        <h2><a class="navbar-brand logo" href="#"><img src="images/expenseBuddyLogoLG.png" width="300"></a></h2>
        <h2>Expense Manager</h2>
        <a href="categories.php" class="btn btn-light mt-2">Manage Categories</a>
        <a href="report.php" class="btn btn-primary mt-2">View Report</a>
        <a href="logout.php" class="btn btn-warning mt-2">Logout</a>
    </div>

    <!-- Add Expense Form -->
    <div class="card mt-4">
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="text" class="form-control" id="date" name="date" value="<?php echo date('m/d/Y'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select class="form-control" id="category" name="category" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
    <label for="amount">Amount</label>
    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
</div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" class="form-control" id="description" name="description">
                </div>
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Save Expense</button>
            </form>
        </div>
    </div>

    <!-- Search Form -->
    <div class="card mt-4">
        <div class="card-body">
            <form method="POST" action="" class="form-inline">
                <input type="hidden" name="action" value="search">
                <div class="form-group mr-2">
                    <label for="startDate" class="mr-2">Start Date</label>
                    <input type="text" class="form-control" id="startDate" name="startDate" value="<?php echo $startDate; ?>">
                </div>
                <div class="form-group mr-2">
                    <label for="endDate" class="mr-2">End Date</label>
                    <input type="text" class="form-control" id="endDate" name="endDate" value="<?php echo $endDate; ?>">
                </div>
                <div class="input-group mr-2">
                    <input type="text" class="form-control" name="search" placeholder="Search by Category or Description" value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary ml-2" id="clearButton">Clear</button>
            </form>
        </div>
    </div>

    <!-- Expense List -->
    <div class="mt-5">
        <h4>Existing Expenses</h4>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th></th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td>
                                <button class="btn btn-success" data-toggle="modal" data-target="#editModal" 
                                        data-id="<?php echo $expense['eID']; ?>" 
                                        data-date="<?php echo $expense['date']; ?>" 
                                        data-category="<?php echo htmlspecialchars($expense['category'], ENT_QUOTES, 'UTF-8'); ?>" 
                                        data-amount="<?php echo $expense['amount']; ?>" 
                                        data-description="<?php echo htmlspecialchars($expense['description'], ENT_QUOTES, 'UTF-8'); ?>" 
                                        data-notes="<?php echo htmlspecialchars($expense['notes'], ENT_QUOTES, 'UTF-8'); ?>">
                                    Edit
                                </button>
                            </td>
                            <td><?php echo htmlspecialchars($expense['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($expense['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo "$" . number_format($expense['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($expense['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Total Amount</th>
                        <th colspan="3"><?php echo "$" . number_format($totalAmount, 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Expense</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editExpenseForm" method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="eID" id="editEID">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editDate">Date</label>
                        <input type="text" class="form-control" id="editDate" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="editCategory">Category</label>
                        <select class="form-control" id="editCategory" name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                   <div class="form-group">
    <label for="editAmount">Amount</label>
    <input type="number" class="form-control" id="editAmount" name="amount" step="0.01" min="0" required>
</div>
                    <div class="form-group">
                        <label for="editDescription">Description</label>
                        <input type="text" class="form-control" id="editDescription" name="description">
                    </div>
                    <div class="form-group">
                        <label for="editNotes">Notes</label>
                        <textarea class="form-control" id="editNotes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="submit" class="btn btn-danger" name="action" value="delete" onclick="return confirm('Are you sure you want to delete this expense?');">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap, Datepicker, and jQuery -->

<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script>
$(document).ready(function () {
    // Restrict input to valid numbers only
    $('#amount, #editAmount').on('input', function () {
        // Regex to match valid decimal values like 123.45
        const validValue = this.value.match(/^\d*\.?\d{0,2}$/);
        if (!validValue) {
            this.value = this.value.slice(0, -1); // Remove the last invalid character
        }
    });

    // Initialize Flatpickr for date fields
    $('#date, #startDate, #endDate, #editDate').flatpickr({
        dateFormat: 'm/d/Y', // Match your database date format
        allowInput: true, // Allow manual input in addition to the calendar
    });

    // Populate modal fields when opened
    $('#editModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget); // Button that triggered the modal
        const modal = $(this);

        // Populate modal fields with button data
        modal.find('#editEID').val(button.data('id'));
        modal.find('#editDate').val(button.data('date'));
        modal.find('#editCategory').val(button.data('category'));
        modal.find('#editAmount').val(button.data('amount'));
        modal.find('#editDescription').val(button.data('description'));
        modal.find('#editNotes').val(button.data('notes'));
    });

    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});


</script>

</body>
</html>

