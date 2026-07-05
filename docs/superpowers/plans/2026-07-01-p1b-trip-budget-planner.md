# Trip & Budget Planner Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement full trip CRUD, per-category budget allocation per trip, trip detail summary (budget vs actual), and BudgetService calculations.

**Architecture:** TripController handles CRUD; TripBudget rows (one per category per trip) are managed in bulk on a dedicated budget page. BudgetService encapsulates all financial calculations. `actual_spent` on trip_budgets is updated by the Expense module (Plan 3) — this plan only handles estimated_cost.

**Tech Stack:** Laravel 13.x, Blade, PHP 8.3+, MySQL.

## Global Constraints

- Project root: `c:\phpsite\Capstone - Budgetra`
- Laravel 13.18.0, PHP 8.3.12
- Requires Plan 1 (Auth) complete — routes/web.php already has profile routes added; all traveler routes are in `auth` middleware group
- `trips` columns: `user_id`, `destination`, `start_date`, `end_date`, `num_travelers` (tinyInteger, default 1), `budget_limit` (decimal 10,2, nullable), `travel_type` (enum: `Solo`|`Family`|`Couple`|`Friends`), `notes` (nullable)
- `trip_budgets` columns: `trip_id`, `category` (enum: `Transportation`|`Accommodation`|`Food`|`Tourist Attractions`|`Shopping`|`Emergency Funds`), `estimated_cost` (decimal 10,2, default 0), `actual_spent` (decimal 10,2, default 0)
- `BUDGET_CATEGORIES` constant = `['Transportation','Accommodation','Food','Tourist Attractions','Shopping','Emergency Funds']`
- Skip git commit steps

---

### Task 1: TripFactory + Missing Trip Routes

**Files:**
- Create/Modify: `database/factories/TripFactory.php`
- Modify: `routes/web.php` — add show, edit, update, destroy, budget routes
- Test: `tests/Feature/Trip/TripRouteTest.php`

**Interfaces:**
- Produces: `Trip::factory()->create(['user_id' => $user->id])` with valid defaults
- Produces: routes `trips.show`, `trips.edit`, `trips.update`, `trips.destroy`, `trips.budget`, `trips.budgetStore`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Trip/TripRouteTest.php
namespace Tests\Feature\Trip;

