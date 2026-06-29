<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/currencies.php';

require_login();

// ── Step 3: Confirm → save trip and redirect ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm') {
    $dest_val      = trim($_POST['destination'] ?? '');
    $sd_val        = $_POST['start_date']    ?? '';
    $ed_val        = $_POST['end_date']      ?? '';
    $trav_val      = max(1, intval($_POST['num_travelers'] ?? 1));
    $group_val     = $_POST['travel_type']   ?? 'Solo';
    $budget_usd    = floatval($_POST['budget_limit_usd'] ?? 0);
    $notes_val     = trim($_POST['notes']    ?? '');

    $errors = [];
    if (empty($dest_val)) $errors[] = "Destination is required.";
    if (empty($sd_val))   $errors[] = "Start date is required.";
    if (empty($ed_val))   $errors[] = "End date is required.";

    if (empty($errors)) {
        $uid      = $_SESSION['user_id'];
        $c_rate   = (float)($_SESSION['user_currency_rate'] ?? 1.0);
        if ($c_rate <= 0) $c_rate = 1.0;

        $stmt = $pdo->prepare(
            "INSERT INTO trips (user_id, destination, start_date, end_date,
             num_travelers, budget_limit, travel_type, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if ($stmt->execute([$uid, $dest_val, $sd_val, $ed_val, $trav_val, $budget_usd, $group_val, $notes_val])) {
            $trip_id = $pdo->lastInsertId();

            // Insert per-category budgets (local currency → USD)
            $budget_cats = [
                'Transportation'      => floatval($_POST['cost_transport']   ?? 0) / $c_rate,
                'Accommodation'       => floatval($_POST['cost_accom']       ?? 0) / $c_rate,
                'Food'                => floatval($_POST['cost_food']        ?? 0) / $c_rate,
                'Tourist Attractions' => floatval($_POST['cost_attractions'] ?? 0) / $c_rate,
                'Shopping'            => floatval($_POST['cost_shopping']    ?? 0) / $c_rate,
                'Emergency Funds'     => floatval($_POST['cost_emergency']   ?? 0) / $c_rate,
            ];
            $stmtb = $pdo->prepare("INSERT INTO trip_budgets (trip_id, category, estimated_cost) VALUES (?, ?, ?)");
            foreach ($budget_cats as $cat => $cost_usd) {
                $stmtb->execute([$trip_id, $cat, round($cost_usd, 4)]);
            }

            // Create savings goal linked to the trip
            $goal_name = $dest_val . ' Trip';
            $stmt_sg   = $pdo->prepare(
                "INSERT INTO savings_goals (user_id, trip_id, goal_name, target_amount, current_savings, deadline)
                 VALUES (?, ?, ?, ?, 0, ?)"
            );
            $goal_id = null;
            if ($stmt_sg->execute([$uid, $trip_id, $goal_name, $budget_usd, $sd_val])) {
                $goal_id = $pdo->lastInsertId();
            }

            $redirect = $goal_id
                ? BASE_URL . '/src/savings/goal.php?goal_id=' . $goal_id
                : BASE_URL . '/src/dashboard/index.php?trip_created=1';
            header('Location: ' . $redirect);
            exit;
        }
        $errors[] = "Failed to save trip. Please try again.";
    }
    // Fall through on error — re-render with same POST data below
}

// ── Require POST from Step 1 ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/src/trips/type.php');
    exit;
}

// ── Form data ─────────────────────────────────────────────────────────────
$dest_val    = trim($_POST['destination'] ?? '');
$sd_val      = $_POST['start_date']   ?? date('Y-m-d', strtotime('+7 days'));
$ed_val      = $_POST['end_date']     ?? date('Y-m-d', strtotime('+12 days'));
$trav_val    = max(1, intval($_POST['num_travelers'] ?? 1));
$group_val   = $_POST['travel_type']  ?? 'Solo';
$budget_tier = $_POST['budget_tier']  ?? 'Mid-range';
$trip_type   = $_POST['trip_type']    ?? 'international';
$notes_val   = trim($_POST['notes']   ?? '');

// ── Currency ──────────────────────────────────────────────────────────────
$cur_symbol   = $_SESSION['user_currency_symbol'] ?? '$';
$cur_code     = $_SESSION['user_currency_code']   ?? 'USD';
$cur_rate     = (float)($_SESSION['user_currency_rate'] ?? 1.0);
$user_country = $_SESSION['user_country'] ?? '';

