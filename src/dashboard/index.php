<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/currencies.php';

require_login();

$user_id    = $_SESSION['user_id'];
$cur_symbol = $_SESSION['user_currency_symbol'] ?? '$';
$cur_rate   = (float)($_SESSION['user_currency_rate']   ?? 1.0);

// If session is missing currency data (old session), reload from DB
if (empty($_SESSION['user_currency_rate'])) {
    $stmt_u = $pdo->prepare("SELECT country, currency_code, currency_symbol FROM users WHERE id = ?");
    $stmt_u->execute([$user_id]);
    $urow = $stmt_u->fetch();
    if ($urow) {
        $cur_info   = get_currency_for_country($urow['country'] ?? '');
        $cur_symbol = $urow['currency_symbol'] ?: $cur_info['symbol'];
        $cur_rate   = $cur_info['rate'];
        $_SESSION['user_currency_symbol'] = $cur_symbol;
        $_SESSION['user_currency_rate']   = $cur_rate;
        $_SESSION['user_currency_code']   = $urow['currency_code'] ?: $cur_info['code'];
        $_SESSION['user_currency_name']   = $cur_info['name'];
    }
}

// Active trip: prefer ?trip_id=X (stored in session), else most recent
if (!empty($_GET['trip_id'])) {
    $req_id = intval($_GET['trip_id']);
    $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
    $stmt->execute([$req_id, $user_id]);
    $active_trip = $stmt->fetch();
    if ($active_trip) {
        $_SESSION['current_trip_id'] = $active_trip['id'];
    }
}
if (empty($active_trip)) {
    if (!empty($_SESSION['current_trip_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$_SESSION['current_trip_id'], $user_id]);
        $active_trip = $stmt->fetch();
    }
    if (empty($active_trip)) {
        $stmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $active_trip = $stmt->fetch();
        if ($active_trip) $_SESSION['current_trip_id'] = $active_trip['id'];
    }
}

// All trips
$stmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$trips = $stmt->fetchAll();

// Budget / spent for active trip
$active_trip_id  = $active_trip['id'] ?? null;
$total_budget    = 0;
$total_spent     = 0;

if ($active_trip) {
    $total_budget = (float)($active_trip['budget_limit'] ?? 0);
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE trip_id = ?");
    $stmt->execute([$active_trip_id]);
    $total_spent = (float)($stmt->fetch()['total'] ?? 0);
} else {
    foreach ($trips as $trip) {
        $total_budget += (float)($trip['budget_limit'] ?? 0);
        $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE trip_id = ?");
        $stmt->execute([$trip['id']]);
        $total_spent += (float)($stmt->fetch()['total'] ?? 0);
    }
}
$remaining   = $total_budget - $total_spent;
$pct_spent   = $total_budget > 0 ? min(100, ($total_spent / $total_budget) * 100) : 0;
$daily_remaining = 0;
if ($active_trip && !empty($active_trip['end_date'])) {
    $days_left = max(1, (strtotime($active_trip['end_date']) - time()) / 86400);
    $daily_remaining = $remaining / $days_left;
}

// Recent expenses
$stmt = $pdo->prepare(
    "SELECT e.*, t.destination FROM expenses e
     LEFT JOIN trips t ON e.trip_id = t.id
     WHERE t.user_id = ?
     ORDER BY e.created_at DESC LIMIT 5"
);
$stmt->execute([$user_id]);
$recent_expenses = $stmt->fetchAll();

// Category breakdown for active trip (stored amounts are in USD)
// Map expense categories to trip_budgets categories
$expense_to_budget = [
    'Food'               => 'Food',
    'Transportation'     => 'Transportation',
    'Accommodation'      => 'Accommodation',
    'Activities'         => 'Tourist Attractions',
    'Shopping'           => 'Shopping',
    'Emergency Expenses' => 'Emergency Funds',
];

$cat_actual_usd = []; // keyed by trip_budget category name
$cat_data = [];
if ($active_trip_id) {
    $stmt = $pdo->prepare(
        "SELECT category, SUM(amount) as total FROM expenses WHERE trip_id = ? GROUP BY category ORDER BY total DESC"
    );
    $stmt->execute([$active_trip_id]);
    $cat_data = $stmt->fetchAll();
    foreach ($cat_data as $c) {
        $bkey = $expense_to_budget[$c['category']] ?? $c['category'];
        $cat_actual_usd[$bkey] = ($cat_actual_usd[$bkey] ?? 0) + (float)$c['total'];
    }
}

// ── Category chart data ────────────────────────────────────────────────
$chart_cat_display = [
    'Food'               => 'Food',
    'Transportation'     => 'Transport',
    'Accommodation'      => 'Stay',
    'Shopping'           => 'Shopping',
    'Activities'         => 'Activities',
    'Emergency Expenses' => 'Emergency',
];
$chart_cat_colors_map = [
    'Food'               => '#E07B2A',
    'Transportation'     => '#1565C0',
    'Accommodation'      => '#16A34A',
    'Shopping'           => '#7C3AED',
    'Activities'         => '#8B3A10',
    'Emergency Expenses' => '#DC2626',
];
$chart_cat_labels     = [];
$chart_cat_percents   = [];
$chart_cat_colors_arr = [];
$chart_total = array_sum(array_column($cat_data, 'total')) ?: 0;
foreach ($cat_data as $c) {
    $chart_cat_labels[]     = $chart_cat_display[$c['category']] ?? $c['category'];
    $chart_cat_percents[]   = $chart_total > 0 ? round($c['total'] / $chart_total * 100, 1) : 0;
    $chart_cat_colors_arr[] = $chart_cat_colors_map[$c['category']] ?? '#D4A412';
}
$top_category_label  = $chart_cat_labels[0] ?? '';
$has_category_data   = !empty($chart_cat_labels);

// ── Trend data (3 periods, single queries each) ────────────────────────
$by_day_7 = $by_day_30 = $by_month_12 = [];
if ($active_trip_id) {
    $stmt = $pdo->prepare("SELECT DATE(created_at) as d, SUM(amount) as total FROM expenses WHERE trip_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at)");
    $stmt->execute([$active_trip_id]);
    foreach ($stmt->fetchAll() as $r) $by_day_7[$r['d']] = (float)$r['total'];

    $stmt = $pdo->prepare("SELECT DATE(created_at) as d, SUM(amount) as total FROM expenses WHERE trip_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at)");
    $stmt->execute([$active_trip_id]);
    foreach ($stmt->fetchAll() as $r) $by_day_30[$r['d']] = (float)$r['total'];

    $stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as m, SUM(amount) as total FROM expenses WHERE trip_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY m ORDER BY m");
    $stmt->execute([$active_trip_id]);
    foreach ($stmt->fetchAll() as $r) $by_month_12[$r['m']] = (float)$r['total'];
}

$trend7  = ['labels' => [], 'data' => []];
$trend30 = ['labels' => [], 'data' => []];
$trend12 = ['labels' => [], 'data' => []];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trend7['labels'][] = date('D', strtotime($d));
    $trend7['data'][]   = round(($by_day_7[$d] ?? 0) * $cur_rate, 2);
}
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trend30['labels'][] = date('M d', strtotime($d));
    $trend30['data'][]   = round(($by_day_30[$d] ?? 0) * $cur_rate, 2);
}
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $trend12['labels'][] = date('M y', strtotime($m . '-01'));
    $trend12['data'][]   = round(($by_month_12[$m] ?? 0) * $cur_rate, 2);
}

