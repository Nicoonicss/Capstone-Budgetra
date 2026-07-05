# P7: Trip Planning Wizard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the multi-page trip-creation flow with a single 5-step Livewire wizard at `GET /trips/plan` that collects destination, dates, trip type, budget tier, shows a cost estimate, then creates a `Trip` + `TripBudget` records on confirmation.

**Architecture:** A single `TripPlannerWizard` Livewire component manages all 5 steps via a `$step` property (1–5). It holds all form data in public properties and never redirects until the final confirmation. On confirm, it creates the `Trip` and 5 `TripBudget` records then redirects to `/trips/{trip}/dashboard`. The Livewire full-page component is served via `Route::get('/trips/plan', TripPlannerWizard::class)` added before the `{trip}` wildcard routes.

**Tech Stack:** Laravel 13, Livewire 3, MySQL, `public/css/budgetra.css` (from P6)

## Global Constraints

- Laravel 13.18.0, PHP 8.3.12, MySQL
- No git — skip ALL git/commit steps
- Traveler views use `@extends('layouts.app')`; Livewire full-page components use `layout('layouts.app')`
- `public/css/budgetra.css` must already exist (P6 prerequisite)
- Add `GET /trips/plan` route BEFORE `GET /trips/{trip}` in `routes/web.php` — it already has `/trips/type` and `/trips/create` before `{trip}`, add `/trips/plan` in the same block
- `Trip` model `$fillable`: `['user_id','destination','start_date','end_date','num_travelers','budget_limit','travel_type','notes']`
- `TripBudget` model `$fillable`: `['trip_id','category','estimated_cost','actual_spent']`
- Budget categories (5): Transportation, Accommodation, Food, Tourist Attractions, Shopping
- `Destination` model lives at `App\Models\Destination` — use it to populate the destination grid in Step 2
- Run tests: `php artisan test <test-file>`

---

## File Structure

- **MODIFY:** `routes/web.php` — add `Route::get('/trips/plan', \App\Livewire\Traveler\TripPlannerWizard::class)->name('trips.plan');` before line 31 (`GET /trips/{trip}`)
- **REPLACE:** `app/Livewire/Traveler/TripPlanner.php` → rename concept; actually create new file `app/Livewire/Traveler/TripPlannerWizard.php` (the stub `TripPlanner.php` can stay as-is, it's not used by any route)
- **CREATE:** `resources/views/livewire/traveler/trip-planner-wizard.blade.php`
- **CREATE:** `tests/Feature/Livewire/TripPlannerWizardTest.php`

---

### Task 1: TripPlannerWizard Livewire Component

**Files:**
- Modify: `routes/web.php` (add one route line)
- Create: `app/Livewire/Traveler/TripPlannerWizard.php`
- Create: `resources/views/livewire/traveler/trip-planner-wizard.blade.php`
- Test: `tests/Feature/Livewire/TripPlannerWizardTest.php`

**Interfaces:**
- Produces: `GET /trips/plan` (route name `trips.plan`) → 5-step wizard, creates Trip + TripBudgets on confirm
- On confirm → redirect to `GET /trips/{trip}/dashboard` (added in P8; for now redirect to `route('trips.show', $trip)`)

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Livewire/TripPlannerWizardTest.php`:

```php
<?php
namespace Tests\Feature\Livewire;

use App\Livewire\Traveler\TripPlannerWizard;
use App\Models\Destination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TripPlannerWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_wizard_page_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/trips/plan')->assertStatus(200);
    }

    public function test_wizard_starts_at_step_one(): void
    {
        $user = User::factory()->create();
        Livewire::actingAs($user)
            ->test(TripPlannerWizard::class)
            ->assertSet('step', 1);
    }

    public function test_can_advance_to_step_two(): void
    {
        $user = User::factory()->create();
        Livewire::actingAs($user)
            ->test(TripPlannerWizard::class)
            ->set('tripScope', 'international')
            ->call('nextStep')
            ->assertSet('step', 2);
    }

    public function test_wizard_creates_trip_on_confirm(): void
    {
        $user = User::factory()->create();
        $dest = Destination::create(['name' => 'Boracay', 'country' => 'Philippines', 'description' => 'Beautiful island']);

        Livewire::actingAs($user)
            ->test(TripPlannerWizard::class)
            ->set('step', 5)
            ->set('tripScope', 'local')
            ->set('destinationId', $dest->id)
            ->set('destinationName', 'Boracay, Philippines')
            ->set('startDate', '2027-01-10')
            ->set('endDate', '2027-01-15')
            ->set('travelType', 'Solo')
            ->set('budgetTier', 'Mid-range')
            ->set('budgetLimit', 50000)
            ->call('confirm')
            ->assertRedirect();

        $this->assertDatabaseHas('trips', [
            'user_id'     => $user->id,
            'destination' => 'Boracay, Philippines',
            'travel_type' => 'Solo',
        ]);
    }

    public function test_wizard_requires_auth(): void
    {
        $this->get('/trips/plan')->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Livewire/TripPlannerWizardTest.php
```

Expected: FAIL — `TripPlannerWizard` class does not exist, route 404.

- [ ] **Step 3: Add route to `routes/web.php`**

Inside the `Route::middleware(['auth'])->group(...)` block, add this line immediately before `Route::get('/trips/{trip}', ...)` (which is currently around line 32):

```php
Route::get('/trips/plan', \App\Livewire\Traveler\TripPlannerWizard::class)->name('trips.plan');
```

The final ordering around that area must be:
```php
Route::get('/trips',        [Traveler\TripController::class, 'index'])->name('trips.index');
Route::get('/trips/type',   [Traveler\TripController::class, 'type'])->name('trips.type');
Route::get('/trips/create', [Traveler\TripController::class, 'create'])->name('trips.create');
Route::get('/trips/plan',   \App\Livewire\Traveler\TripPlannerWizard::class)->name('trips.plan');
Route::post('/trips',       [Traveler\TripController::class, 'store'])->name('trips.store');
Route::get('/trips/{trip}', [Traveler\TripController::class, 'show'])->name('trips.show');
// ... rest of trip routes
```

- [ ] **Step 4: Create `app/Livewire/Traveler/TripPlannerWizard.php`**

```php
<?php
namespace App\Livewire\Traveler;

use App\Models\Destination;
use App\Models\Trip;
use App\Models\TripBudget;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['active' => 'trips'])]
class TripPlannerWizard extends Component
{
    public int    $step           = 1;
    public string $tripScope      = '';
    public ?int   $destinationId  = null;
    public string $destinationName = '';
    public string $startDate      = '';
    public string $endDate        = '';
    public string $travelType     = '';
    public string $budgetTier     = '';
    public float  $budgetLimit    = 0;
    public string $destSearch     = '';

