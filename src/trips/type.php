<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/currencies.php';
require_once __DIR__ . '/../../config/destinations.php';

require_login();

$user_id      = $_SESSION['user_id'];
$user_country = $_SESSION['user_country'] ?? '';
$cur_symbol   = $_SESSION['user_currency_symbol'] ?? '$';
$cur_rate     = (float)($_SESSION['user_currency_rate'] ?? 1.0);
if ($cur_rate <= 0) $cur_rate = 1.0;

// Determine if a destination is local to the user's country
function detect_travel_type(string $destination, string $user_country): string {
    $local_places = LOCAL_DESTINATIONS[$user_country] ?? [];
    foreach ($local_places as $place) {
        if (stripos($destination, $place) !== false || stripos($place, $destination) !== false) {
            return 'local';
        }
    }
    return 'international';
}

// ── Delete trip ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_trip_id'])) {
    $del_id = intval($_POST['delete_trip_id']);
    // Verify ownership, then delete (CASCADE handles child rows)
    $chk = $pdo->prepare("SELECT id FROM trips WHERE id = ? AND user_id = ?");
    $chk->execute([$del_id, $user_id]);
    if ($chk->fetch()) {
        $pdo->prepare("DELETE FROM trips WHERE id = ? AND user_id = ?")->execute([$del_id, $user_id]);
    }
    header('Location: ' . BASE_URL . '/src/trips/type.php');
    exit;
}

// ── Fetch all user trips ─────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT t.*,
            (SELECT SUM(estimated_cost) FROM trip_budgets WHERE trip_id = t.id) AS total_budget,
            (SELECT SUM(amount)         FROM expenses      WHERE trip_id = t.id) AS total_spent
     FROM trips t
     WHERE t.user_id = ?
     ORDER BY t.created_at DESC"
);
$stmt->execute([$user_id]);
$all_trips = $stmt->fetchAll();

// Enrich each trip with detected local/international type
foreach ($all_trips as &$trip) {
    $trip['trip_kind'] = detect_travel_type($trip['destination'], $user_country);
}
unset($trip);

$has_trips    = !empty($all_trips);
$show_new     = !$has_trips || isset($_GET['new']);