// Alerts / Notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Savings goals
$stmt = $pdo->prepare("SELECT * FROM savings_goals WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->execute([$user_id]);
$savings_goals = $stmt->fetchAll();

// Budget breakdown — read estimated costs from trip_budgets (stored in USD)
$breakdown_config = [
    'Food'              => ['cat' => 'Dining',       'icon' => 'fa-utensils',     'color' => '#E07B2A', 'bg' => '#FEF3E8'],
    'Transportation'    => ['cat' => 'Transport',    'icon' => 'fa-car',          'color' => '#1565C0', 'bg' => '#E8F0FE'],
    'Accommodation'     => ['cat' => 'Stay',         'icon' => 'fa-bed',          'color' => '#16A34A', 'bg' => '#F0FDF4'],
    'Tourist Attractions'=>['cat' => 'Attractions',  'icon' => 'fa-ticket',       'color' => '#8B3A10', 'bg' => '#FDF0E8'],
    'Shopping'          => ['cat' => 'Shopping',     'icon' => 'fa-bag-shopping', 'color' => '#7C3AED', 'bg' => '#F5F3FF'],
    'Emergency Funds'   => ['cat' => 'Emergency',   'icon' => 'fa-shield-halved','color' => '#DC2626', 'bg' => '#FEF2F2'],
];

$trip_budgets_rows = [];
if ($active_trip_id) {
    $stmt = $pdo->prepare("SELECT category, estimated_cost FROM trip_budgets WHERE trip_id = ?");
    $stmt->execute([$active_trip_id]);
    foreach ($stmt->fetchAll() as $r) {
        $trip_budgets_rows[$r['category']] = (float)$r['estimated_cost'];
    }
}

$budget_breakdown = [];
foreach ($breakdown_config as $db_cat => $disp) {
    $budgeted_usd = $trip_budgets_rows[$db_cat] ?? null;
    // Skip categories the user hasn't budgeted for in the planner
    if ($budgeted_usd === null) continue;
    $actual_usd = $cat_actual_usd[$db_cat] ?? 0;
    $budget_breakdown[] = [
        'cat'      => $disp['cat'],
        'icon'     => $disp['icon'],
        'color'    => $disp['color'],
        'bg'       => $disp['bg'],
        'budgeted' => (float)$budgeted_usd,
        'actual'   => $actual_usd,
    ];
}

$user_initial = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));

