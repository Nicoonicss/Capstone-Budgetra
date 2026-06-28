<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/currencies.php';

require_login();

$user_id    = $_SESSION['user_id'];
$cur_symbol = $_SESSION['user_currency_symbol'] ?? '$';
$cur_rate   = (float)($_SESSION['user_currency_rate'] ?? 1.0);
if ($cur_rate <= 0) $cur_rate = 1.0;

// Fetch all trips with spending + linked savings goal
$stmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY start_date DESC");
$stmt->execute([$user_id]);
$all_trips = $stmt->fetchAll();

$today        = date('Y-m-d');
$active_trips = [];
$past_trips   = [];

foreach ($all_trips as $trip) {
    // Spending
    $s = $pdo->prepare("SELECT SUM(amount) as t FROM expenses WHERE trip_id = ?");
    $s->execute([$trip['id']]);
    $trip['spent_usd']  = (float)($s->fetch()['t'] ?? 0);
    $trip['budget_usd'] = (float)($trip['budget_limit'] ?? 0);
    $trip['pct']        = $trip['budget_usd'] > 0
        ? min(200, round($trip['spent_usd'] / $trip['budget_usd'] * 100, 1))
        : 0;
    $trip['over_budget'] = $trip['budget_usd'] > 0 && $trip['spent_usd'] > $trip['budget_usd'];

    // Linked savings goal
    $g = $pdo->prepare("SELECT id FROM savings_goals WHERE trip_id = ? LIMIT 1");
    $g->execute([$trip['id']]);
    $trip['goal_id'] = $g->fetchColumn();

    if ($trip['end_date'] >= $today) {
        $trip['badge']      = $trip['start_date'] <= $today ? 'ONGOING' : 'UPCOMING';
        $trip['badge_type'] = $trip['start_date'] <= $today ? 'ongoing' : 'upcoming';
        $active_trips[]     = $trip;
    } else {
        $diff = $trip['budget_usd'] - $trip['spent_usd'];
        if ($trip['over_budget']) {
            $trip['badge']      = 'OVER ' . format_currency(abs($diff) * $cur_rate, $cur_symbol);
            $trip['badge_type'] = 'over';
        } elseif ($trip['budget_usd'] > 0 && $diff > $trip['budget_usd'] * 0.04) {
            $trip['badge']      = 'SAVED ' . format_currency($diff * $cur_rate, $cur_symbol);
            $trip['badge_type'] = 'saved';
        } else {
            $trip['badge']      = 'ON BUDGET';
            $trip['badge_type'] = 'on';
        }
        $past_trips[] = $trip;
    }
}

// ── Helpers ────────────────────────────────────────────────────────────────
function mth_gradient(string $dest): string {
    $d = strtolower($dest);
    if (preg_match('/europe|paris|rome|spain|italy|greece|madrid|barcelona|london|berlin|amsterdam|amalfi|santorini|positano|cinque/', $d))
        return 'linear-gradient(145deg,#1a3a5c 0%,#1565C0 55%,#5499da 100%)';
    if (preg_match('/japan|tokyo|korea|seoul|kyoto|osaka/', $d))
        return 'linear-gradient(145deg,#5c0f0f 0%,#c0392b 55%,#e74c3c 100%)';
    if (preg_match('/bali|thailand|vietnam|singapore|malaysia|phuket|cebu|palawan|boracay|philippines|manila|asia/', $d))
        return 'linear-gradient(145deg,#0f3d1a 0%,#1e8449 55%,#27ae60 100%)';
    if (preg_match('/new york|usa|america|canada|los angeles|chicago|miami|havana|cuba|caribbean/', $d))
        return 'linear-gradient(145deg,#1a0f3d 0%,#6c3483 55%,#9b59b6 100%)';
    if (preg_match('/dubai|abu dhabi|qatar|doha|uae|saudi|middle east/', $d))
        return 'linear-gradient(145deg,#3d2700 0%,#b7860b 55%,#f39c12 100%)';
    if (preg_match('/swiss|switzerland|alps|austria|munich|germany|czech|prague|budapest|croatia/', $d))
        return 'linear-gradient(145deg,#0f3d2a 0%,#1a7a56 55%,#1abc9c 100%)';
    if (preg_match('/africa|kenya|safari|egypt|cairo|morocco|marrakech/', $d))
        return 'linear-gradient(145deg,#3d2200 0%,#c0771a 55%,#e6993a 100%)';
    return 'linear-gradient(145deg,#2c2c3e 0%,#4a4a6a 55%,#6b6b9a 100%)';
}

function mth_emoji(string $dest): string {
    $d = strtolower($dest);
    if (preg_match('/europe|paris|rome|spain|italy|greece|madrid|barcelona|london|berlin|amsterdam|amalfi|santorini/', $d)) return '🏛️';
    if (preg_match('/japan|tokyo|korea|seoul|kyoto|osaka/', $d)) return '⛩️';
    if (preg_match('/bali|thailand|vietnam|singapore|malaysia|phuket|cebu|palawan|boracay|philippines/', $d)) return '🌴';
    if (preg_match('/new york|usa|america|canada|los angeles|chicago|miami|havana|cuba/', $d)) return '🗽';
    if (preg_match('/dubai|abu dhabi|qatar|doha|uae|saudi/', $d)) return '🌆';
    if (preg_match('/swiss|switzerland|alps|austria/', $d)) return '🏔️';
    if (preg_match('/africa|kenya|safari|egypt/', $d)) return '🦁';
    return '✈️';
}

