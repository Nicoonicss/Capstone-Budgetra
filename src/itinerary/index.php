<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

require_login();

$user_id = $_SESSION['user_id'];

// Delete handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    $trip_redir = intval($_POST['trip_id_redirect'] ?? 0);
    $chk = $pdo->prepare(
        "SELECT i.id FROM itinerary i
         JOIN trips t ON i.trip_id = t.id
         WHERE i.id = ? AND t.user_id = ?"
    );
    $chk->execute([$del_id, $user_id]);
    if ($chk->fetch()) {
        $pdo->prepare("DELETE FROM itinerary WHERE id = ?")->execute([$del_id]);
    }
    $redir = BASE_URL . '/src/itinerary/index.php';
    if ($trip_redir) $redir .= '?trip_id=' . $trip_redir;
    header('Location: ' . $redir);
    exit;
}

// Resolve active trip: GET → session → most recent
$active_trip = null;

if (!empty($_GET['trip_id'])) {
    $req_id = intval($_GET['trip_id']);
    $s = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
    $s->execute([$req_id, $user_id]);
    $active_trip = $s->fetch();
    if ($active_trip) $_SESSION['current_trip_id'] = $active_trip['id'];
}
if (empty($active_trip) && !empty($_SESSION['current_trip_id'])) {
    $s = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
    $s->execute([$_SESSION['current_trip_id'], $user_id]);
    $active_trip = $s->fetch();
}
if (empty($active_trip)) {
    $s = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $s->execute([$user_id]);
    $active_trip = $s->fetch();
    if ($active_trip) $_SESSION['current_trip_id'] = $active_trip['id'];
}

// All user trips (for the trip filter pills)
$s = $pdo->prepare("SELECT id, destination, start_date, end_date FROM trips WHERE user_id = ? ORDER BY created_at DESC");
$s->execute([$user_id]);
$all_trips = $s->fetchAll();
$has_trips = !empty($all_trips);

// Fetch itinerary for the active trip
$itinerary_items = [];
if ($active_trip) {
    $s = $pdo->prepare(
        "SELECT i.*, t.destination AS trip_dest
         FROM itinerary i
         JOIN trips t ON i.trip_id = t.id
         WHERE i.trip_id = ? AND t.user_id = ?
         ORDER BY i.start_datetime ASC"
    );
    $s->execute([$active_trip['id'], $user_id]);
    $itinerary_items = $s->fetchAll();
}

// Group items by calendar date
$grouped = [];
foreach ($itinerary_items as $item) {
    $grouped[date('Y-m-d', strtotime($item['start_datetime']))][] = $item;
}

