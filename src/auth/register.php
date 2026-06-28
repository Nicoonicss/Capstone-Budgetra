<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/currencies.php';

$errors  = [];
$success = false;

// Build sorted country list for the selector
$all_countries = array_keys(COUNTRY_CURRENCIES);
sort($all_countries);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name        = trim($_POST['full_name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $country          = trim($_POST['country'] ?? '');

    if (empty($full_name))       $errors[] = "Full name is required.";
    if (empty($email))           $errors[] = "Email is required.";
    if (empty($password))        $errors[] = "Password is required.";
    if (strlen($password) < 6)   $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";
    if (empty($country))         $errors[] = "Please select your country.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "This email is already registered.";
        } else {
            $currency = get_currency_for_country($country);
            $hashed   = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (full_name, email, password, country, currency_code, currency_symbol)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            if ($stmt->execute([$full_name, $email, $hashed, $country, $currency['code'], $currency['symbol']])) {
                $success = true;
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="auth-wrapper">
    <!-- Left panel: orange-brown brand panel -->
    <div class="auth-panel-left-register">
        <div>
            <div class="brand-title">Budgetra</div>
            <p>Start your journey with confidence. Plan, track, and save for your dream destinations without the math-anxiety.</p>
            <!-- Trip card -->
            <div class="auth-trip-card">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;background:rgba(255,255,255,0.2);border-radius:9px;display:flex;align-items:center;justify-content:center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <div>
                        <div class="trip-name">Summer Euro Trip</div>
                        <div class="trip-meta">4 Countries · 14 Days</div>
                    </div>
                </div>
                <div class="trip-progress-wrap">
                    <div class="trip-budget">
                        <span class="trip-budget-label">Budget Goal: $4,500</span>
                        <span class="trip-percent">75% Reached</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-yellow" style="width:75%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Travel image -->
        <div class="auth-register-img">
            <img
                src="https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=400&h=250&fit=crop&auto=format"
                alt="Travel"
                onerror="this.style.display='none';"
            >
        </div>
    </div>

    <!-- Right panel: form -->
    <div class="auth-panel-right">
        <div class="auth-form-wrap">
            <h1 class="auth-title">Create an account</h1>
            <p class="auth-subtitle">Join thousands of smart travelers managing their money.</p>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    Account created! <a href="<?php echo BASE_URL; ?>/src/auth/login.php" style="font-weight:600;">Login here →</a>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?>
                        <div><?php echo htmlspecialchars($e); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <!-- Full Name -->
                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fa-regular fa-user"></i></span>
                        <input
                            type="text" id="full_name" name="full_name"
                            class="form-control"
                            placeholder="John Doe"
                            value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fa-regular fa-envelope"></i></span>
                        <input
                            type="email" id="email" name="email"
                            class="form-control"
                            placeholder="name@example.com"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                        <input
                            type="password" id="password" name="password"
                            class="form-control"
                            placeholder="••••••••"
                            required
                        >
                        <span class="input-suffix" id="togglePwd" style="cursor:pointer;">
                            <i class="fa-regular fa-eye" id="eyeIcon1"></i>
                        </span>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-lock-open"></i></span>
                        <input
                            type="password" id="confirm_password" name="confirm_password"
                            class="form-control"
                            placeholder="••••••••"
                            required
                        >
                        <span class="input-suffix" id="togglePwd2" style="cursor:pointer;">
                            <i class="fa-regular fa-eye" id="eyeIcon2"></i>
                        </span>
                    </div>
                </div>

                <!-- Country Selector -->
                <div class="form-group">
                    <label class="form-label" for="country">
                        <i class="fa-solid fa-globe" style="color:var(--primary);margin-right:4px;"></i>
                        Country
                    </label>

                    <div style="position:relative;">
                        <i class="fa-solid fa-earth-asia" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;pointer-events:none;z-index:1;"></i>
                        <i class="fa-solid fa-chevron-down" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:11px;pointer-events:none;z-index:1;"></i>
                        <select name="country" id="country" required
                                onchange="onCountryChange(this)"
                                style="width:100%;padding:11px 34px 11px 36px;font-size:14px;font-family:inherit;
                                       border:1.5px solid var(--border);border-radius:var(--radius-sm);
                                       background:var(--bg-white);color:var(--text);outline:none;
                                       cursor:pointer;-webkit-appearance:none;appearance:none;
                                       transition:border-color 0.2s;"
                                onfocus="this.style.borderColor='var(--primary)';this.style.boxShadow='0 0 0 3px rgba(139,58,16,0.12)'"
                                onblur="this.style.borderColor='var(--border)';this.style.boxShadow='none'">
                            <option value="" disabled <?php echo empty($_POST['country']) ? 'selected' : ''; ?>>
                                Select your country
                            </option>
                            <?php
                            $flag_map = [
                                'Philippines'=>'🇵🇭','Indonesia'=>'🇮🇩','Thailand'=>'🇹🇭',
                                'Vietnam'=>'🇻🇳','Malaysia'=>'🇲🇾','Singapore'=>'🇸🇬',
                                'Myanmar'=>'🇲🇲','Cambodia'=>'🇰🇭','Laos'=>'🇱🇦',
                                'Brunei'=>'🇧🇳','Timor-Leste'=>'🇹🇱','Japan'=>'🇯🇵',
                                'South Korea'=>'🇰🇷','China'=>'🇨🇳','Taiwan'=>'🇹🇼',
                                'Hong Kong'=>'🇭🇰','Macau'=>'🇲🇴','Mongolia'=>'🇲🇳',
                                'India'=>'🇮🇳','Pakistan'=>'🇵🇰','Bangladesh'=>'🇧🇩',
                                'Sri Lanka'=>'🇱🇰','Nepal'=>'🇳🇵','Maldives'=>'🇲🇻',
                                'United Arab Emirates'=>'🇦🇪','Saudi Arabia'=>'🇸🇦',
                                'Qatar'=>'🇶🇦','Kuwait'=>'🇰🇼','Bahrain'=>'🇧🇭',
                                'Oman'=>'🇴🇲','Israel'=>'🇮🇱','Jordan'=>'🇯🇴',
                                'Turkey'=>'🇹🇷','Germany'=>'🇩🇪','France'=>'🇫🇷',
                                'Italy'=>'🇮🇹','Spain'=>'🇪🇸','Netherlands'=>'🇳🇱',
                                'Belgium'=>'🇧🇪','Portugal'=>'🇵🇹','Austria'=>'🇦🇹',
                                'Greece'=>'🇬🇷','Ireland'=>'🇮🇪','Finland'=>'🇫🇮',
                                'United Kingdom'=>'🇬🇧','Switzerland'=>'🇨🇭','Sweden'=>'🇸🇪',
                                'Norway'=>'🇳🇴','Denmark'=>'🇩🇰','Poland'=>'🇵🇱',
                                'Czech Republic'=>'🇨🇿','Hungary'=>'🇭🇺','Romania'=>'🇷🇴',
                                'Russia'=>'🇷🇺','Ukraine'=>'🇺🇦','United States'=>'🇺🇸',
                                'Canada'=>'🇨🇦','Mexico'=>'🇲🇽','Brazil'=>'🇧🇷',
                                'Argentina'=>'🇦🇷','Chile'=>'🇨🇱','Colombia'=>'🇨🇴',
                                'Peru'=>'🇵🇪','Ecuador'=>'🇪🇨','South Africa'=>'🇿🇦',
                                'Nigeria'=>'🇳🇬','Egypt'=>'🇪🇬','Kenya'=>'🇰🇪',
                                'Ghana'=>'🇬🇭','Ethiopia'=>'🇪🇹','Morocco'=>'🇲🇦',
                                'Tanzania'=>'🇹🇿','Australia'=>'🇦🇺','New Zealand'=>'🇳🇿',
                                'Papua New Guinea'=>'🇵🇬','Fiji'=>'🇫🇯',
                            ];
                            $selected_country = $_POST['country'] ?? '';
                            foreach ($all_countries as $c):
                                $flag = $flag_map[$c] ?? '🌐';
                                $cur  = get_currency_for_country($c);
                                $sel  = ($selected_country === $c) ? 'selected' : '';
                            ?>
                            <option value="<?php echo htmlspecialchars($c); ?>"
                                    data-code="<?php echo $cur['code']; ?>"
                                    data-symbol="<?php echo htmlspecialchars($cur['symbol']); ?>"
                                    data-name="<?php echo htmlspecialchars($cur['name']); ?>"
                                    <?php echo $sel; ?>>
                                <?php echo $flag . ' ' . htmlspecialchars($c) . ' (' . $cur['code'] . ')'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Currency preview badge (shown after selection) -->
                    <div id="currencyPreview"
                         style="display:none;margin-top:8px;padding:10px 14px;
                                background:var(--primary-light);border-radius:var(--radius-sm);
                                align-items:center;gap:10px;">
                        <div id="currencySymbolBig"
                             style="width:32px;height:32px;background:var(--primary);color:#fff;
                                    border-radius:8px;display:flex;align-items:center;justify-content:center;
                                    font-size:16px;font-weight:800;flex-shrink:0;">$</div>
                        <div>
                            <div id="currencyPreviewText"
                                 style="font-size:13px;font-weight:600;color:var(--primary);"></div>
                            <div style="font-size:11px;color:var(--muted);margin-top:1px;">
                                Your budget planner will use this currency
                            </div>
                        </div>
                    </div>
                </div>

                <label class="auth-terms-check">
                    <input type="checkbox" id="agreeTerms" name="agree_terms">
                    <span>
                        By creating an account, I agree to the
                        <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
                    </span>
                </label>

                <button type="submit" id="createAccountBtn" class="btn btn-primary btn-block"
                        style="margin-bottom:16px;" disabled>
                    Create Account
                </button>

                <script>
                document.getElementById('agreeTerms').addEventListener('change', function() {
                    document.getElementById('createAccountBtn').disabled = !this.checked;
                });
                </script>
            </form>

            <div class="auth-footer-text" style="margin-top:16px;">
                Already have an account? <a href="<?php echo BASE_URL; ?>/src/auth/login.php">Login here</a>
            </div>

            <div style="text-align:center;margin-top:32px;font-size:12px;color:var(--muted);">
                © 2024 Budgetra. Smart travel, smarter spending. &nbsp;·&nbsp;
                <a href="#" style="color:var(--muted);">Support</a> &nbsp;·&nbsp;
                <a href="#" style="color:var(--muted);">Privacy</a> &nbsp;·&nbsp;
                <a href="#" style="color:var(--muted);">Security</a>
            </div>
        </div>
    </div>
</div>

<script>
// ── Password toggles ─────────────────────────────────
function togglePassword(inputId, iconId) {
    var pwd  = document.getElementById(inputId);
    var icon = document.getElementById(iconId);
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        pwd.type = 'password';
        icon.className = 'fa-regular fa-eye';
    }
}
document.getElementById('togglePwd').addEventListener('click', function() {
    togglePassword('password', 'eyeIcon1');
});
document.getElementById('togglePwd2').addEventListener('click', function() {
    togglePassword('confirm_password', 'eyeIcon2');
});

// ── Country selector ─────────────────────────────────
function onCountryChange(sel) {
    var opt     = sel.options[sel.selectedIndex];
    var code    = opt.dataset.code   || 'USD';
    var symbol  = opt.dataset.symbol || '$';
    var name    = opt.dataset.name   || 'US Dollar';
    var preview = document.getElementById('currencyPreview');
    document.getElementById('currencySymbolBig').textContent  = symbol;
    document.getElementById('currencyPreviewText').textContent = name + ' (' + code + ')';
    preview.style.display = 'flex';
}

// Restore currency preview on page reload after form error
(function() {
    var sel = document.getElementById('country');
    if (sel && sel.value) onCountryChange(sel);
})();
</script>

</body>
</html>
