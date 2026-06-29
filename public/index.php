<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/init_db.php';

$is_logged_in = is_logged_in();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

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
    </div>
</nav>

<?php if ($is_logged_in): ?>
<!-- Logged-in: redirect hint -->
<div style="max-width:600px;margin:80px auto;text-align:center;padding:0 24px;">
    <h2 style="font-size:28px;font-weight:800;margin-bottom:12px;">Welcome back to Budgetra!</h2>
    <p style="color:var(--muted);margin-bottom:32px;">Manage your trips, track expenses, and plan your next adventure.</p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
        <a href="<?php echo BASE_URL; ?>/src/dashboard/index.php" class="btn btn-primary btn-lg">View Dashboard</a>
        <a href="<?php echo BASE_URL; ?>/src/trips/create.php" class="btn btn-outline btn-lg">Plan New Trip</a>
    </div>
</div>

<?php else: ?>

<!-- ══ HERO ══════════════════════════════════════════ -->
<section>
    <div class="hero-section">
        <!-- Left -->
        <div>
            <div class="hero-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Next-Gen Travel Finance
            </div>
            <h1 class="hero-title">
                Web-Based Smart Vacation Budget Planning and Expense Management System
            </h1>
            <p class="hero-subtitle">
                Plan and manage travel expenses before, during, and after your trip using real-time API data and OCR receipt scanning.
            </p>
            <div class="hero-actions">
                <a href="<?php echo BASE_URL; ?>/src/auth/login.php" class="btn btn-primary btn-lg">
                    Get Started for Free
                </a>
                <a href="#features" class="btn btn-outline btn-lg">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                        <polygon points="10 8 16 12 10 16 10 8" fill="currentColor"/>
                    </svg>
                    How it works
                </a>
            </div>
        </div>

        <!-- Right: image + float card -->
        <div class="hero-image-wrap">
            <div class="hero-img-card">
                <img
                    src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=700&h=480&fit=crop&auto=format"
                    alt="Budgetra app preview"
                    onerror="this.style.background='linear-gradient(135deg,#8B3A10,#C47A3A)';this.style.height='380px';"
                >
            </div>
            <div class="hero-float-card">
                <div class="hero-float-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D4A412" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="8" y1="8" x2="16" y2="8"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                        <line x1="8" y1="16" x2="12" y2="16"/>
                    </svg>
                </div>
                <div>
                    <div class="hero-float-label">OCR Scanned</div>
                    <div style="font-size:11px;color:var(--muted);">Starbucks Milan</div>
                    <div class="hero-float-value">€12.50</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ FEATURES ══════════════════════════════════════ -->
