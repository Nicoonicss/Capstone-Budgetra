# P6: Landing Page + Auth UI Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the default Laravel welcome page and plain auth views with a polished Budgetra landing page, split-screen login, and split-screen register, all driven by a new `public/css/budgetra.css` design system.

**Architecture:** Pure Blade — no Livewire needed for these pages. A new stylesheet `public/css/budgetra.css` defines the design system (colors, typography, layout). `layouts/app.blade.php` is updated to load `budgetra.css` instead of `style.css` and use the new sidebar component. The `GET /` route changes to redirect authenticated users to `/dashboard` and show the landing page to guests. Auth views keep the same form field names so existing auth tests keep passing.

**Tech Stack:** Laravel Blade, CSS3, Google Fonts (Playfair Display + Inter), Font Awesome 6.5 (already on CDN)

## Global Constraints

- Laravel 13.18.0, PHP 8.3.12, MySQL
- No git — skip ALL git/commit steps
- Admin views use `@extends('layouts.admin')` — DO NOT touch `layouts/admin.blade.php` or `public/css/style.css`
- Traveler views use `@extends('layouts.app')`
- All new CSS goes in `public/css/budgetra.css` — the only file in `public/css/` being created
- Run tests: `php artisan test <test-file>`
- Keep existing form field names in auth views (`email`, `password`, `full_name`, `phone`, `country`, `currency_code`, `currency_symbol`, `password_confirmation`) so existing auth tests pass
- Design colors: primary `#5C2D0E`, gold `#C9A84C`, blue `#1A4C8B`, bg `#FAF5F0`

---

## File Structure

- **CREATE:** `public/css/budgetra.css` — complete design system
- **MODIFY:** `resources/views/layouts/app.blade.php` — load budgetra.css, use new sidebar structure
- **MODIFY:** `resources/views/components/sidebar.blade.php` — show real user name/email, fix nav links
- **MODIFY:** `routes/web.php` line 7 — `GET /` shows landing to guests, redirects auth users to `/dashboard`
- **REPLACE:** `resources/views/welcome.blade.php` — new landing page (no `@extends`, standalone HTML)
- **REPLACE:** `resources/views/auth/login.blade.php` — split-screen redesign
- **REPLACE:** `resources/views/auth/register.blade.php` — split-screen redesign
- **CREATE:** `tests/Feature/UI/LandingAuthUiTest.php` — tests for these views

---

### Task 1: CSS Design System + Layout Update

**Files:**
- Create: `public/css/budgetra.css`
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/components/sidebar.blade.php`
- Test: `tests/Feature/UI/LandingAuthUiTest.php`

**Interfaces:**
- Produces: CSS classes used by ALL subsequent P6–P13 plans. Every `.bt-*` class defined here is consumed by later tasks.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/UI/LandingAuthUiTest.php`:

```php
<?php
namespace Tests\Feature\UI;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingAuthUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_landing_page(): void
    {
        $this->get('/')->assertStatus(200)->assertSee('Plan Smart');
    }

    public function test_authenticated_user_redirected_from_root(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/')->assertRedirect('/dashboard');
    }

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertStatus(200)->assertSee('Sign In');
    }

    public function test_register_page_loads(): void
    {
        $this->get('/register')->assertStatus(200)->assertSee('Create Account');
    }

    public function test_dashboard_loads_for_auth_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertStatus(200);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/UI/LandingAuthUiTest.php
```

