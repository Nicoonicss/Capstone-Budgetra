# P9: Savings Goals UI Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the savings goals UI with polished goal cards, a Livewire deposit modal, a projection modal (daily savings needed formula), and darkened completed goals. Also redesign the create/edit views.

**Architecture:** A new `SavingsGoalManager` Livewire component replaces the inline deposit forms on the index view. The index page (`/savings`) is served by the existing `SavingsGoalController::index()` (unchanged). The Livewire component is embedded inside the index Blade view and handles deposit + projection modals. `create.blade.php` and `edit.blade.php` are restyled with `budgetra.css` classes.

**Tech Stack:** Laravel 13, Livewire 3, `public/css/budgetra.css`

## Global Constraints

- Laravel 13.18.0, PHP 8.3.12, MySQL
- No git — skip ALL git/commit steps
- `SavingsGoal` model `$fillable`: `['user_id','trip_id','goal_name','target_amount','current_savings','deadline']`
- Deposit route: `PATCH /savings/{goal}/deposit` → `SavingsGoalController::deposit()` — already exists, do NOT change controller
- Completed goal: `current_savings >= target_amount`
- Projection formula: `daily_needed = (target_amount - current_savings) / max(1, days_until_deadline)`
- Livewire component class: `App\Livewire\Traveler\SavingsGoalManager`
- Livewire view: `resources/views/livewire/traveler/savings-goal-manager.blade.php`
- The existing stub `App\Livewire\Traveler\SavingsGoal` at `app/Livewire/Traveler/SavingsGoal.php` renders `livewire.traveler.savings-goal` — do NOT touch it
- Run tests: `php artisan test <test-file>`

---

## File Structure

- **CREATE:** `app/Livewire/Traveler/SavingsGoalManager.php`
- **CREATE:** `resources/views/livewire/traveler/savings-goal-manager.blade.php`
- **REPLACE:** `resources/views/traveler/savings/index.blade.php`
- **REPLACE:** `resources/views/traveler/savings/create.blade.php`
- **REPLACE:** `resources/views/traveler/savings/edit.blade.php`
- **CREATE:** `tests/Feature/Livewire/SavingsGoalManagerTest.php`

---

### Task 1: SavingsGoalManager Livewire Component + Index View

**Files:**
- Create: `app/Livewire/Traveler/SavingsGoalManager.php`
- Create: `resources/views/livewire/traveler/savings-goal-manager.blade.php`
- Replace: `resources/views/traveler/savings/index.blade.php`
- Test: `tests/Feature/Livewire/SavingsGoalManagerTest.php`

**Interfaces:**
- Consumes: `SavingsGoal` models from `auth()->user()->savingsGoals()` — passed as `$goals` from `SavingsGoalController::index()`
- Produces: deposit modal (posts to `PATCH /savings/{goal}/deposit`), projection modal (read-only calculation)

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Livewire/SavingsGoalManagerTest.php`:

```php
<?php
namespace Tests\Feature\Livewire;

