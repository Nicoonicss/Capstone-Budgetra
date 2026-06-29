<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

require_login();

$user_id = $_SESSION['user_id'];

// Pre-select trip: GET > session > first
$preselect_trip_id = 0;
if (!empty($_GET['trip_id'])) {
    $preselect_trip_id = intval($_GET['trip_id']);
} elseif (!empty($_SESSION['current_trip_id'])) {
    $preselect_trip_id = intval($_SESSION['current_trip_id']);
}

// Get user's trips
$s = $pdo->prepare("SELECT id, destination, start_date, end_date FROM trips WHERE user_id = ? ORDER BY created_at DESC");
$s->execute([$user_id]);
$trips    = $s->fetchAll();
$has_trips = !empty($trips);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_id_post   = intval($_POST['trip_id'] ?? 0);
    $title          = trim($_POST['title'] ?? '');
    $type           = trim($_POST['type'] ?? '');
    $start_datetime = trim($_POST['start_datetime'] ?? '');
    $end_datetime   = !empty($_POST['end_datetime']) ? trim($_POST['end_datetime']) : null;
    $location       = trim($_POST['location'] ?? '');
    $notes          = trim($_POST['notes'] ?? '');

    if (empty($trip_id_post))   $errors[] = "Trip is required.";
    if (empty($title))          $errors[] = "Title is required.";
    if (empty($type))           $errors[] = "Type is required.";
    if (empty($start_datetime)) $errors[] = "Start date/time is required.";

    if (empty($errors)) {
        $s = $pdo->prepare(
            "INSERT INTO itinerary (trip_id, title, type, start_datetime, end_datetime, location, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        if ($s->execute([$trip_id_post, $title, $type, $start_datetime, $end_datetime, $location, $notes])) {
            header('Location: ' . BASE_URL . '/src/itinerary/index.php?trip_id=' . $trip_id_post);
            exit;
        }
        $errors[] = "Failed to save. Please try again.";
    }
    $preselect_trip_id = $trip_id_post;
}

