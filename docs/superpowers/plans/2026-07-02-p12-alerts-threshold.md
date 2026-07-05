# P12: Alerts UI + 50% Budget Threshold Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** (1) Create a `NotificationBadge` Livewire component that displays the unread alert count in the sidebar with `wire:poll.30s`. (2) Update `ExpenseObserver` to fire notifications at both 50% and 80% thresholds (currently only 80%). (3) Redesign the `/alerts` view.

**Architecture:** `NotificationBadge` is a tiny Livewire component embedded in the sidebar's "Alerts" nav item. It polls every 30 seconds and shows a red count badge when there are unread notifications. The sidebar (`resources/views/components/sidebar.blade.php`) already has a `@livewire('traveler.notification-badge')` call (added in P6). The `ExpenseObserver` gains a 50% check alongside the existing 80% check. The alerts view is a standard Blade view served by the existing `AlertController`.

**Tech Stack:** Laravel 13, Livewire 3, `public/css/budgetra.css`

## Global Constraints

- Laravel 13.18.0, PHP 8.3.12, MySQL
- No git — skip ALL git/commit steps
- `Notification` model `$fillable`: `['user_id','trip_id','type','message','is_read']`; `is_read` cast to boolean
- `AlertController::index()` fetches `auth()->user()->notifications()->latest()->paginate(20)` — do not change the controller
- `AlertController::markRead()` sets `is_read = true` — do not change
- `ExpenseObserver::syncBudgetForExpense()` currently fires at ≥ 80% (`pct >= 0.80`). Add a 50% check using type `'budget_warning'` alongside the existing `'budget_alert'` at 80%
- De-duplicate notifications by checking `type` — one notification per type per trip (regardless of `is_read` state)
- Livewire component class: `App\Livewire\Traveler\NotificationBadge`
- Livewire view: `resources/views/livewire/traveler/notification-badge.blade.php`
- The sidebar's Alerts link already calls `@livewire('traveler.notification-badge')` (from P6 plan) — the badge component just needs to exist
- Run tests: `php artisan test <test-file>`

---

## File Structure

- **CREATE:** `app/Livewire/Traveler/NotificationBadge.php`
- **CREATE:** `resources/views/livewire/traveler/notification-badge.blade.php`
- **MODIFY:** `app/Observers/ExpenseObserver.php` — add 50% threshold
- **REPLACE:** `resources/views/traveler/alerts/index.blade.php`
- **CREATE:** `tests/Feature/AlertsUiTest.php`

---

### Task 1: NotificationBadge Livewire Component

**Files:**
- Create: `app/Livewire/Traveler/NotificationBadge.php`
- Create: `resources/views/livewire/traveler/notification-badge.blade.php`
- Test: `tests/Feature/AlertsUiTest.php`

**Interfaces:**
- Produces: badge that renders inside sidebar `<a>` for Alerts; `wire:poll.30s` auto-refreshes count

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AlertsUiTest.php`:

```php
<?php
namespace Tests\Feature;

use App\Livewire\Traveler\NotificationBadge;
use App\Models\Expense;
use App\Models\Notification;
use App\Models\Trip;
use App\Models\TripBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AlertsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerts_page_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/alerts')->assertStatus(200)->assertSee('Alerts');
    }

    public function test_badge_shows_unread_count(): void
    {
        $user = User::factory()->create();
        Notification::create([
            'user_id' => $user->id, 'trip_id' => null,
            'type' => 'budget_warning', 'message' => 'Test alert', 'is_read' => false,
        ]);
        Livewire::actingAs($user)
            ->test(NotificationBadge::class)
            ->assertSee('1');
    }

    public function test_badge_hidden_when_no_unread(): void
    {
        $user = User::factory()->create();
        $html = Livewire::actingAs($user)
            ->test(NotificationBadge::class)
            ->html();
        $this->assertStringNotContainsString('notif-badge', $html);
    }

    public function test_50_percent_threshold_fires_notification(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        TripBudget::create([
            'trip_id'        => $trip->id,
            'category'       => 'Food',
            'estimated_cost' => 1000,
            'actual_spent'   => 0,
        ]);

        $expense = Expense::create([
            'trip_id'      => $trip->id,
            'user_id'      => $user->id,
            'amount'       => 500,
            'category'     => 'Food',
            'description'  => 'Lunch',
            'expense_date' => now()->toDateString(),
        ]);

        \App\Observers\ExpenseObserver::syncBudgetForExpense($expense);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'trip_id' => $trip->id,
            'type'    => 'budget_warning',
        ]);
    }

    public function test_80_percent_threshold_still_fires(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        TripBudget::create([
            'trip_id'        => $trip->id,
            'category'       => 'Food',
            'estimated_cost' => 1000,
            'actual_spent'   => 0,
        ]);

        $expense = Expense::create([
            'trip_id'      => $trip->id,
            'user_id'      => $user->id,
            'amount'       => 800,
            'category'     => 'Food',
            'description'  => 'Dinner',
            'expense_date' => now()->toDateString(),
        ]);

        \App\Observers\ExpenseObserver::syncBudgetForExpense($expense);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type'    => 'budget_alert',
        ]);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/AlertsUiTest.php
