
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

require_login();

$user_id = $_SESSION['user_id'];
$trip_id = isset($_GET['trip_id']) ? intval($_GET['trip_id']) : 0;

// Build query
if ($trip_id) {
    $stmt = $pdo->prepare("SELECT e.*, t.destination FROM expenses e JOIN trips t ON e.trip_id = t.id WHERE e.trip_id = ? AND e.user_id = ? ORDER BY e.expense_date DESC");
    $stmt->execute([$trip_id, $user_id]);
} else {
    $stmt = $pdo->prepare("SELECT e.*, t.destination FROM expenses e JOIN trips t ON e.trip_id = t.id WHERE e.user_id = ? ORDER BY e.expense_date DESC");
    $stmt->execute([$user_id]);
}
$expenses = $stmt->fetchAll();

// Get user's trips for filter
$stmt = $pdo->prepare("SELECT id, destination FROM trips WHERE user_id = ?");
$stmt->execute([$user_id]);
$trips = $stmt->fetchAll();

$is_logged_in = true;
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Expenses</h2>
        <a href="<?php echo BASE_URL; ?>/src/expenses/create.php<?php echo $trip_id ? '?trip_id=' . $trip_id : ''; ?>" class="btn btn-primary">Add Expense</a>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Filter by Trip</label>
                        <select name="trip_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Trips</option>
                            <?php foreach ($trips as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo $trip_id == $t['id'] ? 'selected' : ''; ?>><?php echo $t['destination']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($expenses)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <h4>No expenses yet</h4>
                <p class="text-muted">Start tracking your expenses!</p>
                <a href="<?php echo BASE_URL; ?>/src/expenses/create.php" class="btn btn-primary">Add Expense</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Trip</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                            <td><?php echo $expense['destination']; ?></td>
                            <td><?php echo $expense['description'] ?? 'No description'; ?></td>
                            <td><?php echo $expense['category']; ?></td>
                            <td>$<?php echo number_format($expense['amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