use App\Livewire\Traveler\SavingsGoalManager;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SavingsGoalManagerTest extends TestCase
{
    use RefreshDatabase;

    private function makeGoal(User $user, array $attrs = []): SavingsGoal
    {
        return SavingsGoal::create(array_merge([
            'user_id'         => $user->id,
            'goal_name'       => 'Test Fund',
            'target_amount'   => 10000,
            'current_savings' => 1000,
            'deadline'        => '2030-12-31',
        ], $attrs));
    }

    public function test_savings_index_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/savings')->assertStatus(200)->assertSee('Savings Goals');
    }

    public function test_deposit_modal_opens(): void
    {
        $user = User::factory()->create();
        $goal = $this->makeGoal($user);
        Livewire::actingAs($user)
            ->test(SavingsGoalManager::class, ['goal' => $goal])
            ->call('openDeposit')
            ->assertSet('showDeposit', true);
    }

    public function test_deposit_adds_to_savings(): void
    {
        $user = User::factory()->create();
        $goal = $this->makeGoal($user);
        Livewire::actingAs($user)
            ->test(SavingsGoalManager::class, ['goal' => $goal])
            ->set('depositAmount', 500)
            ->call('submitDeposit')
            ->assertSet('showDeposit', false);

        $this->assertDatabaseHas('savings_goals', ['id' => $goal->id, 'current_savings' => 1500]);
    }

    public function test_projection_modal_opens(): void
    {
        $user = User::factory()->create();
        $goal = $this->makeGoal($user);
        Livewire::actingAs($user)
            ->test(SavingsGoalManager::class, ['goal' => $goal])
            ->call('openProjection')
            ->assertSet('showProjection', true);
    }

    public function test_completed_goal_shows_on_index(): void
    {
        $user = User::factory()->create();
        $this->makeGoal($user, ['current_savings' => 10000, 'target_amount' => 10000]);
        $this->actingAs($user)->get('/savings')->assertStatus(200)->assertSee('COMPLETED');
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Livewire/SavingsGoalManagerTest.php
```

Expected: FAIL — `SavingsGoalManager` class does not exist.

- [ ] **Step 3: Create `app/Livewire/Traveler/SavingsGoalManager.php`**

```php
<?php
namespace App\Livewire\Traveler;

use App\Models\SavingsGoal;
use Carbon\Carbon;
use Livewire\Component;

class SavingsGoalManager extends Component
{
    public SavingsGoal $goal;

    public bool  $showDeposit    = false;
    public bool  $showProjection = false;
    public float $depositAmount  = 0;

    public function openDeposit(): void
    {
        $this->depositAmount = 0;
        $this->showDeposit   = true;
    }

    public function closeDeposit(): void
    {
        $this->showDeposit = false;
    }

    public function openProjection(): void
    {
        $this->showProjection = true;
    }

    public function closeProjection(): void
    {
        $this->showProjection = false;
    }

    public function submitDeposit(): void
    {
        abort_if($this->goal->user_id !== auth()->id(), 403);
        $this->validate(['depositAmount' => 'required|numeric|min:0.01']);
        $this->goal->increment('current_savings', $this->depositAmount);
        $this->goal->refresh();
        $this->depositAmount = 0;
        $this->showDeposit   = false;
        $this->dispatch('goalUpdated');
    }

    public function getPctProperty(): float
    {
        if (!$this->goal->target_amount) return 0;
        return min(100, round($this->goal->current_savings / $this->goal->target_amount * 100, 1));
    }

    public function getDailyNeededProperty(): float
    {
        $remaining = $this->goal->target_amount - $this->goal->current_savings;
        if ($remaining <= 0) return 0;
        $days = max(1, (int) Carbon::today()->diffInDays($this->goal->deadline, false));
        return $days > 0 ? round($remaining / $days, 2) : $remaining;
    }

    public function getDaysLeftProperty(): int
    {
        return max(0, (int) Carbon::today()->diffInDays($this->goal->deadline, false));
    }

    public function getIsCompletedProperty(): bool
    {
        return $this->goal->current_savings >= $this->goal->target_amount;
    }

    public function render()
    {
        return view('livewire.traveler.savings-goal-manager');
    }
}
```

- [ ] **Step 4: Create `resources/views/livewire/traveler/savings-goal-manager.blade.php`**

```html
<div>
    {{-- Goal card --}}
    <div class="bt-goal-card {{ $this->isCompleted ? 'completed' : '' }}">
        {{-- % Badge --}}
        <div class="bt-goal-pct-badge {{ $this->isCompleted ? 'done' : 'in-progress' }}">
            {{ $this->isCompleted ? 'COMPLETED' : $this->pct . '%' }}
        </div>

        {{-- Header --}}
        <h3 style="margin-bottom:4px;padding-right:80px;">{{ $goal->goal_name }}</h3>
        @if ($goal->trip)
        <div class="text-muted mb-8" style="font-size:12px;">
            <i class="fa-solid fa-map-location-dot"></i> {{ $goal->trip->destination }}
        </div>
        @endif

        {{-- Amount + deadline --}}
        <div class="bt-flex-between mb-8">
            <span style="font-size:15px;font-weight:600;">
                ₱{{ number_format($goal->current_savings, 2) }}
                <span class="text-muted" style="font-weight:400;font-size:13px;">/ ₱{{ number_format($goal->target_amount, 2) }}</span>
            </span>
            <span class="text-muted" style="font-size:12px;">
                @if ($this->daysLeft > 0)
                    {{ $this->daysLeft }} days left · {{ $goal->deadline->format('M j, Y') }}
                @else
                    Past deadline
                @endif
            </span>
        </div>

        {{-- Progress bar --}}
        <div class="bt-progress mb-16">
            <div class="bt-progress-bar {{ $this->isCompleted ? 'success' : '' }}"
                 style="width:{{ $this->pct }}%;"></div>
        </div>

        {{-- Actions --}}
        @if (!$this->isCompleted)
        <div class="bt-flex" style="gap:8px;flex-wrap:wrap;">
            <button class="bt-btn bt-btn-success bt-btn-sm" wire:click="openDeposit">
                <i class="fa-solid fa-plus"></i> Add Savings
            </button>
            <button class="bt-btn bt-btn-outline bt-btn-sm" wire:click="openProjection">
                <i class="fa-solid fa-chart-line"></i> Projection
            </button>
            <a href="{{ route('savings.edit', $goal) }}" class="bt-btn bt-btn-outline bt-btn-sm">
                <i class="fa-solid fa-pen"></i> Edit
            </a>
            <form method="POST" action="{{ route('savings.destroy', $goal) }}"
                  onsubmit="return confirm('Delete this goal?')">
                @csrf @method('DELETE')
                <button class="bt-btn bt-btn-danger bt-btn-sm">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </form>
        </div>
        @else
        <div class="bt-flex" style="gap:8px;">
            <span class="bt-chip bt-chip-green"><i class="fa-solid fa-check"></i> Goal Reached!</span>
            <form method="POST" action="{{ route('savings.destroy', $goal) }}"
                  onsubmit="return confirm('Delete this goal?')">
                @csrf @method('DELETE')
                <button class="bt-btn bt-btn-outline bt-btn-sm">Delete</button>
            </form>
        </div>
        @endif
    </div>

    {{-- Deposit modal --}}
    @if ($showDeposit)
    <div class="bt-modal-bg" wire:click.self="closeDeposit">
        <div class="bt-modal">
            <div class="bt-modal-header">
                <h2>Add Savings</h2>
                <button class="bt-modal-close" wire:click="closeDeposit">×</button>
            </div>
            <p class="text-muted mb-16">Add money to <strong>{{ $goal->goal_name }}</strong>. Current: ₱{{ number_format($goal->current_savings, 2) }}</p>
            <div class="bt-form-group">
                <label class="bt-label">Deposit Amount (₱)</label>
                <input type="number" wire:model="depositAmount" class="bt-input {{ $errors->has('depositAmount') ? 'is-invalid' : '' }}"
                       step="0.01" min="0.01" placeholder="0.00" autofocus>
                @error('depositAmount')<div class="bt-error">{{ $message }}</div>@enderror
            </div>
            <div class="bt-flex-between mt-16">
                <button class="bt-btn bt-btn-outline" wire:click="closeDeposit">Cancel</button>
                <button class="bt-btn bt-btn-success" wire:click="submitDeposit">
                    <i class="fa-solid fa-plus"></i> Add ₱{{ number_format($depositAmount ?: 0, 2) }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Projection modal --}}
    @if ($showProjection)
    <div class="bt-modal-bg" wire:click.self="closeProjection">
        <div class="bt-modal">
            <div class="bt-modal-header">
                <h2>Savings Projection</h2>
                <button class="bt-modal-close" wire:click="closeProjection">×</button>
            </div>
            <div class="bt-stat-card primary mb-16">
                <div class="bt-stat-label">Daily Savings Needed</div>
                <div class="bt-stat-value">₱{{ number_format($this->dailyNeeded, 2) }}</div>
                <div class="bt-stat-sub">to reach your goal by {{ $goal->deadline->format('M j, Y') }}</div>
            </div>
            <div style="font-size:14px;line-height:1.8;">
                <div class="bt-flex-between" style="padding:8px 0;border-bottom:1px solid var(--color-border);">
                    <span class="text-muted">Target Amount</span>
                    <strong>₱{{ number_format($goal->target_amount, 2) }}</strong>
                </div>
                <div class="bt-flex-between" style="padding:8px 0;border-bottom:1px solid var(--color-border);">
                    <span class="text-muted">Current Savings</span>
                    <strong>₱{{ number_format($goal->current_savings, 2) }}</strong>
                </div>
                <div class="bt-flex-between" style="padding:8px 0;border-bottom:1px solid var(--color-border);">
                    <span class="text-muted">Amount Remaining</span>
                    <strong>₱{{ number_format(max(0, $goal->target_amount - $goal->current_savings), 2) }}</strong>
                </div>
                <div class="bt-flex-between" style="padding:8px 0;">
                    <span class="text-muted">Days Until Deadline</span>
                    <strong>{{ $this->daysLeft }} days</strong>
                </div>
            </div>
            <div class="bt-progress mt-16">
                <div class="bt-progress-bar success" style="width:{{ $this->pct }}%;"></div>
            </div>
            <p class="text-muted mt-8" style="font-size:12px;text-align:center;">{{ $this->pct }}% of goal reached</p>
            <button class="bt-btn bt-btn-outline bt-btn-block mt-16" wire:click="closeProjection">Close</button>
        </div>
    </div>
    @endif
