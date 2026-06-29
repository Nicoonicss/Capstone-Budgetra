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

$stmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY start_date DESC");
$stmt->execute([$user_id]);
$all_trips_raw = $stmt->fetchAll();

$today        = date('Y-m-d');
$active_trips = [];
$past_trips   = [];
$all_trips    = [];

foreach ($all_trips_raw as $trip) {
    $s = $pdo->prepare("SELECT SUM(amount) as t FROM expenses WHERE trip_id = ?");
    $s->execute([$trip['id']]);
    $trip['spent_usd']  = (float)($s->fetch()['t'] ?? 0);
    $trip['budget_usd'] = (float)($trip['budget_limit'] ?? 0);
    $trip['pct']        = $trip['budget_usd'] > 0
        ? min(200, round($trip['spent_usd'] / $trip['budget_usd'] * 100, 1)) : 0;
    $trip['over_budget'] = $trip['budget_usd'] > 0 && $trip['spent_usd'] > $trip['budget_usd'];

    $c = $pdo->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE trip_id = ? GROUP BY category");
    $c->execute([$trip['id']]);
    $trip['cats'] = [];
    foreach ($c->fetchAll() as $cr) $trip['cats'][$cr['category']] = round((float)$cr['total'], 4);

    $g = $pdo->prepare("SELECT id FROM savings_goals WHERE trip_id = ? LIMIT 1");
    $g->execute([$trip['id']]);
    $trip['goal_id'] = $g->fetchColumn() ?: 0;

    $s2 = new DateTime($trip['start_date']);
    $e2 = new DateTime($trip['end_date']);
    $trip['days'] = max(1, (int)$s2->diff($e2)->days + 1);

    $ic = $pdo->prepare("SELECT COUNT(*) FROM itinerary WHERE trip_id = ?");
    $ic->execute([$trip['id']]);
    $trip['itinerary_count'] = (int)$ic->fetchColumn();

    if ($trip['end_date'] >= $today) {
        $trip['status'] = $trip['start_date'] <= $today ? 'ongoing' : 'upcoming';
        $active_trips[] = $trip;
    } else {
        $diff = $trip['budget_usd'] - $trip['spent_usd'];
        $trip['status'] = $trip['over_budget'] ? 'over'
            : ($trip['budget_usd'] > 0 && $diff > $trip['budget_usd'] * 0.04 ? 'saved' : 'on');
        $past_trips[] = $trip;
    }
    $all_trips[] = $trip;
}

function mth_gradient(string $dest): string {
    $d = strtolower($dest);
    if (preg_match('/europe|paris|rome|spain|italy|greece|madrid|barcelona|london|berlin|amsterdam|amalfi|santorini/', $d))
        return 'linear-gradient(145deg,#1a3a5c,#1565C0)';
    if (preg_match('/japan|tokyo|korea|seoul|kyoto|osaka/', $d))
        return 'linear-gradient(145deg,#5c0f0f,#c0392b)';
    if (preg_match('/bali|thailand|vietnam|singapore|malaysia|phuket|cebu|palawan|boracay|philippines|manila|bohol/', $d))
        return 'linear-gradient(145deg,#0f3d1a,#1e8449)';
    if (preg_match('/new york|usa|america|canada|los angeles|chicago|miami/', $d))
        return 'linear-gradient(145deg,#1a0f3d,#6c3483)';
    if (preg_match('/dubai|abu dhabi|qatar|doha|uae|saudi/', $d))
        return 'linear-gradient(145deg,#3d2700,#b7860b)';
    if (preg_match('/swiss|switzerland|alps|austria/', $d))
        return 'linear-gradient(145deg,#0f3d2a,#1a7a56)';
    if (preg_match('/africa|kenya|safari|egypt|morocco/', $d))
        return 'linear-gradient(145deg,#3d2200,#c0771a)';
    return 'linear-gradient(145deg,#2c2c3e,#4a4a6a)';
}
function mth_emoji(string $dest): string {
    $d = strtolower($dest);
    if (preg_match('/europe|paris|rome|spain|italy|greece/', $d)) return '🏛️';
    if (preg_match('/japan|tokyo|korea|seoul/', $d)) return '⛩️';
    if (preg_match('/bali|thailand|vietnam|singapore|philippines|cebu|palawan|boracay|bohol/', $d)) return '🌴';
    if (preg_match('/new york|usa|america|canada/', $d)) return '🗽';
    if (preg_match('/dubai|abu dhabi|uae|qatar/', $d)) return '🌆';
    if (preg_match('/swiss|switzerland|alps|austria/', $d)) return '🏔️';
    if (preg_match('/africa|kenya|safari|egypt/', $d)) return '🦁';
    return '✈️';
}
function mth_shortname(string $dest): string {
    if (str_contains($dest, ',')) return trim(explode(',', $dest, 2)[0]);
    return $dest;
}

$total_budget_usd = array_sum(array_column($all_trips, 'budget_usd'));
$total_spent_usd  = array_sum(array_column($all_trips, 'spent_usd'));