    // Budget tier multipliers per day (PHP)
    private const TIER_DAILY = [
        'Shoestring' => ['Transportation' => 500,  'Accommodation' => 800,  'Food' => 400,  'Tourist Attractions' => 200, 'Shopping' => 100],
        'Mid-range'  => ['Transportation' => 1200, 'Accommodation' => 2500, 'Food' => 1000, 'Tourist Attractions' => 600, 'Shopping' => 400],
        'Luxury'     => ['Transportation' => 3000, 'Accommodation' => 8000, 'Food' => 2500, 'Tourist Attractions' => 1500,'Shopping' => 1500],
    ];

    public function nextStep(): void
    {
        match ($this->step) {
            1 => $this->validateStep1(),
            2 => $this->validateStep2(),
            3 => $this->validateStep3(),
            4 => $this->validateStep4(),
            default => null,
        };
        if (!$this->getErrorBag()->isEmpty()) return;
        $this->step++;
        if ($this->step === 5) $this->calculateEstimate();
    }

    public function prevStep(): void
    {
        if ($this->step > 1) $this->step--;
    }

    public function selectScope(string $scope): void
    {
        $this->tripScope = $scope;
        $this->nextStep();
    }

    public function selectDestination(int $id, string $name): void
    {
        $this->destinationId   = $id;
        $this->destinationName = $name;
        $this->nextStep();
    }

    public function selectTravelType(string $type): void
    {
        $this->travelType = $type;
    }

    public function selectBudgetTier(string $tier): void
    {
        $this->budgetTier = $tier;
    }