```

Expected: `test_badge_shows_unread_count` and `test_badge_hidden_when_no_unread` FAIL — `NotificationBadge` class does not exist. `test_50_percent_threshold_fires_notification` FAILS.

- [ ] **Step 3: Create `app/Livewire/Traveler/NotificationBadge.php`**

```php
<?php
namespace App\Livewire\Traveler;

use Livewire\Attributes\Poll;
use Livewire\Component;

class NotificationBadge extends Component
{
    #[Poll('30s')]
    public function render()
    {
        $count = auth()->check()
            ? auth()->user()->notifications()->where('is_read', false)->count()
            : 0;
        return view('livewire.traveler.notification-badge', ['count' => $count]);
    }
}
```

- [ ] **Step 4: Create `resources/views/livewire/traveler/notification-badge.blade.php`**

```html
<span>
    @if ($count > 0)
    <span class="notif-badge">{{ $count > 99 ? '99+' : $count }}</span>
    @endif
</span>
```

- [ ] **Step 5: Update `app/Observers/ExpenseObserver.php`**

Replace the entire file (keep existing 80% logic, add 50%):

```php
<?php
namespace App\Observers;

use App\Models\Expense;
use App\Models\Notification;
use App\Models\Trip;
use App\Models\TripBudget;

class ExpenseObserver
{
    private const CATEGORY_MAP = [
        'Transportation'     => 'Transportation',
        'Accommodation'      => 'Accommodation',
        'Food'               => 'Food',
        'Activities'         => 'Tourist Attractions',
        'Shopping'           => 'Shopping',
        'Emergency Expenses' => 'Emergency Funds',
    ];

    public function deleted(Expense $expense): void
    {
        $this->adjustActualSpent($expense->trip_id, $expense->category, -$expense->amount);
    }

    public static function syncBudgetForExpense(Expense $expense): void
    {
        $budgetCategory = self::CATEGORY_MAP[$expense->category] ?? null;
        if (!$budgetCategory) return;

        $budget = TripBudget::where('trip_id', $expense->trip_id)
                             ->where('category', $budgetCategory)
                             ->first();
        if (!$budget) return;

        $budget->increment('actual_spent', $expense->amount);
        $budget->refresh();

        if ($budget->estimated_cost <= 0) return;

        $pct  = $budget->actual_spent / $budget->estimated_cost;
        $trip = Trip::find($expense->trip_id);
        if (!$trip) return;

        // 50% threshold
        if ($pct >= 0.50 && $pct < 0.80) {
            $exists = Notification::where('user_id', $trip->user_id)
                ->where('trip_id', $expense->trip_id)
                ->where('type', 'budget_warning')
                ->whereRaw("message LIKE ?", ["%{$budgetCategory}%"])
                ->exists();
            if (!$exists) {
                Notification::create([
                    'user_id' => $trip->user_id,
                    'trip_id' => $expense->trip_id,
                    'type'    => 'budget_warning',
                    'message' => "Heads up: Your {$budgetCategory} budget for {$trip->destination} has reached " . round($pct * 100) . "% of the estimated amount.",
                ]);
            }
        }

        // 80% threshold
        if ($pct >= 0.80) {
            $exists = Notification::where('user_id', $trip->user_id)
                ->where('trip_id', $expense->trip_id)
                ->where('type', 'budget_alert')
                ->whereRaw("message LIKE ?", ["%{$budgetCategory}%"])
                ->exists();
            if (!$exists) {
                Notification::create([
                    'user_id' => $trip->user_id,
                    'trip_id' => $expense->trip_id,
                    'type'    => 'budget_alert',
                    'message' => "Warning: Your {$budgetCategory} budget for {$trip->destination} has reached " . round($pct * 100) . "% of the estimated amount.",
                ]);
            }
        }
    }

