
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $goal_id = intval($_POST['goal_id']);
    $amount = floatval($_POST['amount']);
    $user_id = $_SESSION['user_id'];

    if ($goal_id && $amount > 0) {
        $stmt = $pdo->prepare("UPDATE savings_goals SET current_savings = current_savings + ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$amount, $goal_id, $user_id]);
    }
}

header('Location: ' . BASE_URL . '/src/savings/index.php');
exit;