use App\Models\User;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_factory_creates_valid_trip(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'user_id' => $user->id]);
        $this->assertContains($trip->travel_type, ['Solo', 'Family', 'Couple', 'Friends']);
    }

    public function test_trips_index_requires_auth(): void
    {
        $this->get('/trips')->assertRedirect(route('login'));
    }

    public function test_trips_show_requires_auth(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $this->get("/trips/{$trip->id}")->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 2: Run test — expect FAIL (TripFactory missing)**

```
php artisan test tests/Feature/Trip/TripRouteTest.php
```

- [ ] **Step 3: Create/update TripFactory**

```php
<?php
// database/factories/TripFactory.php
namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+3 months');
        $end   = fake()->dateTimeBetween($start, '+14 days');

        return [
            'user_id'      => User::factory(),
            'destination'  => fake()->city() . ', ' . fake()->country(),
            'start_date'   => $start->format('Y-m-d'),
            'end_date'     => $end->format('Y-m-d'),
            'num_travelers'=> fake()->numberBetween(1, 6),
            'budget_limit' => fake()->randomFloat(2, 5000, 100000),
            'travel_type'  => fake()->randomElement(['Solo', 'Family', 'Couple', 'Friends']),
            'notes'        => fake()->optional()->sentence(),
        ];
    }
}
```

- [ ] **Step 4: Add missing routes to routes/web.php inside the auth middleware group**

Add these lines inside the `Route::middleware(['auth'])->group(...)` block, after the existing trip routes:

```php
Route::get('/trips/{trip}',        [Traveler\TripController::class, 'show'])->name('trips.show');
Route::get('/trips/{trip}/edit',   [Traveler\TripController::class, 'edit'])->name('trips.edit');
Route::put('/trips/{trip}',        [Traveler\TripController::class, 'update'])->name('trips.update');
Route::delete('/trips/{trip}',     [Traveler\TripController::class, 'destroy'])->name('trips.destroy');
Route::get('/trips/{trip}/budget', [Traveler\TripController::class, 'budget'])->name('trips.budget');
Route::post('/trips/{trip}/budget',[Traveler\TripController::class, 'budgetStore'])->name('trips.budgetStore');
```

- [ ] **Step 5: Run tests — expect PASS**

```
php artisan test tests/Feature/Trip/TripRouteTest.php
```

---

### Task 2: BudgetService

**Files:**
- Modify: `app/Services/BudgetService.php`
- Test: `tests/Feature/Trip/BudgetServiceTest.php`

**Interfaces:**
- Consumes: `Trip` model with loaded `budgets` relation (`hasMany TripBudget`)
- Produces:
  - `BudgetService::summary(Trip $trip): array` — returns `['total_estimated', 'total_spent', 'remaining', 'categories' => [['category', 'estimated_cost', 'actual_spent', 'remaining'],...]]`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Trip/BudgetServiceTest.php
namespace Tests\Feature\Trip;

use App\Models\Trip;
use App\Models\TripBudget;
use App\Models\User;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    private BudgetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BudgetService();
    }

    public function test_summary_returns_correct_totals(): void
    {
        $trip = Trip::factory()->create();
        TripBudget::create(['trip_id' => $trip->id, 'category' => 'Transportation',  'estimated_cost' => 5000, 'actual_spent' => 3000]);
        TripBudget::create(['trip_id' => $trip->id, 'category' => 'Accommodation',   'estimated_cost' => 8000, 'actual_spent' => 8500]);
        TripBudget::create(['trip_id' => $trip->id, 'category' => 'Food',            'estimated_cost' => 3000, 'actual_spent' => 1500]);

        $summary = $this->service->summary($trip->load('budgets'));

        $this->assertEquals(16000, $summary['total_estimated']);
        $this->assertEquals(13000, $summary['total_spent']);
        $this->assertEquals(3000,  $summary['remaining']);
        $this->assertCount(3, $summary['categories']);
    }

    public function test_summary_with_no_budgets_returns_zeros(): void
    {
        $trip = Trip::factory()->create();
        $summary = $this->service->summary($trip->load('budgets'));

        $this->assertEquals(0, $summary['total_estimated']);
        $this->assertEquals(0, $summary['total_spent']);
        $this->assertEquals(0, $summary['remaining']);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Trip/BudgetServiceTest.php
```

- [ ] **Step 3: Implement BudgetService**

```php
<?php
// app/Services/BudgetService.php
namespace App\Services;

use App\Models\Trip;

class BudgetService
{
    public function summary(Trip $trip): array
    {
        $budgets = $trip->budgets;

        $totalEstimated = $budgets->sum('estimated_cost');
        $totalSpent     = $budgets->sum('actual_spent');

        return [
            'total_estimated' => $totalEstimated,
            'total_spent'     => $totalSpent,
            'remaining'       => $totalEstimated - $totalSpent,
            'categories'      => $budgets->map(fn($b) => [
                'category'       => $b->category,
                'estimated_cost' => $b->estimated_cost,
                'actual_spent'   => $b->actual_spent,
                'remaining'      => $b->estimated_cost - $b->actual_spent,
            ])->values()->all(),
        ];
    }
}
```

- [ ] **Step 4: Add `budgets` relationship to Trip model**

Open `app/Models/Trip.php` and verify (or add) this relationship:

```php
public function budgets() { return $this->hasMany(TripBudget::class); }
```

- [ ] **Step 5: Run tests — expect PASS**

```
php artisan test tests/Feature/Trip/BudgetServiceTest.php
```

---

### Task 3: Trip CRUD (index, type, create, store, show, edit, update, destroy)

**Files:**
- Modify: `app/Http/Controllers/Traveler/TripController.php`
- Modify: `resources/views/traveler/trips/index.blade.php`
- Modify: `resources/views/traveler/trips/type.blade.php`
- Modify: `resources/views/traveler/trips/create.blade.php`
- Create: `resources/views/traveler/trips/show.blade.php`
- Create: `resources/views/traveler/trips/edit.blade.php`
- Test: `tests/Feature/Trip/TripCrudTest.php`

**Interfaces:**
- Consumes: `Trip::factory()` from Task 1; `BudgetService::summary()` from Task 2
- Produces: `GET /trips/{trip}` returns trip detail; `POST /trips` creates trip, redirects to `trips.show`; `DELETE /trips/{trip}` authorises ownership check

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Trip/TripCrudTest.php
namespace Tests\Feature\Trip;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_only_users_trips(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $mine  = Trip::factory()->create(['user_id' => $user->id,  'destination' => 'Boracay']);
        $theirs= Trip::factory()->create(['user_id' => $other->id, 'destination' => 'Palawan']);

        $response = $this->actingAs($user)->get('/trips');

        $response->assertStatus(200);
        $response->assertSee('Boracay');
        $response->assertDontSee('Palawan');
    }

    public function test_user_can_create_trip(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/trips', [
            'destination'   => 'El Nido, Palawan',
            'start_date'    => '2026-08-01',
            'end_date'      => '2026-08-07',
            'num_travelers' => 2,
            'budget_limit'  => 50000,
            'travel_type'   => 'Couple',
            'notes'         => 'Anniversary trip',
        ]);

        $this->assertDatabaseHas('trips', [
            'destination' => 'El Nido, Palawan',
            'user_id'     => $user->id,
        ]);
        $trip = Trip::where('destination', 'El Nido, Palawan')->first();
        $response->assertRedirect(route('trips.show', $trip));
    }

    public function test_trip_show_page_loads(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id, 'destination' => 'Siargao']);

        $response = $this->actingAs($user)->get("/trips/{$trip->id}");

        $response->assertStatus(200);
        $response->assertSee('Siargao');
    }

    public function test_user_cannot_view_another_users_trip(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $trip  = Trip::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->get("/trips/{$trip->id}")->assertStatus(403);
    }

    public function test_user_can_update_their_trip(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/trips/{$trip->id}", [
            'destination'   => 'Updated Destination',
            'start_date'    => '2026-09-01',
            'end_date'      => '2026-09-05',
            'num_travelers' => 1,
            'travel_type'   => 'Solo',
        ]);

        $response->assertRedirect(route('trips.show', $trip));
        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'destination' => 'Updated Destination']);
    }

    public function test_user_can_delete_their_trip(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete("/trips/{$trip->id}")->assertRedirect(route('trips.index'));
        $this->assertDatabaseMissing('trips', ['id' => $trip->id]);
    }

    public function test_user_cannot_delete_another_users_trip(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $trip  = Trip::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->delete("/trips/{$trip->id}")->assertStatus(403);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/trips', []);
        $response->assertSessionHasErrors(['destination', 'start_date', 'end_date', 'travel_type']);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Trip/TripCrudTest.php
```

- [ ] **Step 3: Implement TripController**

```php
<?php
// app/Http/Controllers/Traveler/TripController.php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\BudgetService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index()
    {
        $trips = auth()->user()->trips()->latest()->get();
        return view('traveler.trips.index', compact('trips'));
    }

    public function type()
    {
        return view('traveler.trips.type');
    }

    public function create()
    {
        return view('traveler.trips.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'destination'   => 'required|string|max:255',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'num_travelers' => 'required|integer|min:1|max:50',
            'budget_limit'  => 'nullable|numeric|min:0',
            'travel_type'   => 'required|in:Solo,Family,Couple,Friends',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $trip = auth()->user()->trips()->create($validated);

        return redirect()->route('trips.show', $trip);
    }

    public function show(Trip $trip, BudgetService $budgetService)
    {
        abort_if($trip->user_id !== auth()->id(), 403);
        $summary = $budgetService->summary($trip->load('budgets'));
        return view('traveler.trips.show', compact('trip', 'summary'));
    }

    public function edit(Trip $trip)
    {
        abort_if($trip->user_id !== auth()->id(), 403);
        return view('traveler.trips.edit', compact('trip'));
    }

    public function update(Request $request, Trip $trip)
    {
        abort_if($trip->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'destination'   => 'required|string|max:255',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'num_travelers' => 'required|integer|min:1|max:50',
            'budget_limit'  => 'nullable|numeric|min:0',
            'travel_type'   => 'required|in:Solo,Family,Couple,Friends',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $trip->update($validated);
        return redirect()->route('trips.show', $trip);
    }

    public function destroy(Trip $trip)
    {
        abort_if($trip->user_id !== auth()->id(), 403);
        $trip->delete();
        return redirect()->route('trips.index');
    }

    public function estimate(Trip $trip)
    {
        abort_if($trip->user_id !== auth()->id(), 403);
        return view('traveler.trips.estimate', compact('trip'));
    }

    public function budget(Trip $trip)
    {
        abort_if($trip->user_id !== auth()->id(), 403);
        $categories = ['Transportation', 'Accommodation', 'Food', 'Tourist Attractions', 'Shopping', 'Emergency Funds'];
        $budgets    = $trip->budgets()->pluck('estimated_cost', 'category')->toArray();
        return view('traveler.trips.budget', compact('trip', 'categories', 'budgets'));
    }

    public function budgetStore(Request $request, Trip $trip)
    {
        abort_if($trip->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'estimated_cost'   => 'required|array',
            'estimated_cost.*' => 'numeric|min:0',
        ]);

        foreach ($validated['estimated_cost'] as $category => $amount) {
            $trip->budgets()->updateOrCreate(
                ['category' => $category],
                ['estimated_cost' => $amount]
            );
        }

        return redirect()->route('trips.show', $trip)->with('success', 'Budget saved.');
    }
}
```

- [ ] **Step 4: Create/update the views**

`resources/views/traveler/trips/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'My Trips')
@section('content')
<div class="page-header">
    <h1>My Trips</h1>
    <a href="{{ route('trips.type') }}" class="btn btn-primary">+ New Trip</a>
</div>

@forelse ($trips as $trip)
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body">
            <h3>{{ $trip->destination }}</h3>
            <p>{{ $trip->start_date }} → {{ $trip->end_date }} &bull; {{ $trip->travel_type }} &bull; {{ $trip->num_travelers }} traveler(s)</p>
            <a href="{{ route('trips.show', $trip) }}" class="btn btn-sm btn-primary">View</a>
            <a href="{{ route('trips.edit', $trip) }}" class="btn btn-sm btn-secondary">Edit</a>
            <form method="POST" action="{{ route('trips.destroy', $trip) }}" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger"
                        onclick="return confirm('Delete this trip?')">Delete</button>
            </form>
        </div>
    </div>
@empty
    <p>No trips yet. <a href="{{ route('trips.type') }}">Plan your first trip!</a></p>
@endforelse
@endsection
```

`resources/views/traveler/trips/type.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Plan a Trip')
@section('content')
<h1>What kind of trip are you planning?</h1>
<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;max-width:600px;">
    @foreach (['Solo','Family','Couple','Friends'] as $type)
        <a href="{{ route('trips.create') }}?type={{ $type }}"
           class="card" style="text-align:center;padding:2rem;text-decoration:none;">
            <h3>{{ $type }}</h3>
        </a>
    @endforeach
</div>
@endsection
```

`resources/views/traveler/trips/create.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Create Trip')
@section('content')
<h1>New Trip</h1>
@if ($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:1.2em;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('trips.store') }}">
    @csrf
    <div class="form-group">
        <label>Destination</label>
        <input type="text" name="destination" class="form-control" value="{{ old('destination') }}" required>
    </div>
    <div class="form-group">
        <label>Start Date</label>
        <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
    </div>
    <div class="form-group">
        <label>End Date</label>
        <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
    </div>
    <div class="form-group">
        <label>Number of Travelers</label>
        <input type="number" name="num_travelers" class="form-control" value="{{ old('num_travelers', 1) }}" min="1" max="50" required>
    </div>
    <div class="form-group">
        <label>Budget Limit (optional)</label>
        <input type="number" step="0.01" name="budget_limit" class="form-control" value="{{ old('budget_limit') }}">
    </div>
    <div class="form-group">
        <label>Travel Type</label>
        <select name="travel_type" class="form-control" required>
            <option value="">Select...</option>
            @foreach (['Solo','Family','Couple','Friends'] as $t)
                <option value="{{ $t }}" {{ old('travel_type', request('type')) === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
    </div>
    <button type="submit" class="btn btn-primary">Create Trip</button>
</form>
</div></div>
@endsection
```

`resources/views/traveler/trips/show.blade.php` (create this file):
```html
@extends('layouts.app')
@section('title', $trip->destination)
@section('content')
<div class="page-header">
    <h1>{{ $trip->destination }}</h1>
    <div>
        <a href="{{ route('trips.edit', $trip) }}" class="btn btn-secondary">Edit</a>
        <a href="{{ route('trips.budget', $trip) }}" class="btn btn-primary">Manage Budget</a>
    </div>
</div>

<p>{{ $trip->start_date }} → {{ $trip->end_date }} &bull; {{ $trip->travel_type }} &bull; {{ $trip->num_travelers }} traveler(s)</p>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin:1.5rem 0;">
    <x-stat-card icon="fa-coins"      value="{{ number_format($summary['total_estimated'], 2) }}" label="Total Estimated" />
    <x-stat-card icon="fa-receipt"    value="{{ number_format($summary['total_spent'], 2) }}"     label="Total Spent" />
    <x-stat-card icon="fa-piggy-bank" value="{{ number_format($summary['remaining'], 2) }}"       label="Remaining" />
</div>

<div class="card">
    <div class="card-body">
        <h3>Budget Breakdown</h3>
        @if(count($summary['categories']))
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr>
                    <th style="text-align:left;padding:8px;border-bottom:2px solid #eee;">Category</th>
                    <th style="text-align:right;padding:8px;border-bottom:2px solid #eee;">Estimated</th>
                    <th style="text-align:right;padding:8px;border-bottom:2px solid #eee;">Spent</th>
                    <th style="text-align:right;padding:8px;border-bottom:2px solid #eee;">Remaining</th>
                </tr></thead>
                <tbody>
                @foreach($summary['categories'] as $cat)
                    <tr>
                        <td style="padding:8px;border-bottom:1px solid #f0f0f0;">{{ $cat['category'] }}</td>
                        <td style="padding:8px;border-bottom:1px solid #f0f0f0;text-align:right;">{{ number_format($cat['estimated_cost'], 2) }}</td>
                        <td style="padding:8px;border-bottom:1px solid #f0f0f0;text-align:right;">{{ number_format($cat['actual_spent'], 2) }}</td>
                        <td style="padding:8px;border-bottom:1px solid #f0f0f0;text-align:right;{{ $cat['remaining'] < 0 ? 'color:red;' : '' }}">
                            {{ number_format($cat['remaining'], 2) }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <p>No budget set yet. <a href="{{ route('trips.budget', $trip) }}">Set your budget</a></p>
        @endif
    </div>
</div>
@endsection
```

`resources/views/traveler/trips/edit.blade.php` (create this file):
```html
@extends('layouts.app')
@section('title', 'Edit Trip')
@section('content')
<h1>Edit Trip</h1>
@if ($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:1.2em;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('trips.update', $trip) }}">
    @csrf @method('PUT')
    <div class="form-group">
        <label>Destination</label>
        <input type="text" name="destination" class="form-control" value="{{ old('destination', $trip->destination) }}" required>
    </div>
    <div class="form-group">
        <label>Start Date</label>
        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $trip->start_date) }}" required>
    </div>
    <div class="form-group">
        <label>End Date</label>
        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $trip->end_date) }}" required>
    </div>
    <div class="form-group">
        <label>Number of Travelers</label>
        <input type="number" name="num_travelers" class="form-control" value="{{ old('num_travelers', $trip->num_travelers) }}" min="1" max="50" required>
    </div>
    <div class="form-group">
        <label>Budget Limit (optional)</label>
        <input type="number" step="0.01" name="budget_limit" class="form-control" value="{{ old('budget_limit', $trip->budget_limit) }}">
    </div>
    <div class="form-group">
        <label>Travel Type</label>
        <select name="travel_type" class="form-control" required>
            @foreach (['Solo','Family','Couple','Friends'] as $t)
                <option value="{{ $t }}" {{ old('travel_type', $trip->travel_type) === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $trip->notes) }}</textarea>
    </div>
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="{{ route('trips.show', $trip) }}" class="btn btn-secondary">Cancel</a>
</form>
</div></div>
@endsection
```

`resources/views/traveler/trips/budget.blade.php` (create this file):
```html
@extends('layouts.app')
@section('title', 'Budget Allocation')
@section('content')
<div class="page-header">
    <h1>Budget: {{ $trip->destination }}</h1>
    <a href="{{ route('trips.show', $trip) }}" class="btn btn-secondary">← Back to Trip</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card"><div class="card-body">
<form method="POST" action="{{ route('trips.budgetStore', $trip) }}">
    @csrf
    @foreach ($categories as $category)
        <div class="form-group">
            <label>{{ $category }}</label>
            <input type="number" step="0.01" name="estimated_cost[{{ $category }}]"
                   class="form-control" min="0"
                   value="{{ old("estimated_cost.{$category}", $budgets[$category] ?? 0) }}">
        </div>
    @endforeach
    <button type="submit" class="btn btn-primary">Save Budget</button>
</form>
</div></div>
@endsection
```

- [ ] **Step 5: Run tests — expect PASS**

```
php artisan test tests/Feature/Trip/TripCrudTest.php
```

- [ ] **Step 6: Run full trip test suite**

```
php artisan test tests/Feature/Trip/
```

Expected: all tests PASS.
