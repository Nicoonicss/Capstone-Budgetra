# Authentication Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement register, login/logout, profile management, and wire real auth + admin middleware so all protected routes enforce authentication.

**Architecture:** Session-based auth using Laravel's built-in `Auth` facade — no external packages. Two middleware layers: `auth` (redirects guests to login) and `admin` (aborts 403 for non-admins). Profile photos stored on the `public` disk under `profile-photos/`.

**Tech Stack:** Laravel 13.x, PHP 8.3+, MySQL, Blade, session driver = database (sessions table already migrated).

## Global Constraints

- Project root: `c:\phpsite\Capstone - Budgetra`
- Laravel 13.18.0, Livewire 4.3.3, PHP 8.3.12
- No forgot-password / password-reset flow — explicitly excluded
- User table columns: `id`, `full_name`, `email`, `password`, `phone`, `country`, `currency_code` (default `USD`), `currency_symbol` (default `$`), `role` (enum: `traveler`|`admin`, default `traveler`), `profile_photo`, `remember_token`, `timestamps` — no `email_verified_at`
- `password` field has `'password' => 'hashed'` cast — Laravel auto-hashes on assignment; do NOT call `Hash::make()` manually
- Auth controllers go in `app/Http/Controllers/Auth/` namespace
- All tests use `RefreshDatabase` trait
- Skip git commit steps — project is not using git yet
- Run `php artisan test <test-file>` to verify each task

---

### Task 1: Fix UserFactory + Auth Route Scaffold

**Files:**
- Modify: `database/factories/UserFactory.php`
- Modify: `routes/web.php`
- Run: `php artisan storage:link`
- Test: `tests/Feature/Auth/FactoryTest.php`

**Interfaces:**
- Produces: `User::factory()->create()` with `full_name`, `email`, `password='password'`, `role='traveler'`; `User::factory()->admin()->create()` with `role='admin'`
- Produces: named routes `login`, `register`, `logout` in `routes/web.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Auth/FactoryTest.php
namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_traveler_with_correct_fields(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'traveler']);
        $this->assertNotNull($user->full_name);
        $this->assertNotNull($user->email);
    }

    public function test_factory_admin_state_creates_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_factory_password_is_hashed(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('secret123', $user->password));
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Auth/FactoryTest.php
```

Expected: FAIL — factory uses `name` column that does not exist.

- [ ] **Step 3: Fix UserFactory**

Replace the entire contents of `database/factories/UserFactory.php`:

```php
<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name'       => fake()->name(),
            'email'           => fake()->unique()->safeEmail(),
            'password'        => 'password',
            'phone'           => fake()->numerify('09#########'),
            'country'         => fake()->country(),
            'currency_code'   => 'PHP',
            'currency_symbol' => '₱',
            'role'            => 'traveler',
            'remember_token'  => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
}
```

- [ ] **Step 4: Add auth route groups to routes/web.php**

Add a `guest` middleware group (before the existing `auth` group) and a standalone logout route. Replace the full contents of `routes/web.php`:

```php
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Traveler;

Route::get('/', fn() => redirect('/dashboard'));

// ── Guest-only routes (redirect to dashboard if already logged in) ─────────
Route::middleware('guest')->group(function () {
    Route::get('/login',    [Auth\LoginController::class, 'showForm'])->name('login');
    Route::post('/login',   [Auth\LoginController::class, 'login']);
    Route::get('/register', [Auth\RegisterController::class, 'showForm'])->name('register');
    Route::post('/register',[Auth\RegisterController::class, 'store']);
});

Route::post('/logout', [Auth\LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// ── Authenticated traveler routes ─────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [Traveler\DashboardController::class, '__invoke'])->name('dashboard');

    Route::get('/profile', [Traveler\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile',  [Traveler\ProfileController::class, 'update'])->name('profile.update');

    Route::get('/trips',                [Traveler\TripController::class, 'index'])->name('trips.index');
    Route::get('/trips/type',           [Traveler\TripController::class, 'type'])->name('trips.type');
    Route::get('/trips/create',         [Traveler\TripController::class, 'create'])->name('trips.create');
    Route::post('/trips',               [Traveler\TripController::class, 'store'])->name('trips.store');
    Route::get('/trips/{trip}/estimate',[Traveler\TripController::class, 'estimate'])->name('trips.estimate');

    Route::get('/expenses',         [Traveler\ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/create',  [Traveler\ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses',        [Traveler\ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/expenses/{expense}', [Traveler\ExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::get('/savings',              [Traveler\SavingsController::class, 'index'])->name('savings.index');
    Route::post('/savings',             [Traveler\SavingsController::class, 'store'])->name('savings.store');
    Route::post('/savings/{goal}/add',  [Traveler\SavingsController::class, 'addAmount'])->name('savings.add');

    Route::get('/itinerary',            [Traveler\ItineraryController::class, 'index'])->name('itinerary.index');
    Route::post('/itinerary',           [Traveler\ItineraryController::class, 'store'])->name('itinerary.store');
    Route::delete('/itinerary/{item}',  [Traveler\ItineraryController::class, 'destroy'])->name('itinerary.destroy');

    Route::get('/attractions', [Traveler\AttractionController::class, 'index'])->name('attractions.index');
    Route::get('/alerts',      [Traveler\AlertController::class, 'index'])->name('alerts.index');
    Route::get('/reports',     [Traveler\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/download', [Traveler\ReportController::class, 'download'])->name('reports.download');
});
```