</div>
```

- [ ] **Step 5: Replace `resources/views/traveler/savings/index.blade.php`**

```html
@extends('layouts.app')
@section('title', 'Savings Goals')
@section('content')

<div class="bt-flex-between mb-24">
    <div>
        <h1>Savings Goals</h1>
        <p class="text-muted">Track your progress toward each trip fund.</p>
    </div>
    <a href="{{ route('savings.create') }}" class="bt-btn bt-btn-primary">
        <i class="fa-solid fa-plus"></i> New Goal
    </a>
</div>

@if (session('success'))
<div class="bt-alert bt-alert-success">{{ session('success') }}</div>
@endif

@forelse ($goals as $goal)
@livewire('traveler.savings-goal-manager', ['goal' => $goal], key($goal->id))
<div class="mt-16"></div>
@empty
<div class="bt-empty">
    <div class="bt-empty-icon">🐷</div>
    <h3>No savings goals yet</h3>
    <p>Start saving for your next trip!</p>
    <a href="{{ route('savings.create') }}" class="bt-btn bt-btn-primary bt-btn-lg">Create First Goal</a>
</div>
@endforelse

@endsection
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Livewire/SavingsGoalManagerTest.php
```

Expected: 5 tests, all pass.

---

### Task 2: Savings Create + Edit View Redesign

**Files:**
- Replace: `resources/views/traveler/savings/create.blade.php`
- Replace: `resources/views/traveler/savings/edit.blade.php`
- Test: existing `tests/Feature/SavingsGoal/SavingsGoalTest.php` (do not modify)

**Interfaces:**
- Consumes: same form fields as before — `goal_name`, `target_amount`, `current_savings`, `deadline`, `trip_id`
- Produces: same POST/PUT behavior, new visual style

- [ ] **Step 1: Replace `resources/views/traveler/savings/create.blade.php`**

```html
@extends('layouts.app')
@section('title', 'New Savings Goal')
@section('content')

