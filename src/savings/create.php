
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

require_login();

$user_id = $_SESSION['user_id'];

// Get user's trips
$stmt = $pdo->prepare("SELECT id, destination FROM trips WHERE user_id = ?");
$stmt->execute([$user_id]);
$trips = $stmt->fetchAll();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $goal_name = trim($_POST['goal_name']);
    $target_amount = floatval($_POST['target_amount']);
    $current_savings = !empty($_POST['current_savings']) ? floatval($_POST['current_savings']) : 0;
    $deadline = $_POST['deadline'];
    $trip_id = !empty($_POST['trip_id']) ? intval($_POST['trip_id']) : null;

    if (empty($goal_name)) $errors[] = "Goal name is required";
    if (empty($target_amount)) $errors[] = "Target amount is required";
    if (empty($deadline)) $errors[] = "Deadline is required";
    if ($target_amount <= 0) $errors[] = "Target amount must be greater than 0";
    if ($current_savings < 0) $errors[] = "Current savings cannot be negative";
    if (strtotime($deadline) < time()) $errors[] = "Deadline must be in the future";

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO savings_goals (user_id, trip_id, goal_name, target_amount, current_savings, deadline) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $trip_id, $goal_name, $target_amount, $current_savings, $deadline])) {
            $success = true;
        } else {
            $errors[] = "Failed to create savings goal";
        }
    }
}

$is_logged_in = true;
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h3 class="text-center mb-4 text-primary-custom">Create Savings Goal</h3>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Savings goal created successfully! <a href="<?php echo BASE_URL; ?>/src/savings/index.php">View goals</a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Goal Name *</label>
                            <input type="text" name="goal_name" class="form-control" placeholder="e.g., Trip to Japan" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Target Amount *</label>
                                <input type="number" name="target_amount" class="form-control" step="0.01" min="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Current Savings</label>
                                <input type="number" name="current_savings" class="form-control" step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Deadline *</label>
                                <input type="date" name="deadline" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Link to Trip (Optional)</label>
                                <select name="trip_id" class="form-select">
                                    <option value="">None</option>
                                    <?php foreach ($trips as $trip): ?>
                                        <option value="<?php echo $trip['id']; ?>"><?php echo $trip['destination']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Create Goal</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