- [ ] **Step 5: Run storage:link**

```
php artisan storage:link
```

Expected output: `The [public/storage] link has been connected to [storage/app/public].` (or "already exists" — both are fine).

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Auth/FactoryTest.php
```

Expected: 3 tests, 3 assertions, all PASS.

---

### Task 2: Register

**Files:**
- Create: `app/Http/Controllers/Auth/RegisterController.php`
- Create: `resources/views/auth/register.blade.php`
- Test: `tests/Feature/Auth/RegisterTest.php`

**Interfaces:**
- Consumes: `User::factory()` from Task 1; named route `register`; named route `dashboard`
- Produces: `POST /register` creates user with `role='traveler'`, logs them in, redirects to `dashboard`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Auth/RegisterTest.php
namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_loads(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->post('/register', [
            'full_name'             => 'Kent Pielago',
            'email'                 => 'kent@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'kent@example.com', 'role' => 'traveler']);
        $this->assertAuthenticated();
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->post('/register', [
            'full_name'             => 'Someone',
            'email'                 => 'taken@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_register_fails_with_mismatched_passwords(): void
    {
        $response = $this->post('/register', [
            'full_name'             => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_register_fails_with_short_password(): void
    {
        $response = $this->post('/register', [
            'full_name'             => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_from_register_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/register')->assertRedirect(route('dashboard'));
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Auth/RegisterTest.php
```

Expected: FAIL — `App\Http\Controllers\Auth\RegisterController` not found.

- [ ] **Step 3: Create RegisterController**

```php
<?php
// app/Http/Controllers/Auth/RegisterController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function showForm()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email',
            'password'  => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'email'     => $validated['email'],
            'password'  => $validated['password'],
            'role'      => 'traveler',
        ]);

        auth()->login($user);

        return redirect()->route('dashboard');
    }
}
```

- [ ] **Step 4: Create register view**

```html
{{-- resources/views/auth/register.blade.php --}}
@extends('layouts.guest')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <h1>Budgetra</h1>
            <p>Create your account</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul style="margin:0;padding-left:1.2em;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name"
                       class="form-control @error('full_name') is-invalid @enderror"
                       value="{{ old('full_name') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       class="form-control @error('password') is-invalid @enderror" required>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="{{ route('login') }}">Sign in</a>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 5: Run tests — expect PASS**

```
php artisan test tests/Feature/Auth/RegisterTest.php
```

Expected: 5 tests, 5+ assertions, all PASS.

---

### Task 3: Login / Logout

**Files:**
- Create: `app/Http/Controllers/Auth/LoginController.php`
- Create: `resources/views/auth/login.blade.php`
- Test: `tests/Feature/Auth/LoginTest.php`

**Interfaces:**
- Consumes: `User::factory()`, `User::factory()->admin()` from Task 1; routes `login`, `dashboard`, `admin.dashboard`, `logout`
- Produces: `POST /login` authenticates user, redirects traveler → `dashboard`, admin → `admin.dashboard`; `POST /logout` clears session, redirects → `login`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Auth/LoginTest.php
namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_traveler_can_login_and_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'kent@example.com', 'password' => 'password123']);

        $response = $this->post('/login', [
            'email'    => 'kent@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com', 'password' => 'password123']);

        $response = $this->post('/login', [
            'email'    => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'kent@example.com', 'password' => 'correctpass']);

        $response = $this->post('/login', [
            'email'    => 'kent@example.com',
            'password' => 'wrongpass',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $response = $this->post('/login', [
            'email'    => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_from_login_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/login')->assertRedirect(route('dashboard'));
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Auth/LoginTest.php
```