<div class="bt-flex-between mb-24">
    <h1>New Savings Goal</h1>
    <a href="{{ route('savings.index') }}" class="bt-btn bt-btn-outline">← Back</a>
</div>

@if ($errors->any())
<div class="bt-alert bt-alert-danger">{{ $errors->first() }}</div>
@endif

<div class="bt-card" style="max-width:540px;">
    <form method="POST" action="{{ route('savings.store') }}">
        @csrf
        <div class="bt-form-group">
            <label class="bt-label" for="goal_name">Goal Name</label>
            <input id="goal_name" type="text" name="goal_name"
                   value="{{ old('goal_name') }}"
                   class="bt-input {{ $errors->has('goal_name') ? 'is-invalid' : '' }}"
                   placeholder="e.g. Boracay Trip Fund" required autofocus>
            @error('goal_name')<div class="bt-error">{{ $message }}</div>@enderror
        </div>

        <div class="bt-grid-2">
            <div class="bt-form-group">
                <label class="bt-label" for="target_amount">Target Amount (₱)</label>
                <input id="target_amount" type="number" step="0.01" name="target_amount"
                       value="{{ old('target_amount') }}"
                       class="bt-input {{ $errors->has('target_amount') ? 'is-invalid' : '' }}"
                       placeholder="50000" min="1" required>
                @error('target_amount')<div class="bt-error">{{ $message }}</div>@enderror
            </div>
            <div class="bt-form-group">
                <label class="bt-label" for="current_savings">Current Savings (₱)</label>
                <input id="current_savings" type="number" step="0.01" name="current_savings"
                       value="{{ old('current_savings', 0) }}"
                       class="bt-input" placeholder="0.00" min="0">
            </div>
        </div>

        <div class="bt-form-group">
            <label class="bt-label" for="deadline">Deadline</label>
            <input id="deadline" type="date" name="deadline"
                   value="{{ old('deadline') }}"
                   class="bt-input {{ $errors->has('deadline') ? 'is-invalid' : '' }}"
                   min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
            @error('deadline')<div class="bt-error">{{ $message }}</div>@enderror
        </div>

        <div class="bt-form-group">
            <label class="bt-label" for="trip_id">Link to Trip (optional)</label>
            <select id="trip_id" name="trip_id" class="bt-select">
                <option value="">— No trip —</option>
                @foreach ($trips as $trip)
                <option value="{{ $trip->id }}" {{ old('trip_id') == $trip->id ? 'selected' : '' }}>
                    {{ $trip->destination }} ({{ $trip->start_date->format('M Y') }})
                </option>
                @endforeach
            </select>
        </div>

        <div class="bt-flex-between mt-8">
            <a href="{{ route('savings.index') }}" class="bt-btn bt-btn-outline">Cancel</a>
            <button type="submit" class="bt-btn bt-btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Create Goal
            </button>
        </div>
    </form>
