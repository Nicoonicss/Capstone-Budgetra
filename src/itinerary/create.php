
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

require_login();

$user_id = $_SESSION['user_id'];
$trip_id = isset($_GET['trip_id']) ? intval($_GET['trip_id']) : 0;

// Get user's trips
$stmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ?");
$stmt->execute([$user_id]);
$trips = $stmt->fetchAll();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trip_id_post = intval($_POST['trip_id']);
    $title = trim($_POST['title']);
    $type = $_POST['type'];
    $start_datetime = $_POST['start_datetime'];
    $end_datetime = !empty($_POST['end_datetime']) ? $_POST['end_datetime'] : null;
    $location = trim($_POST['location']);
    $notes = trim($_POST['notes']);

    if (empty($trip_id_post)) $errors[] = "Trip is required";
    if (empty($title)) $errors[] = "Title is required";
    if (empty($type)) $errors[] = "Type is required";
    if (empty($start_datetime)) $errors[] = "Start date/time is required";

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO itinerary (trip_id, title, type, start_datetime, end_datetime, location, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$trip_id_post, $title, $type, $start_datetime, $end_datetime, $location, $notes])) {
            $success = true;
        } else {
            $errors[] = "Failed to add to itinerary";
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
                    <h3 class="text-center mb-4 text-primary-custom">Add to Itinerary</h3>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Added to itinerary successfully! <a href="<?php echo BASE_URL; ?>/src/itinerary/index.php?trip_id=<?php echo $trip_id_post; ?>">View itinerary</a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Trip *</label>
                            <select name="trip_id" class="form-select" required>
                                <option value="">Select trip</option>
                                <?php foreach ($trips as $t): ?>
                                    <option value="<?php echo $t['id']; ?>" <?php echo $trip_id == $t['id'] ? 'selected' : ''; ?>><?php echo $t['destination']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g., Flight to Tokyo" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Type *</label>
                                <select name="type" class="form-select" required>
                                    <option value="">Select type</option>
                                    <option value="Flight">Flight</option>
                                    <option value="Hotel">Hotel</option>
                                    <option value="Activity">Activity</option>
                                    <option value="Transportation">Transportation</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date/Time *</label>
                                <input type="datetime-local" name="start_datetime" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date/Time</label>
                                <input type="datetime-local" name="end_datetime" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g., Haneda Airport">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Add any additional notes..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add to Itinerary</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
