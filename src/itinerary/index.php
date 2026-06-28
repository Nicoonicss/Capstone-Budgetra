
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

require_login();

$user_id = $_SESSION['user_id'];
$trip_id = isset($_GET['trip_id']) ? intval($_GET['trip_id']) : 0;

// Build query
if ($trip_id) {
    $stmt = $pdo->prepare("SELECT i.*, t.destination FROM itinerary i JOIN trips t ON i.trip_id = t.id WHERE i.trip_id = ? AND t.user_id = ? ORDER BY i.start_datetime ASC");
    $stmt->execute([$trip_id, $user_id]);
} else {
    $stmt = $pdo->prepare("SELECT i.*, t.destination FROM itinerary i JOIN trips t ON i.trip_id = t.id WHERE t.user_id = ? ORDER BY i.start_datetime ASC");
    $stmt->execute([$user_id]);
}
$itinerary = $stmt->fetchAll();

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
        <h2>Itinerary</h2>
        <a href="<?php echo BASE_URL; ?>/src/itinerary/create.php<?php echo $trip_id ? '?trip_id=' . $trip_id : ''; ?>" class="btn btn-primary">Add to Itinerary</a>
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

    <?php if (empty($itinerary)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <h4>No itinerary items yet</h4>
                <p class="text-muted">Start planning your trip itinerary!</p>
                <a href="<?php echo BASE_URL; ?>/src/itinerary/create.php" class="btn btn-primary">Add to Itinerary</a>
            </div>
        </div>
    <?php else: ?>
        <div class="timeline">
            <?php foreach ($itinerary as $item): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h4 class="card-title mb-1"><?php echo $item['title']; ?></h4>
                                <p class="text-muted mb-1">
                                    <span class="badge bg-primary me-2"><?php echo $item['type']; ?></span>
                                    <?php echo $item['destination']; ?>
                                </p>
                                <p class="mb-1">
                                    <?php echo date('M d, Y h:i A', strtotime($item['start_datetime'])); ?>
                                    <?php if ($item['end_datetime']): ?>
                                        - <?php echo date('h:i A', strtotime($item['end_datetime'])); ?>
                                    <?php endif; ?>
                                </p>
                                <?php if ($item['location']): ?>
                                    <p class="mb-1"><small><i class="bi bi-geo-alt"></i> <?php echo $item['location']; ?></small></p>
                                <?php endif; ?>
                                <?php if ($item['notes']): ?>
                                    <p class="mb-0 text-muted"><small><?php echo $item['notes']; ?></small></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
