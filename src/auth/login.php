<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/currencies.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email))    $errors[] = "Email is required.";
    if (empty($password)) $errors[] = "Password is required.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']              = $user['id'];
            $_SESSION['user_name']            = $user['full_name'];
            $_SESSION['user_country']         = $user['country'] ?? '';
            $_SESSION['user_currency_code']   = $user['currency_code']   ?? 'USD';
            $_SESSION['user_currency_symbol'] = $user['currency_symbol'] ?? '$';

            // Resolve exchange rate from config
            $cur = get_currency_for_country($user['country'] ?? '');
            $_SESSION['user_currency_rate']   = $cur['rate'];
            $_SESSION['user_currency_name']   = $cur['name'];

            header('Location: ' . BASE_URL . '/src/dashboard/index.php');
            exit;
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="auth-wrapper">
    <!-- Left panel: travel image -->
    <div class="auth-panel-left">
        <img
            src="https://images.unsplash.com/photo-1539635278303-d4002c07eae3?w=500&h=900&fit=crop&auto=format"
            alt="Travel"
            class="auth-panel-left-img"
            onerror="this.style.display='none';"
        >
        <div class="auth-panel-left-overlay"></div>
        <div class="auth-panel-left-content">
            <h2>Adventure Smarter</h2>
            <p>Budgetra helps you focus on the journey while we handle the math. Join 50,000+ travelers managing their trips with precision.</p>
        </div>
    </div>

    <!-- Right panel: form -->
    <div class="auth-panel-right">
        <div class="auth-form-wrap">
            <!-- Brand -->
            <div class="auth-brand">
                <div class="auth-brand-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </div>
                Budgetra
            </div>

            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Enter your credentials to access your trips.</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?>
                        <div><?php echo htmlspecialchars($e); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <!-- Email -->
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input
                        type="email" id="email" name="email"
                        class="form-control"
                        placeholder="name@example.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                    >
                </div>

                <!-- Password -->
                <div class="form-group">
                    <div style="margin-bottom:6px;">
                        <label class="form-label" for="password">Password</label>
                    </div>
                    <div class="input-wrapper">
                        <input
                            type="password" id="password" name="password"
                            class="form-control"
                            placeholder="••••••••"
                            required
                        >
                        <span class="input-suffix" id="togglePwd" style="cursor:pointer;">
                            <i class="fa-regular fa-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px;">
                    Login &nbsp;→
                </button>
            </form>

            <div class="auth-footer-text">
                Don't have an account? <a href="<?php echo BASE_URL; ?>/src/auth/register.php">Register here</a>
            </div>

            <div style="text-align:center;margin-top:40px;font-size:12px;color:var(--muted);">
                © 2024 Budgetra &nbsp;·&nbsp;
                <a href="#" style="color:var(--muted);">Privacy</a> &nbsp;·&nbsp;
                <a href="#" style="color:var(--muted);">Terms</a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePwd').addEventListener('click', function() {
    var pwd = document.getElementById('password');
    var icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        pwd.type = 'password';
        icon.className = 'fa-regular fa-eye';
    }
});
</script>

</body>
</html>
