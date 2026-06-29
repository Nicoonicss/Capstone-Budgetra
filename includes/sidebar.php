<?php
// Shared sidebar component.
// Set $active_sidebar before including: 'dashboard','planner','savings','itinerary','reports','alerts','trips'
$_sb_uid    = $_SESSION['user_id'] ?? 0;
$_sb_init   = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));
$_sb_name   = $_SESSION['user_name'] ?? '';
$_sb_active = $active_sidebar ?? '';

// Use session-selected trip, fall back to most recent
if (!empty($_SESSION['current_trip_id'])) {
    $_sb_s = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
    $_sb_s->execute([$_SESSION['current_trip_id'], $_sb_uid]);
    $_sb_trip = $_sb_s->fetch();
}
if (empty($_sb_trip)) {
    $_sb_s = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $_sb_s->execute([$_sb_uid]);
    $_sb_trip = $_sb_s->fetch();
}

$_sb_s2 = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
$_sb_s2->execute([$_sb_uid]);
$_sb_unread = (int)$_sb_s2->fetchColumn();
?>
<aside class="sidebar" id="appSidebar">

    <button class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle sidebar">
        <i class="fa-solid fa-bars" id="sidebarToggleIcon"></i>
    </button>

    <div class="sidebar-brand">
        <div class="sidebar-trip-badge"><span>Active Workspace</span></div>
        <div class="sidebar-trip-name">
            <span><?php echo htmlspecialchars($_sb_trip['destination'] ?? 'My Trips'); ?></span>
        </div>
        <div class="sidebar-trip-status">
            <?php if ($_sb_trip): ?>
                <span><?php echo date('M d', strtotime($_sb_trip['start_date'])); ?> &ndash;
                <?php echo date('M d, Y', strtotime($_sb_trip['end_date'])); ?></span>
            <?php else: ?>
                <span>No active trip</span>
            <?php endif; ?>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php
        $nav = [
            ['href' => BASE_URL.'/src/dashboard/index.php',  'icon' => 'fa-solid fa-house',            'label' => 'Dashboard',    'key' => 'dashboard'],
            ['href' => BASE_URL.'/src/trips/type.php',       'icon' => 'fa-solid fa-map-location-dot', 'label' => 'Planner',      'key' => 'planner'],
            ['href' => BASE_URL.'/src/savings/index.php',    'icon' => 'fa-regular fa-circle-dot',     'label' => 'Savings Goal', 'key' => 'savings'],
            ['href' => BASE_URL.'/src/itinerary/index.php',  'icon' => 'fa-regular fa-calendar-days',  'label' => 'Itinerary',    'key' => 'itinerary'],
            ['href' => BASE_URL.'/src/expenses/index.php',   'icon' => 'fa-solid fa-chart-bar',        'label' => 'Reports',      'key' => 'reports'],
            ['href' => BASE_URL.'/src/alerts/index.php',     'icon' => 'fa-regular fa-bell',           'label' => 'Alerts',       'key' => 'alerts', 'badge' => $_sb_unread],
            ['href' => BASE_URL.'/src/trips/index.php',      'icon' => 'fa-solid fa-suitcase-rolling', 'label' => 'Multi-Trip',   'key' => 'trips'],
        ];
        foreach ($nav as $item):
            $active_cls = $_sb_active === $item['key'] ? 'active' : '';
        ?>
        <a href="<?php echo $item['href']; ?>"
           class="sidebar-link <?php echo $active_cls; ?>"
           title="<?php echo $item['label']; ?>">
            <i class="<?php echo $item['icon']; ?>"></i>
            <span class="sidebar-link-label"><?php echo $item['label']; ?></span>
            <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                <span class="sidebar-badge sidebar-link-label"><?php echo $item['badge']; ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-avatar-wrap">
        <div class="sidebar-avatar"><?php echo $_sb_init; ?></div>
        <div class="sidebar-user-details">
            <div class="sidebar-user-name"><?php echo htmlspecialchars($_sb_name); ?></div>
            <div class="sidebar-user-email">Traveler</div>
        </div>
        <a href="<?php echo BASE_URL; ?>/src/auth/logout.php"
           title="Logout"
           class="sidebar-logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>

    <a href="<?php echo BASE_URL; ?>/src/trips/type.php" class="sidebar-new-trip">
        <i class="fa-solid fa-plus"></i>
        <span class="sidebar-link-label"> New Trip</span>
    </a>
</aside>

<script>
(function () {
    var wrap  = document.querySelector('.dashboard-wrapper');
    var btn   = document.getElementById('sidebarToggle');
    var icon  = document.getElementById('sidebarToggleIcon');
    if (!wrap || !btn) return;

    function applyState(collapsed) {
        wrap.classList.toggle('sidebar-collapsed', collapsed);
        icon.className = collapsed ? 'fa-solid fa-chevron-right' : 'fa-solid fa-bars';
    }

    applyState(localStorage.getItem('sidebarCollapsed') === '1');

    btn.addEventListener('click', function () {
        var c = !wrap.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', c ? '1' : '0');
        applyState(c);
    });
})();
</script>
