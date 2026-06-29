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

// Mark all as read
if (isset($_POST['mark_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?")->execute([$user_id]);
    header('Location: ' . BASE_URL . '/src/alerts/index.php');
    exit;
}

// Fetch DB notifications
$stmt = $pdo->prepare(
    "SELECT n.*, t.destination FROM notifications n
     LEFT JOIN trips t ON n.trip_id = t.id
     WHERE n.user_id = ?
     ORDER BY n.created_at DESC LIMIT 50"
);
$stmt->execute([$user_id]);
$db_notifications = $stmt->fetchAll();

// Active trip for budget alerts
$stmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$active_trip = $stmt->fetch();

// Generate smart budget alerts from actual data
$smart_alerts = [];
if ($active_trip) {
    $trip_id = $active_trip['id'];

    // Per-category spending vs budget
    $stmt = $pdo->prepare(
        "SELECT tb.category, tb.estimated_cost,
                COALESCE(SUM(e.amount),0) as spent
         FROM trip_budgets tb
         LEFT JOIN expenses e ON e.trip_id = tb.trip_id
             AND e.category IN (
                 CASE tb.category
                     WHEN 'Food'               THEN 'Food'
                     WHEN 'Transportation'     THEN 'Transportation'
                     WHEN 'Accommodation'      THEN 'Accommodation'
                     WHEN 'Tourist Attractions'THEN 'Activities'
                     WHEN 'Shopping'           THEN 'Shopping'
                     WHEN 'Emergency Funds'    THEN 'Emergency Expenses'
                 END
             )
         WHERE tb.trip_id = ?
         GROUP BY tb.category, tb.estimated_cost"
    );
    $stmt->execute([$trip_id]);
    $cat_rows = $stmt->fetchAll();

    foreach ($cat_rows as $r) {
        $budget = (float)$r['estimated_cost'];
        $spent  = (float)$r['spent'];
        if ($budget <= 0) continue;
        $pct = $spent / $budget * 100;

        if ($pct >= 100) {
            $over = format_currency(($spent - $budget) * $cur_rate, $cur_symbol);
            $smart_alerts[] = [
                'type'  => 'danger',
                'icon'  => 'fa-triangle-exclamation',
                'title' => $r['category'] . ' budget exceeded',
                'sub'   => "You're " . $over . " over your " . $r['category'] . " budget for " . htmlspecialchars($active_trip['destination']) . ".",
                'time'  => 'Now',
            ];
        } elseif ($pct >= 80) {
            $remaining = format_currency(($budget - $spent) * $cur_rate, $cur_symbol);
            $smart_alerts[] = [
                'type'  => 'warning',
                'icon'  => 'fa-circle-exclamation',
                'title' => $r['category'] . ' is at ' . round($pct) . '%',
                'sub'   => "Only " . $remaining . " remaining in your " . $r['category'] . " budget.",
                'time'  => 'Today',
            ];
        } elseif ($pct > 0 && $pct <= 30) {
            $saved = format_currency(($budget - $spent) * $cur_rate, $cur_symbol);
            $smart_alerts[] = [
                'type'  => 'success',
                'icon'  => 'fa-circle-check',
                'title' => 'Great progress on ' . $r['category'],
                'sub'   => "You've only used " . round($pct) . "% of your " . $r['category'] . " budget. " . $saved . " still available.",
                'time'  => 'Today',
            ];
        }
    }

    // Days remaining alert
    if (!empty($active_trip['end_date'])) {
        $days_left = (int)ceil((strtotime($active_trip['end_date']) - time()) / 86400);
        if ($days_left > 0 && $days_left <= 3) {
            $smart_alerts[] = [
                'type'  => 'warning',
                'icon'  => 'fa-clock',
                'title' => 'Trip ending soon',
                'sub'   => "Your trip to " . htmlspecialchars($active_trip['destination']) . " ends in " . $days_left . " day" . ($days_left > 1 ? 's' : '') . ".",
                'time'  => 'Today',
            ];
        }
    }
}

$unread_count = count(array_filter($db_notifications, fn($n) => !$n['is_read']));

// Sidebar data
$user_initial = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));
$stmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$sidebar_trip = $stmt->fetch();