Expected: FAIL — landing page shows Laravel default welcome (doesn't contain "Plan Smart"), and `/` redirects everyone to `/dashboard` unconditionally.

- [ ] **Step 3: Create `public/css/budgetra.css`**

```css
/* ============================================================
   Budgetra Design System — public/css/budgetra.css
   ============================================================ */
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap');

/* ---------- CSS Variables ---------- */
:root {
  --color-primary:      #5C2D0E;
  --color-primary-dark: #3D1E09;
  --color-gold:         #C9A84C;
  --color-blue:         #1A4C8B;
  --color-bg:           #FAF5F0;
  --color-surface:      #FFFFFF;
  --color-border:       #E8DDD5;
  --color-text:         #1A1A1A;
  --color-text-muted:   #6B6B6B;
  --color-success:      #2ECC71;
  --color-danger:       #E74C3C;
  --color-warning:      #F39C12;
  --sidebar-width:      240px;
  --radius:             12px;
}

/* ---------- Reset ---------- */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: var(--color-bg); color: var(--color-text); line-height: 1.5; }
a { color: inherit; }

/* ---------- Dashboard Layout ---------- */
.bt-wrapper { display: flex; min-height: 100vh; }

/* Sidebar */
.bt-sidebar {
  width: var(--sidebar-width);
  background: var(--color-primary);
  color: white;
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0;
  height: 100vh;
  z-index: 100;
}
.bt-sidebar-brand { padding: 24px 20px 16px; border-bottom: 1px solid rgba(255,255,255,0.1); }
.bt-sidebar-brand .brand-name { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: white; }
.bt-sidebar-brand .brand-tagline { font-size: 11px; color: rgba(255,255,255,0.55); margin-top: 2px; }
.bt-sidebar-nav { flex: 1; padding: 12px 0; overflow-y: auto; }
.bt-sidebar-link {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 20px;
  color: rgba(255,255,255,0.7);
  text-decoration: none;
  font-size: 14px; font-weight: 500;
  transition: all 0.15s;
  position: relative;
}
.bt-sidebar-link:hover { background: rgba(255,255,255,0.08); color: white; }
.bt-sidebar-link.active { background: rgba(255,255,255,0.15); color: white; }
.bt-sidebar-link i { width: 18px; text-align: center; font-size: 15px; flex-shrink: 0; }
.bt-sidebar-link span { flex: 1; }
.bt-sidebar-link .notif-badge {
  background: #E74C3C; color: white;
  font-size: 10px; font-weight: 700;
  border-radius: 10px; padding: 1px 5px;
  min-width: 16px; text-align: center;
  line-height: 1.4;
}
.bt-sidebar-footer {
  padding: 14px 20px;
  border-top: 1px solid rgba(255,255,255,0.1);
  display: flex; align-items: center; gap: 10px;
}
.bt-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: var(--color-gold); color: white;
  font-size: 13px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.bt-user-name { font-size: 13px; font-weight: 500; line-height: 1.3; }
.bt-user-email { font-size: 11px; color: rgba(255,255,255,0.45); line-height: 1.3; }
.bt-sidebar-logout { padding: 8px 20px 16px; }
.bt-sidebar-logout button {
  background: none; border: none;
  color: rgba(255,255,255,0.45); font-size: 12px;
  cursor: pointer; padding: 0; font-family: 'Inter', sans-serif;
}
.bt-sidebar-logout button:hover { color: rgba(255,255,255,0.85); }

/* Main content area */
.bt-main { margin-left: var(--sidebar-width); flex: 1; padding: 28px 32px; min-height: 100vh; }

/* ---------- Typography ---------- */
h1 { font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 700; margin-bottom: 4px; }
h2 { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 600; }
h3 { font-size: 16px; font-weight: 600; }

/* ---------- Cards ---------- */
.bt-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  padding: 20px;
}
.bt-card-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 16px;
}

/* Stat cards */
.bt-stat-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  padding: 20px 24px;
  border-top: 3px solid var(--color-border);
}
.bt-stat-card.primary { border-top-color: var(--color-primary); }
.bt-stat-card.gold    { border-top-color: var(--color-gold); }
.bt-stat-card.blue    { border-top-color: var(--color-blue); }
.bt-stat-label {
  font-size: 11px; font-weight: 600;
  letter-spacing: 0.08em; text-transform: uppercase;
  color: var(--color-text-muted); margin-bottom: 8px;
  display: flex; align-items: center; gap: 6px;
}
.bt-stat-value { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 700; }
.bt-stat-card.primary .bt-stat-value { color: var(--color-primary); }
.bt-stat-card.gold    .bt-stat-value { color: var(--color-gold); }
.bt-stat-card.blue    .bt-stat-value { color: var(--color-blue); }
.bt-stat-sub { font-size: 12px; color: var(--color-text-muted); margin-top: 4px; }
.bt-stat-bar { margin-top: 12px; }

/* ---------- Buttons ---------- */
.bt-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 18px; border-radius: 8px;
  font-size: 14px; font-weight: 500; font-family: 'Inter', sans-serif;
  border: none; cursor: pointer; text-decoration: none;
  transition: all 0.15s; white-space: nowrap;
}
.bt-btn-primary { background: var(--color-primary); color: white; }
.bt-btn-primary:hover { background: var(--color-primary-dark, #3D1E09); color: white; }
.bt-btn-gold { background: var(--color-gold); color: white; }
.bt-btn-gold:hover { opacity: 0.88; color: white; }
.bt-btn-blue { background: var(--color-blue); color: white; }
.bt-btn-blue:hover { opacity: 0.88; color: white; }
.bt-btn-outline {
  background: transparent; color: var(--color-text);
  border: 1px solid var(--color-border);
}
.bt-btn-outline:hover { background: var(--color-bg); color: var(--color-text); }
.bt-btn-outline-white {
  background: transparent; color: white;
  border: 2px solid rgba(255,255,255,0.6);
}
.bt-btn-outline-white:hover { background: rgba(255,255,255,0.15); color: white; }
.bt-btn-danger { background: var(--color-danger); color: white; }
.bt-btn-danger:hover { opacity: 0.88; color: white; }
.bt-btn-success { background: var(--color-success); color: white; }
.bt-btn-success:hover { opacity: 0.88; color: white; }
.bt-btn-sm { padding: 5px 12px; font-size: 12px; }
.bt-btn-lg { padding: 13px 28px; font-size: 16px; font-weight: 600; border-radius: 10px; }
.bt-btn-block { width: 100%; justify-content: center; }

/* ---------- Forms ---------- */
.bt-form-group { margin-bottom: 16px; }
.bt-label { display: block; font-size: 13px; font-weight: 500; color: var(--color-text); margin-bottom: 6px; }
.bt-input, .bt-select, .bt-textarea {
  width: 100%; padding: 10px 13px;
  border: 1px solid var(--color-border); border-radius: 8px;
  font-family: 'Inter', sans-serif; font-size: 14px;
  color: var(--color-text); background: white;
  transition: border 0.15s;
}
.bt-input:focus, .bt-select:focus, .bt-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(92,45,14,0.08);
}
.bt-input.is-invalid, .bt-select.is-invalid { border-color: var(--color-danger); }
.bt-error { font-size: 12px; color: var(--color-danger); margin-top: 4px; }
.bt-textarea { min-height: 80px; resize: vertical; }
.bt-input-group { display: flex; gap: 10px; }
.bt-input-group .bt-input { flex: 1; }

/* ---------- Progress Bar ---------- */
.bt-progress { height: 6px; background: #EEE; border-radius: 3px; overflow: hidden; }
.bt-progress-bar { height: 100%; border-radius: 3px; background: var(--color-primary); transition: width 0.3s; }
.bt-progress-bar.gold    { background: var(--color-gold); }
.bt-progress-bar.success { background: var(--color-success); }
.bt-progress-bar.danger  { background: var(--color-danger); }

/* ---------- Grid Utilities ---------- */
.bt-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.bt-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
.bt-grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 16px; }
.bt-flex { display: flex; align-items: center; gap: 12px; }
.bt-flex-between { display: flex; align-items: center; justify-content: space-between; }
.mt-4  { margin-top: 4px; }
.mt-8  { margin-top: 8px; }
.mt-16 { margin-top: 16px; }
.mt-24 { margin-top: 24px; }
.mt-32 { margin-top: 32px; }
.mb-4  { margin-bottom: 4px; }
.mb-8  { margin-bottom: 8px; }
.mb-16 { margin-bottom: 16px; }
.mb-24 { margin-bottom: 24px; }
.text-muted   { color: var(--color-text-muted); font-size: 13px; }
.text-danger  { color: var(--color-danger); }
.text-success { color: var(--color-success); }
.text-gold    { color: var(--color-gold); }
.text-right   { text-align: right; }
.text-center  { text-align: center; }

/* ---------- Alerts ---------- */
.bt-alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
.bt-alert-success { background: #EAFAF1; border: 1px solid #A9DFBF; color: #1a7a43; }
.bt-alert-danger  { background: #FDEDEC; border: 1px solid #F1948A; color: #922b21; }
.bt-alert-warning { background: #FEF9E7; border: 1px solid #F9E79F; color: #7d6608; }

/* ---------- Tables ---------- */
.bt-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.bt-table th {
  text-align: left; padding: 10px 12px;
  font-size: 11px; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.06em; color: var(--color-text-muted);
  border-bottom: 1px solid var(--color-border);
}
.bt-table td { padding: 10px 12px; border-bottom: 1px solid #F5F0EB; }
.bt-table tr:last-child td { border-bottom: none; }
.bt-table tr:hover td { background: rgba(250,245,240,0.6); }

/* ---------- Chips ---------- */
.bt-chip {
  display: inline-flex; align-items: center;
  padding: 2px 8px; border-radius: 12px;
  font-size: 11px; font-weight: 600;
}
.bt-chip-brown  { background: rgba(92,45,14,0.1);    color: var(--color-primary); }
.bt-chip-gold   { background: rgba(201,168,76,0.15); color: #8B6914; }
.bt-chip-blue   { background: rgba(26,76,139,0.1);   color: var(--color-blue); }
.bt-chip-green  { background: rgba(46,204,113,0.12); color: #1a7a43; }
.bt-chip-grey   { background: #F0F0F0; color: #555; }
.bt-chip-red    { background: rgba(231,76,60,0.1);   color: var(--color-danger); }

/* ---------- Modal ---------- */
.bt-modal-bg {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.45); z-index: 200;
  display: flex; align-items: center; justify-content: center;
}
.bt-modal {
  background: white; border-radius: var(--radius);
  padding: 28px; max-width: 480px; width: 90%;
  max-height: 90vh; overflow-y: auto;
}
.bt-modal-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 20px;
}
.bt-modal-close {
  background: none; border: none; font-size: 18px;
  cursor: pointer; color: var(--color-text-muted); padding: 4px;
  line-height: 1;
}

/* ---------- Wizard Step Bar ---------- */
.bt-steps { display: flex; align-items: flex-start; margin-bottom: 32px; }
.bt-step { flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; }
.bt-step:not(:last-child)::after {
  content: '';
  position: absolute; top: 13px; left: 50%; width: 100%; height: 2px;
  background: var(--color-border); z-index: 0;
}
.bt-step.step-done:not(:last-child)::after { background: var(--color-primary); }
.bt-step-dot {
  width: 26px; height: 26px; border-radius: 50%;
  border: 2px solid var(--color-border); background: white;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; z-index: 1; position: relative;
}
.bt-step.step-active .bt-step-dot { border-color: var(--color-primary); background: var(--color-primary); color: white; }
.bt-step.step-done   .bt-step-dot { border-color: var(--color-primary); background: var(--color-primary); color: white; }
.bt-step-label { font-size: 11px; margin-top: 6px; color: var(--color-text-muted); text-align: center; }
.bt-step.step-active .bt-step-label { color: var(--color-primary); font-weight: 600; }

/* ---------- Selection Cards (Wizard) ---------- */
.bt-select-cards { display: grid; gap: 16px; }
.bt-select-cards.cols-2 { grid-template-columns: 1fr 1fr; }
.bt-select-cards.cols-4 { grid-template-columns: repeat(4, 1fr); }
.bt-select-card {
  border: 2px solid var(--color-border); border-radius: var(--radius);
  padding: 24px 20px; text-align: center; cursor: pointer; transition: all 0.15s;
}
.bt-select-card:hover { border-color: var(--color-primary); background: rgba(92,45,14,0.03); }
.bt-select-card.selected { border-color: var(--color-primary); background: rgba(92,45,14,0.06); }
.bt-select-card .card-icon { font-size: 32px; margin-bottom: 12px; }
.bt-select-card .card-title { font-size: 15px; font-weight: 600; }
.bt-select-card .card-desc { font-size: 12px; color: var(--color-text-muted); margin-top: 4px; }

/* ---------- Landing Page ---------- */
.bt-hero {
  background: linear-gradient(135deg, #5C2D0E 0%, #8B4513 60%, #A0522D 100%);
  color: white; padding: 100px 40px; text-align: center;
  display: flex; flex-direction: column; align-items: center;
}
.bt-hero h1 { font-family: 'Playfair Display', serif; font-size: 52px; font-weight: 700; color: white; margin-bottom: 16px; }
.bt-hero p { font-size: 18px; color: rgba(255,255,255,0.82); max-width: 560px; margin-bottom: 36px; }
.bt-hero-btns { display: flex; gap: 16px; justify-content: center; }
.bt-how { background: var(--color-bg); padding: 72px 40px; text-align: center; }
.bt-how h2 { font-family: 'Playfair Display', serif; font-size: 30px; margin-bottom: 48px; }
.bt-how-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; max-width: 840px; margin: 0 auto; }
.bt-how-card { background: white; border: 1px solid var(--color-border); border-radius: var(--radius); padding: 32px 24px; }
.bt-how-icon {
  width: 60px; height: 60px; border-radius: 50%;
  background: rgba(92,45,14,0.08);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 16px; font-size: 24px; color: var(--color-primary);
}
.bt-how-step { font-size: 11px; font-weight: 700; color: var(--color-primary); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
.bt-how-card h3 { font-size: 17px; font-weight: 600; margin-bottom: 8px; }
.bt-how-card p { font-size: 14px; color: var(--color-text-muted); }
.bt-footer { background: var(--color-primary); color: rgba(255,255,255,0.6); text-align: center; padding: 28px 40px; font-size: 13px; }

/* ---------- Split-Screen Auth ---------- */
.bt-auth-wrap { display: flex; min-height: 100vh; }
.bt-auth-left {
  width: 42%; background: linear-gradient(160deg, #5C2D0E 0%, #8B4513 100%);
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 48px; color: white;
}
.bt-auth-left .auth-brand { font-family: 'Playfair Display', serif; font-size: 34px; font-weight: 700; margin-bottom: 8px; }
.bt-auth-left .auth-tagline { font-size: 15px; color: rgba(255,255,255,0.7); margin-bottom: 48px; }
.bt-auth-illustration {
  width: 140px; height: 140px; border-radius: 50%;
  background: rgba(255,255,255,0.1);
  display: flex; align-items: center; justify-content: center;
  font-size: 60px;
}
.bt-auth-right {
  width: 58%; display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 48px; background: white; overflow-y: auto;
}
.bt-auth-form { width: 100%; max-width: 420px; }
.bt-auth-form h1 { font-family: 'Playfair Display', serif; font-size: 28px; margin-bottom: 4px; }
.bt-auth-sub { font-size: 14px; color: var(--color-text-muted); margin-bottom: 28px; }
.bt-auth-link { font-size: 14px; text-align: center; margin-top: 20px; color: var(--color-text-muted); }
.bt-auth-link a { color: var(--color-primary); font-weight: 500; text-decoration: none; }
.bt-auth-link a:hover { text-decoration: underline; }
.bt-forgot { font-size: 13px; color: var(--color-primary); text-decoration: none; float: right; margin-top: -4px; }
.bt-forgot:hover { text-decoration: underline; }
.pw-toggle-wrap { position: relative; }
.pw-toggle-wrap .bt-input { padding-right: 40px; }
.pw-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 14px; }

/* ---------- Expense Drop Zone ---------- */
.bt-drop-zone {
  border: 2px dashed var(--color-gold); border-radius: var(--radius);
  background: #FDF6EE; padding: 48px 32px; text-align: center; cursor: pointer;
  transition: all 0.15s;
}
.bt-drop-zone:hover, .bt-drop-zone.dragover { background: #FAEADA; border-color: var(--color-primary); }
.bt-drop-zone .drop-icon { font-size: 38px; color: var(--color-primary); margin-bottom: 12px; }
.bt-drop-zone h3 { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
.bt-drop-zone p { font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px; }

/* ---------- Savings Goal Cards ---------- */
.bt-goal-card {
  background: white; border: 1px solid var(--color-border);
  border-radius: var(--radius); padding: 20px; position: relative;
}
.bt-goal-card.completed { background: #F0EBE5; opacity: 0.82; }
.bt-goal-pct-badge {
  position: absolute; top: 14px; right: 14px;
  padding: 3px 9px; border-radius: 12px;
  font-size: 11px; font-weight: 700;
}
.bt-goal-pct-badge.done        { background: rgba(46,204,113,0.15); color: #1a7a43; }
.bt-goal-pct-badge.in-progress { background: rgba(201,168,76,0.15); color: #8B6914; }

/* ---------- Itinerary Calendar ---------- */
.bt-day-strip { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 20px; }
.bt-day-strip::-webkit-scrollbar { height: 4px; }
.bt-day-strip::-webkit-scrollbar-thumb { background: var(--color-border); border-radius: 2px; }
.bt-day-pill {
  flex-shrink: 0; padding: 9px 14px; border-radius: 20px;
  border: 1.5px solid var(--color-border); cursor: pointer;
  text-align: center; background: white; transition: all 0.15s;
  min-width: 58px;
}
.bt-day-pill .day-name { font-size: 10px; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
.bt-day-pill .day-num { font-size: 17px; font-weight: 700; line-height: 1.2; }
.bt-day-pill.has-items .day-num::after { content: '·'; display: block; font-size: 18px; color: var(--color-primary); line-height: 0.4; }
.bt-day-pill.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }
.bt-day-pill.active .day-name { color: rgba(255,255,255,0.75); }
.bt-day-pill.active.has-items .day-num::after { color: rgba(255,255,255,0.75); }
.bt-day-pill:hover:not(.active) { border-color: var(--color-primary); }
.bt-itinerary-item {
  display: flex; gap: 14px; align-items: flex-start;
  padding: 14px; background: white;
  border: 1px solid var(--color-border); border-radius: 8px; margin-bottom: 10px;
}
.bt-itinerary-time { flex-shrink: 0; font-size: 13px; font-weight: 600; color: var(--color-primary); min-width: 56px; }
.bt-itinerary-body { flex: 1; }
.bt-itinerary-title { font-size: 14px; font-weight: 600; }
.bt-itinerary-notes { font-size: 12px; color: var(--color-text-muted); margin-top: 2px; }
.bt-slide-panel { background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 20px; margin-top: 16px; }

/* ---------- Attraction Cards ---------- */
.bt-attraction-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
.bt-attraction-card {
  background: white; border: 1px solid var(--color-border);
  border-radius: var(--radius); overflow: hidden;
  transition: transform 0.15s, box-shadow 0.15s;
}
.bt-attraction-card:hover { transform: translateY(-2px); box-shadow: 0 4px 18px rgba(0,0,0,0.08); }
.bt-attraction-img { height: 130px; background: linear-gradient(135deg, #5C2D0E, #C9A84C); display: flex; align-items: center; justify-content: center; font-size: 36px; }
.bt-attraction-body { padding: 14px; }
.bt-stars { color: #F39C12; letter-spacing: 1px; font-size: 13px; }

/* ---------- Notification / Alert Cards ---------- */
.bt-notif-card {
  background: white; border: 1px solid var(--color-border);
  border-radius: var(--radius); padding: 16px;
  display: flex; gap: 14px; align-items: flex-start;
  margin-bottom: 10px;
}
.bt-notif-card.unread { border-left: 3px solid var(--color-primary); }
.bt-notif-icon {
  width: 40px; height: 40px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 16px;
}
.bt-notif-icon.warning { background: rgba(243,156,18,0.12); color: var(--color-warning); }
.bt-notif-icon.danger  { background: rgba(231,76,60,0.12);  color: var(--color-danger); }
.bt-notif-icon.success { background: rgba(46,204,113,0.12); color: var(--color-success); }
.bt-notif-body { flex: 1; }
.bt-notif-title { font-size: 14px; font-weight: 600; }
.bt-notif-msg   { font-size: 13px; color: var(--color-text-muted); margin-top: 2px; }
.bt-notif-time  { font-size: 11px; color: var(--color-text-muted); margin-top: 4px; }
.bt-unread-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--color-blue); flex-shrink: 0; margin-top: 5px; }

/* ---------- Multi-Trip Hub ---------- */
.bt-trip-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; }
.bt-trip-card {
  border-radius: var(--radius); overflow: hidden; color: white;
  position: relative; min-height: 190px;
  display: flex; flex-direction: column; justify-content: flex-end;
  background: linear-gradient(160deg, #2E7D32, #66BB6A);
}
.bt-trip-card-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.15); }
.bt-trip-card-compare { position: absolute; top: 12px; left: 12px; z-index: 2; }
.bt-trip-card-compare input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; }
.bt-trip-card-status {
  position: absolute; top: 12px; right: 12px; z-index: 2;
  padding: 3px 8px; border-radius: 12px;
  font-size: 10px; font-weight: 700; text-transform: uppercase;
}
.bt-trip-card-status.upcoming  { background: rgba(26,76,139,0.85); }
.bt-trip-card-status.active    { background: rgba(46,204,113,0.85); }
.bt-trip-card-status.completed { background: rgba(0,0,0,0.55); }
.bt-trip-card-body { padding: 16px; background: linear-gradient(0deg, rgba(0,0,0,0.55) 0%, transparent 100%); position: relative; z-index: 2; }
.bt-trip-card-dest  { font-family: 'Playfair Display', serif; font-size: 18px; font-weight: 700; }
.bt-trip-card-dates { font-size: 12px; opacity: 0.85; margin-top: 2px; }
.bt-trip-card-budget { font-size: 12px; opacity: 0.85; margin-top: 4px; }
.bt-trip-card-actions { display: flex; gap: 6px; margin-top: 10px; }
.bt-trip-card-actions a,
.bt-trip-card-actions button {
  padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;
  border: none; cursor: pointer;
  background: rgba(255,255,255,0.22); color: white;
  text-decoration: none; font-family: 'Inter', sans-serif;
  transition: background 0.15s;
}
.bt-trip-card-actions a:hover,
.bt-trip-card-actions button:hover { background: rgba(255,255,255,0.38); color: white; }
.bt-trip-new-card {
  border: 2px dashed var(--color-border); border-radius: var(--radius);
  min-height: 190px; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 8px;
  cursor: pointer; color: var(--color-text-muted);
  text-decoration: none; transition: all 0.15s;
}
.bt-trip-new-card:hover { border-color: var(--color-primary); color: var(--color-primary); background: rgba(92,45,14,0.02); }
.bt-past-trip-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px; background: white;
  border: 1px solid var(--color-border); border-radius: 8px; margin-bottom: 8px;
  filter: grayscale(25%); opacity: 0.8;
}
.bt-past-trip-dest  { font-size: 15px; font-weight: 600; }
.bt-past-trip-dates { font-size: 12px; color: var(--color-text-muted); margin-top: 2px; }
.bt-compare-bar {
  position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
  background: var(--color-primary); color: white;
  padding: 14px 28px; border-radius: 40px;
  display: flex; align-items: center; gap: 16px; z-index: 150;
  box-shadow: 0 4px 24px rgba(0,0,0,0.25);
}
.bt-compare-bar span { font-size: 14px; font-weight: 500; }
.bt-compare-modal { max-width: 640px; }
.bt-compare-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.bt-compare-col h3 { font-size: 16px; font-weight: 600; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid var(--color-primary); }
.bt-compare-metric { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #F0F0F0; font-size: 14px; }
.bt-compare-metric:last-child { border-bottom: none; }
.bt-compare-label { color: var(--color-text-muted); }
.bt-compare-val { font-weight: 600; }

/* ---------- Empty State ---------- */
.bt-empty { text-align: center; padding: 56px 24px; }
.bt-empty-icon { font-size: 52px; margin-bottom: 14px; opacity: 0.28; }
.bt-empty h3 { font-size: 19px; font-weight: 600; margin-bottom: 8px; }
.bt-empty p  { font-size: 14px; color: var(--color-text-muted); margin-bottom: 20px; }

/* ---------- Scan Banner ---------- */
.bt-scan-banner {
  background: white; border: 1px solid var(--color-border);
  border-radius: var(--radius); padding: 18px 24px;
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 24px;
}
.bt-scan-banner-left { display: flex; align-items: center; gap: 14px; }
.bt-scan-banner-icon {
  width: 44px; height: 44px; background: rgba(92,45,14,0.08);
  border-radius: 10px; display: flex; align-items: center; justify-content: center;
  font-size: 20px; color: var(--color-primary);
}
.bt-scan-banner h3 { font-size: 15px; font-weight: 600; margin-bottom: 2px; }
.bt-scan-banner p { font-size: 13px; color: var(--color-text-muted); }

/* ---------- Star Rating ---------- */
.bt-star-picker { display: flex; gap: 4px; }
.bt-star-picker input[type="radio"] { display: none; }
.bt-star-picker label { font-size: 24px; color: #DDD; cursor: pointer; transition: color 0.1s; }
.bt-star-picker input:checked ~ label,
.bt-star-picker label:hover,
.bt-star-picker label:hover ~ label { color: #F39C12; }
/* Reverse trick for star picker */
.bt-star-picker { flex-direction: row-reverse; }
.bt-star-picker label:hover,
.bt-star-picker label:hover ~ label { color: #F39C12; }

/* ---------- Sidebar mobile (collapse) ---------- */
@media (max-width: 768px) {
  .bt-sidebar { width: 60px; }
  .bt-sidebar-brand .brand-name,
  .bt-sidebar-brand .brand-tagline,
  .bt-sidebar-link span,
  .bt-sidebar-link .notif-badge,
  .bt-sidebar-footer .bt-user-name,
  .bt-sidebar-footer .bt-user-email,
  .bt-sidebar-logout { display: none; }
  .bt-main { margin-left: 60px; }
}
```

- [ ] **Step 4: Update `resources/views/layouts/app.blade.php`**

Replace the entire file:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Budgetra') }}</title>
    <link rel="stylesheet" href="{{ asset('css/budgetra.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @livewireStyles
    @stack('styles')
</head>
<body>
    <div class="bt-wrapper">
        <x-sidebar :active="$active ?? ''" />
        <main class="bt-main">
            @yield('content')
        </main>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
```

- [ ] **Step 5: Update `resources/views/components/sidebar.blade.php`**

Replace the entire file:

```html
@props(['active' => ''])
@php $user = auth()->user(); @endphp

<aside class="bt-sidebar">
    <div class="bt-sidebar-brand">
        <div class="brand-name">Budgetra</div>
        <div class="brand-tagline">Plan Smart. Travel More.</div>
    </div>

    <nav class="bt-sidebar-nav">
        @php
        $links = [
            ['href' => url('/dashboard'),   'icon' => 'fa-solid fa-house',            'label' => 'Dashboard',    'key' => 'dashboard'],
            ['href' => url('/trips'),        'icon' => 'fa-solid fa-suitcase-rolling', 'label' => 'My Trips',     'key' => 'trips'],
            ['href' => url('/expenses'),     'icon' => 'fa-solid fa-receipt',          'label' => 'Expenses',     'key' => 'expenses'],
            ['href' => url('/savings'),      'icon' => 'fa-regular fa-circle-dot',     'label' => 'Savings',      'key' => 'savings'],
            ['href' => url('/itinerary'),    'icon' => 'fa-regular fa-calendar-days',  'label' => 'Itinerary',    'key' => 'itinerary'],
            ['href' => url('/attractions'),  'icon' => 'fa-solid fa-mountain-sun',     'label' => 'Attractions',  'key' => 'attractions'],
        ];
        @endphp
        @foreach ($links as $link)
        <a href="{{ $link['href'] }}"
           class="bt-sidebar-link {{ $active === $link['key'] ? 'active' : '' }}"
           title="{{ $link['label'] }}">
            <i class="{{ $link['icon'] }}"></i>
            <span>{{ $link['label'] }}</span>
        </a>
        @endforeach

        {{-- Alerts with live badge --}}
        <a href="{{ url('/alerts') }}"
           class="bt-sidebar-link {{ $active === 'alerts' ? 'active' : '' }}"
           title="Alerts">
            <i class="fa-regular fa-bell"></i>
            <span>Alerts</span>
            @livewire('traveler.notification-badge')
        </a>
    </nav>

    <div class="bt-sidebar-footer">
        <div class="bt-avatar">{{ strtoupper(substr($user->full_name ?? 'U', 0, 1)) }}</div>
        <div>
            <div class="bt-user-name">{{ $user->full_name ?? 'Traveler' }}</div>
            <div class="bt-user-email">{{ $user->email ?? '' }}</div>
        </div>
    </div>
    <div class="bt-sidebar-logout">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Sign out</button>
        </form>
    </div>
</aside>
```

- [ ] **Step 6: Update `routes/web.php` line 7**

Change `Route::get('/', fn() => redirect('/dashboard'));` to:

```php
Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : view('welcome');
});
```

- [ ] **Step 7: Run tests**

```
php artisan test tests/Feature/UI/LandingAuthUiTest.php
```

Expected: 2/5 pass (login and register pages load, dashboard loads). The landing test and root-redirect test still fail because `welcome.blade.php` hasn't been updated yet.

---

### Task 2: Landing Page

**Files:**
- Replace: `resources/views/welcome.blade.php`
- Test: `tests/Feature/UI/LandingAuthUiTest.php` (same file from Task 1)

**Interfaces:**
- Consumes: `public/css/budgetra.css` (from Task 1)
- Produces: `GET /` shows landing page with "Plan Smart" text for guests

- [ ] **Step 1: Replace `resources/views/welcome.blade.php`**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgetra — Plan Smart. Travel More.</title>
    <link rel="stylesheet" href="{{ asset('css/budgetra.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body style="margin:0;padding:0;">

{{-- Hero --}}
<section class="bt-hero">
    <h1>Plan Smart.<br>Travel More.</h1>
    <p>Track budgets, manage itineraries, and hit your savings goals — all in one place.</p>
    <div class="bt-hero-btns">
        <a href="{{ route('register') }}" class="bt-btn bt-btn-gold bt-btn-lg">Start Planning</a>
        <a href="{{ route('login') }}"    class="bt-btn bt-btn-outline-white bt-btn-lg">Sign In</a>
    </div>
</section>

{{-- How It Works --}}
<section class="bt-how">
    <h2>How It Works</h2>
    <div class="bt-how-grid">
        <div class="bt-how-card">
            <div class="bt-how-icon"><i class="fa-solid fa-map-location-dot"></i></div>
            <div class="bt-how-step">Step 1</div>
            <h3>Plan Your Trip</h3>
            <p>Choose your destination, set travel dates, and get an instant cost estimate.</p>
        </div>
        <div class="bt-how-card">
            <div class="bt-how-icon"><i class="fa-solid fa-receipt"></i></div>
            <div class="bt-how-step">Step 2</div>
            <h3>Track Expenses</h3>
            <p>Scan receipts with your camera. OCR auto-fills amounts so logging is effortless.</p>
        </div>
        <div class="bt-how-card">
            <div class="bt-how-icon"><i class="fa-solid fa-piggy-bank"></i></div>
            <div class="bt-how-step">Step 3</div>
            <h3>Hit Your Goals</h3>
            <p>Set savings targets, track deposits, and see exactly when you'll be ready to go.</p>
        </div>
    </div>
</section>

{{-- Footer --}}
<footer class="bt-footer">
    <strong style="color:rgba(255,255,255,0.9);">Budgetra</strong> &mdash; Plan Smart. Travel More.
    <br><span style="font-size:12px;">© {{ date('Y') }} Budgetra. All rights reserved.</span>
</footer>

</body>
</html>
```

