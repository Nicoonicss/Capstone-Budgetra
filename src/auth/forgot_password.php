<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        // Always show success to avoid user enumeration
        $success = true;
    }
}
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="forgot-wrapper">
    <!-- Brand at top -->
    <div class="forgot-brand">
        <div class="forgot-brand-icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </div>
        <div class="forgot-brand-name">Budgetra</div>
        <div class="forgot-brand-underline"></div>
    </div>

    <!-- Card -->
    <div class="forgot-card">
        <?php if ($success): ?>
            <div class="forgot-icon-wrap" style="background:var(--success);background:rgba(22,163,74,0.12);color:var(--success);">
                <i class="fa-solid fa-envelope-circle-check"></i>
            </div>
            <h2 class="forgot-title">Check Your Email</h2>
            <p class="forgot-subtitle">
                If an account exists for <strong><?php echo htmlspecialchars($_POST['email']); ?></strong>,
                we've sent a password reset link. Check your inbox.
            </p>
            <a href="<?php echo BASE_URL; ?>/src/auth/login.php" class="btn btn-primary btn-block">
                ← Back to Login
            </a>
        <?php else: ?>
            <div class="forgot-icon-wrap">
                <i class="fa-solid fa-key"></i>
            </div>
            <h2 class="forgot-title">Forgot Password</h2>
            <p class="forgot-subtitle">Enter your email address and we'll send you a link to reset your password.</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): echo htmlspecialchars($e); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
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

                <button type="submit" class="btn btn-primary btn-block">
                    Send Reset Link &nbsp;→
                </button>
            </form>

            <div style="text-align:center;margin-top:20px;">
                <a href="<?php echo BASE_URL; ?>/src/auth/login.php"
                   style="font-size:14px;color:var(--muted);display:inline-flex;align-items:center;gap:6px;">
                    ← Back to Login
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div style="margin-top:24px;font-size:12px;color:var(--muted);text-align:center;">
        Having trouble? <a href="#" style="color:var(--primary);">Contact support</a>
    </div>

    <!-- Footer bar -->
    <div style="position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid var(--border);
        padding:14px 24px;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--muted);">
        <span>© 2024 Budgetra. Smart travel, smarter spending.</span>
        <span>
            <a href="#" style="color:var(--muted);margin-left:16px;">Privacy Policy</a>
            <a href="#" style="color:var(--muted);margin-left:16px;">Terms of Service</a>
        </span>
    </div>
</div>

</body>
</html>