$body_class = 'dashboard-body';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<?php $active_sidebar = 'alerts'; ?>
<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- ══ MAIN ═════════════════════════════════════════ -->
    <div class="dash-main">
        <div class="dash-content" style="display:flex;flex-direction:column;">

        <?php if (empty($smart_alerts) && empty($db_notifications)): ?>

            <!-- ── Empty state ──────────────────────────────── -->
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                        flex:1;text-align:center;padding:40px 24px;">
                <div style="width:72px;height:72px;border-radius:20px;background:#FDF0E8;
                            display:flex;align-items:center;justify-content:center;
                            font-size:30px;margin-bottom:20px;">
                    <i class="fa-regular fa-bell" style="color:var(--primary);"></i>
                </div>
                <h2 style="font-size:22px;font-weight:800;margin:0 0 8px;">All caught up!</h2>
                <p style="color:var(--muted);font-size:14px;max-width:300px;margin:0;">
                    No notifications yet. We'll alert you when your budget needs attention.
                </p>
            </div>

        <?php else: ?>

            <!-- Page header -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 style="font-size:clamp(20px,3vw,26px);font-weight:800;margin-bottom:4px;">
                        <i class="fa-regular fa-bell" style="color:var(--primary);margin-right:8px;"></i>
                        Alerts &amp; Notifications
                    </h1>
                    <p style="font-size:13px;color:var(--muted);margin:0;">
                        Budget alerts and trip updates for
                        <strong><?php echo htmlspecialchars($active_trip['destination'] ?? 'your trips'); ?></strong>
                    </p>
                </div>
                <?php if ($unread_count > 0): ?>
                <form method="POST">
                    <button type="submit" name="mark_read" value="1"
                            style="background:none;border:1.5px solid var(--border);border-radius:8px;
                                   padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;color:var(--muted);">
                        <i class="fa-solid fa-check-double"></i> Mark all as read
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <?php if (!empty($smart_alerts)): ?>
            <div class="chart-card" style="margin-bottom:20px;">
                <div class="chart-card-header" style="margin-bottom:16px;">
                    <span class="chart-card-title">Budget Alerts</span>
                    <span style="font-size:12px;color:var(--muted);">
                        <?php echo htmlspecialchars($active_trip['destination'] ?? ''); ?>
                    </span>
                </div>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <?php foreach ($smart_alerts as $a):
                        $icon_colors = [
                            'danger'  => ['bg' => '#FEF2F2', 'color' => '#DC2626'],
                            'warning' => ['bg' => '#FEF9E8', 'color' => '#D4A412'],
                            'success' => ['bg' => '#F0FDF4', 'color' => '#16A34A'],
                        ];
                        $ic = $icon_colors[$a['type']] ?? $icon_colors['warning'];
                    ?>
                    <div style="display:flex;align-items:flex-start;gap:14px;padding:14px;
                                background:<?php echo $ic['bg']; ?>;border-radius:10px;">
                        <div style="width:36px;height:36px;border-radius:50%;flex-shrink:0;
                                    background:<?php echo $ic['bg']; ?>;border:2px solid <?php echo $ic['color']; ?>;
                                    display:flex;align-items:center;justify-content:center;
                                    color:<?php echo $ic['color']; ?>;font-size:14px;">
                            <i class="fa-solid <?php echo $a['icon']; ?>"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:3px;">
                                <?php echo htmlspecialchars($a['title']); ?>
                            </div>
                            <div style="font-size:13px;color:var(--muted);line-height:1.5;">
                                <?php echo htmlspecialchars($a['sub']); ?>
                            </div>
                        </div>
                        <div style="font-size:11px;color:var(--muted);white-space:nowrap;flex-shrink:0;">
                            <?php echo $a['time']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- DB Notifications -->
            <div class="chart-card">
                <div class="chart-card-header" style="margin-bottom:16px;">
                    <span class="chart-card-title">Notifications</span>
                    <?php if ($unread_count > 0): ?>
                    <span style="font-size:12px;font-weight:700;padding:3px 10px;border-radius:99px;
                                 background:#FEF2F2;color:#DC2626;">
                        <?php echo $unread_count; ?> unread
                    </span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;flex-direction:column;gap:2px;">
                    <?php foreach ($db_notifications as $n):
                        $is_unread = !$n['is_read'];
                        $ntype = $n['type'];
                        if (str_starts_with($ntype, 'cat_exceeded_') || $ntype === 'budget_exceeded') {
                            $n_icon = 'fa-triangle-exclamation'; $n_bg = '#FEF2F2'; $n_color = '#DC2626';
                        } elseif (str_starts_with($ntype, 'cat_warning_') || $ntype === 'trip_ending'
                               || in_array($ntype, ['budget_90', 'budget_75'])) {
                            $n_icon = 'fa-circle-exclamation'; $n_bg = '#FEF9E8'; $n_color = '#D4A412';
                        } elseif (str_starts_with($ntype, 'cat_success_')) {
                            $n_icon = 'fa-circle-check'; $n_bg = '#F0FDF4'; $n_color = '#16A34A';
                        } elseif ($ntype === 'trip_created') {
                            $n_icon = 'fa-plane'; $n_bg = '#EFF6FF'; $n_color = '#1565C0';
                        } else {
                            $n_icon = 'fa-bell'; $n_bg = '#FDF0E8'; $n_color = 'var(--primary)';
                        }
                    ?>
                    <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 10px;
                                border-radius:10px;background:<?php echo $is_unread ? '#FDFAF7' : 'transparent'; ?>;
                                border-bottom:1px solid var(--border);">
                        <div style="width:36px;height:36px;border-radius:50%;flex-shrink:0;
                                    background:<?php echo $n_bg; ?>;
                                    display:flex;align-items:center;justify-content:center;
                                    color:<?php echo $n_color; ?>;font-size:14px;">
                            <i class="fa-solid <?php echo $n_icon; ?>"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:14px;font-weight:<?php echo $is_unread ? '700' : '500'; ?>;
                                        color:var(--text);margin-bottom:3px;">
                                <?php echo htmlspecialchars($n['message']); ?>
                            </div>
                            <?php if ($n['destination']): ?>
                            <div style="font-size:12px;color:var(--muted);">
                                <i class="fa-solid fa-location-dot" style="color:var(--primary);"></i>
                                <?php echo htmlspecialchars($n['destination']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0;">
                            <div style="font-size:11px;color:var(--muted);white-space:nowrap;">
                                <?php echo date('M d, g:i A', strtotime($n['created_at'])); ?>
                            </div>
                            <?php if ($is_unread): ?>
                            <div style="width:8px;height:8px;border-radius:50%;background:var(--primary);"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php endif; ?>

        </div><!-- /dash-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

</body>
</html>