<section id="features" class="features-section">
    <div class="section-header">
        <h2 class="section-title">Engineered for the Modern Explorer</h2>
        <p class="section-subtitle">Stop worrying about the math and start focusing on the memories. Budgetra gives you the tools to stay financially focused without the friction.</p>
    </div>

    <div class="bento-grid">
        <!-- Card 1: Vacation Budget Planner (large, light) -->
        <div class="bento-card bento-card-light" style="min-height:280px;">
            <div class="bento-icon bento-icon-light">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <h3>Vacation Budget Planner</h3>
            <p>Comprehensive planning suite that syncs with your travel calendar to predict daily spend.</p>
            <!-- Mini app mockup -->
            <div style="margin-top:24px;background:#fff;border-radius:10px;padding:16px;box-shadow:var(--shadow-sm);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <span style="font-size:12px;font-weight:600;">Budget Overview</span>
                    <span style="font-size:11px;color:var(--muted);">14 Days</span>
                </div>
                <!-- Mini donut visual -->
                <div style="display:flex;gap:12px;align-items:center;">
                    <div style="width:64px;height:64px;border-radius:50%;background:conic-gradient(var(--primary) 0% 60%,var(--orange) 60% 85%,var(--tertiary) 85% 100%);flex-shrink:0;"></div>
                    <div style="flex:1;">
                        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px;"><span>Dining</span><span style="color:var(--primary);font-weight:600;">$650</span></div>
                        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px;"><span>Transport</span><span style="color:var(--orange);font-weight:600;">$430</span></div>
                        <div style="display:flex;justify-content:space-between;font-size:11px;"><span>Stay</span><span style="color:var(--tertiary);font-weight:600;">$380</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Trip Cost Estimator (blue) -->
        <div class="bento-card bento-card-blue" style="min-height:280px;">
            <div class="bento-icon bento-icon-white">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
            <h3 style="color:#fff;">Trip Cost Estimator</h3>
            <p style="color:rgba(255,255,255,0.8);">Get precise estimates for any destination using real-time local cost data APIs.</p>
            <div class="bento-mini-card" style="width:100%;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div class="dest">Tokyo, Japan</div>
                    <span style="font-size:12px;color:rgba(255,255,255,0.8);">7 Days</span>
                </div>
                <div class="bar-wrap" style="margin-top:10px;">
                    <div class="bar"><div class="bar-fill" style="width:75%;"></div></div>
                </div>
                <div class="price">$2,450 Est.</div>
            </div>
        </div>

        <!-- Bottom row -->
        <div class="bento-row-bottom">
            <!-- Card 3: Expense Tracking -->
            <div class="bento-card bento-card-white">
                <div class="bento-icon bento-icon-light">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <line x1="2" y1="10" x2="22" y2="10"/>
                    </svg>
                </div>
                <h3>Expense Tracking</h3>
                <p>Real-time currency conversion and categorization for every transaction abroad.</p>
                <div style="margin-top:16px;">
                    <div class="expense-item">
                        <div class="expense-avatar" style="background:#FEF3E8;color:#E07B2A;">D</div>
                        <div class="expense-name">Dinner at Le Marais</div>
                        <div class="expense-amt">-$84.00</div>
                    </div>
                    <div class="expense-item">
                        <div class="expense-avatar" style="background:#EEF2FF;color:#1565C0;">T</div>
                        <div class="expense-name">Taxi to Louvre</div>
                        <div class="expense-amt">-$12.50</div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Savings Goal Calculator (orange) -->
            <div class="bento-card bento-card-orange">
                <div class="bento-icon bento-icon-white">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 5c-1.5 0-2.8 1.4-3 2-3.5-1.5-11-.3-11 5 0 1.8 0 3 2 4.5V20h4v-2h3v2h4v-3.5c1.4-.7 2-1.5 2-2.5V7c0-1.1-.9-2-2-2z"/>
                        <path d="M2 9v1c0 1.1.9 2 2 2h1"/>
                    </svg>
                </div>
                <h3 style="color:#fff;">Savings Goal Calculator</h3>
                <p style="color:rgba(255,255,255,0.85);">Calculate how much you need to save daily to reach your dream destination.</p>
                <div class="savings-big">$15.20 <span style="font-size:18px;font-weight:600;">/day</span></div>
                <div class="savings-label">Remaining for Swiss Alps</div>
            </div>

            <!-- Card 5: OCR Scanning -->
            <div class="bento-card bento-card-yellow">
                <div class="bento-icon bento-icon-dark">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <line x1="8" y1="8" x2="16" y2="8"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                        <line x1="8" y1="16" x2="12" y2="16"/>
                    </svg>
                </div>
                <h3>OCR Scanning</h3>
                <p>Instantly scan and parse receipts from anywhere in the world – no manual entry.</p>
                <!-- Receipt mockup lines -->
                <div style="margin-top:16px;background:rgba(255,255,255,0.6);border-radius:8px;padding:12px;">
                    <div style="height:8px;background:rgba(0,0,0,0.12);border-radius:4px;margin-bottom:6px;width:80%;"></div>
                    <div style="height:8px;background:rgba(0,0,0,0.08);border-radius:4px;margin-bottom:6px;width:60%;"></div>
                    <div style="height:8px;background:rgba(0,0,0,0.12);border-radius:4px;width:70%;"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ CTA ════════════════════════════════════════════ -->
<section class="cta-section">
    <div class="cta-card">
        <h2>Ready to travel without financial stress?</h2>
        <p>Join 50,000+ travelers who use Budgetra to keep their adventures on track and under budget.</p>
        <a href="<?php echo BASE_URL; ?>/src/auth/login.php" class="btn btn-white btn-lg">
            Get Started for Free
        </a>
    </div>
</section>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