    public function confirm(): mixed
    {
        $this->validate([
            'destinationName' => 'required|string',
            'startDate'       => 'required|date',
            'endDate'         => 'required|date|after_or_equal:startDate',
            'travelType'      => 'required|in:Solo,Family,Couple,Friends',
            'budgetTier'      => 'required|in:Shoestring,Mid-range,Luxury',
            'budgetLimit'     => 'required|numeric|min:1',
        ]);

        $trip = Trip::create([
            'user_id'       => auth()->id(),
            'destination'   => $this->destinationName,
            'start_date'    => $this->startDate,
            'end_date'      => $this->endDate,
            'num_travelers' => 1,
            'budget_limit'  => $this->budgetLimit,
            'travel_type'   => $this->travelType,
            'notes'         => "Budget tier: {$this->budgetTier}",
        ]);

        $days = (int) \Carbon\Carbon::parse($this->startDate)->diffInDays($this->endDate) ?: 1;
        $tiers = self::TIER_DAILY[$this->budgetTier] ?? self::TIER_DAILY['Mid-range'];
        foreach ($tiers as $category => $dailyRate) {
            TripBudget::create([
                'trip_id'        => $trip->id,
                'category'       => $category,
                'estimated_cost' => $dailyRate * $days,
                'actual_spent'   => 0,
            ]);
        }

        return $this->redirect(route('trips.show', $trip), navigate: true);
    }

    private function validateStep1(): void
    {
        $this->validate(['tripScope' => 'required|in:local,international'], [], ['tripScope' => 'trip scope']);
    }

    private function validateStep2(): void
    {
        $this->validate(['destinationId' => 'required|integer']);
    }

    private function validateStep3(): void
    {
        $this->validate([
            'startDate' => 'required|date',
            'endDate'   => 'required|date|after_or_equal:startDate',
        ]);
    }

    private function validateStep4(): void
    {
        $this->validate([
            'travelType' => 'required|in:Solo,Family,Couple,Friends',
            'budgetTier' => 'required|in:Shoestring,Mid-range,Luxury',
        ]);
    }

    private function calculateEstimate(): void
    {
        if (!$this->budgetTier || !$this->startDate || !$this->endDate) return;
        $days  = (int) \Carbon\Carbon::parse($this->startDate)->diffInDays($this->endDate) ?: 1;
        $tiers = self::TIER_DAILY[$this->budgetTier] ?? self::TIER_DAILY['Mid-range'];
        $this->budgetLimit = (float) collect($tiers)->sum() * $days;
    }

    public function getDestinationsProperty()
    {
        $query = Destination::orderBy('name');
        if ($this->destSearch) {
            $query->where('name', 'like', "%{$this->destSearch}%");
        }
        return $query->get();
    }

    public function getDaysProperty(): int
    {
        if (!$this->startDate || !$this->endDate) return 0;
        return (int) \Carbon\Carbon::parse($this->startDate)->diffInDays($this->endDate);
    }

    public function getCategoryBreakdownProperty(): array
    {
        if (!$this->budgetTier || !$this->startDate || !$this->endDate) return [];
        $days  = $this->getDaysProperty() ?: 1;
        $tiers = self::TIER_DAILY[$this->budgetTier] ?? [];
        return collect($tiers)->map(fn($rate) => $rate * $days)->toArray();
    }