Expected: FAIL — `App\Http\Controllers\Auth\LoginController` not found.

- [ ] **Step 3: Create LoginController**

```php
<?php
// app/Http/Controllers/Auth/LoginController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function showForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (auth()->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (auth()->user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['email' => 'These credentials do not match our records.'])
            ->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
```

- [ ] **Step 4: Create login view**

```html
{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.guest')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <h1>Budgetra</h1>
            <p>Sign in to your account</p>
        </div>

        @if ($errors->has('email'))
            <div class="alert alert-danger">{{ $errors->first('email') }}</div>
        @endif

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       class="form-control" required>
            </div>

            <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember" style="margin:0;">Remember Me</label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="{{ route('register') }}">Create one</a>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 5: Run tests — expect PASS**

```
php artisan test tests/Feature/Auth/LoginTest.php
```

Expected: 7 tests, 7+ assertions, all PASS.

---

### Task 4: Wire Real Auth + Admin Middleware

**Files:**
- Modify: `bootstrap/app.php` — remove `'auth'` alias override
- Modify: `app/Http/Middleware/AdminMiddleware.php` — replace stub with role check
- Delete: `app/Http/Middleware/AuthMiddleware.php` — no longer needed
- Test: `tests/Feature/Auth/MiddlewareTest.php`

**Interfaces:**
- Consumes: `User::factory()`, `User::factory()->admin()` from Task 1; named route `login` from Task 3
- Produces: unauthenticated requests to `auth`-guarded routes redirect to `/login`; non-admin requests to `admin`-guarded routes return 403

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Auth/MiddlewareTest.php
namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_is_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_authenticated_traveler_can_access_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'traveler']);
        $this->actingAs($user)->get('/dashboard')->assertStatus(200);
    }

    public function test_traveler_cannot_access_admin_area(): void
    {
        $user = User::factory()->create(['role' => 'traveler']);
        $this->actingAs($user)->get('/admin')->assertStatus(403);
    }

    public function test_admin_can_access_admin_area(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin')->assertStatus(200);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL (middleware still pass-through)**

```
php artisan test tests/Feature/Auth/MiddlewareTest.php
```

Expected: FAIL on unauthenticated redirect tests — currently passes instead of redirecting.

- [ ] **Step 3: Remove the `auth` alias from bootstrap/app.php**

Replace `bootstrap/app.php` with:

```php
<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                 ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

- [ ] **Step 4: Replace AdminMiddleware with real role check**

```php
<?php
// app/Http/Middleware/AdminMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }
        return $next($request);
    }
}
```

- [ ] **Step 5: Delete the AuthMiddleware stub**

Delete the file `app/Http/Middleware/AuthMiddleware.php` — the alias is gone, the file is dead code.

```
del "app\Http\Middleware\AuthMiddleware.php"
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Auth/MiddlewareTest.php
```

Expected: 5 tests, 5+ assertions, all PASS.

- [ ] **Step 7: Verify existing ScaffoldTest still passes (it uses withoutMiddleware)**

```
php artisan test tests/Feature/ScaffoldTest.php
```

Expected: 3 tests, 20 assertions, all PASS.

---

### Task 5: Profile Management

**Files:**
- Create: `app/Http/Controllers/Traveler/ProfileController.php`
- Create: `resources/views/traveler/profile/edit.blade.php`
- Test: `tests/Feature/Auth/ProfileTest.php`

