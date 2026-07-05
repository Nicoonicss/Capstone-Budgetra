# P13: Multi-Trip Hub Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the existing `/trips` index view with a polished Multi-Trip Hub: a Livewire `MultiTripHub` component with live search, active trip cards (with comparison checkbox), past trips as a greyed list, and a side-by-side comparison modal for 2 selected trips.

**Architecture:** The existing `TripController::index()` is updated to return the hub view. The `MultiTripHub` Livewire component is embedded in the index view and handles search, active/past segmentation, comparison selection state, and the modal. The component calls `auth()->user()->trips()` directly — no additional controller methods needed.

**Tech Stack:** Laravel 13, Livewire 3, `public/css/budgetra.css`

## Global Constraints

- Laravel 13.18.0, PHP 8.3.12, MySQL
- No git — skip ALL git/commit steps
- `Trip` model `$fillable`: `['user_id','destination','start_date','end_date','num_travelers','budget_limit','travel_type','notes']`
- Trip status logic: upcoming = `start_date > today`, active = `start_date <= today AND end_date >= today`, past = `end_date < today`
- Comparison modal: shown only when exactly 2 trips are selected via `$compareIds` array
- Comparison metric table: Budget, Spent, Budget Used %, Daily Avg, Duration
- `Route::get('/trips', ...)` already exists pointing to `TripController::index()` — only modify the controller method and view
- `TripController::index()` currently returns `view('traveler.trips.index')` — update to pass data and embed Livewire component
- Run tests: `php artisan test <test-file>`

---

## File Structure

- **MODIFY:** `app/Http/Controllers/Traveler/TripController.php` — update `index()` to pass aggregate data
- **CREATE:** `app/Livewire/Traveler/MultiTripHub.php`
- **CREATE:** `resources/views/livewire/traveler/multi-trip-hub.blade.php`
- **REPLACE:** `resources/views/traveler/trips/index.blade.php`
- **CREATE:** `tests/Feature/Livewire/MultiTripHubTest.php`

---

### Task 1: MultiTripHub Livewire Component

**Files:**
- Modify: `app/Http/Controllers/Traveler/TripController.php` (index method only)
- Create: `app/Livewire/Traveler/MultiTripHub.php`
- Create: `resources/views/livewire/traveler/multi-trip-hub.blade.php`
- Replace: `resources/views/traveler/trips/index.blade.php`
- Test: `tests/Feature/Livewire/MultiTripHubTest.php`

**Interfaces:**
- Produces: `GET /trips` shows the Multi-Trip Hub with search, active cards, past list, comparison modal

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Livewire/MultiTripHubTest.php`:

```php
<?php
namespace Tests\Feature\Livewire;

use App\Livewire\Traveler\MultiTripHub;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultiTripHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_trips_index_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/trips')->assertStatus(200)->assertSee('Multi-Trip Hub');
    }

    public function test_search_filters_trips(): void
    {
        $user = User::factory()->create();
        Trip::factory()->create(['user_id' => $user->id, 'destination' => 'Boracay, Philippines']);
        Trip::factory()->create(['user_id' => $user->id, 'destination' => 'Bangkok, Thailand']);

        Livewire::actingAs($user)
            ->test(MultiTripHub::class)
            ->set('search', 'Boracay')
            ->assertSee('Boracay')
            ->assertDontSee('Bangkok');
    }

    public function test_compare_ids_can_be_toggled(): void
    {
        $user  = User::factory()->create();
        $trip1 = Trip::factory()->create(['user_id' => $user->id]);
        $trip2 = Trip::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(MultiTripHub::class)
            ->call('toggleCompare', $trip1->id)
            ->assertSet('compareIds', [$trip1->id])
            ->call('toggleCompare', $trip2->id)
            ->assertSet('compareIds', [$trip1->id, $trip2->id]);
    }

    public function test_comparison_modal_opens_with_two_trips(): void
    {
        $user  = User::factory()->create();
        $trip1 = Trip::factory()->create(['user_id' => $user->id]);
        $trip2 = Trip::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(MultiTripHub::class)
            ->set('compareIds', [$trip1->id, $trip2->id])
            ->call('openComparison')
            ->assertSet('showComparison', true);
    }

    public function test_empty_state_shown_when_no_trips(): void
    {
        $user = User::factory()->create();
        Livewire::actingAs($user)
            ->test(MultiTripHub::class)
            ->assertSee('No trips yet');
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Livewire/MultiTripHubTest.php
```

Expected: FAIL — `MultiTripHub` class does not exist, page doesn't say "Multi-Trip Hub".

- [ ] **Step 3: Update `TripController::index()` in `app/Http/Controllers/Traveler/TripController.php`**

Find the `index()` method (do NOT change any other methods). Replace only the `index()` method body:

```php
public function index()
{
    return view('traveler.trips.index');
}
```

- [ ] **Step 4: Create `app/Livewire/Traveler/MultiTripHub.php`**

```php
<?php
namespace App\Livewire\Traveler;

use App\Models\Trip;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['active' => 'trips'])]
class MultiTripHub extends Component
{
    public string $search         = '';
    public array  $compareIds     = [];
    public bool   $showComparison = false;