    public function render()
    {
        return view('livewire.traveler.trip-planner-wizard');
    }
}
```

- [ ] **Step 5: Check that `Destination` model exists**

```
php artisan tinker --execute="echo App\Models\Destination::count();"
```

If `Destination` model doesn't exist or table is missing, create the model:

```php
// app/Models/Destination.php — only create if it doesn't exist
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Destination extends Model
{
    protected $fillable = ['name', 'country', 'description', 'image'];
}
```

- [ ] **Step 6: Create `resources/views/livewire/traveler/trip-planner-wizard.blade.php`**

```html
<div>
    {{-- Page heading --}}
    <div class="bt-flex-between mb-24">
        <div>
            <h1>Plan New Adventure</h1>
            <p class="text-muted">Follow the steps to create your perfect trip.</p>
        </div>
        <a href="{{ route('trips.index') }}" class="bt-btn bt-btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>

    {{-- Step indicator --}}
    <div class="bt-steps mb-24">
        @php
        $stepLabels = ['Scope', 'Destination', 'Dates', 'Details', 'Estimate'];
        @endphp
        @foreach ($stepLabels as $i => $label)
        @php $n = $i + 1; @endphp
        <div class="bt-step {{ $step > $n ? 'step-done' : ($step === $n ? 'step-active' : '') }}">
            <div class="bt-step-dot">
                @if ($step > $n) <i class="fa-solid fa-check" style="font-size:10px;"></i>
                @else {{ $n }} @endif
            </div>
            <div class="bt-step-label">{{ $label }}</div>
        </div>
        @endforeach
    </div>

    <div class="bt-card" style="max-width:680px;margin:0 auto;">

        {{-- Step 1: Local / International --}}
        @if ($step === 1)
        <h2 class="mb-16">What kind of trip?</h2>
        <div class="bt-select-cards cols-2">
            <div class="bt-select-card {{ $tripScope === 'local' ? 'selected' : '' }}"
                 wire:click="selectScope('local')">
                <div class="card-icon">🏝️</div>
                <div class="card-title">Local</div>
                <div class="card-desc">Within the Philippines</div>
            </div>
            <div class="bt-select-card {{ $tripScope === 'international' ? 'selected' : '' }}"
                 wire:click="selectScope('international')">
                <div class="card-icon">✈️</div>
                <div class="card-title">International</div>
                <div class="card-desc">Outside the Philippines</div>
            </div>
        </div>
        @error('tripScope')<div class="bt-error mt-8">{{ $message }}</div>@enderror
        @endif

        {{-- Step 2: Destination grid --}}
        @if ($step === 2)
        <h2 class="mb-8">Choose Your Destination</h2>
        <div class="bt-form-group">
            <input type="text" wire:model.debounce.300ms="destSearch"
                   class="bt-input" placeholder="Search destinations...">
        </div>
        <div class="bt-attraction-grid" style="max-height:380px;overflow-y:auto;">
            @forelse ($this->destinations as $dest)
            <div class="bt-attraction-card" wire:click="selectDestination({{ $dest->id }}, '{{ $dest->name }}, {{ $dest->country }}')"
                 style="cursor:pointer;{{ $destinationId === $dest->id ? 'outline:2px solid #5C2D0E;' : '' }}">
                <div class="bt-attraction-img">🗺️</div>
                <div class="bt-attraction-body">
                    <div style="font-size:14px;font-weight:600;">{{ $dest->name }}</div>
                    <div class="text-muted" style="font-size:12px;">{{ $dest->country }}</div>
                </div>
            </div>
            @empty
            <div class="bt-empty" style="grid-column:1/-1;">
                <div class="bt-empty-icon">🌍</div>
                <p>No destinations found. Try a different search.</p>
            </div>
            @endforelse
        </div>
        @error('destinationId')<div class="bt-error mt-8">Please select a destination.</div>@enderror
        <div class="bt-flex-between mt-16">
            <button class="bt-btn bt-btn-outline" wire:click="prevStep">← Back</button>
        </div>
        @endif

        {{-- Step 3: Dates --}}
        @if ($step === 3)
        <h2 class="mb-16">When are you going?</h2>
        <div class="bt-grid-2">
            <div class="bt-form-group">
                <label class="bt-label">Departure Date</label>
                <input type="date" wire:model="startDate"
                       class="bt-input {{ $errors->has('startDate') ? 'is-invalid' : '' }}"
                       min="{{ date('Y-m-d') }}">
                @error('startDate')<div class="bt-error">{{ $message }}</div>@enderror
            </div>
            <div class="bt-form-group">
                <label class="bt-label">Return Date</label>
                <input type="date" wire:model="endDate"
                       class="bt-input {{ $errors->has('endDate') ? 'is-invalid' : '' }}"
                       min="{{ $startDate ?: date('Y-m-d') }}">
                @error('endDate')<div class="bt-error">{{ $message }}</div>@enderror
            </div>
        </div>
        @if ($startDate && $endDate)
        <div class="bt-alert bt-alert-success">
            <i class="fa-solid fa-calendar-check"></i>
            {{ $this->days }} day{{ $this->days !== 1 ? 's' : '' }} trip to <strong>{{ $destinationName }}</strong>
        </div>
        @endif
        <div class="bt-flex-between mt-16">
            <button class="bt-btn bt-btn-outline" wire:click="prevStep">← Back</button>
            <button class="bt-btn bt-btn-primary" wire:click="nextStep">Next →</button>
        </div>
        @endif

        {{-- Step 4: Trip type + budget tier --}}
        @if ($step === 4)
        <h2 class="mb-16">Trip Details</h2>
        <div class="bt-form-group">
            <label class="bt-label">Who's traveling?</label>
            <div class="bt-select-cards cols-4">
                @foreach (['Solo', 'Family', 'Couple', 'Friends'] as $type)
                @php $icons = ['Solo' => '🧍', 'Family' => '👨‍👩‍👧', 'Couple' => '👫', 'Friends' => '👥']; @endphp
                <div class="bt-select-card {{ $travelType === $type ? 'selected' : '' }}"
                     wire:click="selectTravelType('{{ $type }}')">
                    <div class="card-icon">{{ $icons[$type] }}</div>
                    <div class="card-title">{{ $type }}</div>
                </div>
                @endforeach
            </div>
            @error('travelType')<div class="bt-error mt-4">{{ $message }}</div>@enderror
        </div>
        <div class="bt-form-group mt-16">
            <label class="bt-label">Budget style</label>
            <div class="bt-select-cards cols-2" style="grid-template-columns:1fr 1fr 1fr;">
                @foreach (['Shoestring', 'Mid-range', 'Luxury'] as $tier)
                @php $tierIcons = ['Shoestring' => '🎒', 'Mid-range' => '🏨', 'Luxury' => '💎']; @endphp
                @php $tierDescs = ['Shoestring' => 'Budget traveler', 'Mid-range' => 'Comfortable stay', 'Luxury' => 'Premium experience']; @endphp
                <div class="bt-select-card {{ $budgetTier === $tier ? 'selected' : '' }}"
                     wire:click="selectBudgetTier('{{ $tier }}')">
                    <div class="card-icon">{{ $tierIcons[$tier] }}</div>
                    <div class="card-title">{{ $tier }}</div>
                    <div class="card-desc">{{ $tierDescs[$tier] }}</div>
                </div>
                @endforeach
            </div>
            @error('budgetTier')<div class="bt-error mt-4">{{ $message }}</div>@enderror
        </div>
        <div class="bt-flex-between mt-16">
            <button class="bt-btn bt-btn-outline" wire:click="prevStep">← Back</button>
            <button class="bt-btn bt-btn-primary" wire:click="nextStep">Calculate Estimate →</button>
        </div>
        @endif

        {{-- Step 5: Cost estimator --}}
        @if ($step === 5)
        <h2 class="mb-4">Trip Cost Estimator</h2>
        <p class="text-muted mb-16">{{ $destinationName }} · {{ $this->days }} days · {{ $travelType }} · {{ $budgetTier }}</p>

        <div class="bt-stat-card primary mb-16">
            <div class="bt-stat-label"><i class="fa-solid fa-coins"></i> Estimated Total</div>
            <div class="bt-stat-value">₱{{ number_format($budgetLimit, 2) }}</div>
            <div class="bt-stat-sub">Based on {{ $this->days }} days at {{ $budgetTier }} level</div>
        </div>

        <div class="bt-card mb-16" style="padding:0;">
            <table class="bt-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-right" style="padding-right:12px;">Estimated Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->categoryBreakdown as $cat => $amount)
                    <tr>
                        <td>{{ $cat }}</td>
                        <td class="text-right" style="padding-right:12px;">₱{{ number_format($amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bt-form-group">
            <label class="bt-label">Adjust Total Budget (₱)</label>
            <input type="number" wire:model="budgetLimit"
                   class="bt-input" step="100" min="1">
            <div class="text-muted mt-4" style="font-size:12px;">You can adjust the estimate above.</div>
        </div>

        @if ($errors->any())
        <div class="bt-alert bt-alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="bt-flex-between mt-16">
            <button class="bt-btn bt-btn-outline" wire:click="prevStep">← Back</button>
            <button class="bt-btn bt-btn-primary bt-btn-lg" wire:click="confirm" wire:loading.attr="disabled">
                <span wire:loading.remove>Confirm & Create Trip</span>
                <span wire:loading><i class="fa-solid fa-spinner fa-spin"></i> Creating...</span>
            </button>
        </div>
        @endif

    </div>
</div>
```

- [ ] **Step 7: Run tests — expect PASS**

```
php artisan test tests/Feature/Livewire/TripPlannerWizardTest.php
```

Expected: 5 tests, all pass.

- [ ] **Step 8: Run full suite for regressions**

```
php artisan test
```

Expected: all previously passing tests still pass.

## Report Contract

Write your full report to:
`C:\Users\ASUS\AppData\Local\Temp\claude\c--phpsite-Capstone---Budgetra\55e34b67-7087-4f9b-8add-8becf1178a87\scratchpad\p7-report.md`

Return ONLY: status (DONE/DONE_WITH_CONCERNS/NEEDS_CONTEXT/BLOCKED), files changed, one-line test summary, concerns.
