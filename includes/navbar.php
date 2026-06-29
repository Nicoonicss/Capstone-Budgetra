<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<nav class="site-navbar">
    <div class="container-nav">
        <a href="<?php echo BASE_URL; ?>/" class="nav-brand" style="text-decoration:none;">
            <div class="nav-brand-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            Budgetra
        </a>

        <ul class="nav-links" id="navLinks">
            <li>
                <a href="<?php echo BASE_URL; ?>/src/dashboard/index.php"
                   class="<?php echo ($current_page === 'index.php' && $current_dir === 'dashboard') ? 'active' : ''; ?>">
                    Dashboard
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/src/trips/create.php"
                   class="<?php echo ($current_dir === 'trips') ? 'active' : ''; ?>">
                    Planner
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/src/itinerary/index.php"
                   class="<?php echo ($current_dir === 'itinerary') ? 'active' : ''; ?>">
                    Itinerary
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/src/expenses/index.php"
                   class="<?php echo ($current_dir === 'expenses') ? 'active' : ''; ?>">
                    Reports
                </a>
            </li>
        </ul>

        <div class="nav-actions" id="navActions">
            <?php if (isset($is_logged_in) && $is_logged_in): ?>
                <a href="<?php echo BASE_URL; ?>/src/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/src/auth/login.php" class="btn btn-outline btn-sm">Login</a>
                <a href="<?php echo BASE_URL; ?>/src/auth/login.php" class="btn btn-primary btn-sm">Get Started for Free</a>
            <?php endif; ?>
        </div>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
    </div>
</nav>

<script>
document.getElementById('navToggle').addEventListener('click', function() {
    document.getElementById('navLinks').classList.toggle('open');
    document.getElementById('navActions').classList.toggle('open');
});
</script>
