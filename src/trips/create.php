<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/currencies.php';
require_once __DIR__ . '/../../config/destinations.php';

require_login();

$errors  = [];
$success = false;
$trip_id = null;

// ── User's currency (set at registration, stored in session) ──────────────
$cur_symbol = $_SESSION['user_currency_symbol'] ?? '$';
$cur_code   = $_SESSION['user_currency_code']   ?? 'USD';
$cur_rate   = (float)($_SESSION['user_currency_rate'] ?? 1.0);
$cur_name   = $_SESSION['user_currency_name']   ?? 'US Dollar';
$user_country = $_SESSION['user_country'] ?? '';

// Trip type: local or international
$trip_type   = $_GET['type'] ?? $_POST['trip_type'] ?? 'international';
if (!in_array($trip_type, ['local', 'international'])) $trip_type = 'international';
$local_places = LOCAL_DESTINATIONS[$user_country] ?? [];

// Fallback: reload from DB if session is missing currency rate
if (empty($_SESSION['user_currency_rate']) && !empty($user_country)) {
    $cur_info  = get_currency_for_country($user_country);
    $cur_rate  = $cur_info['rate'];
    $cur_name  = $cur_info['name'];
    $_SESSION['user_currency_rate'] = $cur_rate;
    $_SESSION['user_currency_name'] = $cur_name;
}

// ── Estimated cost presets (USD base) ────────────────────────────────────
$cost_presets = [
    'Budget'    => ['transport' => 400,  'accommodation' => 350,  'food' => 200,  'attractions' => 100, 'emergency' => 80],
    'Mid-range' => ['transport' => 850,  'accommodation' => 800,  'food' => 400,  'attractions' => 250, 'emergency' => 150],
    'Luxury'    => ['transport' => 2200, 'accommodation' => 2800, 'food' => 1200, 'attractions' => 600, 'emergency' => 400],
];

// Form state
$dest_val    = $_POST['destination'] ?? '';
$sd_val      = $_POST['start_date'] ?? '';
$ed_val      = $_POST['end_date'] ?? '';
$trav_val    = intval($_POST['num_travelers'] ?? 1);
$group_val   = $_POST['travel_type'] ?? 'Solo';
$budget_tier = $_POST['budget_tier'] ?? 'Mid-range';
$notes_val   = trim($_POST['notes'] ?? '');

// Calculate costs in USD, then convert to user's currency
$preset = $cost_presets[$budget_tier] ?? $cost_presets['Mid-range'];
$days   = 5;
if ($sd_val && $ed_val) {
    $days = max(1, (strtotime($ed_val) - strtotime($sd_val)) / 86400);
}
$multiplier = max(1, $trav_val);

// USD amounts
$usd_transport = $preset['transport'] * ($days / 5) * (1 + ($multiplier - 1) * 0.4);
$usd_accom     = $preset['accommodation'] * ($days / 5);
$usd_food      = $preset['food'] * ($days / 5) * (1 + ($multiplier - 1) * 0.6);
$usd_attract   = $preset['attractions'] * ($days / 5);
$usd_emergency = $preset['emergency'];
$usd_total     = $usd_transport + $usd_accom + $usd_food + $usd_attract + $usd_emergency;

// Converted to user's currency
$t_transport = usd_to_currency($usd_transport, $cur_rate);
$t_accom     = usd_to_currency($usd_accom,     $cur_rate);
$t_food      = usd_to_currency($usd_food,      $cur_rate);
$t_attract   = usd_to_currency($usd_attract,   $cur_rate);
$t_emergency = usd_to_currency($usd_emergency, $cur_rate);
$t_total     = usd_to_currency($usd_total,     $cur_rate);

$calculated = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'calculate';
$saving     = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save';

if ($saving) {
    $budget_limit = floatval($_POST['budget_limit'] ?? $t_total);

    if (empty($dest_val))   $errors[] = "Destination is required.";
    if (empty($sd_val))     $errors[] = "Start date is required.";
    if (empty($ed_val))     $errors[] = "End date is required.";
    if (empty($group_val))  $errors[] = "Travel type is required.";
    if ($trav_val < 1)      $errors[] = "Number of travelers must be at least 1.";
    if ($sd_val > $ed_val)  $errors[] = "End date must be after start date.";

    if (empty($errors)) {
        $uid = $_SESSION['user_id'];
        $stmt = $pdo->prepare(
            "INSERT INTO trips (user_id, destination, start_date, end_date, num_travelers, budget_limit, travel_type, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if ($stmt->execute([$uid, $dest_val, $sd_val, $ed_val, $trav_val, $budget_limit, $group_val, $notes_val])) {
            $trip_id = $pdo->lastInsertId();
            $categories = ['Transportation', 'Accommodation', 'Food', 'Tourist Attractions', 'Shopping', 'Emergency Funds'];
            $stmtb = $pdo->prepare("INSERT INTO trip_budgets (trip_id, category) VALUES (?, ?)");
            foreach ($categories as $cat) $stmtb->execute([$trip_id, $cat]);
            $success = true;
        } else {
            $errors[] = "Failed to create trip. Please try again.";
        }
    }
}

