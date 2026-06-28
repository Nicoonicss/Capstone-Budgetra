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

$stmt = $pdo->prepare(
    "SELECT sg.*, t.destination FROM savings_goals sg
     LEFT JOIN trips t ON sg.trip_id = t.id
     WHERE sg.user_id = ?
     ORDER BY sg.created_at DESC"
);
$stmt->execute([$user_id]);
$savings_goals = $stmt->fetchAll();

// If exactly one goal, redirect straight to its projection page
if (count($savings_goals) === 1) {
    header('Location: ' . BASE_URL . '/src/savings/goal.php?goal_id=' . $savings_goals[0]['id']);
    exit;
}

$body_class = 'dashboard-body';
$active_sidebar = 'savings';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="dash-main">
    <div class="app-content" style="max-width:900px;overflow-y:auto;">

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
            <div>
                <div style="font-size:11px;font-weight:700;letter-spacing:.1em;color:var(--primary);text-transform:uppercase;margin-bottom:4px;">
                    SAVINGS GOALS
                </div>
                <h1 style="font-size:clamp(22px,3.5vw,30px);font-weight:800;margin:0;">Your Travel Funds</h1>
            </div>
            <a href="<?php echo BASE_URL; ?>/src/trips/type.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> New Goal
            </a>
        </div>

        <?php if (empty($savings_goals)): ?>
            <div class="chart-card" style="text-align:center;padding:56px 24px;">
                <div style="font-size:48px;margin-bottom:16px;">🎯</div>
                <div style="font-size:18px;font-weight:700;margin-bottom:8px;">No savings goals yet</div>
                <div style="font-size:14px;color:var(--muted);margin-bottom:24px;">
                    Create your first trip plan to start building a savings goal.
                </div>
                <a href="<?php echo BASE_URL; ?>/src/trips/type.php" class="btn btn-primary">
                    <i class="fa-solid fa-plane"></i> Plan a Trip
                </a>
            </div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <?php foreach ($savings_goals as $g):
                    $t_usd   = (float)$g['target_amount'];
                    $s_usd   = (float)$g['current_savings'];
                    $r_usd   = max(0, $t_usd - $s_usd);
                    $pct     = $t_usd > 0 ? min(100, round($s_usd / $t_usd * 100, 1)) : 0;
                    $today   = new DateTime('today');
                    $dl      = new DateTime($g['deadline']);
                    $diff    = $today->diff($dl);
                    $days_l  = $diff->invert ? 0 : $diff->days;
                    $m_left  = max(0.5, $days_l / 30.44);
                    $monthly = round(($r_usd / $m_left) * $cur_rate);
                    $dest    = $g['destination'] ?? '—';
                ?>
                <div class="chart-card" style="padding:22px;display:flex;flex-direction:column;gap:14px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                        <div>
                            <div style="font-size:16px;font-weight:700;margin-bottom:2px;">
                                <?php echo htmlspecialchars($g['goal_name']); ?>
                            </div>
                            <?php if ($dest !== '—'): ?>
                            <div style="font-size:12px;color:var(--muted);">
                                <i class="fa-solid fa-location-dot" style="color:var(--primary);"></i>
                                <?php echo htmlspecialchars($dest); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <span style="font-size:11px;font-weight:700;padding:4px 10px;border-radius:99px;
                              background:<?php echo $pct >= 100 ? '#F0FDF4' : '#FDF0E8'; ?>;
                              color:<?php echo $pct >= 100 ? '#16A34A' : '#8B3A10'; ?>;">
                            <?php echo $pct >= 100 ? 'Complete' : ($pct . '% Done'); ?>
                        </span>
                    </div>

                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:6px;">
                            <span><?php echo format_currency($s_usd * $cur_rate, $cur_symbol); ?> saved</span>
                            <span><?php echo format_currency($t_usd * $cur_rate, $cur_symbol); ?> goal</span>
                        </div>
                        <div style="height:6px;background:#F3F4F6;border-radius:99px;overflow:hidden;">
                            <div style="height:100%;width:<?php echo $pct; ?>%;background:var(--primary);border-radius:99px;"></div>
                        </div>
                    </div>

                    <div style="display:flex;gap:16px;">
                        <div>
                            <div style="font-size:11px;color:var(--muted);margin-bottom:2px;">Monthly needed</div>
                            <div style="font-size:14px;font-weight:700;color:var(--primary);">
                                <?php echo format_currency($monthly, $cur_symbol); ?>
                            </div>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--muted);margin-bottom:2px;">Deadline</div>
                            <div style="font-size:14px;font-weight:700;">
                                <?php echo date('M Y', strtotime($g['deadline'])); ?>
                            </div>
                        </div>
                        <div style="margin-left:auto;">
                            <div style="font-size:11px;color:var(--muted);margin-bottom:2px;">Days left</div>
                            <div style="font-size:14px;font-weight:700;"><?php echo max(0, $days_l); ?></div>
                        </div>
                    </div>

                    <a href="<?php echo BASE_URL; ?>/src/savings/goal.php?goal_id=<?php echo $g['id']; ?>"
                       class="btn btn-primary btn-sm" style="width:100%;text-align:center;">
                        View Projection →
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    </div><!-- /app-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

</body>
</html>