    private function adjustActualSpent(int $tripId, string $expenseCategory, float $delta): void
    {
        $budgetCategory = self::CATEGORY_MAP[$expenseCategory] ?? null;
        if (!$budgetCategory) return;

        TripBudget::where('trip_id', $tripId)
                  ->where('category', $budgetCategory)
                  ->increment('actual_spent', $delta);
    }
}
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/AlertsUiTest.php
```

Expected: 5 tests, all pass.

---

### Task 2: Alerts View Redesign

**Files:**
- Replace: `resources/views/traveler/alerts/index.blade.php`
- Test: existing `tests/Feature/Alert/AlertTest.php`

**Interfaces:**
- Consumes: `$notifications` paginated collection from `AlertController::index()`
- Produces: styled alert page with unread indicator and "Mark as Read" buttons

- [ ] **Step 1: Read existing `AlertController::index()` to confirm variable names**

The existing controller is at `app/Http/Controllers/Traveler/AlertController.php`. Confirm it passes `$notifications` to the view and has `markRead` method at `PATCH /alerts/{notification}/read`.

- [ ] **Step 2: Replace `resources/views/traveler/alerts/index.blade.php`**

```html
@extends('layouts.app')
@section('title', 'Alerts')
@section('content')

<div class="bt-flex-between mb-24">
    <div>
        <h1>Alerts & Notifications</h1>
        <p class="text-muted">Budget warnings and trip updates.</p>
    </div>
    @php $unreadCount = auth()->user()->notifications()->where('is_read', false)->count(); @endphp
    @if ($unreadCount > 0)
    <span class="bt-chip bt-chip-brown">{{ $unreadCount }} unread</span>
    @endif
</div>

@if (session('success'))
<div class="bt-alert bt-alert-success">{{ session('success') }}</div>
@endif

@forelse ($notifications as $notif)
@php
$iconClass = match($notif->type) {
    'budget_warning' => 'warning',
    'budget_alert'   => 'danger',
    default          => 'success',
};
$icon = match($notif->type) {
    'budget_warning' => 'fa-triangle-exclamation',
    'budget_alert'   => 'fa-circle-exclamation',
    default          => 'fa-bell',
};
$title = match($notif->type) {
    'budget_warning' => 'Budget Warning (50%)',
    'budget_alert'   => 'Budget Alert (80%+)',
    default          => 'Notification',
};
@endphp

<div class="bt-notif-card {{ !$notif->is_read ? 'unread' : '' }}">
    <div class="bt-notif-icon {{ $iconClass }}">
        <i class="fa-solid {{ $icon }}"></i>
    </div>
    <div class="bt-notif-body">
        <div class="bt-notif-title">{{ $title }}</div>
        <div class="bt-notif-msg">{{ $notif->message }}</div>
        <div class="bt-notif-time">{{ $notif->created_at->diffForHumans() }}</div>
    </div>
    @if (!$notif->is_read)
    <div class="bt-unread-dot"></div>
    @endif
    @if (!$notif->is_read)
    <form method="POST" action="{{ route('alerts.read', $notif) }}" style="flex-shrink:0;">
        @csrf @method('PATCH')
        <button class="bt-btn bt-btn-outline bt-btn-sm">Mark read</button>
    </form>
    @else
    <span class="text-muted" style="font-size:11px;flex-shrink:0;">Read</span>
    @endif
</div>

@empty
<div class="bt-empty">
    <div class="bt-empty-icon">🔔</div>
    <h3>You're all caught up!</h3>
    <p>No notifications yet. They'll appear here when your budget thresholds are reached.</p>
</div>
@endforelse

@if ($notifications->hasPages())
<div class="mt-24">{{ $notifications->links() }}</div>
@endif

@endsection
```

- [ ] **Step 3: Run existing alert tests**

```
php artisan test tests/Feature/Alert
```

Expected: all pass (controller unchanged).

- [ ] **Step 4: Run full suite for regressions**

```
php artisan test
```

Expected: all previously passing tests still pass.

## Report Contract

Write your full report to:
`C:\Users\ASUS\AppData\Local\Temp\claude\c--phpsite-Capstone---Budgetra\55e34b67-7087-4f9b-8add-8becf1178a87\scratchpad\p12-report.md`

Return ONLY: status (DONE/DONE_WITH_CONCERNS/NEEDS_CONTEXT/BLOCKED), files changed, one-line test summary, concerns.
