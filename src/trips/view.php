
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

require_login();

$trip_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Get trip
$stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
$stmt->execute([$trip_id, $user_id]);
$trip = $stmt->fetch();

if (!$trip) {
    header('Location: ' . BASE_URL . '/src/trips/index.php');
    exit;
}

// Get trip budgets
$stmt = $pdo->prepare("SELECT * FROM trip_budgets WHERE trip_id = ?");
$stmt->execute([$trip_id]);
$budgets = $stmt->fetchAll();

// Get expenses
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE trip_id = ? ORDER BY expense_date DESC");
$stmt->execute([$trip_id]);
$expenses = $stmt->fetchAll();

// Get itinerary
$stmt = $pdo->prepare("SELECT * FROM itinerary WHERE trip_id = ? ORDER BY start_datetime ASC");
$stmt->execute([$trip_id]);
$itinerary = $stmt->fetchAll();

// Calculate totals
$total_spent = array_sum(array_column($expenses, 'amount'));
$total_estimated = array_sum(array_column($budgets, 'estimated_cost'));
$budget_limit = $trip['budget_limit'] ?? 0;

$is_logged_in = true;
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?php echo $trip['destination']; ?></h2>
        <div>
            <a href="<?php echo BASE_URL; ?>/src/expenses/create.php?trip_id=<?php echo $trip['id']; ?>" class="btn btn-primary me-2">Add Expense</a>
            <a href="<?php echo BASE_URL; ?>/src/itinerary/create.php?trip_id=<?php echo $trip['id']; ?>" class="btn btn-outline-primary">Add to Itinerary</a>
        </div>
    </div>

    <!-- Trip Info -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <p class="text-muted mb-0">Dates</p>
                    <h5><?php echo date('M d', strtotime($trip['start_date'])) . ' - ' . date('M d, Y', strtotime($trip['end_date'])); ?></h5>
                </div>
                <div class="col-md-3">
                    <p class="text-muted mb-0">Travel Type</p>
                    <h5><?php echo $trip['travel_type']; ?></h5>
                </div>
                <div class="col-md-3">
                    <p class="text-muted mb-0">Travelers</p>
                    <h5><?php echo $trip['num_travelers']; ?></h5>
                </div>
                <div class="col-md-3">
                    <p class="text-muted mb-0">Budget</p>
                    <h5>$<?php echo number_format($total_spent, 2); ?> / $<?php echo number_format($budget_limit, 2); ?></h5>
                    <?php if ($budget_limit): ?>
                        <div class="progress mt-1">
                            <div class="progress-bar <?php echo ($total_spent / $budget_limit) > 0.9 ? 'bg-danger' : (($total_spent / $budget_limit) > 0.75 ? 'bg-warning' : 'bg-primary'); ?>" 
                                 style="width: <?php echo min(100, ($total_spent / $budget_limit) * 100); ?>%"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Budget Categories -->
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title mb-4">Budget Breakdown</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Estimated</th>
                            <th>Actual Spent</th>
                            <th>Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($budgets as $budget): ?>
                            <tr>
                                <td><?php echo $budget['category']; ?></td>
                                <td>$<?php echo number_format($budget['estimated_cost'], 2); ?></td>
                                <td>$<?php echo number_format($budget['actual_spent'], 2); ?></td>
                                <td class="<?php echo ($budget['estimated_cost'] - $budget['actual_spent']) < 0 ? 'text-danger' : 'text-success'; ?>">
                                    $<?php echo number_format($budget['estimated_cost'] - $budget['actual_spent'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-light">
                            <td><strong>Total</strong></td>
                            <td><strong>$<?php echo number_format($total_estimated, 2); ?></strong></td>
                            <td><strong>$<?php echo number_format($total_spent, 2); ?></strong></td>
                            <td><strong class="<?php echo ($total_estimated - $total_spent) < 0 ? 'text-danger' : 'text-success'; ?>">
                                $<?php echo number_format($total_estimated - $total_spent, 2); ?>
                            </strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Expenses -->
    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">Expenses</h4>
            <a href="<?php echo BASE_URL; ?>/src/expenses/index.php?trip_id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body pt-0">
            <?php if (empty($expenses)): ?>
                <p class="text-muted">No expenses yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($expenses, 0, 5) as $expense): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
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
    </div>

    <!-- Itinerary -->
    <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">Itinerary</h4>
            <a href="<?php echo BASE_URL; ?>/src/itinerary/index.php?trip_id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body pt-0">
            <?php if (empty($itinerary)): ?>
                <p class="text-muted">No itinerary items yet.</p>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($itinerary as $item): ?>
                        <div class="mb-3 p-3 border rounded">
                            <h5 class="mb-1"><?php echo $item['title']; ?></h5>
                            <p class="text-muted mb-1">
                                <span class="badge bg-primary"><?php echo $item['type']; ?></span>
                                <?php echo date('M d, Y h:i A', strtotime($item['start_datetime'])); ?>
                                <?php if ($item['end_datetime']): ?>
                                    - <?php echo date('h:i A', strtotime($item['end_datetime'])); ?>
                                <?php endif; ?>
                            </p>
                            <?php if ($item['location']): ?>
                                <p class="mb-1"><small><?php echo $item['location']; ?></small></p>
                            <?php endif; ?>
                            <?php if ($item['notes']): ?>
                                <p class="mb-0"><small class="text-muted"><?php echo $item['notes']; ?></small></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