    public function toggleCompare(int $tripId): void
    {
        if (in_array($tripId, $this->compareIds)) {
            $this->compareIds = array_values(array_filter($this->compareIds, fn($id) => $id !== $tripId));
        } else {
            if (count($this->compareIds) < 2) {
                $this->compareIds[] = $tripId;
            }
        }
    }

    public function openComparison(): void
    {
        if (count($this->compareIds) === 2) {
            $this->showComparison = true;
        }
    }

    public function closeComparison(): void
    {
        $this->showComparison = false;
    }

    public function clearCompare(): void
    {
        $this->compareIds     = [];
        $this->showComparison = false;
    }

    public function getTripsProperty()
    {
        $query = auth()->user()->trips()->latest('start_date');
        if ($this->search) {
            $query->where('destination', 'like', "%{$this->search}%");
        }
        return $query->get()->map(function (Trip $trip) {
            $today = Carbon::today();
            $spent = $trip->expenses()->sum('amount');
            $days  = max(1, (int) $trip->start_date->diffInDays($trip->end_date));
            $trip->setAttribute('total_spent', (float) $spent);
            $trip->setAttribute('pct_used',    $trip->budget_limit > 0 ? round($spent / $trip->budget_limit * 100) : 0);
            $trip->setAttribute('days',        $days);
            $trip->setAttribute('daily_avg',   round($spent / $days, 2));
            $trip->setAttribute('status',
                $trip->start_date->gt($today) ? 'upcoming' :
                ($trip->end_date->lt($today)  ? 'past'     : 'active'));
            return $trip;
        });
    }

    public function getActiveTripsProperty()
    {
        return $this->trips->whereIn('status', ['upcoming', 'active'])->values();
    }

    public function getPastTripsProperty()
    {
        return $this->trips->where('status', 'past')->values();
    }

    public function getTotalsProperty(): array
    {
        $all = $this->trips;
        return [
            'count'  => $all->count(),
            'budget' => $all->sum('budget_limit'),
            'spent'  => $all->sum('total_spent'),
        ];
    }

    public function getCompareTripsProperty(): array
    {
        if (count($this->compareIds) !== 2) return [];
        return $this->trips->whereIn('id', $this->compareIds)->values()->toArray();
    }