$body_class     = 'dashboard-body';
$active_sidebar = 'planner';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="dash-main">
    <div class="app-content" style="max-width:1100px;display:flex;flex-direction:column;">

    <?php if ($show_new): ?>

        <!-- ── New Trip Selection ──────────────────────── -->
        <div style="display:flex;flex-direction:column;align-items:center;width:100%;padding:40px 0 60px;">

            <?php if ($has_trips): ?>
            <div style="align-self:flex-start;margin-bottom:28px;">
                <a href="<?php echo BASE_URL; ?>/src/trips/type.php"
                   style="font-size:13px;color:var(--muted);text-decoration:none;font-weight:600;">
                    <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i>Back to My Trips
                </a>
            </div>
            <?php endif; ?>

            <div class="trip-type-header">
                <h1 class="trip-type-title">Where are you headed?</h1>
                <p class="trip-type-sub">
                    Choose your travel type to get personalized destination suggestions
                    and accurate cost estimates.
                </p>
            </div>

            <div class="trip-type-cards">
                <a href="<?php echo BASE_URL; ?>/src/trips/create.php?type=local"
                   class="trip-type-card trip-type-card--local">
                    <div class="trip-type-card-bg-circle trip-type-card-bg-circle--1"></div>
                    <div class="trip-type-card-bg-circle trip-type-card-bg-circle--2"></div>
                    <div class="trip-type-card-icon"><i class="fa-solid fa-house-chimney"></i></div>
                    <div class="trip-type-card-badge">LOCAL TRAVEL</div>
                    <h2 class="trip-type-card-title">
                        Explore <?php echo $user_country ? htmlspecialchars($user_country) : 'Your Country'; ?>
                    </h2>
                    <p class="trip-type-card-desc">
                        Discover hidden gems and popular spots within your home country.
                        <?php if ($user_country): ?>
                            Choose from curated destinations in
                            <strong><?php echo htmlspecialchars($user_country); ?></strong>.
                        <?php endif; ?>
                    </p>
                    <div class="trip-type-card-cta">Plan Local Trip <i class="fa-solid fa-arrow-right"></i></div>
                </a>

                <a href="<?php echo BASE_URL; ?>/src/trips/create.php?type=international"
                   class="trip-type-card trip-type-card--intl">
                    <div class="trip-type-card-bg-circle trip-type-card-bg-circle--1"></div>
                    <div class="trip-type-card-bg-circle trip-type-card-bg-circle--2"></div>
                    <div class="trip-type-card-icon"><i class="fa-solid fa-earth-asia"></i></div>
                    <div class="trip-type-card-badge">INTERNATIONAL TRAVEL</div>
                    <h2 class="trip-type-card-title">Travel the World</h2>
                    <p class="trip-type-card-desc">
                        Explore iconic destinations across Asia, Europe, the Americas,
                        Africa, and Oceania. Your next big adventure awaits.
                    </p>
                    <div class="trip-type-card-cta">Plan International Trip <i class="fa-solid fa-arrow-right"></i></div>
                </a>
            </div>

        </div><!-- /centering wrapper -->

    <?php else: ?>

        <!-- ── My Trips List ──────────────────────────── -->
        <div style="display:flex;align-items:center;justify-content:space-between;
                    flex-wrap:wrap;gap:12px;margin-bottom:28px;">
            <div>
                <div style="font-size:11px;font-weight:700;letter-spacing:.1em;
                            color:var(--primary);text-transform:uppercase;margin-bottom:4px;">
                    TRIP PLANNER
                </div>
                <h1 style="font-size:clamp(20px,3vw,26px);font-weight:800;margin:0;">
                    My Planned Trips
                </h1>
            </div>
        </div>

        <div class="planner-trips-grid">
        <?php foreach ($all_trips as $trip):
            $start     = $trip['start_date'];
            $end       = $trip['end_date'];
            $today     = date('Y-m-d');
            $is_active = $today >= $start && $today <= $end;
            $is_past   = $today > $end;
            $is_future = $today < $start;

            $badge_label = $is_active ? 'Active' : ($is_past ? 'Completed' : 'Upcoming');
            $badge_color = $is_active ? '#16A34A' : ($is_past ? '#6B7280' : '#1565C0');
            $badge_bg    = $is_active ? '#F0FDF4'  : ($is_past ? '#F3F4F6'  : '#EFF6FF');

            $budget_usd = (float)($trip['total_budget'] ?? 0);
            $spent_usd  = (float)($trip['total_spent']  ?? 0);
            $budget_loc = $budget_usd * $cur_rate;
            $spent_loc  = $spent_usd  * $cur_rate;
            $days       = max(1, (strtotime($end) - strtotime($start)) / 86400 + 1);

            $type_icon  = $trip['trip_kind'] === 'local' ? 'fa-house-chimney' : 'fa-earth-asia';
            $type_label = $trip['trip_kind'] === 'local' ? 'Local' : 'International';
        ?>
        <div class="planner-trip-card">
            <!-- Card header -->
            <div class="planner-trip-card-head">
                <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
                    <div class="planner-trip-icon">
                        <i class="fa-solid <?php echo $type_icon; ?>"></i>
                    </div>
                    <div style="min-width:0;">
                        <div class="planner-trip-dest">
                            <?php echo htmlspecialchars($trip['destination']); ?>
                        </div>
                        <div class="planner-trip-meta">
                            <?php echo $type_label; ?> &middot;
                            <?php echo date('M d', strtotime($start)); ?> &ndash;
                            <?php echo date('M d, Y', strtotime($end)); ?> &middot;
                            <?php echo $days; ?> day<?php echo $days != 1 ? 's' : ''; ?>
                        </div>
                    </div>
                </div>
                <span class="planner-trip-badge"
                      style="background:<?php echo $badge_bg; ?>;color:<?php echo $badge_color; ?>;">
                    <?php echo $badge_label; ?>
                </span>
            </div>

            <!-- Budget bar -->
            <?php if ($budget_usd > 0): ?>
            <div class="planner-trip-budget">
                <?php $pct = min(100, $budget_usd > 0 ? ($spent_usd / $budget_usd * 100) : 0); ?>
                <div style="display:flex;justify-content:space-between;font-size:12px;
                            color:var(--muted);margin-bottom:6px;">
                    <span>Spent: <?php echo format_currency($spent_loc, $cur_symbol); ?></span>
                    <span>Budget: <?php echo format_currency($budget_loc, $cur_symbol); ?></span>
                </div>
                <div style="height:5px;background:#F3F4F6;border-radius:99px;overflow:hidden;">
                    <div style="height:100%;width:<?php echo $pct; ?>%;border-radius:99px;
                                background:<?php echo $pct >= 100 ? '#DC2626' : 'var(--primary)'; ?>;"></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="planner-trip-actions">
                <a href="<?php echo BASE_URL; ?>/src/dashboard/index.php?trip_id=<?php echo $trip['id']; ?>"
                   class="planner-action-btn planner-action-view">
                    <i class="fa-solid fa-gauge-high"></i> View Dashboard
                </a>
                <form method="POST" onsubmit="return confirm('Delete this trip? This cannot be undone.');">
                    <input type="hidden" name="delete_trip_id" value="<?php echo $trip['id']; ?>">
                    <button type="submit" class="planner-action-btn planner-action-delete">
                        <i class="fa-solid fa-trash"></i> Delete
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Plan New Adventure card -->
        <a href="<?php echo BASE_URL; ?>/src/trips/type.php?new=1"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                  min-height:180px;border:2px dashed #E5E7EB;border-radius:14px;
                  text-decoration:none;color:var(--muted);transition:all .2s;gap:10px;
                  background:rgba(255,255,255,.5);"
           onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'"
           onmouseout="this.style.borderColor='#E5E7EB';this.style.color='var(--muted)'">
            <div style="width:48px;height:48px;border-radius:12px;background:#F3F4F6;
                        display:flex;align-items:center;justify-content:center;font-size:20px;">
                <i class="fa-solid fa-plus"></i>
            </div>
            <div style="font-size:14px;font-weight:700;">Plan New Adventure</div>
            <div style="font-size:12px;">Start tracking your next destination today.</div>
        </a>
        </div>

    <?php endif; ?>

    </div><!-- /app-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

</body>
</html>