$js_trips = [];
foreach ($all_trips as $t) {
    $js_trips[] = [
        'id'              => (int)$t['id'],
        'destination'     => $t['destination'],
        'name'            => mth_shortname($t['destination']),
        'emoji'           => mth_emoji($t['destination']),
        'gradient'        => mth_gradient($t['destination']),
        'start_date'      => $t['start_date'],
        'end_date'        => $t['end_date'],
        'days'            => $t['days'],
        'budget_usd'      => $t['budget_usd'],
        'spent_usd'       => $t['spent_usd'],
        'pct'             => $t['pct'],
        'status'          => $t['status'],
        'cats'            => $t['cats'],
        'group_type'      => $t['group_type'] ?? '',
        'travelers'       => (int)($t['travelers'] ?? 1),
        'budget_tier'     => $t['budget_tier'] ?? '',
        'goal_id'         => (int)$t['goal_id'],
        'itinerary_count' => $t['itinerary_count'],
    ];
}

$active_sidebar = 'trips';
$body_class     = 'dashboard-body';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="dash-main">
    <div class="dash-content" style="padding-bottom:90px;display:flex;flex-direction:column;">

    <!-- ── Page Header ─────────────────────────────────── -->
    <?php if (!empty($all_trips)): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
        <div>
            <h1 style="font-size:26px;font-weight:800;margin:0 0 4px;">Multi-Trip Hub</h1>
            <div style="font-size:13px;color:var(--muted);">
                <?php echo count($all_trips); ?> trip<?php echo count($all_trips) !== 1 ? 's' : ''; ?> total
            </div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <div style="position:relative;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:12px;pointer-events:none;"></i>
                <input type="text" id="tripSearch" placeholder="Search trips..."
                       oninput="filterTrips(this.value)"
                       style="padding:9px 14px 9px 33px;border:1.5px solid var(--border);border-radius:8px;
                              font-size:14px;font-family:inherit;background:#fff;outline:none;width:190px;
                              color:var(--text);transition:border-color .2s;"
                       onfocus="this.style.borderColor='var(--primary)'"
                       onblur="this.style.borderColor='var(--border)'">
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($all_trips)): ?>

    <!-- ── Summary Stats ───────────────────────────────── -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:28px;">
        <div class="chart-card" style="padding:18px 20px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:42px;height:42px;border-radius:10px;background:#FDF0E8;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">✈️</div>
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);">Total Trips</div>
                    <div style="font-size:26px;font-weight:800;color:var(--dark);line-height:1.1;"><?php echo count($all_trips); ?></div>
                    <div style="font-size:12px;color:var(--muted);"><?php echo count($active_trips); ?> active · <?php echo count($past_trips); ?> past</div>
                </div>
            </div>
        </div>
        <div class="chart-card" style="padding:18px 20px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:42px;height:42px;border-radius:10px;background:#F0FDF4;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">💰</div>
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);">Total Budget</div>
                    <div style="font-size:20px;font-weight:800;color:var(--dark);line-height:1.1;"><?php echo format_currency($total_budget_usd * $cur_rate, $cur_symbol); ?></div>
                    <div style="font-size:12px;color:var(--muted);">across all trips</div>
                </div>
            </div>
        </div>
        <div class="chart-card" style="padding:18px 20px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:42px;height:42px;border-radius:10px;background:#EFF6FF;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">📊</div>
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);">Total Spent</div>
                    <div style="font-size:20px;font-weight:800;color:var(--dark);line-height:1.1;"><?php echo format_currency($total_spent_usd * $cur_rate, $cur_symbol); ?></div>
                    <div style="font-size:12px;color:var(--muted);">
                        <?php echo $total_budget_usd > 0 ? round($total_spent_usd / $total_budget_usd * 100) . '% of budget' : 'no budget set'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Active Trips ─────────────────────────────────── -->
    <?php if (!empty($active_trips)): ?>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
        <span style="width:8px;height:8px;border-radius:50%;background:#E07B2A;display:inline-block;"></span>
        <span style="font-size:13px;font-weight:700;color:var(--dark);">Active Trips</span>
        <span style="font-size:12px;background:#FDF0E8;color:var(--primary);font-weight:700;padding:1px 9px;border-radius:99px;"><?php echo count($active_trips); ?></span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(255px,1fr));gap:16px;margin-bottom:32px;" id="activeGrid">
        <?php foreach ($active_trips as $t):
            $name  = mth_shortname($t['destination']);
            $grad  = mth_gradient($t['destination']);
            $emoji = mth_emoji($t['destination']);
            $fill  = $t['over_budget'] ? '#DC2626' : ($t['pct'] > 80 ? '#E07B2A' : 'var(--primary)');
            $badge_bg    = $t['status'] === 'ongoing' ? '#D1FAE5' : '#FEF3C7';
            $badge_color = $t['status'] === 'ongoing' ? '#065F46' : '#92400E';
        ?>
        <div class="chart-card" style="padding:0;overflow:hidden;"
             data-name="<?php echo htmlspecialchars(strtolower($t['destination'])); ?>">
            <div style="background:<?php echo $grad; ?>;padding:22px 18px 18px;position:relative;min-height:108px;display:flex;align-items:flex-end;">
                <span style="font-size:38px;line-height:1;position:absolute;top:14px;right:14px;opacity:.9;"><?php echo $emoji; ?></span>
                <span style="font-size:10px;font-weight:700;letter-spacing:.5px;padding:3px 9px;border-radius:99px;background:<?php echo $badge_bg; ?>;color:<?php echo $badge_color; ?>;">
                    <?php echo strtoupper($t['status']); ?>
                </span>
                <button data-compare-btn="<?php echo $t['id']; ?>"
                        onclick="toggleCompare(<?php echo $t['id']; ?>)"
                        style="position:absolute;top:12px;left:14px;padding:3px 10px;border-radius:99px;
                               border:1.5px solid rgba(255,255,255,.7);background:rgba(255,255,255,.15);
                               color:#fff;font-size:11px;font-weight:700;cursor:pointer;backdrop-filter:blur(4px);">
                    Compare
                </button>
            </div>
            <div style="padding:14px 16px 10px;">
                <div style="font-size:16px;font-weight:800;margin-bottom:2px;"><?php echo htmlspecialchars($name); ?></div>
                <div style="font-size:12px;color:var(--muted);margin-bottom:12px;">
                    <i class="fa-regular fa-calendar" style="font-size:10px;"></i>
                    <?php echo date('M d', strtotime($t['start_date'])); ?> – <?php echo date('M d, Y', strtotime($t['end_date'])); ?>
                    · <?php echo $t['days']; ?>d
                </div>
                <?php if ($t['budget_usd'] > 0): ?>
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                    <span style="color:var(--muted);">Spent</span>
                    <span style="font-weight:700;color:<?php echo $fill; ?>;">
                        <?php echo format_currency($t['spent_usd'] * $cur_rate, $cur_symbol); ?>
                        <span style="font-weight:400;color:var(--muted);">/ <?php echo format_currency($t['budget_usd'] * $cur_rate, $cur_symbol); ?></span>
                    </span>
                </div>
                <div style="height:5px;background:#F3F4F6;border-radius:99px;overflow:hidden;margin-bottom:6px;">
                    <div style="height:100%;width:<?php echo min(100,$t['pct']); ?>%;background:<?php echo $fill; ?>;border-radius:99px;"></div>
                </div>
                <div style="font-size:11px;color:var(--muted);text-align:right;margin-bottom:4px;"><?php echo $t['pct']; ?>% used</div>
                <?php else: ?>
                <div style="font-size:12px;color:var(--muted);padding:6px 0 4px;">No budget set</div>
                <?php endif; ?>
            </div>
            <div style="padding:10px 16px 14px;border-top:1px solid var(--border);display:flex;gap:8px;">
                <button onclick="openDetails(<?php echo $t['id']; ?>)"
                        style="flex:1;padding:8px 0;border-radius:7px;border:1.5px solid var(--border);
                               font-size:12px;font-weight:700;cursor:pointer;background:#fff;color:var(--dark);transition:background .15s;"
                        onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='#fff'">
                    <i class="fa-solid fa-eye" style="font-size:11px;"></i> Details
                </button>
                <a href="<?php echo BASE_URL; ?>/src/expenses/index.php?trip_id=<?php echo $t['id']; ?>"
                   style="flex:1;padding:8px 0;border-radius:7px;text-align:center;
                          font-size:12px;font-weight:700;text-decoration:none;background:var(--primary);color:#fff;">
                    <i class="fa-solid fa-receipt" style="font-size:11px;"></i> Expenses
                </a>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
    <?php endif; ?>

    <!-- ── Past Trips ───────────────────────────────────── -->
    <?php if (!empty($past_trips)): ?>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
        <i class="fa-regular fa-clock" style="font-size:12px;color:var(--muted);"></i>
        <span style="font-size:13px;font-weight:700;color:var(--dark);">Past Trips</span>
        <span style="font-size:12px;background:#F3F4F6;color:var(--muted);font-weight:700;padding:1px 9px;border-radius:99px;"><?php echo count($past_trips); ?></span>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;" id="pastGrid">
        <?php foreach ($past_trips as $t):
            $name  = mth_shortname($t['destination']);
            $grad  = mth_gradient($t['destination']);
            $emoji = mth_emoji($t['destination']);
            $sc = [
                'saved' => ['#D1FAE5','#065F46','Saved Under Budget'],
                'on'    => ['#F3F4F6','#6B7280','On Budget'],
                'over'  => ['#FEE2E2','#DC2626','Over Budget'],
            ][$t['status']] ?? ['#F3F4F6','#6B7280','Completed'];
        ?>
        <div class="chart-card" style="padding:0;overflow:hidden;"
             data-name="<?php echo htmlspecialchars(strtolower($t['destination'])); ?>">
            <div style="display:flex;align-items:stretch;">
                <div style="background:<?php echo $grad; ?>;width:70px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:26px;">
                    <?php echo $emoji; ?>
                </div>
                <div style="flex:1;min-width:0;padding:13px 16px;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
                        <span style="font-size:15px;font-weight:800;"><?php echo htmlspecialchars($name); ?></span>
                        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:<?php echo $sc[0]; ?>;color:<?php echo $sc[1]; ?>;">
                            <?php echo $sc[2]; ?>
                        </span>
                    </div>
                    <div style="font-size:12px;color:var(--muted);margin-bottom:7px;">
                        <?php echo date('M d, Y', strtotime($t['start_date'])); ?> – <?php echo date('M d, Y', strtotime($t['end_date'])); ?>
                        · <?php echo $t['days']; ?> days
                    </div>
                    <div style="display:flex;align-items:center;gap:18px;font-size:13px;flex-wrap:wrap;">
                        <?php if ($t['budget_usd'] > 0): ?>
                        <span><span style="color:var(--muted);">Budget </span><strong><?php echo format_currency($t['budget_usd'] * $cur_rate, $cur_symbol); ?></strong></span>
                        <?php endif; ?>
                        <span><span style="color:var(--muted);">Spent </span><strong><?php echo format_currency($t['spent_usd'] * $cur_rate, $cur_symbol); ?></strong></span>
                        <?php if ($t['budget_usd'] > 0): ?>
                        <span style="color:var(--muted);"><?php echo $t['pct']; ?>% used</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="padding:13px 14px;display:flex;flex-direction:column;gap:7px;flex-shrink:0;justify-content:center;">
                    <button onclick="openDetails(<?php echo $t['id']; ?>)"
                            style="padding:7px 13px;border-radius:7px;border:1.5px solid var(--border);
                                   background:#fff;font-size:12px;font-weight:700;cursor:pointer;
                                   color:var(--dark);white-space:nowrap;transition:background .15s;"
                            onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='#fff'">
                        <i class="fa-solid fa-eye" style="font-size:11px;"></i> View Details
                    </button>
                    <button data-compare-btn="<?php echo $t['id']; ?>"
                            onclick="toggleCompare(<?php echo $t['id']; ?>)"
                            style="padding:7px 13px;border-radius:7px;border:1.5px solid var(--primary);
                                   background:#fff;font-size:12px;font-weight:700;cursor:pointer;
                                   color:var(--primary);white-space:nowrap;transition:all .15s;">
                        Compare
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                flex:1;text-align:center;padding:40px 24px;">
        <div style="width:72px;height:72px;border-radius:20px;background:#FDF0E8;
                    display:flex;align-items:center;justify-content:center;
                    font-size:30px;margin-bottom:20px;">
            <i class="fa-solid fa-earth-asia" style="color:var(--primary);"></i>
        </div>
        <h2 style="font-size:22px;font-weight:800;margin:0 0 8px;">No trips yet</h2>
        <p style="color:var(--muted);font-size:14px;max-width:300px;margin:0 0 24px;">
            Start planning your next adventure and track every expense.
        </p>
        <a href="<?php echo BASE_URL; ?>/src/trips/type.php" class="btn btn-primary">
            <i class="fa-solid fa-plane"></i> Plan a Trip
        </a>
    </div>
    <?php endif; ?>

    </div><!-- /dash-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