    public function render()
    {
        return view('livewire.traveler.multi-trip-hub');
    }
}
```

- [ ] **Step 5: Create `resources/views/livewire/traveler/multi-trip-hub.blade.php`**

```html
<div>
    {{-- Header --}}
    <div class="bt-flex-between mb-24">
        <div>
            <h1>Multi-Trip Hub</h1>
            <p class="text-muted">{{ $this->totals['count'] }} trip{{ $this->totals['count'] !== 1 ? 's' : '' }} total</p>
        </div>
        <a href="{{ route('trips.plan') }}" class="bt-btn bt-btn-primary">
            <i class="fa-solid fa-plus"></i> Plan New Adventure
        </a>
    </div>

    {{-- Aggregate stat tiles --}}
    <div class="bt-grid-3 mb-24">
        <div class="bt-stat-card primary">
            <div class="bt-stat-label"><i class="fa-solid fa-suitcase-rolling"></i> Total Trips</div>
            <div class="bt-stat-value">{{ $this->totals['count'] }}</div>
        </div>
        <div class="bt-stat-card gold">
            <div class="bt-stat-label"><i class="fa-solid fa-coins"></i> Total Budget</div>
            <div class="bt-stat-value">₱{{ number_format($this->totals['budget'], 0) }}</div>
        </div>
        <div class="bt-stat-card blue">
            <div class="bt-stat-label"><i class="fa-regular fa-credit-card"></i> Total Spent</div>
            <div class="bt-stat-value">₱{{ number_format($this->totals['spent'], 0) }}</div>
        </div>
    </div>

    {{-- Search --}}
    <div class="bt-flex-between mb-16">
        <div style="max-width:300px;width:100%;">
            <input type="text" wire:model.debounce.300ms="search"
                   class="bt-input" placeholder="Search by destination...">
        </div>
        @if (count($compareIds) > 0)
        <div class="bt-flex" style="gap:8px;">
            <span class="text-muted" style="font-size:13px;">{{ count($compareIds) }}/2 selected</span>
            @if (count($compareIds) === 2)
            <button class="bt-btn bt-btn-primary bt-btn-sm" wire:click="openComparison">Compare Now</button>
            @endif
            <button class="bt-btn bt-btn-outline bt-btn-sm" wire:click="clearCompare">Clear</button>
        </div>
        @endif
    </div>

    {{-- Active Trips --}}
    @if ($this->activeTrips->isNotEmpty())
    <h2 class="mb-16">Active Trips</h2>
    <div class="bt-trip-grid mb-32">
        @foreach ($this->activeTrips as $trip)
        <div class="bt-trip-card" style="background:linear-gradient(160deg, #2E7D32, #66BB6A);">
            <div class="bt-trip-card-overlay"></div>
            <div class="bt-trip-card-compare">
                <input type="checkbox"
                       wire:click="toggleCompare({{ $trip->id }})"
                       {{ in_array($trip->id, $compareIds) ? 'checked' : '' }}
                       title="Select for comparison">
            </div>
            <span class="bt-trip-card-status {{ $trip->status }}">{{ ucfirst($trip->status) }}</span>
            <div class="bt-trip-card-body">
                <div class="bt-trip-card-dest">{{ $trip->destination }}</div>
                <div class="bt-trip-card-dates">
                    {{ $trip->start_date->format('M j') }} – {{ $trip->end_date->format('M j, Y') }}
                    · {{ $trip->days }} days
                </div>
                <div class="bt-trip-card-budget">
                    Spent ₱{{ number_format($trip->total_spent, 0) }} / ₱{{ number_format($trip->budget_limit, 0) }}
                </div>
                <div class="bt-progress mt-8" style="background:rgba(255,255,255,0.25);">
                    <div class="bt-progress-bar"
                         style="background:rgba(255,255,255,0.85);width:{{ min(100,$trip->pct_used) }}%;"></div>
                </div>
                <div class="bt-trip-card-actions mt-8">
                    <a href="{{ route('trips.dashboard', $trip) }}">Dashboard</a>
                    <a href="{{ route('expenses.index') }}?trip_id={{ $trip->id }}">Expenses</a>
                </div>
            </div>
        </div>
        @endforeach

        {{-- New trip card --}}
        <a href="{{ route('trips.plan') }}" class="bt-trip-new-card">
            <i class="fa-solid fa-plus" style="font-size:26px;"></i>
            <span style="font-size:13px;font-weight:600;">Plan New Adventure</span>
        </a>
    </div>
    @endif

    {{-- Past Trips --}}
    @if ($this->pastTrips->isNotEmpty())
    <h2 class="mb-16">Past Trips</h2>
    @foreach ($this->pastTrips as $trip)
    <div class="bt-past-trip-row">
        <div>
            <div class="bt-past-trip-dest">{{ $trip->destination }}</div>
            <div class="bt-past-trip-dates">{{ $trip->start_date->format('M j') }} – {{ $trip->end_date->format('M j, Y') }}</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:13px;font-weight:600;">₱{{ number_format($trip->budget_limit, 0) }}</div>
            <div class="text-muted" style="font-size:11px;">Budget</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:13px;font-weight:600;">₱{{ number_format($trip->total_spent, 0) }}</div>
            <div class="text-muted" style="font-size:11px;">Spent</div>
        </div>
        <span class="bt-chip bt-chip-grey">PAST</span>
        <div class="bt-flex" style="gap:6px;">
            <input type="checkbox"
                   wire:click="toggleCompare({{ $trip->id }})"
                   {{ in_array($trip->id, $compareIds) ? 'checked' : '' }}
                   title="Compare">
            <a href="{{ route('trips.dashboard', $trip) }}" class="bt-btn bt-btn-outline bt-btn-sm">View</a>
        </div>
    </div>
    @endforeach
    @endif

    {{-- Empty state --}}
    @if ($this->trips->isEmpty())
    <div class="bt-empty">
        <div class="bt-empty-icon">✈️</div>
        <h3>No trips yet</h3>
        <p>Start planning your first adventure and track every expense.</p>
        <a href="{{ route('trips.plan') }}" class="bt-btn bt-btn-primary bt-btn-lg">Plan Your First Trip</a>
    </div>
    @elseif ($search && $this->trips->isEmpty())
    <div class="bt-empty">
        <div class="bt-empty-icon">🔍</div>
        <h3>No trips match "{{ $search }}"</h3>
        <p>Try a different search term.</p>
    </div>
    @endif

    {{-- Comparison Modal --}}
    @if ($showComparison && count($this->compareTrips) === 2)
    @php [$t1, $t2] = $this->compareTrips; @endphp
    <div class="bt-modal-bg" wire:click.self="closeComparison">
        <div class="bt-modal bt-compare-modal" style="max-width:640px;">
            <div class="bt-modal-header">
                <h2>Trip Comparison</h2>
                <button class="bt-modal-close" wire:click="closeComparison">×</button>
            </div>
            <div class="bt-compare-grid">
                @foreach ([$t1, $t2] as $t)
                @php $tArr = (array) $t; @endphp
                <div class="bt-compare-col">
                    <h3>{{ $tArr['destination'] ?? $t['destination'] }}</h3>
                    @php
                    $dest   = is_array($t) ? $t['destination']   : $t->destination;
                    $start  = is_array($t) ? $t['start_date']    : $t->start_date;
                    $end    = is_array($t) ? $t['end_date']      : $t->end_date;
                    $budget = is_array($t) ? $t['budget_limit']  : $t->budget_limit;
                    $spent  = is_array($t) ? $t['total_spent']   : $t->total_spent;
                    $pct    = is_array($t) ? $t['pct_used']      : $t->pct_used;
                    $avg    = is_array($t) ? $t['daily_avg']     : $t->daily_avg;
                    $days   = is_array($t) ? $t['days']          : $t->days;
                    @endphp
                    <div class="bt-compare-metric">
                        <span class="bt-compare-label">Budget</span>
                        <span class="bt-compare-val">₱{{ number_format($budget, 0) }}</span>
                    </div>
                    <div class="bt-compare-metric">
                        <span class="bt-compare-label">Spent</span>
                        <span class="bt-compare-val">₱{{ number_format($spent, 0) }}</span>
                    </div>
                    <div class="bt-compare-metric">
                        <span class="bt-compare-label">Budget Used</span>
                        <span class="bt-compare-val">{{ $pct }}%</span>
                    </div>
                    <div class="bt-compare-metric">
                        <span class="bt-compare-label">Daily Avg</span>
                        <span class="bt-compare-val">₱{{ number_format($avg, 0) }}</span>
                    </div>
                    <div class="bt-compare-metric">
                        <span class="bt-compare-label">Duration</span>
                        <span class="bt-compare-val">{{ $days }} days</span>
                    </div>
                </div>
                @endforeach
            </div>
            <button class="bt-btn bt-btn-outline bt-btn-block mt-16" wire:click="closeComparison">Close</button>
        </div>
    </div>
    @endif
</div>
```

- [ ] **Step 6: Replace `resources/views/traveler/trips/index.blade.php`**

```html
@extends('layouts.app')
@section('title', 'My Trips')
@section('content')
@livewire('traveler.multi-trip-hub')
@endsection
```

- [ ] **Step 7: Run tests — expect PASS**

```
php artisan test tests/Feature/Livewire/MultiTripHubTest.php
```

Expected: 5 tests, all pass.

- [ ] **Step 8: Run full suite for regressions**

```
php artisan test
```

Expected: all previously passing tests still pass.

## Report Contract

Write your full report to:
`C:\Users\ASUS\AppData\Local\Temp\claude\c--phpsite-Capstone---Budgetra\55e34b67-7087-4f9b-8add-8becf1178a87\scratchpad\p13-report.md`

Return ONLY: status (DONE/DONE_WITH_CONCERNS/NEEDS_CONTEXT/BLOCKED), files changed, one-line test summary, concerns.
