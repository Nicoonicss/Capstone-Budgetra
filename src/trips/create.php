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

$cur_symbol   = $_SESSION['user_currency_symbol'] ?? '$';
$cur_code     = $_SESSION['user_currency_code']   ?? 'USD';
$cur_rate     = (float)($_SESSION['user_currency_rate'] ?? 1.0);
$cur_name     = $_SESSION['user_currency_name']   ?? 'US Dollar';
$user_country = $_SESSION['user_country'] ?? '';

$trip_type    = $_GET['type'] ?? $_POST['trip_type'] ?? 'international';
if (!in_array($trip_type, ['local', 'international'])) $trip_type = 'international';
$local_places = LOCAL_DESTINATIONS[$user_country] ?? [];

if (empty($_SESSION['user_currency_rate']) && !empty($user_country)) {
    $cur_info  = get_currency_for_country($user_country);
    $cur_rate  = $cur_info['rate'];
    $cur_name  = $cur_info['name'];
    $_SESSION['user_currency_rate'] = $cur_rate;
    $_SESSION['user_currency_name'] = $cur_name;
}

// Form state
$dest_val    = $_POST['destination'] ?? '';
$sd_val      = $_POST['start_date'] ?? '';
$ed_val      = $_POST['end_date'] ?? '';
$trav_val    = max(1, intval($_POST['num_travelers'] ?? 1));
$group_val   = $_POST['travel_type'] ?? 'Solo';
$budget_tier = $_POST['budget_tier'] ?? 'Mid-range';
$notes_val   = trim($_POST['notes'] ?? '');

// Solo = 1 traveler, Couple = 2 traveler, both locked
if ($group_val === 'Solo')   $trav_val = 1;
if ($group_val === 'Couple') $trav_val = 2;

// Budget limit (USD) for hidden field — recalculated server-side as fallback
$cost_presets = [
    'Shoestring/Backpacker' => ['transport' => 400,  'accommodation' => 350,  'food' => 200,  'attractions' => 100,  'emergency' => 80],
    'Mid-range'             => ['transport' => 850,  'accommodation' => 800,  'food' => 400,  'attractions' => 250,  'emergency' => 150],
    'Luxury/Premium'        => ['transport' => 2200, 'accommodation' => 2800, 'food' => 1200, 'attractions' => 600,  'emergency' => 400],
];
$preset     = $cost_presets[$budget_tier] ?? $cost_presets['Mid-range'];
$days_php   = 5;
if ($sd_val && $ed_val) {
    $days_php = max(1, (strtotime($ed_val) - strtotime($sd_val)) / 86400);
}
$usd_total_php = (
    $preset['transport']    * ($days_php / 5) * (1 + ($trav_val - 1) * 0.4) +
    $preset['accommodation'] * ($days_php / 5) +
    $preset['food']         * ($days_php / 5) * (1 + ($trav_val - 1) * 0.6) +
    $preset['attractions']  * ($days_php / 5) +
    $preset['emergency']
);

$saving = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save';
if ($saving) {
    $budget_limit = floatval($_POST['budget_limit'] ?? $usd_total_php);
    if (empty($dest_val))  $errors[] = "Destination is required.";
    if (empty($sd_val))    $errors[] = "Start date is required.";
    if (empty($ed_val))    $errors[] = "End date is required.";
    if (empty($group_val)) $errors[] = "Travel type is required.";
    if ($trav_val < 1)     $errors[] = "Number of travelers must be at least 1.";
    if ($sd_val > $ed_val) $errors[] = "End date must be after start date.";
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

            $pdo->prepare(
                "INSERT INTO notifications (user_id, trip_id, type, message) VALUES (?, ?, ?, ?)"
            )->execute([$uid, $trip_id, 'trip_created',
                'Trip to ' . $dest_val . ' created! Start planning your adventure.'
            ]);

            $success = true;
        } else {
            $errors[] = "Failed to create trip. Please try again.";
        }
    }
}

$user_country = $_SESSION['user_country'] ?? 'Philippines';
$map_dest = urlencode($dest_val ?: $user_country);
$map_url  = "https://maps.google.com/maps?q=" . $map_dest . "&output=embed";