</div>

@endsection
```

- [ ] **Step 2: Replace `resources/views/traveler/savings/edit.blade.php`**

```html
@extends('layouts.app')
@section('title', 'Edit Savings Goal')
@section('content')

<div class="bt-flex-between mb-24">
    <h1>Edit: {{ $goal->goal_name }}</h1>
    <a href="{{ route('savings.index') }}" class="bt-btn bt-btn-outline">← Back</a>
</div>

@if ($errors->any())
<div class="bt-alert bt-alert-danger">{{ $errors->first() }}</div>
@endif

<div class="bt-card" style="max-width:540px;">
    <form method="POST" action="{{ route('savings.update', $goal) }}">
        @csrf @method('PUT')
        <div class="bt-form-group">
            <label class="bt-label" for="goal_name">Goal Name</label>
            <input id="goal_name" type="text" name="goal_name"
                   value="{{ old('goal_name', $goal->goal_name) }}"
                   class="bt-input {{ $errors->has('goal_name') ? 'is-invalid' : '' }}" required>
            @error('goal_name')<div class="bt-error">{{ $message }}</div>@enderror
        </div>

        <div class="bt-grid-2">
            <div class="bt-form-group">
                <label class="bt-label" for="target_amount">Target Amount (₱)</label>
                <input id="target_amount" type="number" step="0.01" name="target_amount"
                       value="{{ old('target_amount', $goal->target_amount) }}"
                       class="bt-input {{ $errors->has('target_amount') ? 'is-invalid' : '' }}"
                       min="1" required>
                @error('target_amount')<div class="bt-error">{{ $message }}</div>@enderror
            </div>
            <div class="bt-form-group">
                <label class="bt-label" for="current_savings">Current Savings (₱)</label>
                <input id="current_savings" type="number" step="0.01" name="current_savings"
                       value="{{ old('current_savings', $goal->current_savings) }}"
                       class="bt-input {{ $errors->has('current_savings') ? 'is-invalid' : '' }}" min="0">
                @error('current_savings')<div class="bt-error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="bt-form-group">
            <label class="bt-label" for="deadline">Deadline</label>
            <input id="deadline" type="date" name="deadline"
                   value="{{ old('deadline', $goal->deadline->format('Y-m-d')) }}"
                   class="bt-input {{ $errors->has('deadline') ? 'is-invalid' : '' }}" required>
            @error('deadline')<div class="bt-error">{{ $message }}</div>@enderror
        </div>

        <div class="bt-form-group">
            <label class="bt-label" for="trip_id">Link to Trip (optional)</label>
            <select id="trip_id" name="trip_id" class="bt-select">
                <option value="">— No trip —</option>
                @foreach ($trips as $trip)
                <option value="{{ $trip->id }}"
                    {{ old('trip_id', $goal->trip_id) == $trip->id ? 'selected' : '' }}>
                    {{ $trip->destination }} ({{ $trip->start_date->format('M Y') }})
                </option>
                @endforeach
            </select>
        </div>

        <div class="bt-flex-between mt-8">
            <a href="{{ route('savings.index') }}" class="bt-btn bt-btn-outline">Cancel</a>
            <button type="submit" class="bt-btn bt-btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Update Goal
            </button>
        </div>
    </form>
</div>

@endsection
```

- [ ] **Step 3: Run existing savings tests**

```
php artisan test tests/Feature/SavingsGoal/SavingsGoalTest.php
```

Expected: 6 tests, all pass.

- [ ] **Step 4: Run full suite for regressions**

```
php artisan test
```

Expected: all previously passing tests still pass.

## Report Contract

Write your full report to:
`C:\Users\ASUS\AppData\Local\Temp\claude\c--phpsite-Capstone---Budgetra\55e34b67-7087-4f9b-8add-8becf1178a87\scratchpad\p9-report.md`

Return ONLY: status (DONE/DONE_WITH_CONCERNS/NEEDS_CONTEXT/BLOCKED), files changed, one-line test summary, concerns.
