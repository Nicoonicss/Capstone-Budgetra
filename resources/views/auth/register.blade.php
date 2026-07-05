<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — Budgetra</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body style="margin:0;padding:0;">

<div class="auth-wrapper">

    {{-- Left panel --}}
    <div class="auth-panel-left-register">
        <div>
            <div class="brand-title">Budgetra</div>
            <p>Start your journey with confidence. Plan, track, and save for your dream destinations without the math-anxiety.</p>
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
        <div class="auth-register-img">
            <img src="https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=400&h=250&fit=crop&auto=format"
                 alt="Travel" onerror="this.style.display='none';">
        </div>
    </div>

    {{-- Right panel --}}
    <div class="auth-panel-right">
        <div class="auth-form-wrap">
            <h1 class="auth-title">Create an account</h1>
            <p class="auth-subtitle">Join thousands of smart travelers managing their money.</p>

            @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                {{-- Full Name --}}
                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fa-regular fa-user"></i></span>
                        <input id="full_name" type="text" name="full_name"
                               value="{{ old('full_name') }}"
                               class="form-control {{ $errors->has('full_name') ? 'is-invalid' : '' }}"
                               placeholder="John Doe" required autofocus>
                    </div>
                    @error('full_name')<div class="error">{{ $message }}</div>@enderror
                </div>

                {{-- Email --}}
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fa-regular fa-envelope"></i></span>
                        <input id="email" type="email" name="email"
                               value="{{ old('email') }}"
                               class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                               placeholder="name@example.com" required>
                    </div>
                    @error('email')<div class="error">{{ $message }}</div>@enderror
                </div>

                {{-- Password --}}
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                        <input id="password" type="password" name="password"
                               class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                               placeholder="••••••••" required>
                        <span class="input-suffix" id="togglePwd1" style="cursor:pointer;">
                            <i class="fa-regular fa-eye" id="eyeIcon1"></i>
                        </span>
                    </div>
                    @error('password')<div class="error">{{ $message }}</div>@enderror
                </div>

                {{-- Confirm Password --}}
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Confirm Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-lock-open"></i></span>
                        <input id="password_confirmation" type="password" name="password_confirmation"
                               class="form-control" placeholder="••••••••" required>
                        <span class="input-suffix" id="togglePwd2" style="cursor:pointer;">
                            <i class="fa-regular fa-eye" id="eyeIcon2"></i>
                        </span>
                    </div>
                </div>

                {{-- Country --}}
                <div class="form-group">
                    <label class="form-label" for="country">
                        <i class="fa-solid fa-globe" style="color:var(--primary);margin-right:4px;"></i>
                        Country
                    </label>
                    <div style="position:relative;">
                        <i class="fa-solid fa-earth-asia" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;pointer-events:none;z-index:1;"></i>
                        <i class="fa-solid fa-chevron-down" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:11px;pointer-events:none;z-index:1;"></i>
                        <select name="country" id="country"
                                style="width:100%;padding:11px 34px 11px 36px;font-size:14px;font-family:inherit;
                                       border:1.5px solid var(--border);border-radius:var(--radius-sm);
                                       background:var(--bg-white);color:var(--text);outline:none;
                                       cursor:pointer;-webkit-appearance:none;appearance:none;
                                       transition:border-color 0.2s;"
                                onfocus="this.style.borderColor='var(--primary)';this.style.boxShadow='0 0 0 3px rgba(139,58,16,0.12)'"
                                onblur="this.style.borderColor='var(--border)';this.style.boxShadow='none'">
                            <option value="" disabled selected>Select your country</option>
                            @foreach([
                                '🇵🇭 Philippines','🇮🇩 Indonesia','🇹🇭 Thailand','🇻🇳 Vietnam',
                                '🇲🇾 Malaysia','🇸🇬 Singapore','🇯🇵 Japan','🇰🇷 South Korea',
                                '🇨🇳 China','🇮🇳 India','🇦🇺 Australia','🇳🇿 New Zealand',
                                '🇺🇸 United States','🇨🇦 Canada','🇬🇧 United Kingdom',
                                '🇩🇪 Germany','🇫🇷 France','🇮🇹 Italy','🇪🇸 Spain',
                                '🇳🇱 Netherlands','🇧🇷 Brazil','🇲🇽 Mexico','🇦🇷 Argentina',
                                '🇸🇦 Saudi Arabia','🇦🇪 United Arab Emirates','🇪🇬 Egypt',
                                '🇳🇬 Nigeria','🇿🇦 South Africa','🇰🇪 Kenya',
                            ] as $c)
                                <option value="{{ $c }}" {{ old('country') === $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Terms --}}
                <label class="auth-terms-check">
                    <input type="checkbox" id="agreeTerms">
                    <span>
                        By creating an account, I agree to the
                        <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
                    </span>
                </label>

                <button type="submit" id="createAccountBtn" class="btn btn-primary btn-block"
                        style="margin-bottom:16px;" disabled>
                    Create Account
                </button>
            </form>

            <div class="auth-footer-text">
                Already have an account? <a href="{{ route('login') }}">Login here</a>
            </div>

            <div style="text-align:center;margin-top:32px;font-size:12px;color:var(--muted);">
                © {{ date('Y') }} Budgetra. Smart travel, smarter spending.
                &nbsp;·&nbsp; <a href="#" style="color:var(--muted);">Support</a>
                &nbsp;·&nbsp; <a href="#" style="color:var(--muted);">Privacy</a>
                &nbsp;·&nbsp; <a href="#" style="color:var(--muted);">Security</a>
            </div>
        </div>
    </div>
</div>

<script>
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
document.getElementById('togglePwd1').addEventListener('click', function () {
    togglePassword('password', 'eyeIcon1');
});
document.getElementById('togglePwd2').addEventListener('click', function () {
    togglePassword('password_confirmation', 'eyeIcon2');
});
document.getElementById('agreeTerms').addEventListener('change', function () {
    document.getElementById('createAccountBtn').disabled = !this.checked;
});
</script>

</body>
</html>
