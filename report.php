<?php

include"userAuth.php";

session_start();

include "master_inc.php";

$fID = $_SESSION['fID'];

// Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Get selected month and year from the form, or default to the current month and year
$selectedMonth = isset($_POST['month']) ? $_POST['month'] : $currentMonth;
$selectedYear = isset($_POST['year']) ? $_POST['year'] : $currentYear;

// Fetch categories and their budgets from the categories table
$categoryResult = $conn->query("SELECT cID, catName, catBudget FROM categories WHERE `fID` = '$fID' ORDER BY catName ASC");
if ($categoryResult === false) {
    die("Error fetching categories: " . $conn->error);
}

$categories = array();
while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row;
}

// Calculate month-to-date totals for each category from the expenses table
$reportData = array();
$totalBudget = 0.00;
$totalSpent = 0.00;

foreach ($categories as $category) {
    $catName = $category['catName'];
    $catBudget = $category['catBudget'];
    $totalBudget += (float) $catBudget;

    // Get total expenses for the selected month and year
    $stmt = $conn->prepare("SELECT SUM(amount) AS monthToDate FROM expenses WHERE `fID` = '$fID' AND category = ? AND MONTH(STR_TO_DATE(date, '%m/%d/%Y')) = ? AND YEAR(STR_TO_DATE(date, '%m/%d/%Y')) = ?");
    $stmt->bind_param("sii", $catName, $selectedMonth, $selectedYear);
    $stmt->execute();
    $stmt->bind_result($monthToDate);
    $stmt->fetch();
    $stmt->close();

    $monthToDate = $monthToDate ? $monthToDate : 0.00;
    $totalSpent += (float) $monthToDate;

    $remainingBudget = $catBudget - $monthToDate;
    $percentSpent = $catBudget > 0 ? ($monthToDate / $catBudget) * 100 : 0;

    $reportData[] = array(
        'catName' => $catName,
        'catBudget' => $catBudget,
        'monthToDate' => $monthToDate,
        'remainingBudget' => $remainingBudget,
        'percentSpent' => $percentSpent
    );
}

$remainingTotalBudget = $totalBudget - $totalSpent;
$profitOrLoss = $remainingTotalBudget >= 0 ? "under" : "over";
$profitOrLossAmount = abs($remainingTotalBudget);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Report</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .progress {
            height: 20px;
        }
        .progress-bar {
            line-height: 20px;
            font-size: 12px;
        }
        #totalBudgetBar {
            height: 30px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="text-center">
            <h2>Expense Report</h2>
        </div>
        <div class="text-center mt-4">
            <a href="categories.php" class="btn btn-light">Categories</a>
            <a href="expenses.php" class="btn btn-success">Expenses</a>
			<a href="logout.php" class="btn btn-warning">Logout</a>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                <!-- Month and Year Selection Form -->
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="month">Month</label>
                            <select class="form-control" id="month" name="month" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo ($m == $selectedMonth) ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="year">Year</label>
                            <select class="form-control" id="year" name="year" required>
                                <?php for ($y = date('Y') - 10; $y <= date('Y') + 10; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($y == $selectedYear) ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </form>
            </div>
        </div>

        <!-- Report Table -->
        <div class="mt-5">
            <h4>Report for <?php echo date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?></h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Budget Amount</th>
                        <th>Month to Date</th>
                        <th>Remaining Budget</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $data): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($data['catName'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo "$" . number_format($data['catBudget'], 2); ?></td>
                            <td><?php echo "$" . number_format($data['monthToDate'], 2); ?></td>
                            <td style="color: <?php echo $data['remainingBudget'] >= 0 ? 'green' : 'red'; ?>">
                                <?php echo "$" . number_format($data['remainingBudget'], 2); ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <div class="progress">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo min($data['percentSpent'], 100); ?>%" aria-valuenow="<?php echo $data['percentSpent']; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo number_format($data['percentSpent'], 2); ?>%
                                    </div>
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo max(100 - $data['percentSpent'], 0); ?>%" aria-valuenow="<?php echo 100 - $data['percentSpent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th><?php echo "$" . number_format($totalBudget, 2); ?></th>
                        <th><?php echo "$" . number_format($totalSpent, 2); ?></th>
                        <th style="color: <?php echo $remainingTotalBudget >= 0 ? 'green' : 'red'; ?>">
                            <?php echo "$" . number_format($remainingTotalBudget, 2); ?>
                        </th>
                    </tr>
                </tfoot>
            </table>

            <!-- Total Budget vs Remaining Budget Bar -->
            <h4>Total Budget vs Remaining Budget</h4>
            <div class="progress" id="totalBudgetBar">
                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo min(($totalSpent / $totalBudget) * 100, 100); ?>%" aria-valuenow="<?php echo ($totalSpent / $totalBudget) * 100; ?>" aria-valuemin="0" aria-valuemax="100">Spent: <?php echo "$" . number_format($totalSpent, 2); ?></div>
                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo max(100 - ($totalSpent / $totalBudget) * 100, 0); ?>%" aria-valuenow="<?php echo 100 - ($totalSpent / $totalBudget) * 100; ?>" aria-valuemin="0" aria-valuemax="100">Remaining: <?php echo "$" . number_format($remainingTotalBudget, 2); ?></div>
            </div>

            <!-- Profit or Loss Statement -->
            <h5 class="mt-3">This month, you are <?php echo $profitOrLoss; ?> budget by <?php echo "$" . number_format($profitOrLossAmount, 2); ?>.</h5>
        </div>
    </div>

    <!-- Bootstrap and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