$body_class     = 'dashboard-body';
$active_sidebar = 'itinerary';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="dash-main">
    <div class="app-content" style="max-width:700px;">

        <!-- Back link -->
        <div style="margin-bottom:20px;">
            <a href="<?php echo BASE_URL; ?>/src/itinerary/index.php<?php echo $preselect_trip_id ? '?trip_id=' . $preselect_trip_id : ''; ?>"
               style="font-size:13px;color:var(--muted);text-decoration:none;font-weight:600;">
                <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i>Back to Itinerary
            </a>
        </div>

        <!-- Header -->
        <div style="margin-bottom:24px;">
            <div style="font-size:11px;font-weight:700;letter-spacing:.1em;
                        color:var(--primary);text-transform:uppercase;margin-bottom:4px;">
                ITINERARY
            </div>
            <h1 style="font-size:clamp(20px,3vw,26px);font-weight:800;margin:0;">Add Itinerary Item</h1>
        </div>

        <?php if (!$has_trips): ?>
            <div class="chart-card" style="text-align:center;padding:48px 24px;">
                <div style="font-size:40px;margin-bottom:16px;">✈️</div>
                <div style="font-size:16px;font-weight:700;margin-bottom:8px;">No trips found</div>
                <div style="font-size:14px;color:var(--muted);margin-bottom:20px;">
                    You need to plan a trip before adding itinerary items.
                </div>
                <a href="<?php echo BASE_URL; ?>/src/trips/type.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Plan a Trip
                </a>
            </div>
        <?php else: ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="margin-bottom:20px;">
                <?php foreach ($errors as $e): echo htmlspecialchars($e) . '<br>'; endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="chart-card" style="padding:28px;">
                <form method="POST">

                    <!-- Trip selector -->
                    <div style="margin-bottom:20px;">
                        <label style="display:block;font-size:12px;font-weight:700;
                                      color:var(--muted);margin-bottom:6px;letter-spacing:.05em;
                                      text-transform:uppercase;">Trip</label>
                        <div class="filter-select-wrap" style="max-width:100%;">
                            <select name="trip_id" class="filter-select" required
                                    style="width:100%;border-radius:10px;border:1.5px solid #E5E7EB;
                                           padding:10px 14px;font-size:14px;background:#fff;">
                                <option value="">Select a trip…</option>
                                <?php foreach ($trips as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"
                                        <?php echo $preselect_trip_id == $t['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($t['destination']); ?>
                                        (<?php echo date('M d', strtotime($t['start_date'])); ?> –
                                        <?php echo date('M d, Y', strtotime($t['end_date'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Title + Type row -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:700;
                                          color:var(--muted);margin-bottom:6px;letter-spacing:.05em;
                                          text-transform:uppercase;">Title *</label>
                            <input type="text" name="title" required
                                   placeholder="e.g., Flight to Tokyo"
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                   style="width:100%;border:1.5px solid #E5E7EB;border-radius:10px;
                                          padding:10px 14px;font-size:14px;font-family:inherit;
                                          outline:none;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:700;
                                          color:var(--muted);margin-bottom:6px;letter-spacing:.05em;
                                          text-transform:uppercase;">Type *</label>
                            <div class="filter-select-wrap" style="max-width:100%;">
                                <select name="type" required
                                        style="width:100%;border-radius:10px;border:1.5px solid #E5E7EB;
                                               padding:10px 14px;font-size:14px;background:#fff;">
                                    <option value="">Select type…</option>
                                    <?php
                                    $types = ['Flight','Hotel','Activity','Transportation','Restaurant'];
                                    $sel_type = $_POST['type'] ?? '';
                                    foreach ($types as $tp):
                                    ?>
                                    <option value="<?php echo $tp; ?>" <?php echo $sel_type === $tp ? 'selected' : ''; ?>>
                                        <?php echo $tp; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Dates row -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:700;
                                          color:var(--muted);margin-bottom:6px;letter-spacing:.05em;
                                          text-transform:uppercase;">Start Date &amp; Time *</label>
                            <input type="datetime-local" name="start_datetime" required
                                   value="<?php echo htmlspecialchars($_POST['start_datetime'] ?? ''); ?>"
                                   style="width:100%;border:1.5px solid #E5E7EB;border-radius:10px;
                                          padding:10px 14px;font-size:14px;font-family:inherit;
                                          outline:none;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:700;
                                          color:var(--muted);margin-bottom:6px;letter-spacing:.05em;
                                          text-transform:uppercase;">End Date &amp; Time</label>
                            <input type="datetime-local" name="end_datetime"
                                   value="<?php echo htmlspecialchars($_POST['end_datetime'] ?? ''); ?>"
                                   style="width:100%;border:1.5px solid #E5E7EB;border-radius:10px;
                                          padding:10px 14px;font-size:14px;font-family:inherit;
                                          outline:none;box-sizing:border-box;">
                        </div>
                    </div>

                    <!-- Location -->
                    <div style="margin-bottom:20px;">
                        <label style="display:block;font-size:12px;font-weight:700;
                                      color:var(--muted);margin-bottom:6px;letter-spacing:.05em;
                                      text-transform:uppercase;">Location</label>
                        <input type="text" name="location"
                               placeholder="e.g., Ninoy Aquino International Airport"
                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                               style="width:100%;border:1.5px solid #E5E7EB;border-radius:10px;
                                      padding:10px 14px;font-size:14px;font-family:inherit;
                                      outline:none;box-sizing:border-box;">
                    </div>

                    <!-- Notes -->
                    <div style="margin-bottom:28px;">
                        <label style="display:block;font-size:12px;font-weight:700;
                                      color:var(--muted);margin-bottom:6px;letter-spacing:.05em;
                                      text-transform:uppercase;">Notes</label>
                        <textarea name="notes" rows="3"
                                  placeholder="Confirmation number, reminders, or extra details…"
                                  style="width:100%;border:1.5px solid #E5E7EB;border-radius:10px;
                                         padding:10px 14px;font-size:14px;font-family:inherit;
                                         outline:none;resize:vertical;box-sizing:border-box;"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;">
                        <i class="fa-solid fa-check"></i> Save Itinerary Item
                    </button>

                </form>
            </div>

        <?php endif; ?>

    </div><!-- /app-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

<style>
input:focus, textarea:focus, select:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px rgba(139,58,16,.10) !important;
}
</style>

</body>
</html>