- [ ] **Step 2: Run tests — expect all 5 pass**

```
php artisan test tests/Feature/UI/LandingAuthUiTest.php
```

Expected: 5 tests, 5 pass.

- [ ] **Step 3: Run full suite for regressions**

```
php artisan test
```

Expected: all previously passing tests still pass. Pre-existing failures ≤ 3 acceptable.

---

### Task 3: Login Page Redesign

**Files:**
- Replace: `resources/views/auth/login.blade.php`
- Test: existing `tests/Feature/Auth/LoginTest.php` (do not modify)

**Interfaces:**
- Consumes: `public/css/budgetra.css`
- Produces: `GET /login` renders split-screen login with email + password fields (same names as before)

- [ ] **Step 1: Replace `resources/views/auth/login.blade.php`**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Budgetra</title>
    <link rel="stylesheet" href="{{ asset('css/budgetra.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body style="margin:0;padding:0;">

<div class="bt-auth-wrap">
    {{-- Left panel --}}
    <div class="bt-auth-left">
        <div class="auth-brand">Budgetra</div>
        <div class="auth-tagline">Plan Smart. Travel More.</div>
        <div class="bt-auth-illustration">
            <i class="fa-solid fa-plane" style="color:rgba(255,255,255,0.8);font-size:56px;"></i>
        </div>
    </div>

    {{-- Right panel --}}
    <div class="bt-auth-right">
        <div class="bt-auth-form">
            <h1>Welcome back</h1>
            <p class="bt-auth-sub">Sign in to continue your travel journey.</p>

            @if ($errors->any())
            <div class="bt-alert bt-alert-danger">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="bt-form-group">
                    <label class="bt-label" for="email">Email Address</label>
                    <input id="email" type="email" name="email"
                           value="{{ old('email') }}"
                           class="bt-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                           placeholder="you@example.com" required autofocus>
                    @error('email')<div class="bt-error">{{ $message }}</div>@enderror
                </div>

                <div class="bt-form-group">
                    <label class="bt-label" for="password">Password</label>
                    <div class="pw-toggle-wrap">
                        <input id="password" type="password" name="password"
                               class="bt-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                               placeholder="••••••••" required>
                        <button type="button" class="pw-toggle" onclick="
                            var i=document.getElementById('password');
                            i.type=i.type==='password'?'text':'password';
                            this.querySelector('i').className=i.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash';
                        "><i class="fa-solid fa-eye"></i></button>
                    </div>
                    @error('password')<div class="bt-error">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="bt-btn bt-btn-primary bt-btn-lg bt-btn-block">
                    Sign In
                </button>
            </form>

            <p class="bt-auth-link">
                Don't have an account? <a href="{{ route('register') }}">Create one</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>