function mth_country(string $dest): string {
    // If comma-separated, take the last part as country
    if (str_contains($dest, ',')) {
        $parts = explode(',', $dest, 2);
        return trim($parts[1]);
    }
    $map = [
        'italy'=>'Italy','japan'=>'Japan','spain'=>'Spain','france'=>'France',
        'greece'=>'Greece','thailand'=>'Thailand','bali'=>'Indonesia',
        'singapore'=>'Singapore','philippines'=>'Philippines','malaysia'=>'Malaysia',
        'vietnam'=>'Vietnam','usa'=>'USA','america'=>'USA','canada'=>'Canada',
        'cuba'=>'Cuba','dubai'=>'UAE','uae'=>'UAE','switzerland'=>'Switzerland',
        'germany'=>'Germany','korea'=>'South Korea','taiwan'=>'Taiwan',
        'australia'=>'Australia','india'=>'India','china'=>'China',
    ];
    $d = strtolower($dest);
    foreach ($map as $kw => $c) {
        if (str_contains($d, $kw)) return $c;
    }
    return '';
}

function mth_shortname(string $dest): string {
    if (str_contains($dest, ',')) {
        return trim(explode(',', $dest, 2)[0]);
    }
    return $dest;
}

// Sidebar data
$user_initial = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));
$trip_count   = count($all_trips);

// Most recent active trip for sidebar
$sidebar_trip = $active_trips[0] ?? $all_trips[0] ?? null;