**Interfaces:**
- Consumes: `User::factory()` from Task 1; named routes `profile.edit`, `profile.update` (already in `routes/web.php` from Task 1)
- Produces: `GET /profile` shows edit form; `PUT /profile` updates `full_name`, `phone`, `country`, `currency_code`, `currency_symbol`, optional `profile_photo` stored to `profile-photos/` on `public` disk

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Auth/ProfileTest.php
namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create(['full_name' => 'Kent Pielago']);
        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);
        $response->assertSee('Kent Pielago');
    }

    public function test_user_can_update_profile_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile', [
            'full_name'       => 'Updated Name',
            'phone'           => '09123456789',
            'country'         => 'Philippines',
            'currency_code'   => 'PHP',
            'currency_symbol' => '₱',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id'        => $user->id,
            'full_name' => 'Updated Name',
            'phone'     => '09123456789',
            'country'   => 'Philippines',
        ]);
    }

    public function test_user_can_upload_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile', [
            'full_name'     => $user->full_name,
            'profile_photo' => UploadedFile::fake()->image('photo.jpg', 200, 200),
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertNotNull($user->profile_photo);
        Storage::disk('public')->assertExists($user->profile_photo);
    }

    public function test_profile_update_replaces_old_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['profile_photo' => 'profile-photos/old.jpg']);
        Storage::disk('public')->put('profile-photos/old.jpg', 'fake-content');

        $this->actingAs($user)->put('/profile', [
            'full_name'     => $user->full_name,
            'profile_photo' => UploadedFile::fake()->image('new.jpg', 200, 200),
        ]);

        $user->refresh();
        Storage::disk('public')->assertMissing('profile-photos/old.jpg');
        Storage::disk('public')->assertExists($user->profile_photo);
    }

    public function test_profile_requires_authentication(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_full_name_is_required(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->put('/profile', ['full_name' => '']);
        $response->assertSessionHasErrors('full_name');
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Auth/ProfileTest.php
```

Expected: FAIL — `App\Http\Controllers\Traveler\ProfileController` not found.

- [ ] **Step 3: Create ProfileController**

```php
<?php
// app/Http/Controllers/Traveler/ProfileController.php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('traveler.profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'full_name'       => 'required|string|max:255',
            'phone'           => 'nullable|string|max:20',
            'country'         => 'nullable|string|max:100',
            'currency_code'   => 'nullable|string|max:10',
            'currency_symbol' => 'nullable|string|max:10',
            'profile_photo'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $validated['profile_photo'] = $request->file('profile_photo')
                ->store('profile-photos', 'public');
        } else {
            unset($validated['profile_photo']);
        }

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }
}
```

- [ ] **Step 4: Create profile edit view**

Create the directory `resources/views/traveler/profile/` if it does not exist, then create the view:

```html
{{-- resources/views/traveler/profile/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="page-header">
    <h1>My Profile</h1>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:1.2em;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">
        @if ($user->profile_photo)
            <div style="margin-bottom:1rem;">
                <img src="{{ Storage::url($user->profile_photo) }}"
                     width="80" height="80"
                     style="border-radius:50%;object-fit:cover;"
                     alt="Profile photo">
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="full_name">Full Name <span style="color:red">*</span></label>
                <input type="text" id="full_name" name="full_name"
                       class="form-control @error('full_name') is-invalid @enderror"
                       value="{{ old('full_name', $user->full_name) }}" required>
                @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone"
                       class="form-control"
                       value="{{ old('phone', $user->phone) }}">
            </div>

            <div class="form-group">
                <label for="country">Country</label>
                <input type="text" id="country" name="country"
                       class="form-control"
                       value="{{ old('country', $user->country) }}">
            </div>

            <div class="form-group">
                <label for="currency_code">Currency Code</label>
                <input type="text" id="currency_code" name="currency_code"
                       class="form-control" maxlength="10"
                       value="{{ old('currency_code', $user->currency_code) }}">
            </div>

            <div class="form-group">
                <label for="currency_symbol">Currency Symbol</label>
                <input type="text" id="currency_symbol" name="currency_symbol"
                       class="form-control" maxlength="10"
                       value="{{ old('currency_symbol', $user->currency_symbol) }}">
            </div>

            <div class="form-group">
                <label for="profile_photo">Profile Photo</label>
                <input type="file" id="profile_photo" name="profile_photo"
                       class="form-control @error('profile_photo') is-invalid @enderror"
                       accept="image/jpeg,image/png,image/jpg,image/webp">
                @error('profile_photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>
@endsection
```

- [ ] **Step 5: Run tests — expect PASS**

```
php artisan test tests/Feature/Auth/ProfileTest.php
```

Expected: 6 tests, 10+ assertions, all PASS.

- [ ] **Step 6: Run full auth test suite**

```
php artisan test tests/Feature/Auth/
```

Expected: all PASS across FactoryTest, RegisterTest, LoginTest, MiddlewareTest, ProfileTest.
