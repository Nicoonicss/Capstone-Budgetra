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

// Resolve goal_id
$goal_id = intval($_GET['goal_id'] ?? 0);
if (!$goal_id) {
    $stmt = $pdo->prepare("SELECT id FROM savings_goals WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row) {
        header('Location: ' . BASE_URL . '/src/savings/goal.php?goal_id=' . $row['id']);
        exit;
    }
    header('Location: ' . BASE_URL . '/src/dashboard/index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT sg.*, t.destination, t.start_date, t.end_date
     FROM savings_goals sg
     LEFT JOIN trips t ON sg.trip_id = t.id
     WHERE sg.id = ? AND sg.user_id = ?"
);
$stmt->execute([$goal_id, $user_id]);
$goal = $stmt->fetch();

if (!$goal) {
    header('Location: ' . BASE_URL . '/src/dashboard/index.php');
    exit;
}

// Core values
$target_usd    = (float)$goal['target_amount'];
$saved_usd     = (float)$goal['current_savings'];
$remaining_usd = max(0, $target_usd - $saved_usd);
$pct_saved     = $target_usd > 0 ? min(100, round($saved_usd / $target_usd * 100, 1)) : 0;
$dest          = $goal['destination'] ?? 'Your Destination';
$goal_name     = $goal['goal_name'] ?? ($dest . ' Trip');
$deadline      = $goal['deadline'];
$created_at    = $goal['created_at'];

// Time calculations
$today        = new DateTime('today');
$dl           = new DateTime($deadline);
$diffDl       = $today->diff($dl);
$days_left    = $diffDl->invert ? 0 : $diffDl->days;
$months_left  = max(0.5, $days_left / 30.44);
$monthly_usd  = $remaining_usd / $months_left;
$weekly_usd   = $monthly_usd / 4.33;

$started_fmt  = date('M Y', strtotime($created_at));
$target_fmt   = date('M Y', strtotime($deadline));

// On-track status
$created_dt    = new DateTime(date('Y-m-d', strtotime($created_at)));
$days_total    = max(1, $created_dt->diff($dl)->days);
$days_elapsed  = max(0, min($days_total, $created_dt->diff($today)->days));
$expected_pct  = ($days_elapsed / $days_total) * 100;
$is_on_track   = ($pct_saved >= $expected_pct);

if ($pct_saved == 0 && $days_elapsed < 3) {
    $track_title      = 'Ready to Start Saving!';
    $track_text       = 'Set a monthly auto-transfer to reach your goal effortlessly. Consistent small amounts compound into big results.';
    $track_icon       = 'fa-rocket';
    $track_icon_bg    = '#E8F0FE';
    $track_icon_color = '#1565C0';
} elseif ($is_on_track) {
    $weeks_ahead = max(0, round(($pct_saved - $expected_pct) / 100 * $days_total / 7));
    if ($weeks_ahead >= 1) {
        $track_title = 'On Track for ' . date('F', strtotime($deadline)) . '!';
        $track_text  = 'If you maintain your current pace, you\'ll reach your goal ' . $weeks_ahead . ' week' . ($weeks_ahead > 1 ? 's' : '') . ' ahead of schedule.';
    } else {
        $track_title = 'Right on Track!';
        $track_text  = 'You\'re saving at the right pace to reach your goal by ' . $target_fmt . '. Keep it up!';
    }
    $track_icon       = 'fa-lightbulb';
    $track_icon_bg    = '#FEF9E8';
    $track_icon_color = '#D4A412';
} else {
    $track_title      = 'Slightly Behind Schedule';
    $track_text       = 'Consider boosting your monthly savings by ' . format_currency(round($monthly_usd * 0.2 * $cur_rate), $cur_symbol) . ' to get back on track.';
    $track_icon       = 'fa-triangle-exclamation';
    $track_icon_bg    = '#FEF2F2';
    $track_icon_color = '#DC2626';
}

// Destination gradient & emoji
$dl_lower = strtolower($dest);
if (preg_match('/europe|paris|rome|spain|italy|greece|madrid|barcelona|london|berlin|amsterdam|santorini/', $dl_lower)) {
    $dest_gradient = 'linear-gradient(145deg,#0f2a5a 0%,#1565C0 60%,#2196F3 100%)';
    $dest_emoji    = '🏛️';
} elseif (preg_match('/japan|tokyo|korea|seoul|kyoto|osaka/', $dl_lower)) {
    $dest_gradient = 'linear-gradient(145deg,#5c0f0f 0%,#c0392b 60%,#e74c3c 100%)';
    $dest_emoji    = '⛩️';
} elseif (preg_match('/bali|thailand|vietnam|singapore|malaysia|phuket|cebu|palawan|boracay|philippines/', $dl_lower)) {
    $dest_gradient = 'linear-gradient(145deg,#0f3d1a 0%,#1e8449 60%,#27ae60 100%)';
    $dest_emoji    = '🌴';
} elseif (preg_match('/new york|usa|america|canada|los angeles|chicago|miami/', $dl_lower)) {
    $dest_gradient = 'linear-gradient(145deg,#1a0f3d 0%,#6c3483 60%,#9b59b6 100%)';
    $dest_emoji    = '🗽';
} elseif (preg_match('/dubai|abu dhabi|qatar|doha|uae|saudi/', $dl_lower)) {
    $dest_gradient = 'linear-gradient(145deg,#3d2700 0%,#b7860b 60%,#f39c12 100%)';
    $dest_emoji    = '🌆';
} else {
    $dest_gradient = 'linear-gradient(145deg,#3d1a00 0%,#8B3A10 60%,#c4621a 100%)';
    $dest_emoji    = '✈️';
}

// Feature tip values (local currency, rounded sensibly)
$target_local  = round($target_usd  * $cur_rate);
$monthly_local = round($monthly_usd * $cur_rate);
$weekly_local  = round($weekly_usd  * $cur_rate);
$roundup_save  = max(50, (int)round($monthly_local * 0.12, -1));
$interest_earn = max(10, (int)round($target_local  * 0.045 / 12));
$dining_save   = max(20, (int)round($weekly_local  * 0.60,  -1));

$body_class = 'dashboard-body';
$active_sidebar = 'savings';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="dash-main">
    <div class="app-content gp-wrapper" style="overflow-y:auto;">

        <h1 class="gp-title"><?php echo htmlspecialchars($goal_name); ?></h1>
        <p class="gp-subtitle">
            We've crunched the numbers for your <?php echo htmlspecialchars($dest); ?> adventure.
            Here is your roadmap to reaching your <?php echo format_currency($target_usd * $cur_rate, $cur_symbol); ?> target.
        </p>

        <!-- Two-column layout -->
        <div class="gp-layout">

            <!-- LEFT: savings progress + on-track tip -->
            <div>
                <div class="gp-card gp-savings-card">
                    <div class="gp-savings-top">
                        <div>
                            <div class="gp-savings-label">Current Savings Progress</div>
                            <div class="gp-savings-amount">
                                <?php echo format_currency($saved_usd * $cur_rate, $cur_symbol); ?>
                                <span class="gp-savings-of">saved of <?php echo format_currency($target_usd * $cur_rate, $cur_symbol); ?></span>
                            </div>
                        </div>
                        <div class="gp-remaining-box">
                            <div class="gp-remaining-label">Remaining to Save</div>
                            <div class="gp-remaining-amount"><?php echo format_currency($remaining_usd * $cur_rate, $cur_symbol); ?></div>
                        </div>
                    </div>

                    <div class="gp-progress-bar-wrap">
                        <div class="gp-progress-bar" style="width:<?php echo $pct_saved; ?>%;"></div>
                    </div>

                    <div class="gp-progress-info">
                        <span>Started: <?php echo $started_fmt; ?></span>
                        <span><?php echo $pct_saved; ?>% Complete</span>
                        <span>Target: <?php echo $target_fmt; ?></span>
                    </div>
                </div>

                <div class="gp-track-tip"
                     style="--tip-bg:<?php echo $track_icon_bg; ?>;--tip-color:<?php echo $track_icon_color; ?>;">
                    <div class="gp-track-icon">
                        <i class="fa-solid <?php echo $track_icon; ?>"></i>
                    </div>
                    <div>
                        <div class="gp-track-title"><?php echo htmlspecialchars($track_title); ?></div>
                        <div class="gp-track-text"><?php echo htmlspecialchars($track_text); ?></div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: stat cards + destination image -->
            <div class="gp-right-col">
                <div class="gp-stat-cards">
                    <div class="gp-card gp-stat-card">
                        <div class="gp-stat-label">Monthly Savings</div>
                        <div class="gp-stat-value">
                            <?php echo format_currency($monthly_local, $cur_symbol); ?><span class="gp-stat-unit">/month</span>
                        </div>
                        <div class="gp-stat-icon" style="color:#8B3A10;">
                            <i class="fa-regular fa-calendar"></i>
                        </div>
                    </div>
                    <div class="gp-card gp-stat-card">
                        <div class="gp-stat-label">Weekly Savings</div>
                        <div class="gp-stat-value" style="color:var(--tertiary);">
                            <?php echo format_currency($weekly_local, $cur_symbol); ?><span class="gp-stat-unit">/week</span>
                        </div>
                        <div class="gp-stat-icon" style="color:var(--tertiary);">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </div>
                    </div>
                </div>

                <div class="gp-dest-card" style="background:<?php echo $dest_gradient; ?>;">
                    <div class="gp-dest-overlay">
                        <span class="gp-dest-emoji"><?php echo $dest_emoji; ?></span>
                        <span class="gp-dest-name"><?php echo htmlspecialchars($dest); ?></span>
                    </div>
                </div>
            </div>

        </div><!-- /gp-layout -->

        <!-- Feature cards -->
        <div class="gp-features-grid">
            <div class="gp-feature-card">
                <div class="gp-feature-icon" style="color:#8B3A10;background:#FDF0E8;">
                    <i class="fa-solid fa-scissors"></i>
                </div>
                <div class="gp-feature-title">Round-Up Rules</div>
                <div class="gp-feature-text">
                    Enable "Round-Up" on everyday purchases to reach your goal
                    <?php echo format_currency($roundup_save, $cur_symbol); ?> faster per month.
                </div>
                <a href="#" class="gp-feature-link">Configure Rules →</a>
            </div>

            <div class="gp-feature-card">
                <div class="gp-feature-icon" style="color:#D4A412;background:#FEF9E8;">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div class="gp-feature-title">High-Yield Boost</div>
                <div class="gp-feature-text">
                    Moving your '<?php echo htmlspecialchars($goal_name); ?>' fund to a 4.5% APY account
                    earns you <?php echo format_currency($interest_earn, $cur_symbol); ?>/month in passive interest.
                </div>
                <a href="#" class="gp-feature-link">See Rates →</a>
            </div>

            <div class="gp-feature-card">
                <div class="gp-feature-icon" style="color:#1565C0;background:#E8F0FE;">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <div class="gp-feature-title">Dining Adjustment</div>
                <div class="gp-feature-text">
                    Switching one dinner out per week to home cooking adds
                    <?php echo format_currency($dining_save, $cur_symbol); ?>/week to your travel fund.
                </div>
                <a href="#" class="gp-feature-link">View Analysis →</a>
            </div>
        </div>

        <!-- Finish button -->
        <div class="gp-finish-wrap">
            <a href="<?php echo BASE_URL; ?>/src/dashboard/index.php" class="gp-finish-btn">
                Finish &amp; Go to Dashboard
                <i class="fa-solid fa-house"></i>
            </a>
            <p class="gp-footer-note">You can always adjust these targets later in your Planner settings.</p>
        </div>

    </div><!-- /app-content -->

    </div><!-- /app-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

</body>
</html>