$body_class = 'dashboard-body';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<?php $active_sidebar = 'dashboard'; ?>
<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- ══ MAIN AREA ══════════════════════════════════ -->
    <div class="dash-main">

        <!-- Content -->
        <div class="dash-content">

            <!-- Expense Actions Banner -->
            <div class="ocr-banner">
                <div class="ocr-banner-left">
                    <div class="ocr-banner-icon">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div>
                        <div class="ocr-banner-title">Track Your Spending</div>
                        <div class="ocr-banner-sub">Scan a receipt or log an expense manually to keep your budget up to date.</div>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/src/expenses/create.php" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-camera"></i> Scan Expense
                </a>
            </div>

            <!-- Stats Row -->
            <div class="stats-row">
                <!-- Total Budget -->
                <div class="stat-card">
                    <div class="stat-card-accent" style="background:var(--primary);"></div>
                    <div class="stat-label">
                        <i class="fa-regular fa-calendar" style="color:var(--primary);"></i>
                        Total Budget
                    </div>
                    <div class="stat-value"><?php echo format_currency($total_budget * $cur_rate, $cur_symbol); ?></div>
                    <div class="stat-sub">
                        Allocated for
                        <?php
                        if ($active_trip && !empty($active_trip['start_date']) && !empty($active_trip['end_date'])) {
                            $days = max(1, round((strtotime($active_trip['end_date']) - strtotime($active_trip['start_date'])) / 86400));
                            echo $days . ' Days';
                        } else {
                            echo 'All Trips';
                        }
                        ?>
                    </div>
                    <div class="stat-progress-wrap">
                        <div class="progress">
                            <div class="progress-bar" style="width:100%;background:var(--primary);"></div>
                        </div>
                    </div>
                </div>

                <!-- Amount Spent -->
                <div class="stat-card">
                    <div class="stat-card-accent" style="background:var(--secondary-dark);"></div>
                    <div class="stat-label">
                        <i class="fa-regular fa-credit-card" style="color:var(--secondary-dark);"></i>
                        Amount Spent
                    </div>
                    <div class="stat-value" style="color:var(--secondary-dark);"><?php echo format_currency($total_spent * $cur_rate, $cur_symbol); ?></div>
                    <div class="stat-sub"><?php echo round($pct_spent); ?>% of total budget</div>
                    <div class="stat-progress-wrap">
                        <div class="progress">
                            <div class="progress-bar progress-bar-yellow" style="width:<?php echo $pct_spent; ?>%;"></div>
                        </div>
                    </div>
                </div>

                <!-- Remaining -->
                <div class="stat-card">
                    <div class="stat-card-accent" style="background:var(--tertiary);"></div>
                    <div class="stat-label">
                        <i class="fa-solid fa-landmark" style="color:var(--tertiary);"></i>
                        Remaining Funds
                    </div>
                    <div class="stat-value" style="color:var(--tertiary);"><?php echo format_currency($remaining * $cur_rate, $cur_symbol); ?></div>
                    <div class="stat-sub">
                        <?php if ($daily_remaining > 0): ?>
                            Est. <?php echo format_currency($daily_remaining * $cur_rate, $cur_symbol); ?> / Day
                        <?php else: ?>
                            Stay on budget!
                        <?php endif; ?>
                    </div>
                    <div class="stat-progress-wrap">
                        <div class="progress">
                            <div class="progress-bar progress-bar-blue"
                                 style="width:<?php echo max(0, 100 - $pct_spent); ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
                <!-- Spending by Category -->
                <div class="chart-card">
                    <div class="chart-card-header">
                        <span class="chart-card-title">Spending by Category</span>
                        <span class="chart-card-action">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </span>
                    </div>
                    <?php if ($has_category_data): ?>
                    <div style="position:relative;height:180px;display:flex;align-items:center;justify-content:center;">
                        <canvas id="categoryChart" style="max-height:180px;"></canvas>
                        <div style="position:absolute;text-align:center;pointer-events:none;">
                            <div style="font-size:11px;color:var(--muted);">TOP</div>
                            <div style="font-size:14px;font-weight:700;color:var(--primary);">
                                <?php echo htmlspecialchars($top_category_label); ?>
                            </div>
                        </div>
                    </div>
                    <div class="chart-legend" id="cat-legend">
                        <?php foreach ($chart_cat_labels as $i => $lbl): ?>
                        <div class="chart-legend-item">
                            <div class="legend-dot" style="background:<?php echo $chart_cat_colors_arr[$i]; ?>;"></div>
                            <?php echo htmlspecialchars($lbl); ?> (<?php echo $chart_cat_percents[$i]; ?>%)
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="cat-empty-state">
                        <div class="cat-empty-ring"></div>
                        <div class="cat-empty-text">No spending recorded yet</div>
                        <div class="cat-empty-sub">
                            <a href="<?php echo BASE_URL; ?>/src/expenses/create.php" style="color:var(--primary);font-weight:600;text-decoration:none;">
                                Add your first expense →
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Trend Over Time -->
                <div class="chart-card">
                    <div class="chart-card-header">
                        <span class="chart-card-title">Trend Over Time</span>
                        <div class="trend-dropdown-wrap">
                            <button class="trend-dropdown-btn" id="trendDropdownBtn" onclick="toggleTrendDropdown()">
                                Last 7 Days <i class="fa-solid fa-chevron-down" style="font-size:10px;margin-left:4px;"></i>
                            </button>
                            <div class="trend-dropdown-menu" id="trendDropdownMenu">
                                <div class="trend-dropdown-item active" onclick="setTrendPeriod('7d', this)">Last 7 Days</div>
                                <div class="trend-dropdown-item" onclick="setTrendPeriod('30d', this)">Last 30 Days</div>
                                <div class="trend-dropdown-item" onclick="setTrendPeriod('12m', this)">Last 12 Months</div>
                            </div>
                        </div>
                    </div>
                    <canvas id="trendChart" style="max-height:200px;"></canvas>
                </div>
            </div>

            <!-- Recent Expenses + Budget Breakdown -->
            <div class="dash-bottom-grid">
                <!-- Budget Breakdown -->
                <div class="chart-card">
                    <div class="chart-card-header">
                        <span class="chart-card-title">Budget Breakdown</span>
                        <a href="<?php echo BASE_URL; ?>/src/expenses/index.php"
                           style="font-size:13px;color:var(--primary);font-weight:600;text-decoration:none;">
                            Export CSV
                        </a>
                    </div>
                    <?php if (empty($budget_breakdown)): ?>
                        <div style="padding:32px 0;text-align:center;color:var(--muted);">
                            <i class="fa-solid fa-map-location-dot" style="font-size:28px;opacity:.3;display:block;margin-bottom:10px;"></i>
                            <div style="font-size:14px;font-weight:600;margin-bottom:4px;">No budget set yet</div>
                            <div style="font-size:13px;margin-bottom:16px;">Use the Planner to estimate costs and set your category budgets.</div>
                            <a href="<?php echo BASE_URL; ?>/src/trips/type.php"
                               style="font-size:13px;font-weight:600;color:var(--primary);text-decoration:none;">
                                Go to Planner →
                            </a>
                        </div>
                    <?php else: ?>
                    <table class="budget-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Budgeted</th>
                                <th>Actual</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($budget_breakdown as $row): ?>
                                <?php
                                $pct = $row['budgeted'] > 0 ? min(100, ($row['actual'] / $row['budgeted']) * 100) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="budget-cat-icon" style="background:<?php echo $row['bg']; ?>;color:<?php echo $row['color']; ?>;">
                                            <i class="fa-solid <?php echo $row['icon']; ?>"></i>
                                        </span>
                                        <span class="budget-cat-name"><?php echo $row['cat']; ?></span>
                                    </td>
                                    <td><?php echo format_currency($row['budgeted'] * $cur_rate, $cur_symbol); ?></td>
                                    <td><?php echo format_currency($row['actual'] * $cur_rate, $cur_symbol); ?></td>
                                    <td style="width:140px;">
                                        <div class="progress">
                                            <div class="progress-bar"
                                                 style="width:<?php echo $pct; ?>%;background:<?php echo $row['color']; ?>;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <!-- Recent Expenses -->
                <div class="chart-card">
                    <div class="chart-card-header" style="margin-bottom:4px;">
                        <span class="chart-card-title">Recent Expenses</span>
                        <a href="<?php echo BASE_URL; ?>/src/expenses/index.php"
                           style="font-size:13px;color:var(--primary);font-weight:600;text-decoration:none;">
                            View All
                        </a>
                    </div>

                    <?php if (empty($recent_expenses)): ?>
                        <div style="padding:24px 0;text-align:center;color:var(--muted);font-size:14px;">
                            No expenses yet.
                            <a href="<?php echo BASE_URL; ?>/src/expenses/create.php" style="color:var(--primary);">Add one →</a>
                        </div>
                    <?php else: ?>
                        <?php
                        $cat_icons = [
                            'Dining'    => ['icon' => 'fa-utensils', 'bg' => '#FEF3E8', 'color' => '#E07B2A'],
                            'Transport' => ['icon' => 'fa-car',      'bg' => '#E8F0FE', 'color' => '#1565C0'],
                            'Stay'      => ['icon' => 'fa-bed',      'bg' => '#F0FDF4', 'color' => '#16A34A'],
                            'Shopping'  => ['icon' => 'fa-bag-shopping','bg'=>'#F5F3FF','color'=>'#7C3AED'],
                            'Other'     => ['icon' => 'fa-star',     'bg' => '#FDF0E8', 'color' => '#8B3A10'],
                        ];
                        ?>
                        <?php foreach ($recent_expenses as $exp): ?>
                            <?php
                            $cat = $exp['category'] ?? 'Other';
                            $ci  = $cat_icons[$cat] ?? $cat_icons['Other'];
                            ?>
                            <div class="expense-row">
                                <div class="expense-row-icon" style="background:<?php echo $ci['bg']; ?>;color:<?php echo $ci['color']; ?>;">
                                    <i class="fa-solid <?php echo $ci['icon']; ?>"></i>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <div class="expense-row-name"><?php echo htmlspecialchars($exp['description'] ?? $cat); ?></div>
                                    <div class="expense-row-time">
                                        <?php echo $exp['destination'] ?? ''; ?> ·
                                        <?php echo date('M d, g:i A', strtotime($exp['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="expense-row-amt" style="color:var(--danger);">-<?php echo format_currency((float)$exp['amount'] * $cur_rate, $cur_symbol); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /dash-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

<script>
// ── Category Donut Chart ──────────────────────────────
<?php if ($has_category_data): ?>
var catCtx = document.getElementById('categoryChart').getContext('2d');
var categoryChart = new Chart(catCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($chart_cat_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($chart_cat_percents); ?>,
            backgroundColor: <?php echo json_encode($chart_cat_colors_arr); ?>,
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        cutout: '72%',
        plugins: { legend: { display: false }, tooltip: { callbacks: {
            label: function(c) { return c.label + ': ' + c.raw + '%'; }
        }}},
        responsive: true, maintainAspectRatio: false
    }
});
<?php endif; ?>

// ── Trend Over Time Chart ─────────────────────────────
var trendDatasets = {
    '7d':  { labels: <?php echo json_encode($trend7['labels']); ?>,  data: <?php echo json_encode($trend7['data']); ?> },
    '30d': { labels: <?php echo json_encode($trend30['labels']); ?>, data: <?php echo json_encode($trend30['data']); ?> },
    '12m': { labels: <?php echo json_encode($trend12['labels']); ?>, data: <?php echo json_encode($trend12['data']); ?> }
};

function makeTrendBg(data) {
    var max = Math.max.apply(null, data);
    return data.map(function(v) { return (v === max && max > 0) ? '#8B3A10' : '#E5E7EB'; });
}

var trendCtx = document.getElementById('trendChart').getContext('2d');
var trendChart = new Chart(trendCtx, {
    type: 'bar',
    data: {
        labels: trendDatasets['7d'].labels,
        datasets: [{
            data: trendDatasets['7d'].data,
            backgroundColor: makeTrendBg(trendDatasets['7d'].data),
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#6B7280', maxTicksLimit: 8 } },
            y: { grid: { color: '#F3F4F6' }, ticks: { font: { size: 11 }, color: '#6B7280' }, beginAtZero: true }
        },
        responsive: true, maintainAspectRatio: false
    }
});

function toggleTrendDropdown() {
    document.getElementById('trendDropdownMenu').classList.toggle('open');
}

function setTrendPeriod(period, el) {
    var ds = trendDatasets[period];
    trendChart.data.labels = ds.labels;
    trendChart.data.datasets[0].data = ds.data;
    trendChart.data.datasets[0].backgroundColor = makeTrendBg(ds.data);
    trendChart.update();

    var labels = {'7d': 'Last 7 Days', '30d': 'Last 30 Days', '12m': 'Last 12 Months'};
    document.getElementById('trendDropdownBtn').innerHTML =
        labels[period] + ' <i class="fa-solid fa-chevron-down" style="font-size:10px;margin-left:4px;"></i>';
    document.querySelectorAll('.trend-dropdown-item').forEach(function(i) { i.classList.remove('active'); });
    el.classList.add('active');
    document.getElementById('trendDropdownMenu').classList.remove('open');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.trend-dropdown-wrap')) {
        document.getElementById('trendDropdownMenu').classList.remove('open');
    }
});
</script>

</body>
</html>
