<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

require_login();

$user_country = $_SESSION['user_country'] ?? '';
$body_class   = 'dashboard-body';
$active_sidebar = 'planner';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<?php $active_sidebar = 'planner'; ?>
<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="dash-main">
    <div class="app-content trip-type-page" style="overflow-y:auto;">

        <div class="trip-type-header">
            <h1 class="trip-type-title">Where are you headed?</h1>
            <p class="trip-type-sub">
                Choose your travel type to get personalized destination suggestions
                and accurate cost estimates.
            </p>
        </div>

        <div class="trip-type-cards">

            <!-- Local -->
            <a href="<?php echo BASE_URL; ?>/src/trips/create.php?type=local"
               class="trip-type-card trip-type-card--local">
                <div class="trip-type-card-bg-circle trip-type-card-bg-circle--1"></div>
                <div class="trip-type-card-bg-circle trip-type-card-bg-circle--2"></div>
                <div class="trip-type-card-icon">
                    <i class="fa-solid fa-house-chimney"></i>
                </div>
                <div class="trip-type-card-badge">LOCAL TRAVEL</div>
                <h2 class="trip-type-card-title">
                    Explore
                    <?php echo $user_country ? htmlspecialchars($user_country) : 'Your Country'; ?>
                </h2>
                <p class="trip-type-card-desc">
                    Discover hidden gems and popular spots within your home country.
                    <?php if ($user_country): ?>
                        Choose from curated destinations in
                        <strong><?php echo htmlspecialchars($user_country); ?></strong>.
                    <?php endif; ?>
                </p>
                <div class="trip-type-card-cta">
                    Plan Local Trip <i class="fa-solid fa-arrow-right"></i>
                </div>
            </a>

            <!-- International -->
            <a href="<?php echo BASE_URL; ?>/src/trips/create.php?type=international"
               class="trip-type-card trip-type-card--intl">
                <div class="trip-type-card-bg-circle trip-type-card-bg-circle--1"></div>
                <div class="trip-type-card-bg-circle trip-type-card-bg-circle--2"></div>
                <div class="trip-type-card-icon">
                    <i class="fa-solid fa-earth-asia"></i>
                </div>
                <div class="trip-type-card-badge">INTERNATIONAL TRAVEL</div>
                <h2 class="trip-type-card-title">Travel the World</h2>
                <p class="trip-type-card-desc">
                    Explore iconic destinations across Asia, Europe, the Americas,
                    Africa, and Oceania. Your next big adventure awaits.
                </p>
                <div class="trip-type-card-cta">
                    Plan International Trip <i class="fa-solid fa-arrow-right"></i>
                </div>
            </a>

        </div>

    </div>

    </div><!-- /app-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

</body>
</html>