$body_class = 'dashboard-body';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<?php $active_sidebar = 'trips'; ?>
<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- ══ MAIN AREA ════════════════════════════════════ -->
    <div class="dash-main">

        <!-- Page header -->
        <div class="mth-page-header">
            <h1 class="mth-hub-title">Multi-Trip Hub</h1>
            <div class="mth-search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="tripSearch" placeholder="Search trips..."
                       oninput="filterTrips(this.value)">
            </div>
            <div class="mth-header-right">
                <div style="position:relative;">
                    <i class="fa-regular fa-bell" style="font-size:16px;color:var(--muted);cursor:pointer;"></i>
                </div>
                <div class="dash-avatar"><?php echo $user_initial; ?></div>
            </div>
        </div>

        <div class="dash-content">

            <!-- Active Trips -->
            <div class="mth-section-head">
                <span class="mth-section-title">
                    <i class="fa-solid fa-circle-dot" style="color:#E07B2A;font-size:10px;"></i>
                    Active Trips
                </span>
                <button class="mth-view-map-btn" onclick="alert('Map view coming soon!')">
                    View Map
                </button>
            </div>

            <div class="mth-active-grid" id="activeGrid">
                <?php foreach ($active_trips as $t):
                    $name    = mth_shortname($t['destination']);
                    $country = mth_country($t['destination']);
                    $grad    = mth_gradient($t['destination']);
                    $emoji   = mth_emoji($t['destination']);
                    $fill_color = $t['over_budget'] ? '#DC2626' : ($t['pct'] > 80 ? '#E07B2A' : 'var(--primary)');
                    $details_url = $t['goal_id']
                        ? BASE_URL . '/src/savings/goal.php?goal_id=' . $t['goal_id']
                        : BASE_URL . '/src/dashboard/index.php';
                ?>
                <div class="mth-trip-card" data-name="<?php echo htmlspecialchars(strtolower($t['destination'])); ?>">
                    <div class="mth-trip-img" style="background:<?php echo $grad; ?>;">
                        <span class="mth-trip-emoji"><?php echo $emoji; ?></span>
                        <span class="mth-badge mth-badge-<?php echo $t['badge_type']; ?>">
                            <?php echo htmlspecialchars($t['badge']); ?>
                        </span>
                    </div>
                    <div class="mth-trip-body">
                        <div class="mth-trip-name"><?php echo htmlspecialchars($name); ?></div>
                        <?php if ($country): ?>
                            <div class="mth-trip-country"><?php echo htmlspecialchars($country); ?></div>
                        <?php endif; ?>
                        <div class="mth-trip-dates">
                            <?php echo date('M d', strtotime($t['start_date'])); ?> –
                            <?php echo date('M d, Y', strtotime($t['end_date'])); ?>
                        </div>
                        <?php if ($t['budget_usd'] > 0): ?>
                        <div class="mth-trip-budget-label">Spent vs Budget</div>
                        <div class="mth-trip-budget-nums">
                            <?php echo format_currency($t['spent_usd'] * $cur_rate, $cur_symbol); ?> /
                            <?php echo format_currency($t['budget_usd'] * $cur_rate, $cur_symbol); ?>
                        </div>
                        <div class="mth-trip-budget-bar">
                            <div class="mth-trip-budget-fill"
                                 style="width:<?php echo min(100, $t['pct']); ?>%;background:<?php echo $fill_color; ?>;"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mth-trip-footer">
                        <a href="<?php echo $details_url; ?>" class="mth-btn-details">Details</a>
                        <button class="mth-btn-compare"
                                onclick="openCompare(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars(addslashes($name)); ?>')">
                            Compare
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Plan New Adventure card -->
                <a href="<?php echo BASE_URL; ?>/src/trips/type.php" class="mth-new-card">
                    <div class="mth-new-card-icon">
                        <i class="fa-solid fa-plus" style="color:var(--muted);"></i>
                    </div>
                    <div class="mth-new-card-title">Plan New Adventure</div>
                    <div class="mth-new-card-sub">Start tracking your next destination today.</div>
                </a>
            </div>

            <!-- Past Trips -->
            <?php if (!empty($past_trips)): ?>
            <div class="mth-section-head">
                <span class="mth-section-title">
                    <i class="fa-regular fa-clock" style="color:var(--muted);font-size:11px;"></i>
                    Past Trips
                </span>
            </div>

            <div class="mth-past-grid" id="pastGrid">
                <?php foreach ($past_trips as $t):
                    $name    = mth_shortname($t['destination']);
                    $country = mth_country($t['destination']);
                    $grad    = mth_gradient($t['destination']);
                    $emoji   = mth_emoji($t['destination']);
                    $badge_class = [
                        'saved' => 'mth-past-badge-saved',
                        'on'    => 'mth-past-badge-on',
                        'over'  => 'mth-past-badge-over',
                    ][$t['badge_type']] ?? 'mth-past-badge-on';
                    $details_url = $t['goal_id']
                        ? BASE_URL . '/src/savings/goal.php?goal_id=' . $t['goal_id']
                        : BASE_URL . '/src/dashboard/index.php';
                ?>
                <div class="mth-past-card" data-name="<?php echo htmlspecialchars(strtolower($t['destination'])); ?>">
                    <div class="mth-past-img" style="background:<?php echo $grad; ?>;">
                        <div class="mth-past-img-inner">
                            <span style="font-size:26px;"><?php echo $emoji; ?></span>
                        </div>
                    </div>
                    <div class="mth-past-body">
                        <div class="mth-past-name"><?php echo htmlspecialchars($name); ?></div>
                        <div class="mth-past-date">
                            <?php echo date('M Y', strtotime($t['start_date'])); ?>
                            <?php if ($country): ?>&nbsp;·&nbsp;<?php echo htmlspecialchars($country); ?><?php endif; ?>
                        </div>
                        <div class="mth-past-budget-row">
                            <span class="mth-past-total">
                                Total: <?php echo format_currency($t['spent_usd'] * $cur_rate, $cur_symbol); ?>
                            </span>
                            <span class="mth-past-badge <?php echo $badge_class; ?>">
                                <?php echo htmlspecialchars($t['badge']); ?>
                            </span>
                        </div>
                        <button class="mth-past-compare-btn"
                                onclick="openCompare(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars(addslashes($name)); ?>')">
                            Compare
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($all_trips)): ?>
            <div class="chart-card" style="text-align:center;padding:56px 24px;">
                <div style="font-size:48px;margin-bottom:16px;">🌍</div>
                <div style="font-size:18px;font-weight:700;margin-bottom:8px;">No trips yet</div>
                <div style="font-size:14px;color:var(--muted);margin-bottom:24px;">
                    Start planning your next adventure and track every expense.
                </div>
                <a href="<?php echo BASE_URL; ?>/src/trips/type.php" class="btn btn-primary">
                    <i class="fa-solid fa-plane"></i> Plan My First Trip
                </a>
            </div>
            <?php endif; ?>

        </div><!-- /dash-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

<!-- Compare placeholder modal -->
<div id="compareModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;
     display:none;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:32px;max-width:420px;width:90%;text-align:center;">
        <div style="font-size:24px;margin-bottom:12px;">📊</div>
        <div style="font-size:18px;font-weight:700;margin-bottom:8px;" id="compareTitle">Compare Trip</div>
        <div style="font-size:14px;color:var(--muted);margin-bottom:24px;">
            Trip comparison feature is coming soon. You'll be able to compare spending across multiple trips side-by-side.
        </div>
        <button onclick="document.getElementById('compareModal').style.display='none'"
                style="background:var(--primary);color:#fff;border:none;border-radius:8px;
                       padding:10px 28px;font-size:14px;font-weight:600;cursor:pointer;">
            Got it
        </button>
    </div>
</div>

<script>
function filterTrips(q) {
    q = q.toLowerCase();
    document.querySelectorAll('[data-name]').forEach(function(el) {
        var match = !q || el.dataset.name.includes(q);
        el.style.display = match ? '' : 'none';
    });
}

function openCompare(id, name) {
    document.getElementById('compareTitle').textContent = 'Compare: ' + name;
    document.getElementById('compareModal').style.display = 'flex';
}

document.getElementById('compareModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

</body>
</html>