if (empty($_SESSION['user_currency_rate'])) {
    $cur_info = get_currency_for_country($user_country);
    $cur_rate = $cur_info['rate'];
    $_SESSION['user_currency_rate'] = $cur_rate;
    $_SESSION['user_currency_name'] = $cur_info['name'];
}

// ── Cost presets (USD base, per 5 days, 1 traveler) ───────────────────────
$presets = [
    'Shoestring/Backpacker' => ['transport' => 400,  'accom' => 350,  'food' => 200,  'attractions' => 100, 'shopping' => 120, 'emergency' => 80],
    'Mid-range'             => ['transport' => 850,  'accom' => 800,  'food' => 400,  'attractions' => 250, 'shopping' => 300, 'emergency' => 150],
    'Luxury/Premium'        => ['transport' => 2200, 'accom' => 2800, 'food' => 1200, 'attractions' => 600, 'shopping' => 800, 'emergency' => 400],
];

$p    = $presets[$budget_tier] ?? $presets['Mid-range'];
$days = max(1, round((strtotime($ed_val) - strtotime($sd_val)) / 86400));
$m    = max(1, $trav_val);

$usd = [
    'transport'  => round($p['transport']   * ($days / 5) * (1 + ($m - 1) * 0.4)),
    'accom'      => round($p['accom']       * ($days / 5)),
    'food'       => round($p['food']        * ($days / 5) * (1 + ($m - 1) * 0.6)),
    'attractions'=> round($p['attractions'] * ($days / 5)),
    'shopping'   => round($p['shopping']    * ($days / 5) * (1 + ($m - 1) * 0.3)),
    'emergency'  => round($p['emergency']),
];
$usd_total = array_sum($usd);

// User-currency versions (editable by user — re-submit overrides these)
function cur_val(string $key, array $usd, float $rate): int {
    if (!empty($_POST['cost_' . $key]) && ($_POST['action'] ?? '') === 'confirm') {
        return max(0, (int)$_POST['cost_' . $key]);
    }
    return (int)ceil(usd_to_currency($usd[$key], $rate));
}

$c = [
    'transport'  => cur_val('transport',   $usd, $cur_rate),
    'accom'      => cur_val('accom',       $usd, $cur_rate),
    'food'       => cur_val('food',        $usd, $cur_rate),
    'attractions'=> cur_val('attractions', $usd, $cur_rate),
    'shopping'   => cur_val('shopping',    $usd, $cur_rate),
    'emergency'  => cur_val('emergency',   $usd, $cur_rate),
];
$c_total  = array_sum($c);
$variance = max(1, (int)($c_total * 0.05));

// ── Comfort badge ─────────────────────────────────────────────────────────
$comfort_map = [
    'Shoestring/Backpacker' => ['label' => 'STANDARD', 'color' => '#16A34A', 'bg' => '#F0FDF4'],
    'Mid-range'             => ['label' => 'COMFORT',  'color' => '#E07B2A', 'bg' => '#FEF3E8'],
    'Luxury/Premium'        => ['label' => 'PREMIUM',  'color' => '#8B3A10', 'bg' => '#FDF0E8'],
];
$comfort = $comfort_map[$budget_tier] ?? $comfort_map['Mid-range'];

// ── Smart tip ─────────────────────────────────────────────────────────────
$d = strtolower($dest_val);
if (str_contains($d, 'japan') || str_contains($d, 'tokyo') || str_contains($d, 'kyoto') || str_contains($d, 'osaka')) {
    $tip = "Seasonal prices in Japan are up to 15% higher during Cherry Blossom season (late March–April). We've adjusted your estimates to reflect this.";
} elseif (str_contains($d, 'bali') || str_contains($d, 'indonesia')) {
    $tip = "Bali's dry season (May–September) sees peak pricing. Traveling in shoulder season can save you up to 20% on accommodation.";
} elseif (str_contains($d, 'europe') || str_contains($d, 'paris') || str_contains($d, 'rome') || str_contains($d, 'london')) {
    $tip = "European city travel in summer (July–August) commands a premium. Consider visiting in May, June, or September for better rates.";
} elseif ($budget_tier === 'Shoestring/Backpacker') {
    $tip = "Traveling on a shoestring? Opt for hostels, local street food, and free attractions to stretch your funds further.";
} elseif ($budget_tier === 'Luxury/Premium') {
    $tip = "For a premium experience, consider a travel concierge to handle reservations and private tours seamlessly.";
} else {
    $tip = "Book your accommodation and flights at least 21 days in advance to secure the best rates for " . htmlspecialchars($dest_val) . ".";
}

