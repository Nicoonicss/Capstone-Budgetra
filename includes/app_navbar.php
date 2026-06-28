<?php
// $active_nav: 'dashboard' | 'planner' | 'scan-receipt' | 'itinerary' | 'reports'
$active_nav = $active_nav ?? '';
$user_initial = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));

// Unread notification count
$notif_count = 0;
if (isset($pdo)) {
    $uid = $_SESSION['user_id'] ?? 0;
    $ns  = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $ns->execute([$uid]);
    $notif_count = (int)$ns->fetchColumn();
}
?>
<nav class="app-topnav">
    <a href="<?php echo BASE_URL; ?>/" class="app-topnav-brand">
        <div class="app-topnav-brand-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </div>
        Budgetra
    </a>

    <div class="app-nav-links">
        <a href="<?php echo BASE_URL; ?>/src/dashboard/index.php"
           class="app-nav-link <?php echo $active_nav === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
        <a href="<?php echo BASE_URL; ?>/src/trips/type.php"
           class="app-nav-link <?php echo $active_nav === 'planner' ? 'active' : ''; ?>">Planner</a>
        <?php if ($active_nav === 'scan-receipt'): ?>
            <a href="<?php echo BASE_URL; ?>/src/expenses/create.php"
               class="app-nav-link active">Scan Receipt</a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/src/itinerary/index.php"
               class="app-nav-link <?php echo $active_nav === 'itinerary' ? 'active' : ''; ?>">Itinerary</a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/src/expenses/index.php"
           class="app-nav-link <?php echo $active_nav === 'reports' ? 'active' : ''; ?>">Reports</a>
    </div>

    <div class="app-topnav-right">
        <div class="app-topnav-bell" title="Notifications" style="position:relative;">
            <i class="fa-regular fa-bell" style="font-size:16px;"></i>
            <?php if ($notif_count > 0): ?>
                <span style="position:absolute;top:4px;right:4px;width:8px;height:8px;background:var(--danger);border-radius:50%;border:2px solid #fff;"></span>
            <?php endif; ?>
        </div>
        <div class="app-topnav-avatar"><?php echo $user_initial; ?></div>
    </div>
</nav>