<!-- ── Compare Selection Bar ─────────────────────────── -->
<div id="compareBar" style="position:fixed;bottom:0;left:240px;right:0;z-index:200;
     transform:translateY(100%);transition:transform .28s cubic-bezier(.4,0,.2,1);
     background:#fff;border-top:2px solid var(--primary);
     box-shadow:0 -4px 24px rgba(0,0,0,.12);padding:14px 32px;
     display:flex;align-items:center;gap:16px;">
    <div style="flex:1;min-width:0;">
        <div id="compareBarText" style="font-size:14px;font-weight:800;color:var(--dark);">1 trip selected</div>
        <div id="compareBarSub" style="font-size:12px;color:var(--muted);">Select one more trip to compare</div>
    </div>
    <button id="compareNowBtn" onclick="openCompareModal()"
            style="display:none;padding:10px 24px;border-radius:8px;border:none;
                   background:var(--primary);color:#fff;font-size:14px;font-weight:700;cursor:pointer;">
        Compare Now
    </button>
    <button onclick="clearCompare()"
            style="padding:10px 18px;border-radius:8px;border:1.5px solid var(--border);
                   background:#fff;font-size:14px;font-weight:600;cursor:pointer;color:var(--muted);">
        Cancel
    </button>
</div>

<!-- ── Details Modal ─────────────────────────────────── -->
<div id="detailsModal" onclick="if(event.target===this)closeDetails()"
     style="display:none;position:fixed;top:0;bottom:0;left:240px;right:0;background:rgba(0,0,0,.5);z-index:300;
            align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:20px;max-width:520px;width:100%;
                max-height:90vh;overflow-y:auto;position:relative;">
        <div id="detailsContent"></div>
    </div>