// Cost items config
$cost_items = [
    'transport'   => ['label' => 'Transportation',     'sub' => 'Flights + Local Transit', 'icon' => 'fa-plane',         'icon_color' => '#1565C0', 'icon_bg' => '#E8F0FE'],
    'accom'       => ['label' => 'Accommodation',      'sub' => '4-star average',           'icon' => 'fa-bed',           'icon_color' => '#16A34A', 'icon_bg' => '#F0FDF4'],
    'food'        => ['label' => 'Food & Dining',       'sub' => 'Street & mid-range dining','icon' => 'fa-utensils',     'icon_color' => '#E07B2A', 'icon_bg' => '#FEF3E8'],
    'attractions' => ['label' => 'Tourist Attractions', 'sub' => 'Entry fees + tours',       'icon' => 'fa-ticket',       'icon_color' => '#8B3A10', 'icon_bg' => '#FDF0E8'],
    'shopping'    => ['label' => 'Shopping',            'sub' => 'Souvenirs & retail',       'icon' => 'fa-bag-shopping', 'icon_color' => '#7C3AED', 'icon_bg' => '#F5F3FF'],
    'emergency'   => ['label' => 'Emergency Funds',    'sub' => 'Buffer recommendation',    'icon' => 'fa-shield-halved','icon_color' => '#DC2626', 'icon_bg' => '#FEF2F2'],
];

$body_class = 'dashboard-body';
$active_sidebar = 'planner';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="dash-main">
    <div class="app-content">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="margin-bottom:20px;">
                <?php foreach ($errors as $e): echo htmlspecialchars($e) . '<br>'; endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Page header -->
        <div class="estimator-header">
            <div>
                <h1 class="estimator-title">Trip Cost Estimator</h1>
                <p class="estimator-subtitle">
                    Reviewing your estimated budget for
                    <a href="<?php echo BASE_URL; ?>/src/trips/create.php?type=<?php echo urlencode($trip_type); ?>"
                       style="color:var(--primary);font-weight:600;text-decoration:none;">
                        <?php echo htmlspecialchars($dest_val ?: 'your destination'); ?>
                    </a>.
                </p>
            </div>
        </div>

        <!-- Main form -->
        <form method="POST" action="<?php echo BASE_URL; ?>/src/trips/estimate.php" id="estimatorForm">
            <!-- Pass all Step 1 data through -->
            <input type="hidden" name="destination"    value="<?php echo htmlspecialchars($dest_val); ?>">
            <input type="hidden" name="start_date"     value="<?php echo htmlspecialchars($sd_val); ?>">
            <input type="hidden" name="end_date"       value="<?php echo htmlspecialchars($ed_val); ?>">
            <input type="hidden" name="num_travelers"  value="<?php echo $trav_val; ?>">
            <input type="hidden" name="travel_type"    value="<?php echo htmlspecialchars($group_val); ?>">
            <input type="hidden" name="budget_tier"    value="<?php echo htmlspecialchars($budget_tier); ?>">
            <input type="hidden" name="trip_type"      value="<?php echo htmlspecialchars($trip_type); ?>">
            <input type="hidden" name="notes"          value="<?php echo htmlspecialchars($notes_val); ?>">
            <input type="hidden" name="budget_limit_usd" value="<?php echo $usd_total; ?>">

            <!-- Hidden cost inputs (JS updates these when user edits) -->
            <?php foreach (array_keys($cost_items) as $key): ?>
                <input type="hidden" name="cost_<?php echo $key; ?>" id="inp_<?php echo $key; ?>" value="<?php echo $c[$key]; ?>">
            <?php endforeach; ?>

            <!-- Two-column layout -->
            <div class="estimator-layout">

                <!-- LEFT: details + tip -->
                <div>
                    <div class="estimator-details-card">
                        <div class="estimator-details-label">
                            <i class="fa-solid fa-location-dot"></i> Destination Details
                        </div>
                        <div class="estimator-dest-name"><?php echo htmlspecialchars($dest_val ?: '—'); ?></div>

                        <div class="estimator-detail-row">
                            <span>Duration</span>
                            <strong><?php echo $days; ?> Day<?php echo $days !== 1 ? 's' : ''; ?></strong>
                        </div>
                        <div class="estimator-detail-row">
                            <span>Travelers</span>
                            <strong><?php echo $trav_val; ?> Adult<?php echo $trav_val !== 1 ? 's' : ''; ?></strong>
                        </div>
                        <div class="estimator-detail-row">
                            <span>Travel Type</span>
                            <strong><?php echo htmlspecialchars($group_val); ?></strong>
                        </div>
                        <div class="estimator-detail-row">
                            <span>Comfort Level</span>
                            <span class="comfort-badge"
                                  style="color:<?php echo $comfort['color']; ?>;background:<?php echo $comfort['bg']; ?>;">
                                <?php echo $comfort['label']; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Smart Tip -->
                    <div class="smart-tip-card">
                        <div class="smart-tip-icon-wrap">
                            <i class="fa-solid fa-lightbulb"></i>
                        </div>
                        <div>
                            <div class="smart-tip-title">Smart Tip</div>
                            <div class="smart-tip-text"><?php echo $tip; ?></div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: cost grid -->
                <div>
                    <div class="estimator-cost-grid">
                        <?php foreach ($cost_items as $key => $item): ?>
                            <div class="cost-item-box">
                                <div class="cost-item-box-header">
                                    <div class="cost-item-box-icon"
                                         style="background:<?php echo $item['icon_bg']; ?>;color:<?php echo $item['icon_color']; ?>;">
                                        <i class="fa-solid <?php echo $item['icon']; ?>"></i>
                                    </div>
                                    <button type="button" class="cost-item-edit-btn"
                                            id="btn_<?php echo $key; ?>"
                                            onclick="toggleEdit('<?php echo $key; ?>')">Edit</button>
                                </div>
                                <div class="cost-item-box-name"><?php echo $item['label']; ?></div>
                                <div class="cost-item-box-sub"><?php echo $item['sub']; ?></div>
                                <div class="cost-item-box-amount"
                                     id="disp_<?php echo $key; ?>"><?php echo format_currency($c[$key], $cur_symbol); ?></div>
                                <input class="cost-item-box-input" type="number" min="0"
                                       id="edit_<?php echo $key; ?>"
                                       value="<?php echo $c[$key]; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Total card -->
                    <div class="estimator-total-card">
                        <div>
                            <div class="estimator-total-label">Total Estimated Cost</div>
                            <div class="estimator-total-amount" id="total-display">
                                <?php echo format_currency($c_total, $cur_symbol); ?>
                            </div>
                            <div class="estimator-total-note">
                                Estimated variance: +/- <?php echo format_currency($variance, $cur_symbol); ?> based on current market data.
                            </div>
                        </div>
                        <button type="submit" name="action" value="confirm" class="btn-confirm-savings">
                            Confirm and Create Savings Goal
                        </button>
                    </div>
                </div>

            </div><!-- /estimator-layout -->

            <div class="estimator-back">
                <a href="javascript:history.back()">
                    <i class="fa-solid fa-arrow-left"></i> Go back to Step 1
                </a>
            </div>
        </form>

    </div><!-- /app-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