$body_class     = 'dashboard-body';
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
                            <select name="destination" id="destSelect" class="filter-select" required>
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
                            <select name="destination" id="destSelect" class="filter-select" required>
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
                        <input type="text" name="destination" id="destSelect" class="filter-input"
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
                            <input type="date" name="start_date" id="startDate"
                                   value="<?php echo htmlspecialchars($sd_val ?: date('Y-m-d', strtotime('+7 days'))); ?>"
                                   onclick="try{this.showPicker()}catch(e){}">
                        </label>
                        <span class="filter-date-sep">–</span>
                        <label class="filter-date-item">
                            <i class="fa-regular fa-calendar"></i>
                            <input type="date" name="end_date" id="endDate"
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
                        <button type="button" class="stepper-btn" id="stepper-minus" onclick="adjustTravelers(-1)">−</button>
                        <span class="stepper-val" id="traveler-display"><?php echo $trav_val; ?></span>
                        <button type="button" class="stepper-btn" id="stepper-plus" onclick="adjustTravelers(1)">+</button>
                        <input type="hidden" name="num_travelers" id="num_travelers_input" value="<?php echo $trav_val; ?>">
                    </div>
                </div>

                <!-- Group -->
                <div class="filter-field">
                    <div class="filter-label">
                        <i class="fa-solid fa-people-group" style="color:var(--primary);"></i> Group
                    </div>
                    <div class="filter-select-wrap">
                        <select name="travel_type" id="groupSelect" class="filter-select">
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
                        <select name="budget_tier" id="budgetSelect" class="filter-select">
                            <?php foreach (['Shoestring/Backpacker','Mid-range','Luxury/Premium'] as $bt): ?>
                                <option value="<?php echo $bt; ?>" <?php echo $budget_tier === $bt ? 'selected' : ''; ?>><?php echo $bt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Calculate → Step 2 -->
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
                    <div class="planner-map-wrap">
                        <iframe id="plannerMap" src="<?php echo $map_url; ?>"
                                title="Map" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>

                    <div class="planner-insights">
                        <div class="insight-card">
                            <div class="insight-icon" style="background:var(--orange-light);color:var(--orange);">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div>
                                <div class="insight-label">Market Insight</div>
                                <div class="insight-text" id="insight-market">
                                    Select a destination to see market insights.
                                </div>
                            </div>
                        </div>
                        <div class="insight-card">
                            <div class="insight-icon" style="background:#F0FDF4;color:var(--success);">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div>
                                <div class="insight-label">Recommendation</div>
                                <div class="insight-text" id="insight-tip">
                                    Book flights 21 days in advance for best rates.
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="notes" value="<?php echo htmlspecialchars($notes_val); ?>">
                    <input type="hidden" name="budget_limit" id="budget-limit-hidden" value="0">
                </div>

                <!-- Right: Cost Estimator card -->
                <div>
                    <div class="cost-card" style="position:relative;">

                        <!-- Loading overlay -->
                        <div id="cost-loading-overlay"
                             style="display:none;position:absolute;inset:0;background:rgba(255,255,255,0.88);
                                    border-radius:inherit;align-items:center;justify-content:center;
                                    z-index:10;flex-direction:column;gap:10px;">
                            <div style="width:30px;height:30px;border:3px solid #F3F4F6;
                                        border-top-color:var(--primary);border-radius:50%;
                                        animation:costSpin 0.7s linear infinite;"></div>
                            <div style="font-size:12px;color:var(--muted);font-weight:600;
                                        letter-spacing:.05em;">Fetching estimates…</div>
                        </div>

                        <div class="cost-card-header">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                                <div class="cost-card-title" id="cost-card-title">
                                    Estimated Costs for Your Destination
                                </div>
                                <div style="background:rgba(255,255,255,0.2);border-radius:6px;padding:4px 10px;
                                            font-size:12px;font-weight:700;color:#fff;white-space:nowrap;flex-shrink:0;">
                                    <?php echo htmlspecialchars($cur_symbol); ?> <?php echo htmlspecialchars($cur_code); ?>
                                </div>
                            </div>
                            <div class="cost-card-sub" id="cost-card-sub">
                                Select a destination to see cost estimates
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
                                    <div class="cost-item-amount" id="cost-amt-transport"><?php echo format_currency(0, $cur_symbol); ?></div>
                                    <div class="cost-item-pct"   id="cost-pct-transport">0% of total</div>
                                </div>
                            </div>

                            <!-- Accommodation -->
                            <div class="cost-item">
                                <div class="cost-item-icon" style="background:#F0FDF4;color:#16A34A;">
                                    <i class="fa-solid fa-bed"></i>
                                </div>
                                <div class="cost-item-info">
                                    <div class="cost-item-name">Accommodation</div>
                                    <div class="cost-item-sub" id="accom-sub-label">4-star average</div>
                                </div>
                                <div>
                                    <div class="cost-item-amount" id="cost-amt-accom"><?php echo format_currency(0, $cur_symbol); ?></div>
                                    <div class="cost-item-pct"   id="cost-pct-accom">0% of total</div>
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
                                    <div class="cost-item-amount" id="cost-amt-food"><?php echo format_currency(0, $cur_symbol); ?></div>
                                    <div class="cost-item-pct"   id="cost-pct-food">0% of total</div>
                                </div>
                            </div>

                            <!-- Attractions (via Klook) -->
                            <div class="cost-item">
                                <div class="cost-item-icon" style="background:#FDF0E8;color:#8B3A10;">
                                    <i class="fa-solid fa-ticket"></i>
                                </div>
                                <div class="cost-item-info">
                                    <div class="cost-item-name">
                                        Attractions
                                        <span class="badge-saved">KLOOK</span>
                                    </div>
                                    <div class="cost-item-sub">Entry fees + tours via Klook</div>
                                </div>
                                <div>
                                    <div class="cost-item-amount" id="cost-amt-attract"><?php echo format_currency(0, $cur_symbol); ?></div>
                                    <div class="cost-item-pct"   id="cost-pct-attract">0% of total</div>
                                </div>
                            </div>

                            <!-- Emergency Fund -->
                            <div class="cost-item">
                                <div class="cost-item-icon" style="background:#FEF2F2;color:#DC2626;">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </div>
                                <div class="cost-item-info">
                                    <div class="cost-item-name">Emergency Fund</div>
                                    <div class="cost-item-sub">Buffer recommendation</div>
                                </div>
                                <div>
                                    <div class="cost-item-amount" id="cost-amt-emergency"><?php echo format_currency(0, $cur_symbol); ?></div>
                                    <div class="cost-item-pct"   id="cost-pct-emergency">0% of total</div>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="cost-total-row">
                                <div>
                                    <div class="cost-total-label">Total Estimated Budget</div>
                                    <div class="cost-total-note">All inclusive of taxes &amp; fees</div>
                                </div>
                                <div class="cost-total-value" id="cost-total-value"><?php echo format_currency(0, $cur_symbol); ?></div>
                            </div>

                            <!-- Exclusive offer -->
                            <div class="cost-exclusive" style="margin-top:14px;">
                                <div>
                                    <span class="cost-exclusive-tag">EXCLUSIVE OFFER</span>
                                    <div class="cost-exclusive-text">
                                        Get 15% off <span id="exclusive-dest">your destination</span> accommodation with Expedia Gold.
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-right" style="color:var(--muted);font-size:12px;flex-shrink:0;"></i>
                            </div>

                        </div><!-- /cost-card-body -->
                    </div><!-- /cost-card -->
                </div>

            </div><!-- /planner-grid -->
        </form>

    </div><!-- /app-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