</div>

<!-- ── Compare Modal ─────────────────────────────────── -->
<div id="compareModal" onclick="if(event.target===this)closeCompare()"
     style="display:none;position:fixed;top:0;bottom:0;left:240px;right:0;background:rgba(0,0,0,.5);z-index:300;
            align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:20px;max-width:780px;width:100%;
                max-height:92vh;overflow-y:auto;position:relative;">
        <div id="compareContent"></div>
    </div>
</div>

<script>
var BASE_URL   = '<?php echo BASE_URL; ?>';
var CUR_RATE   = <?php echo $cur_rate; ?>;
var CUR_SYMBOL = '<?php echo addslashes($cur_symbol); ?>';
var TRIPS      = <?php echo json_encode($js_trips, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP); ?>;
var compareState = [];

function fmt(usd) {
    var local = usd * CUR_RATE;
    return CUR_SYMBOL + local.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function fmtDate(s) {
    if (!s) return '';
    var m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var d = new Date(s + 'T00:00:00');
    return m[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
}
function escH(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function getTrip(id) {
    return TRIPS.find(function(t){ return t.id === id; });
}

// ── Search ────────────────────────────────────────────
function filterTrips(q) {
    q = q.toLowerCase();
    document.querySelectorAll('[data-name]').forEach(function(el) {
        el.style.display = (!q || el.dataset.name.includes(q)) ? '' : 'none';
    });
}

// ── Compare ───────────────────────────────────────────
function toggleCompare(tripId) {
    var idx = compareState.indexOf(tripId);
    if (idx !== -1) {
        compareState.splice(idx, 1);
    } else if (compareState.length < 2) {
        compareState.push(tripId);
        if (compareState.length === 2) openCompareModal();
    }
    updateCompareUI();
}

function clearCompare() {
    compareState = [];
    updateCompareUI();
}

function updateCompareUI() {
    document.querySelectorAll('[data-compare-btn]').forEach(function(btn) {
        var id = parseInt(btn.dataset.compareBtn);
        var sel = compareState.indexOf(id) !== -1;
        btn.style.background = sel ? 'var(--primary)' : '';
        btn.style.color      = sel ? '#fff' : '';
        btn.style.borderColor = sel ? 'var(--primary)' : '';
        btn.textContent = sel ? '✓ Selected' : 'Compare';
    });
    var bar     = document.getElementById('compareBar');
    var barText = document.getElementById('compareBarText');
    var barSub  = document.getElementById('compareBarSub');
    var nowBtn  = document.getElementById('compareNowBtn');
    if (compareState.length === 0) {
        bar.style.transform = 'translateY(100%)';
    } else {
        bar.style.transform = 'translateY(0)';
        if (compareState.length === 1) {
            var t = getTrip(compareState[0]);
            barText.textContent = escH(t ? t.name : '') + ' selected';
            barSub.textContent  = 'Select one more trip to compare';
            nowBtn.style.display = 'none';
        } else {
            var t1 = getTrip(compareState[0]), t2 = getTrip(compareState[1]);
            barText.textContent  = escH(t1.name) + ' vs ' + escH(t2.name);
            barSub.textContent   = 'Ready to compare';
            nowBtn.style.display = 'block';
        }
    }
}

function openCompareModal() {
    if (compareState.length < 2) return;
    var t1 = getTrip(compareState[0]), t2 = getTrip(compareState[1]);
    document.getElementById('compareContent').innerHTML = buildCompareHTML(t1, t2);
    document.getElementById('compareModal').style.display = 'flex';
}
function closeCompare() {
    document.getElementById('compareModal').style.display = 'none';
}

function buildCompareHTML(t1, t2) {
    var maxSpent  = Math.max(t1.spent_usd,  t2.spent_usd,  0.01);
    var maxBudget = Math.max(t1.budget_usd, t2.budget_usd, 0.01);
    var da1 = t1.days > 0 ? t1.spent_usd / t1.days : 0;
    var da2 = t2.days > 0 ? t2.spent_usd / t2.days : 0;

    function win(v1, v2, lowerBetter) {
        if (v1 === 0 && v2 === 0) return [0,0];
        if (v1 === 0) return [0,1];
        if (v2 === 0) return [1,0];
        if (lowerBetter) return v1 < v2 ? [1,0] : (v2 < v1 ? [0,1] : [0,0]);
        return v1 > v2 ? [1,0] : (v2 > v1 ? [0,1] : [0,0]);
    }
    function badge(isWinner) {
        return isWinner
            ? '<span style="font-size:10px;background:#D1FAE5;color:#065F46;padding:1px 6px;border-radius:4px;font-weight:700;margin-left:4px;">BETTER</span>'
            : '';
    }

    var wSpent  = win(t1.spent_usd, t2.spent_usd, true);
    var wPct    = win(t1.pct, t2.pct, true);
    var wDaily  = win(da1, da2, true);
    var wDays   = win(t1.days, t2.days, false);

    // Category rows
    var allCats = {};
    Object.keys(t1.cats).forEach(function(c){ allCats[c]=1; });
    Object.keys(t2.cats).forEach(function(c){ allCats[c]=1; });
    var catHTML = '';
    Object.keys(allCats).forEach(function(cat) {
        var a1 = t1.cats[cat] || 0, a2 = t2.cats[cat] || 0;
        var mx = Math.max(a1, a2, 0.01);
        var wc = win(a1, a2, true);
        catHTML += '<tr style="border-top:1px solid #F3F4F6;">' +
            '<td style="padding:8px 0 8px 4px;font-size:12px;color:var(--muted);white-space:nowrap;">' + escH(cat) + '</td>' +
            '<td style="padding:8px 12px;">' +
              '<div style="font-size:13px;font-weight:700;text-align:right;">' + fmt(a1) + badge(wc[0]) + '</div>' +
              '<div style="height:4px;background:#F3F4F6;border-radius:99px;margin-top:3px;">' +
              '<div style="height:100%;width:' + (a1/mx*100).toFixed(1) + '%;background:var(--primary);border-radius:99px;"></div></div>' +
            '</td>' +
            '<td style="padding:8px 12px;">' +
              '<div style="font-size:13px;font-weight:700;text-align:right;">' + fmt(a2) + badge(wc[1]) + '</div>' +
              '<div style="height:4px;background:#F3F4F6;border-radius:99px;margin-top:3px;">' +
              '<div style="height:100%;width:' + (a2/mx*100).toFixed(1) + '%;background:#1565C0;border-radius:99px;"></div></div>' +
            '</td>' +
            '</tr>';
    });
    if (!catHTML) catHTML = '<tr><td colspan="3" style="padding:12px;text-align:center;color:var(--muted);font-size:13px;">No expense data recorded</td></tr>';

    function metricRow(label, v1html, v2html) {
        return '<tr style="border-top:1px solid #F3F4F6;">' +
            '<td style="padding:11px 4px;font-size:12px;color:var(--muted);white-space:nowrap;">' + label + '</td>' +
            '<td style="padding:11px 12px;text-align:right;">' + v1html + '</td>' +
            '<td style="padding:11px 12px;text-align:right;">' + v2html + '</td>' +
            '</tr>';
    }

    return '<div>' +
        // Header strip
        '<div style="display:flex;border-radius:20px 20px 0 0;overflow:hidden;position:relative;">' +
          '<div style="flex:1;background:' + t1.gradient + ';padding:28px 20px;text-align:center;">' +
            '<div style="font-size:34px;margin-bottom:6px;">' + t1.emoji + '</div>' +
            '<div style="font-size:17px;font-weight:800;color:#fff;">' + escH(t1.name) + '</div>' +
            '<div style="font-size:12px;color:rgba(255,255,255,.75);margin-top:2px;">' + t1.days + ' days</div>' +
          '</div>' +
          '<div style="width:50px;background:#111827;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
            '<span style="color:#fff;font-size:12px;font-weight:800;">VS</span>' +
          '</div>' +
          '<div style="flex:1;background:' + t2.gradient + ';padding:28px 20px;text-align:center;">' +
            '<div style="font-size:34px;margin-bottom:6px;">' + t2.emoji + '</div>' +
            '<div style="font-size:17px;font-weight:800;color:#fff;">' + escH(t2.name) + '</div>' +
            '<div style="font-size:12px;color:rgba(255,255,255,.75);margin-top:2px;">' + t2.days + ' days</div>' +
          '</div>' +
          '<button onclick="closeCompare()" style="position:absolute;top:12px;right:14px;width:30px;height:30px;border-radius:50%;background:rgba(0,0,0,.3);border:none;cursor:pointer;color:#fff;font-size:18px;line-height:1;display:flex;align-items:center;justify-content:center;">×</button>' +
        '</div>' +
        // Body
        '<div style="padding:22px 24px;">' +
          // Legend
          '<div style="display:flex;align-items:center;gap:16px;margin-bottom:16px;font-size:12px;">' +
            '<span style="display:flex;align-items:center;gap:5px;"><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:var(--primary);"></span>' + escH(t1.name) + '</span>' +
            '<span style="display:flex;align-items:center;gap:5px;"><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#1565C0;"></span>' + escH(t2.name) + '</span>' +
          '</div>' +
          // Dates
          '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">' +
            '<div style="background:#F9FAFB;border-radius:8px;padding:10px 12px;text-align:center;">' +
              '<div style="font-size:10px;color:var(--muted);font-weight:700;margin-bottom:2px;">DATES</div>' +
              '<div style="font-size:12px;font-weight:700;">' + fmtDate(t1.start_date) + ' – ' + fmtDate(t1.end_date) + '</div>' +
            '</div>' +
            '<div style="background:#F9FAFB;border-radius:8px;padding:10px 12px;text-align:center;">' +
              '<div style="font-size:10px;color:var(--muted);font-weight:700;margin-bottom:2px;">DATES</div>' +
              '<div style="font-size:12px;font-weight:700;">' + fmtDate(t2.start_date) + ' – ' + fmtDate(t2.end_date) + '</div>' +
            '</div>' +
          '</div>' +
          // Metrics table
          '<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">' +
            '<thead><tr>' +
              '<th style="padding:6px 4px;font-size:11px;color:var(--muted);font-weight:700;text-align:left;text-transform:uppercase;letter-spacing:.5px;">Metric</th>' +
              '<th style="padding:6px 12px;font-size:11px;color:var(--muted);font-weight:700;text-align:right;text-transform:uppercase;">' + escH(t1.name) + '</th>' +
              '<th style="padding:6px 12px;font-size:11px;color:var(--muted);font-weight:700;text-align:right;text-transform:uppercase;">' + escH(t2.name) + '</th>' +
            '</tr></thead><tbody>' +
            metricRow('Budget',
                '<span style="font-size:14px;font-weight:800;">' + (t1.budget_usd > 0 ? fmt(t1.budget_usd) : '—') + '</span>',
                '<span style="font-size:14px;font-weight:800;">' + (t2.budget_usd > 0 ? fmt(t2.budget_usd) : '—') + '</span>'
            ) +
            metricRow('Spent',
                '<div><span style="font-size:14px;font-weight:800;">' + fmt(t1.spent_usd) + '</span>' + badge(wSpent[0]) + '</div>' +
                '<div style="height:4px;background:#F3F4F6;border-radius:99px;margin-top:4px;"><div style="height:100%;width:' + (t1.spent_usd/maxSpent*100).toFixed(1) + '%;background:var(--primary);border-radius:99px;"></div></div>',
                '<div><span style="font-size:14px;font-weight:800;">' + fmt(t2.spent_usd) + '</span>' + badge(wSpent[1]) + '</div>' +
                '<div style="height:4px;background:#F3F4F6;border-radius:99px;margin-top:4px;"><div style="height:100%;width:' + (t2.spent_usd/maxSpent*100).toFixed(1) + '%;background:#1565C0;border-radius:99px;"></div></div>'
            ) +
            (t1.budget_usd > 0 && t2.budget_usd > 0 ? metricRow('Budget Used',
                '<span style="font-size:14px;font-weight:800;">' + t1.pct + '%</span>' + badge(wPct[0]),
                '<span style="font-size:14px;font-weight:800;">' + t2.pct + '%</span>' + badge(wPct[1])
            ) : '') +
            metricRow('Daily Avg',
                '<span style="font-size:14px;font-weight:800;">' + (t1.days > 0 ? fmt(t1.spent_usd/t1.days) : '—') + '</span>' + badge(wDaily[0]),
                '<span style="font-size:14px;font-weight:800;">' + (t2.days > 0 ? fmt(t2.spent_usd/t2.days) : '—') + '</span>' + badge(wDaily[1])
            ) +
            metricRow('Duration',
                '<span style="font-size:14px;font-weight:800;">' + t1.days + ' days</span>' + badge(wDays[0]),
                '<span style="font-size:14px;font-weight:800;">' + t2.days + ' days</span>' + badge(wDays[1])
            ) +
            '</tbody></table>' +
          // Category breakdown
          '<div style="font-size:13px;font-weight:700;margin-bottom:10px;">Spending by Category</div>' +
          '<table style="width:100%;border-collapse:collapse;"><tbody>' + catHTML + '</tbody></table>' +
          // Action
          '<div style="margin-top:20px;text-align:center;">' +
            '<button onclick="clearCompare();closeCompare();" style="padding:10px 24px;border-radius:8px;border:1.5px solid var(--border);background:#fff;font-size:14px;font-weight:600;cursor:pointer;color:var(--muted);">Close</button>' +
          '</div>' +
        '</div>' +
    '</div>';
}

// ── Details ───────────────────────────────────────────
function openDetails(tripId) {
    var t = getTrip(tripId);
    if (!t) return;
    document.getElementById('detailsContent').innerHTML = buildDetailsHTML(t);
    document.getElementById('detailsModal').style.display = 'flex';
}
function closeDetails() {
    document.getElementById('detailsModal').style.display = 'none';
}

function buildDetailsHTML(t) {
    var sl = {
        'upcoming': ['#FEF3C7','#92400E','Upcoming'],
        'ongoing':  ['#D1FAE5','#065F46','Ongoing'],
        'saved':    ['#D1FAE5','#065F46','Saved Under Budget'],
        'on':       ['#F3F4F6','#6B7280','On Budget'],
        'over':     ['#FEE2E2','#DC2626','Over Budget']
    }[t.status] || ['#F3F4F6','#6B7280','Completed'];

    var now = new Date(); now.setHours(0,0,0,0);
    var start = new Date(t.start_date + 'T00:00:00');
    var end   = new Date(t.end_date   + 'T00:00:00');
    var daysLeft = '';
    if (t.status === 'upcoming') {
        var d = Math.round((start - now) / 86400000);
        daysLeft = d + ' day' + (d!==1?'s':'') + ' until departure';
    } else if (t.status === 'ongoing') {
        var d = Math.round((end - now) / 86400000);
        daysLeft = d + ' day' + (d!==1?'s':'') + ' remaining';
    }

    var pctColor = (t.spent_usd > t.budget_usd && t.budget_usd > 0) ? '#DC2626' : (t.pct > 80 ? '#E07B2A' : '#16A34A');

    var catIcons = {'Food':'🍽️','Transportation':'🚗','Accommodation':'🏨','Activities':'🎯','Shopping':'🛍️','Emergency Expenses':'🛡️'};
    var catVals = Object.values(t.cats);
    var catMax = catVals.length > 0 ? Math.max.apply(null, catVals) : 0.01;
    var catHTML = '';
    Object.keys(t.cats).forEach(function(cat) {
        var amt = t.cats[cat];
        var bp  = catMax > 0 ? (amt/catMax*100).toFixed(1) : 0;
        catHTML += '<div style="margin-bottom:10px;">' +
            '<div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:3px;">' +
            '<span>' + (catIcons[cat]||'📌') + ' ' + escH(cat) + '</span>' +
            '<span style="font-weight:700;">' + fmt(amt) + '</span>' +
            '</div>' +
            '<div style="height:5px;background:#F3F4F6;border-radius:99px;overflow:hidden;">' +
            '<div style="height:100%;width:' + bp + '%;background:var(--primary);border-radius:99px;"></div></div>' +
            '</div>';
    });
    if (!catHTML) catHTML = '<div style="font-size:13px;color:var(--muted);padding:8px 0;">No expenses recorded yet.</div>';

    function infoBox(icon, label, val) {
        return '<div style="background:#F9FAFB;border-radius:8px;padding:11px 13px;">' +
            '<div style="font-size:11px;color:var(--muted);margin-bottom:2px;">' + icon + ' ' + label + '</div>' +
            '<div style="font-size:13px;font-weight:700;">' + escH(String(val)) + '</div>' +
            '</div>';
    }

    var travInfo = t.travelers > 0 ? (t.travelers + ' traveler' + (t.travelers!==1?'s':'') + (t.group_type?' · '+t.group_type:'')) : '';
    var tierInfo = t.budget_tier || '';

    return '<div>' +
        // Gradient header
        '<div style="background:' + t.gradient + ';padding:30px 24px 24px;border-radius:20px 20px 0 0;position:relative;">' +
          '<button onclick="closeDetails()" style="position:absolute;top:14px;right:16px;width:30px;height:30px;border-radius:50%;background:rgba(0,0,0,.25);border:none;cursor:pointer;color:#fff;font-size:18px;line-height:1;display:flex;align-items:center;justify-content:center;">×</button>' +
          '<div style="font-size:38px;margin-bottom:8px;">' + t.emoji + '</div>' +
          '<div style="font-size:22px;font-weight:800;color:#fff;margin-bottom:6px;">' + escH(t.destination) + '</div>' +
          '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">' +
            '<span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px;background:' + sl[0] + ';color:' + sl[1] + ';">' + sl[2] + '</span>' +
            (daysLeft ? '<span style="font-size:12px;color:rgba(255,255,255,.8);">' + escH(daysLeft) + '</span>' : '') +
          '</div>' +
        '</div>' +
        // Body
        '<div style="padding:22px 24px;">' +
          // Info grid
          '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:18px;">' +
            infoBox('📅','Start Date', fmtDate(t.start_date)) +
            infoBox('🏁','End Date', fmtDate(t.end_date)) +
            infoBox('⏱️','Duration', t.days + ' days') +
            (travInfo ? infoBox('👥','Group', travInfo) : (tierInfo ? infoBox('💼','Budget Tier', tierInfo) : '')) +
          '</div>' +
          // Budget progress
          (t.budget_usd > 0 ? (
            '<div style="background:#F9FAFB;border-radius:10px;padding:16px 18px;margin-bottom:18px;">' +
            '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">' +
            '<span style="font-size:13px;font-weight:700;">Budget Usage</span>' +
            '<span style="font-size:15px;font-weight:800;color:' + pctColor + ';">' + t.pct + '%</span>' +
            '</div>' +
            '<div style="height:8px;background:#E5E7EB;border-radius:99px;overflow:hidden;margin-bottom:10px;">' +
            '<div style="height:100%;width:' + Math.min(100,t.pct) + '%;background:' + pctColor + ';border-radius:99px;transition:width .5s;"></div></div>' +
            '<div style="display:flex;justify-content:space-between;font-size:12px;">' +
            '<span style="color:var(--muted);">Spent: <strong style="color:var(--dark);">' + fmt(t.spent_usd) + '</strong></span>' +
            '<span style="color:var(--muted);">Budget: <strong style="color:var(--dark);">' + fmt(t.budget_usd) + '</strong></span>' +
            '</div>' +
            (t.days > 0 ? '<div style="margin-top:6px;font-size:12px;color:var(--muted);">Daily avg: <strong style="color:var(--dark);">' + fmt(t.spent_usd/t.days) + '/day</strong></div>' : '') +
            '</div>'
          ) : '') +
          // Category breakdown
          (Object.keys(t.cats).length > 0 ? (
            '<div style="margin-bottom:18px;">' +
            '<div style="font-size:13px;font-weight:700;margin-bottom:12px;">Expenses by Category</div>' +
            catHTML + '</div>'
          ) : '') +
          // Itinerary count
          (t.itinerary_count > 0 ? (
            '<div style="background:#EFF6FF;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;">' +
            '<i class="fa-regular fa-calendar-days" style="color:#1565C0;margin-right:6px;"></i>' +
            '<strong>' + t.itinerary_count + '</strong> itinerary item' + (t.itinerary_count!==1?'s':'') + ' planned' +
            '</div>'
          ) : '') +
          // Action buttons
          '<div style="display:flex;gap:10px;">' +
            '<a href="' + BASE_URL + '/src/expenses/index.php?trip_id=' + t.id + '" ' +
               'style="flex:1;text-align:center;padding:11px;border-radius:8px;background:var(--primary);' +
                      'color:#fff;text-decoration:none;font-size:14px;font-weight:700;">' +
               '<i class="fa-solid fa-receipt" style="margin-right:5px;"></i>Expenses</a>' +
            '<a href="' + BASE_URL + '/src/itinerary/index.php?trip_id=' + t.id + '" ' +
               'style="flex:1;text-align:center;padding:11px;border-radius:8px;border:1.5px solid var(--border);' +
                      'background:#fff;color:var(--dark);text-decoration:none;font-size:14px;font-weight:700;">' +
               '<i class="fa-solid fa-calendar-days" style="margin-right:5px;"></i>Itinerary</a>' +
          '</div>' +
        '</div>' +
    '</div>';
}
</script>

</body>
</html>