<script>
var curSymbol = <?php echo json_encode($cur_symbol); ?>;

function fmtCurrency(n) {
    if (n >= 10000) return curSymbol + n.toLocaleString('en-US', {maximumFractionDigits: 0});
    return curSymbol + n.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function toggleEdit(key) {
    var btn    = document.getElementById('btn_'  + key);
    var disp   = document.getElementById('disp_' + key);
    var inp    = document.getElementById('edit_' + key);
    var hidden = document.getElementById('inp_'  + key);

    if (btn.textContent === 'Edit') {
        disp.style.display = 'none';
        inp.style.display  = 'block';
        inp.focus();
        inp.select();
        btn.textContent = 'Save';
    } else {
        var val = Math.max(0, parseFloat(inp.value) || 0);
        inp.value          = val;
        hidden.value       = val;
        disp.textContent   = fmtCurrency(val);
        disp.style.display = 'block';
        inp.style.display  = 'none';
        btn.textContent    = 'Edit';
        updateTotal();
    }
}

function updateTotal() {
    var keys  = ['transport','accom','food','attractions','shopping','emergency'];
    var total = 0;
    keys.forEach(function(k) {
        total += parseFloat(document.getElementById('inp_' + k).value) || 0;
    });
    document.getElementById('total-display').textContent = fmtCurrency(total);
    // Update variance (~5%)
    var variance = Math.round(total * 0.05);
    var noteEl = document.querySelector('.estimator-total-note');
    if (noteEl) noteEl.textContent = 'Estimated variance: +/- ' + fmtCurrency(variance) + ' based on current market data.';
}

// Allow pressing Enter in the edit input to save
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.classList.contains('cost-item-box-input')) {
        var key = e.target.id.replace('edit_', '');
        toggleEdit(key);
    }
});
</script>

</body>
</html>