// Map: always visible; default to Philippines when no destination entered yet
$user_country = $_SESSION['user_country'] ?? 'Philippines';
$map_dest = urlencode($dest_val ?: $user_country);
$map_url  = "https://maps.google.com/maps?q=" . $map_dest . "&output=embed";

$body_class = 'dashboard-body';
$active_sidebar = 'planner';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="dash-main">
    <div class="app-content">

        <!-- Type breadcrumb -->
        <div class="planner-type-bar">
            <a href="<?php echo BASE_URL; ?>/src/trips/type.php" class="planner-type-back">
                <i class="fa-solid fa-arrow-left"></i> Change Type
            </a>
            <span class="planner-type-pill planner-type-pill--<?php echo $trip_type; ?>">
                <?php if ($trip_type === 'local'): ?>
                    <i class="fa-solid fa-house-chimney"></i>
                    Local Travel<?php echo $user_country ? ' · ' . htmlspecialchars($user_country) : ''; ?>
                <?php else: ?>
                    <i class="fa-solid fa-earth-asia"></i> International Travel
                <?php endif; ?>
            </span>
        </div>

        <h1 class="planner-heading">
            <?php echo $trip_type === 'local' ? 'Plan Your Local Getaway.' : 'Plan Your International Adventure.'; ?>
        </h1>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:20px;">
                Trip created!
                <a href="<?php echo BASE_URL; ?>/src/trips/view.php?id=<?php echo $trip_id; ?>" style="font-weight:600;">View trip details →</a>
                <span style="margin:0 8px;color:var(--muted);">·</span>
                <a href="<?php echo BASE_URL; ?>/src/dashboard/index.php" style="font-weight:600;">Go to Dashboard →</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="margin-bottom:20px;">
                <?php foreach ($errors as $e): echo htmlspecialchars($e) . '<br>'; endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Filter bar -->
        <form method="POST" id="plannerForm">
            <div class="planner-filter-bar">
                <!-- Destination -->
                <div class="filter-field">
                    <div class="filter-label">
                        <i class="fa-solid fa-location-dot" style="color:var(--primary);"></i> Destination
                    </div>
                    <?php if ($trip_type === 'local' && !empty($local_places)): ?>
                        <div class="filter-select-wrap">
                            <select name="destination" class="filter-select" required>
                                <option value="">Choose a place…</option>
                                <?php foreach ($local_places as $place): ?>
                                    <option value="<?php echo htmlspecialchars($place); ?>"
                                        <?php echo $dest_val === $place ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($place); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php elseif ($trip_type === 'international'): ?>
                        <div class="filter-select-wrap">
                            <select name="destination" class="filter-select" required>
                                <option value="">Choose a destination…</option>
                                <?php foreach (INTERNATIONAL_DESTINATIONS as $region => $places): ?>
                                    <optgroup label="── <?php echo htmlspecialchars($region); ?>">
                                        <?php foreach ($places as $place): ?>
                                            <option value="<?php echo htmlspecialchars($place); ?>"
                                                <?php echo $dest_val === $place ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($place); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="text" name="destination" class="filter-input"
                               placeholder="Enter destination"
                               value="<?php echo htmlspecialchars($dest_val); ?>" required>
                    <?php endif; ?>
                    <input type="hidden" name="trip_type" value="<?php echo htmlspecialchars($trip_type); ?>">
                </div>
                <!-- Dates -->
                <div class="filter-field" style="flex:2.5;min-width:220px;">
                    <div class="filter-label">
                        <i class="fa-regular fa-calendar" style="color:var(--primary);"></i> Dates
                    </div>
                    <div class="filter-date-pair">
                        <label class="filter-date-item">
                            <i class="fa-regular fa-calendar"></i>
                            <input type="date" name="start_date"
                                   value="<?php echo htmlspecialchars($sd_val ?: date('Y-m-d', strtotime('+7 days'))); ?>"
                                   onclick="try{this.showPicker()}catch(e){}">
                        </label>
                        <span class="filter-date-sep">–</span>
                        <label class="filter-date-item">
                            <i class="fa-regular fa-calendar"></i>
                            <input type="date" name="end_date"
                                   value="<?php echo htmlspecialchars($ed_val ?: date('Y-m-d', strtotime('+12 days'))); ?>"
                                   onclick="try{this.showPicker()}catch(e){}">
                        </label>
                    </div>
                </div>
                <!-- Travelers -->
                <div class="filter-field" style="max-width:110px;">
                    <div class="filter-label">
                        <i class="fa-solid fa-person" style="color:var(--primary);"></i> Travelers
                    </div>
                    <div class="filter-stepper">
                        <button type="button" class="stepper-btn" onclick="adjustTravelers(-1)">−</button>
                        <span class="stepper-val" id="traveler-display"><?php echo $trav_val; ?></span>
                        <button type="button" class="stepper-btn" onclick="adjustTravelers(1)">+</button>
                        <input type="hidden" name="num_travelers" id="num_travelers_input" value="<?php echo $trav_val; ?>">
                    </div>
                </div>
                <!-- Group -->
                <div class="filter-field">
                    <div class="filter-label">
                        <i class="fa-solid fa-people-group" style="color:var(--primary);"></i> Group
                    </div>
                    <div class="filter-select-wrap">
                        <select name="travel_type" class="filter-select">
                            <?php foreach (['Solo','Couple','Family','Friends'] as $g): ?>
                                <option value="<?php echo $g; ?>" <?php echo $group_val === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <!-- Budget tier -->
                <div class="filter-field">
                    <div class="filter-label">
                        <i class="fa-solid fa-suitcase" style="color:var(--primary);"></i> Budget
                    </div>
                    <div class="filter-select-wrap">
                        <select name="budget_tier" class="filter-select">
                            <?php foreach (['Budget','Mid-range','Luxury'] as $bt): ?>
                                <option value="<?php echo $bt; ?>" <?php echo $budget_tier === $bt ? 'selected' : ''; ?>><?php echo $bt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <!-- Calculate → goes to Step 2 (estimate.php) -->
                <button type="submit" name="action" value="calculate"
                        formaction="<?php echo BASE_URL; ?>/src/trips/estimate.php"
                        class="filter-calculate">
                    CALCULATE
                </button>
            </div>

            <!-- Two-column content -->
            <div class="planner-grid">

                <!-- Left: Map + Insights -->
                <div>
                    <!-- Map -->
                    <div class="planner-map-wrap">
                        <iframe
                            id="plannerMap"
                            src="<?php echo $map_url; ?>"
                            title="Map"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>

                    <!-- Insight cards -->
                    <div class="planner-insights">
                        <div class="insight-card">
                            <div class="insight-icon" style="background:var(--orange-light);color:var(--orange);">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div>
                                <div class="insight-label">Market Insight</div>
                                <div class="insight-text">Prices are 12% lower than usual for
                                    <?php echo $dest_val ? date('F', strtotime($sd_val)) : 'this month'; ?>.
                                </div>
                            </div>
                        </div>
                        <div class="insight-card">
                            <div class="insight-icon" style="background:#F0FDF4;color:var(--success);">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div>
                                <div class="insight-label">Recommendation</div>
                                <div class="insight-text">Book flights 21 days in advance for best rates.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes (hidden, part of form) -->
                    <input type="hidden" name="notes" value="<?php echo htmlspecialchars($notes_val); ?>">
                    <!-- Store USD value in DB so comparisons stay consistent -->
                    <input type="hidden" name="budget_limit" value="<?php echo round($usd_total, 2); ?>">
                </div>

                <!-- Right: Cost Estimator -->
                <div>
                    <div class="cost-card">
                        <div class="cost-card-header">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                                <div class="cost-card-title">
                                    Estimated Costs for
                                    <?php echo $dest_val ? htmlspecialchars($dest_val) : 'Your Destination'; ?>
                                </div>
                                <!-- Currency badge -->
                                <div style="background:rgba(255,255,255,0.2);border-radius:6px;padding:4px 10px;
                                    font-size:12px;font-weight:700;color:#fff;white-space:nowrap;flex-shrink:0;">
                                    <?php echo htmlspecialchars($cur_symbol); ?> <?php echo htmlspecialchars($cur_code); ?>
                                </div>
                            </div>
                            <div class="cost-card-sub">
                                Duration: <?php echo $days; ?> day<?php echo $days != 1 ? 's' : ''; ?>
                                · <?php echo htmlspecialchars($budget_tier); ?> Travel
                                <?php if ($user_country): ?>
                                    · <?php echo htmlspecialchars($user_country); ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="cost-card-body">
                            <!-- Transportation -->
                            <div class="cost-item">
                                <div class="cost-item-icon" style="background:#E8F0FE;color:#1565C0;">
                                    <i class="fa-solid fa-plane"></i>
                                </div>
                                <div class="cost-item-info">
                                    <div class="cost-item-name">Transportation</div>
                                    <div class="cost-item-sub">Flights + Local Transit</div>
                                </div>
                                <div>
                                    <div class="cost-item-amount"><?php echo format_currency($t_transport, $cur_symbol); ?></div>
                                    <div class="cost-item-pct"><?php echo $t_total > 0 ? round($t_transport/$t_total*100) : 0; ?>% of total</div>
                                </div>
                            </div>

                            <!-- Accommodation -->
                            <div class="cost-item">
                                <div class="cost-item-icon" style="background:#F0FDF4;color:#16A34A;">
                                    <i class="fa-solid fa-bed"></i>
                                </div>
                                <div class="cost-item-info">
                                    <div class="cost-item-name">Accommodation</div>
                                    <div class="cost-item-sub">4-star average</div>
                                </div>
                                <div>
                                    <div class="cost-item-amount"><?php echo format_currency($t_accom, $cur_symbol); ?></div>
                                    <div class="cost-item-pct"><?php echo $t_total > 0 ? round($t_accom/$t_total*100) : 0; ?>% of total</div>
                                </div>
                            </div>

                            <!-- Food -->
                            <div class="cost-item">
                                <div class="cost-item-icon" style="background:#FEF3E8;color:#E07B2A;">
                                    <i class="fa-solid fa-utensils"></i>
                                </div>
                                <div class="cost-item-info">
                                    <div class="cost-item-name">Food &amp; Dining</div>
                                    <div class="cost-item-sub">Street &amp; Mid-range dining</div>
                                </div>
                                <div>
                                    <div class="cost-item-amount"><?php echo format_currency($t_food, $cur_symbol); ?></div>
                                    <div class="cost-item-pct"><?php echo $t_total > 0 ? round($t_food/$t_total*100) : 0; ?>% of total</div>
                                </div>
                            </div>

                            <!-- Attractions -->
                            <div class="cost-item">
                                <div class="cost-item-icon" style="background:#FDF0E8;color:#8B3A10;">
                                    <i class="fa-solid fa-ticket"></i>
                                </div>
                                <div class="cost-item-info">
                                    <div class="cost-item-name">
                                        Attractions
                                        <span class="badge-saved">SAVED</span>
                                    </div>
                                    <div class="cost-item-sub">Via Klook</div>
                                </div>
                                <div>
                                    <div class="cost-item-amount"><?php echo format_currency($t_attract, $cur_symbol); ?></div>
                                    <div class="cost-item-pct"><?php echo $t_total > 0 ? round($t_attract/$t_total*100) : 0; ?>% of total</div>
                                </div>
                            </div>

                            <!-- Emergency -->
                            <div class="cost-item">
                                <div class="cost-item-icon" style="background:#FEF2F2;color:#DC2626;">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </div>
                                <div class="cost-item-info">
                                    <div class="cost-item-name">Emergency Fund</div>
                                    <div class="cost-item-sub">Buffer recommendation</div>
                                </div>
                                <div>
                                    <div class="cost-item-amount"><?php echo format_currency($t_emergency, $cur_symbol); ?></div>
                                    <div class="cost-item-pct"><?php echo $t_total > 0 ? round($t_emergency/$t_total*100) : 0; ?>% of total</div>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="cost-total-row">
                                <div>
                                    <div class="cost-total-label">Total Estimated Budget</div>
                                    <div class="cost-total-note">All inclusive of taxes &amp; fees</div>
                                </div>
                                <div class="cost-total-value"><?php echo format_currency($t_total, $cur_symbol); ?></div>
                            </div>

                            <!-- Exclusive offer -->
                            <div class="cost-exclusive" style="margin-top:14px;">
                                <div>
                                    <span class="cost-exclusive-tag">EXCLUSIVE OFFER</span>
                                    <div class="cost-exclusive-text">
                                        Get 15% off
                                        <?php echo $dest_val ? htmlspecialchars($dest_val) : 'destination'; ?>
                                        accommodation with Expedia Gold.
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-right" style="color:var(--muted);font-size:12px;flex-shrink:0;"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /planner-grid -->
        </form>
    </div><!-- /app-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

<script>
function adjustTravelers(delta) {
    var inp = document.getElementById('num_travelers_input');
    var disp = document.getElementById('traveler-display');
    var val = parseInt(inp.value) + delta;
    if (val < 1) val = 1;
    if (val > 20) val = 20;
    inp.value = val;
    disp.textContent = val;
}

// Update map when destination changes
(function () {
    var sel = document.querySelector('select[name="destination"]');
    var map = document.getElementById('plannerMap');
    if (!sel || !map) return;
    sel.addEventListener('change', function () {
        var dest = this.value || '<?php echo addslashes($user_country); ?>';
        map.src = 'https://maps.google.com/maps?q=' + encodeURIComponent(dest) + '&output=embed';
    });
})();
</script>
</body>
</html>