$body_class     = 'dashboard-body';
$active_sidebar = 'itinerary';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="dash-main">
    <div class="app-content" style="max-width:860px;display:flex;flex-direction:column;">

    <?php if (!$has_trips): ?>

        <!-- ── No trips state ───────────────────────────── -->
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                    flex:1;text-align:center;padding:40px 24px;">
            <div style="width:72px;height:72px;border-radius:20px;background:#FDF0E8;
                        display:flex;align-items:center;justify-content:center;
                        font-size:30px;margin-bottom:20px;">
                <i class="fa-regular fa-calendar-days" style="color:var(--primary);"></i>
            </div>
            <h2 style="font-size:22px;font-weight:800;margin:0 0 8px;">No trips planned yet</h2>
            <p style="color:var(--muted);font-size:14px;max-width:300px;margin:0 0 24px;">
                Create your first trip to start building your day-by-day itinerary.
            </p>
            <a href="<?php echo BASE_URL; ?>/src/trips/type.php" class="btn btn-primary">
                <i class="fa-solid fa-plane"></i> Plan a Trip
            </a>
        </div>

    <?php else: ?>

        <!-- ── Page header ──────────────────────────────── -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;
                    flex-wrap:wrap;gap:12px;margin-bottom:20px;">
            <div>
                <div style="font-size:11px;font-weight:700;letter-spacing:.1em;
                            color:var(--primary);text-transform:uppercase;margin-bottom:4px;">
                    ITINERARY
                </div>
                <h1 style="font-size:clamp(20px,3vw,26px);font-weight:800;margin:0 0 4px;">
                    <?php echo $active_trip ? htmlspecialchars($active_trip['destination']) . ' Schedule' : 'My Itinerary'; ?>
                </h1>
                <?php if ($active_trip): ?>
                <div style="font-size:13px;color:var(--muted);">
                    <i class="fa-regular fa-calendar" style="font-size:11px;"></i>
                    <?php echo date('M d', strtotime($active_trip['start_date'])); ?> &ndash;
                    <?php echo date('M d, Y', strtotime($active_trip['end_date'])); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Trip filter pills (only if user has multiple trips) ── -->
        <?php if (count($all_trips) > 1): ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;">
            <?php foreach ($all_trips as $t):
                $is_sel = $active_trip && $t['id'] == $active_trip['id'];
            ?>
            <a href="?trip_id=<?php echo $t['id']; ?>"
               style="padding:6px 14px;border-radius:99px;font-size:13px;font-weight:600;
                      text-decoration:none;transition:all .15s;
                      border:1.5px solid <?php echo $is_sel ? 'var(--primary)' : '#E5E7EB'; ?>;
                      background:<?php echo $is_sel ? 'var(--primary)' : '#fff'; ?>;
                      color:<?php echo $is_sel ? '#fff' : 'var(--text)'; ?>;">
                <?php echo htmlspecialchars($t['destination']); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ── Empty itinerary for this trip ─────────────── -->
        <?php if (empty($itinerary_items)): ?>
        <div class="chart-card" style="text-align:center;padding:56px 24px;">
            <div style="width:60px;height:60px;border-radius:16px;background:#FDF0E8;
                        display:flex;align-items:center;justify-content:center;
                        font-size:26px;margin:0 auto 16px;">
                <i class="fa-regular fa-calendar-days" style="color:var(--primary);"></i>
            </div>
            <div style="font-size:18px;font-weight:700;margin-bottom:8px;">No items yet</div>
            <div style="font-size:14px;color:var(--muted);max-width:320px;margin-left:auto;margin-right:auto;">
                Add flights, hotels, activities, and restaurants to plan your trip day by day.
            </div>
        </div>

        <?php else: ?>

        <!-- ── Timeline grouped by date ──────────────────── -->
        <?php foreach ($grouped as $date => $items):
            $today    = date('Y-m-d');
            $is_today = $date === $today;
            $is_past  = $date < $today;
        ?>
        <div style="margin-bottom:32px;">

            <!-- Date row -->
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="padding:5px 14px;border-radius:99px;font-size:12px;font-weight:700;
                            white-space:nowrap;
                            background:<?php echo $is_today ? 'var(--primary)' : ($is_past ? '#F3F4F6' : '#EFF6FF'); ?>;
                            color:<?php echo $is_today ? '#fff' : ($is_past ? '#6B7280' : '#1565C0'); ?>;">
                    <?php echo $is_today ? 'Today · ' : ''; ?><?php echo date('D, M d', strtotime($date)); ?>
                </div>
                <div style="flex:1;height:1px;background:#F3F4F6;"></div>
                <div style="font-size:12px;color:var(--muted);font-weight:600;white-space:nowrap;">
                    <?php echo count($items); ?> item<?php echo count($items) !== 1 ? 's' : ''; ?>
                </div>
            </div>

            <!-- Item cards -->
            <div style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($items as $item):
                $type_map = [
                    'Flight'         => ['icon' => 'fa-plane',           'bg' => '#E8F0FE', 'color' => '#1565C0'],
                    'Hotel'          => ['icon' => 'fa-bed',              'bg' => '#F0FDF4', 'color' => '#16A34A'],
                    'Activity'       => ['icon' => 'fa-ticket',           'bg' => '#FDF0E8', 'color' => '#8B3A10'],
                    'Transportation' => ['icon' => 'fa-car-side',         'bg' => '#F5F3FF', 'color' => '#7C3AED'],
                    'Restaurant'     => ['icon' => 'fa-utensils',         'bg' => '#FEF3E8', 'color' => '#E07B2A'],
                ];
                $tc = $type_map[$item['type']] ?? ['icon' => 'fa-map-pin', 'bg' => '#F3F4F6', 'color' => '#6B7280'];
            ?>
            <div class="chart-card" style="padding:16px 20px;display:flex;align-items:flex-start;gap:14px;
                                           <?php echo $is_past ? 'opacity:0.65;' : ''; ?>">

                <!-- Icon -->
                <div style="width:42px;height:42px;border-radius:11px;flex-shrink:0;
                            background:<?php echo $tc['bg']; ?>;color:<?php echo $tc['color']; ?>;
                            display:flex;align-items:center;justify-content:center;font-size:17px;margin-top:2px;">
                    <i class="fa-solid <?php echo $tc['icon']; ?>"></i>
                </div>

                <!-- Body -->
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                        <div>
                            <div style="font-size:15px;font-weight:700;margin-bottom:4px;">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;
                                      background:<?php echo $tc['bg']; ?>;color:<?php echo $tc['color']; ?>;">
                                    <?php echo htmlspecialchars($item['type']); ?>
                                </span>
                                <span style="font-size:13px;color:var(--muted);">
                                    <i class="fa-regular fa-clock" style="font-size:11px;margin-right:3px;"></i>
                                    <?php echo date('h:i A', strtotime($item['start_datetime'])); ?>
                                    <?php if ($item['end_datetime']): ?>
                                        &ndash; <?php echo date('h:i A', strtotime($item['end_datetime'])); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div style="display:flex;gap:6px;flex-shrink:0;">
                            <form method="POST" style="margin:0;"
                                  onsubmit="return confirm('Delete this itinerary item?');">
                                <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="trip_id_redirect" value="<?php echo $active_trip ? $active_trip['id'] : ''; ?>">
                                <button type="submit"
                                        style="width:30px;height:30px;border-radius:8px;cursor:pointer;
                                               border:1.5px solid #FEE2E2;background:#FEF2F2;
                                               color:#DC2626;display:flex;align-items:center;
                                               justify-content:center;font-size:13px;"
                                        title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if ($item['location']): ?>
                    <div style="margin-top:7px;font-size:13px;color:var(--muted);">
                        <i class="fa-solid fa-location-dot" style="color:var(--primary);font-size:11px;margin-right:4px;"></i>
                        <?php echo htmlspecialchars($item['location']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($item['notes']): ?>
                    <div style="margin-top:8px;font-size:13px;color:var(--muted);
                                background:#F9FAFB;border-radius:7px;padding:7px 11px;
                                border-left:3px solid #E5E7EB;">
                        <?php echo htmlspecialchars($item['notes']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>

        <!-- Add Item card -->
        <?php if ($active_trip): ?>
        <a href="<?php echo BASE_URL; ?>/src/itinerary/create.php?trip_id=<?php echo $active_trip['id']; ?>"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                  min-height:140px;border:2px dashed #E5E7EB;border-radius:14px;margin-top:16px;
                  text-decoration:none;color:var(--muted);transition:all .2s;gap:10px;
                  background:rgba(255,255,255,.5);"
           onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'"
           onmouseout="this.style.borderColor='#E5E7EB';this.style.color='var(--muted)'">
            <div style="width:44px;height:44px;border-radius:12px;background:#F3F4F6;
                        display:flex;align-items:center;justify-content:center;font-size:18px;">
                <i class="fa-solid fa-plus"></i>
            </div>
            <div style="font-size:14px;font-weight:700;">Add Item</div>
            <div style="font-size:12px;">Add a flight, hotel, activity, or restaurant</div>
        </a>
        <?php endif; ?>

    <?php endif; ?>

    </div><!-- /app-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

</body>
</html>