```

- [ ] **Step 2: Run existing auth tests**

```
php artisan test tests/Feature/Auth/LoginTest.php
```

Expected: all pass (form field names unchanged).

---

### Task 4: Register Page Redesign

**Files:**
- Replace: `resources/views/auth/register.blade.php`
- Test: existing `tests/Feature/Auth/RegisterTest.php` (do not modify)

**Interfaces:**
- Consumes: `public/css/budgetra.css`
- Produces: `GET /register` renders split-screen register

- [ ] **Step 1: Replace `resources/views/auth/register.blade.php`**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — Budgetra</title>
    <link rel="stylesheet" href="{{ asset('css/budgetra.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body style="margin:0;padding:0;">

<div class="bt-auth-wrap">
    {{-- Left panel --}}
    <div class="bt-auth-left">
        <div class="auth-brand">Budgetra</div>
        <div class="auth-tagline">Your journey starts here.</div>
        <div class="bt-auth-illustration">
            <i class="fa-solid fa-earth-asia" style="color:rgba(255,255,255,0.8);font-size:56px;"></i>
        </div>
    </div>

    {{-- Right panel --}}
    <div class="bt-auth-right">
        <div class="bt-auth-form">
            <h1>Create Account</h1>
            <p class="bt-auth-sub">Join thousands of smart travelers.</p>

            @if ($errors->any())
            <div class="bt-alert bt-alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="bt-form-group">
                    <label class="bt-label" for="full_name">Full Name</label>
                    <input id="full_name" type="text" name="full_name"
                           value="{{ old('full_name') }}"
                           class="bt-input {{ $errors->has('full_name') ? 'is-invalid' : '' }}"
                           placeholder="Juan dela Cruz" required autofocus>
                    @error('full_name')<div class="bt-error">{{ $message }}</div>@enderror
                </div>

                <div class="bt-form-group">
                    <label class="bt-label" for="email">Email Address</label>
                    <input id="email" type="email" name="email"
                           value="{{ old('email') }}"
                           class="bt-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                           placeholder="you@example.com" required>
                    @error('email')<div class="bt-error">{{ $message }}</div>@enderror
                </div>

                <div class="bt-input-group">
                    <div class="bt-form-group" style="flex:1;">
                        <label class="bt-label" for="phone">Phone (optional)</label>
                        <input id="phone" type="text" name="phone"
                               value="{{ old('phone') }}"
                               class="bt-input" placeholder="+63 912 345 6789">
                    </div>
                    <div class="bt-form-group" style="flex:1;">
                        <label class="bt-label" for="country">Country</label>
                        <input id="country" type="text" name="country"
                               value="{{ old('country', 'Philippines') }}"
                               class="bt-input {{ $errors->has('country') ? 'is-invalid' : '' }}"
                               placeholder="Philippines" required>
                        @error('country')<div class="bt-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="bt-input-group">
                    <div class="bt-form-group" style="flex:1;">
                        <label class="bt-label" for="currency_code">Currency Code</label>
                        <input id="currency_code" type="text" name="currency_code"
                               value="{{ old('currency_code', 'PHP') }}"
                               class="bt-input {{ $errors->has('currency_code') ? 'is-invalid' : '' }}"
                               placeholder="PHP" maxlength="3" required>
                        @error('currency_code')<div class="bt-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="bt-form-group" style="flex:1;">
                        <label class="bt-label" for="currency_symbol">Currency Symbol</label>
                        <input id="currency_symbol" type="text" name="currency_symbol"
                               value="{{ old('currency_symbol', '₱') }}"
                               class="bt-input {{ $errors->has('currency_symbol') ? 'is-invalid' : '' }}"
                               placeholder="₱" maxlength="5" required>
                        @error('currency_symbol')<div class="bt-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="bt-form-group">
                    <label class="bt-label" for="password">Password</label>
                    <div class="pw-toggle-wrap">
                        <input id="password" type="password" name="password"
                               class="bt-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                               placeholder="Min. 8 characters" required>
                        <button type="button" class="pw-toggle" onclick="
                            var i=document.getElementById('password');
                            i.type=i.type==='password'?'text':'password';
                            this.querySelector('i').className=i.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash';
                        "><i class="fa-solid fa-eye"></i></button>
                    </div>
                    @error('password')<div class="bt-error">{{ $message }}</div>@enderror
                </div>

                <div class="bt-form-group">
                    <label class="bt-label" for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation"
                           class="bt-input" placeholder="Repeat password" required>
                </div>

                <button type="submit" class="bt-btn bt-btn-primary bt-btn-lg bt-btn-block">
                    Create Account
                </button>
            </form>

            <p class="bt-auth-link">
                Already have an account? <a href="{{ route('login') }}">Sign in</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>
```

- [ ] **Step 2: Run existing register tests**

```
php artisan test tests/Feature/Auth/RegisterTest.php
```

Expected: all pass.

- [ ] **Step 3: Run full suite**

```
php artisan test
```

Expected: all previously passing tests still pass. Pre-existing failures ≤ 3 acceptable.

## Report Contract

Write your full report to:
`C:\Users\ASUS\AppData\Local\Temp\claude\c--phpsite-Capstone---Budgetra\55e34b67-7087-4f9b-8add-8becf1178a87\scratchpad\p6-report.md`

Return ONLY: status (DONE/DONE_WITH_CONCERNS/NEEDS_CONTEXT/BLOCKED), files changed, one-line test summary, concerns.