<style>
@keyframes costSpin { to { transform: rotate(360deg); } }
#stepper-minus:disabled,
#stepper-plus:disabled  { opacity: 0.35; cursor: not-allowed; }
</style>

<script>
var BASE_URL    = <?php echo json_encode(BASE_URL); ?>;
var curSymbol   = <?php echo json_encode($cur_symbol); ?>;
var userCountry = <?php echo json_encode($user_country); ?>;

// Match PHP format_currency logic
function fmtCur(n) {
    n = Math.round(n);
    if (n >= 10000) return curSymbol + n.toLocaleString('en-US', {maximumFractionDigits: 0});
    return curSymbol + parseFloat(n).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Lock/unlock traveler stepper based on group
function applyGroupLock(group) {
    var minus = document.getElementById('stepper-minus');
    var plus  = document.getElementById('stepper-plus');
    var inp   = document.getElementById('num_travelers_input');
    var disp  = document.getElementById('traveler-display');
    if (group === 'Solo') {
        inp.value        = '1';
        disp.textContent = '1';
        if (minus) minus.disabled = true;
        if (plus)  plus.disabled  = true;
    } else if (group === 'Couple') {
        inp.value        = '2';
        disp.textContent = '2';
        if (minus) minus.disabled = true;
        if (plus)  plus.disabled  = true;
    } else {
        if (minus) minus.disabled = false;
        if (plus)  plus.disabled  = false;
    }
}

function adjustTravelers(delta) {
    var grpEl = document.getElementById('groupSelect');
    if (grpEl && (grpEl.value === 'Solo' || grpEl.value === 'Couple')) return;
    var inp  = document.getElementById('num_travelers_input');
    var disp = document.getElementById('traveler-display');
    var val  = parseInt(inp.value) + delta;
    if (val < 1) val = 1;
    if (val > 20) val = 20;
    inp.value        = val;
    disp.textContent = val;
    fetchCosts();
}

function resetCosts() {
    var zero = fmtCur(0);
    ['transport','accom','food','attract','emergency'].forEach(function (k) {
        var a = document.getElementById('cost-amt-' + k);
        var p = document.getElementById('cost-pct-' + k);
        if (a) a.textContent = zero;
        if (p) p.textContent = '0% of total';
    });
    var tv = document.getElementById('cost-total-value');
    if (tv) tv.textContent = zero;
    var title = document.getElementById('cost-card-title');
    if (title) title.textContent = 'Estimated Costs for Your Destination';
    var sub = document.getElementById('cost-card-sub');
    if (sub) sub.textContent = 'Select a destination to see cost estimates';
    var bl = document.getElementById('budget-limit-hidden');
    if (bl) bl.value = '0';
    var im = document.getElementById('insight-market');
    if (im) im.textContent = 'Select a destination to see market insights.';
}

function applyCosts(data) {
    var total = data.total || 0;
    var map   = {transport:'transport', accom:'accom', food:'food', attract:'attractions', emergency:'emergency'};

    Object.keys(map).forEach(function (dispKey) {
        var val   = data[map[dispKey]] || 0;
        var amtEl = document.getElementById('cost-amt-' + dispKey);
        var pctEl = document.getElementById('cost-pct-' + dispKey);
        if (amtEl) amtEl.textContent = fmtCur(val);
        if (pctEl) pctEl.textContent = (total > 0 ? Math.round(val / total * 100) : 0) + '% of total';
    });

    var tv = document.getElementById('cost-total-value');
    if (tv) tv.textContent = fmtCur(total);

    var title = document.getElementById('cost-card-title');
    if (title) title.textContent = 'Estimated Costs for ' + data.destination;

    var budgetEl = document.getElementById('budgetSelect');
    var sub      = document.getElementById('cost-card-sub');
    if (sub) sub.textContent =
        'Duration: ' + data.days + ' day' + (data.days !== 1 ? 's' : '') +
        ' · ' + (budgetEl ? budgetEl.value : 'Mid-range') + ' Travel · ' +
        data.travelers + ' Traveler' + (data.travelers !== 1 ? 's' : '');

    var excDest = document.getElementById('exclusive-dest');
    if (excDest) excDest.textContent = data.destination;

    var bl = document.getElementById('budget-limit-hidden');
    if (bl) bl.value = data.usd_total || 0;

    // Accommodation label based on budget tier
    var accomSub = document.getElementById('accom-sub-label');
    if (accomSub) {
        var accomLabels = {'Shoestring/Backpacker': 'Hostels & budget guesthouses', 'Mid-range': '4-star average', 'Luxury/Premium': '5-star resorts'};
        accomSub.textContent = accomLabels[data.budget_tier] || '4-star average';
    }

    // Market insight based on destination cost multiplier
    var im   = document.getElementById('insight-market');
    var mult = parseFloat(data.dest_mult) || 1.0;
    if (im) {
        if      (mult <= 0.82) im.textContent = data.destination + ' is one of the most budget-friendly destinations — costs are significantly below the global average.';
        else if (mult <= 0.92) im.textContent = 'Great value! Prices in ' + data.destination + ' are below the global average — ideal for budget-conscious travelers.';
        else if (mult <= 1.10) im.textContent = data.destination + ' offers mid-range pricing aligned with global averages. Good balance of comfort and cost.';
        else if (mult <= 1.30) im.textContent = data.destination + ' is a premium destination. Expect higher costs — book early to lock in better rates.';
        else                   im.textContent = data.destination + ' is a high-cost destination. Consider the Budget tier or traveling off-season to save significantly.';
    }

    // Recommendation tip
    var tip = document.getElementById('insight-tip');
    if (tip) {
        if      (mult <= 0.85) tip.textContent = 'Tip: ' + data.destination + ' has excellent value. Even Mid-range gives you a premium-like experience.';
        else if (mult >= 1.40) tip.textContent = 'Tip: Book your accommodation for ' + data.destination + ' at least 30 days ahead to avoid last-minute price surges.';
        else                   tip.textContent = 'Book flights 21 days in advance for best rates to ' + data.destination + '.';
    }
}

// Debounced AJAX fetch
var _fetchTimer = null;
function fetchCosts() {
    clearTimeout(_fetchTimer);
    _fetchTimer = setTimeout(doFetchCosts, 350);
}

function doFetchCosts() {
    var destEl = document.getElementById('destSelect');
    var dest   = destEl ? destEl.value.trim() : '';
    if (!dest) { resetCosts(); return; }

    var grpEl    = document.getElementById('groupSelect');
    var budgetEl = document.getElementById('budgetSelect');
    var sdEl     = document.getElementById('startDate');
    var edEl     = document.getElementById('endDate');
    var travInp  = document.getElementById('num_travelers_input');

    var group    = grpEl    ? grpEl.value    : 'Solo';
    var budget   = budgetEl ? budgetEl.value : 'Mid-range';
    var travelers= parseInt(travInp ? travInp.value : '1') || 1;
    var days     = 5;

    if (sdEl && edEl && sdEl.value && edEl.value) {
        days = Math.max(1, Math.round((new Date(edEl.value) - new Date(sdEl.value)) / 86400000));
    }

    var overlay = document.getElementById('cost-loading-overlay');
    if (overlay) overlay.style.display = 'flex';

    fetch(BASE_URL + '/src/api/costs.php' +
        '?destination=' + encodeURIComponent(dest) +
        '&group='        + encodeURIComponent(group) +
        '&budget_tier='  + encodeURIComponent(budget) +
        '&days='         + days +
        '&travelers='    + travelers)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (overlay) overlay.style.display = 'none';
            if (data.success) applyCosts(data);
        })
        .catch(function () {
            if (overlay) overlay.style.display = 'none';
        });
}

