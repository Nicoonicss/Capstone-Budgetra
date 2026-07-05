<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Budgetra</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body style="margin:0;padding:0;">

<div class="auth-wrapper">
    {{-- Left panel --}}
    <div class="auth-panel-left">
        <div class="auth-panel-left-overlay"></div>
        <div class="auth-panel-left-content">
            <h2>Adventure Smarter</h2>
            <p>Budgetra helps you focus on the journey while we handle the math. Join 50,000+ travelers managing their trips with precision.</p>
        </div>
    </div>

    {{-- Right panel --}}
    <div class="auth-panel-right">
        <div class="auth-form-wrap">
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

            @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input id="email" type="email" name="email"
                           value="{{ old('email') }}"
                           class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                           placeholder="name@example.com" required autofocus>
                    @error('email')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <input id="password" type="password" name="password"
                               class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                               placeholder="••••••••" required>
                        <span class="input-suffix" id="togglePwd" style="cursor:pointer;">
                            <i class="fa-regular fa-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                    @error('password')<div class="error">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    Sign In &nbsp;→
                </button>
            </form>

            <div class="auth-footer-text">
                Don't have an account? <a href="{{ route('register') }}">Create one</a>
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
document.getElementById('togglePwd').addEventListener('click', function () {
    var pwd  = document.getElementById('password');
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