(function () {
    // Apply Solo lock on initial load
    var grpEl = document.getElementById('groupSelect');
    if (grpEl) {
        applyGroupLock(grpEl.value);
        grpEl.addEventListener('change', function () {
            applyGroupLock(this.value);
            fetchCosts();
        });
    }

    // Destination: update map + costs
    var destEl = document.getElementById('destSelect');
    var map    = document.getElementById('plannerMap');
    if (destEl) {
        destEl.addEventListener('change', function () {
            if (map) {
                var d = this.value || userCountry;
                map.src = 'https://maps.google.com/maps?q=' + encodeURIComponent(d) + '&output=embed';
            }
            fetchCosts();
        });
        // Text input: also listen to blur
        if (destEl.tagName === 'INPUT') {
            destEl.addEventListener('blur', fetchCosts);
        }
    }

    // Dates
    var sdEl = document.getElementById('startDate');
    var edEl = document.getElementById('endDate');
    if (sdEl) sdEl.addEventListener('change', fetchCosts);
    if (edEl) edEl.addEventListener('change', fetchCosts);

    // Budget tier
    var budgetEl = document.getElementById('budgetSelect');
    if (budgetEl) budgetEl.addEventListener('change', fetchCosts);

    // If destination already set (e.g., user navigated back), fetch immediately
    var initialDest = destEl ? destEl.value.trim() : '';
    if (initialDest) {
        doFetchCosts();
    } else {
        resetCosts();
    }
})();
</script>
</body>
</html>
