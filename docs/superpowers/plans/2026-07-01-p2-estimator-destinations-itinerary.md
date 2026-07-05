# Cost Estimator, Destinations & Itinerary Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement destination browsing, side-by-side cost comparison, trip cost estimator using destination multipliers and KlookService stub, and full itinerary CRUD per trip.

**Architecture:** Destinations and attractions are read-only for travelers (seeded by admin). Cost estimation multiplies a base daily cost by the destination's `multiplier` and number of travelers/days. KlookService is stubbed to return placeholder data until a real API key is configured. Itinerary items belong to a trip and are grouped by date on the timeline view.

**Tech Stack:** Laravel 13.x, Blade, PHP 8.3+, MySQL.

## Global Constraints

- Project root: `c:\phpsite\Capstone - Budgetra`
- Laravel 13.18.0, PHP 8.3.12
- Requires Plans 1 & 2 complete
- `destination_costs` columns: `destination`, `category` (nullable), `cost_level` (enum: Budget-friendly|Moderate|Pricey|Very Expensive), `multiplier` (decimal 4,3 default 1.000), `image_url` (nullable), `description` (nullable)
- `attractions` columns: `name`, `destination`, `category` (nullable), `image` (nullable), `rating` (decimal 3,1 default 0), `description` (nullable)
- `itinerary` columns: `trip_id`, `title`, `type` (enum: Flight|Hotel|Activity|Transportation), `start_datetime` (dateTime), `end_datetime` (nullable dateTime), `location` (nullable), `notes` (nullable)
- Base daily cost for estimation: PHP 2500 per person per day (multiplied by destination `multiplier`)
- KlookService stub returns mock response when no API key set
- Skip git commit steps

---

### Task 1: Destination Browse & Detail

**Files:**
- Modify: `app/Http/Controllers/Traveler/AttractionController.php`
- Modify: `resources/views/traveler/attractions/index.blade.php`
- Create: `resources/views/traveler/attractions/show.blade.php`
- Modify: `routes/web.php` — add attractions.show route
- Test: `tests/Feature/Destination/DestinationBrowseTest.php`

**Interfaces:**
- Produces: `GET /attractions` lists unique destinations with cost info; `GET /attractions/{destination}` shows destination detail + linked attractions

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Destination/DestinationBrowseTest.php
namespace Tests\Feature\Destination;

use App\Models\Attraction;
use App\Models\DestinationCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestinationBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_attractions_index_loads_for_auth_user(): void
    {
        $user = User::factory()->create();
        DestinationCost::create([
            'destination' => 'Boracay', 'cost_level' => 'Moderate', 'multiplier' => 1.200,
        ]);
        $this->actingAs($user)->get('/attractions')->assertStatus(200)->assertSee('Boracay');
    }

    public function test_destination_detail_shows_attractions(): void
    {
        $user = User::factory()->create();
        DestinationCost::create(['destination' => 'Palawan', 'cost_level' => 'Pricey', 'multiplier' => 1.500]);
        Attraction::create(['name' => 'Underground River', 'destination' => 'Palawan', 'rating' => 4.9]);

        $this->actingAs($user)
             ->get('/attractions/' . urlencode('Palawan'))
             ->assertStatus(200)
             ->assertSee('Underground River');
    }

    public function test_attractions_page_requires_auth(): void
    {
        $this->get('/attractions')->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Destination/DestinationBrowseTest.php
```

- [ ] **Step 3: Add attraction show route to routes/web.php inside auth group**

```php
Route::get('/attractions/{destination}', [Traveler\AttractionController::class, 'show'])->name('attractions.show');
```

- [ ] **Step 4: Implement AttractionController**

```php
<?php
// app/Http/Controllers/Traveler/AttractionController.php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\Attraction;
use App\Models\DestinationCost;
use Illuminate\Http\Request;

class AttractionController extends Controller
{
    public function index(Request $request)
    {
        $query = DestinationCost::query()->orderBy('destination');
        if ($request->filled('search')) {
            $query->where('destination', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('cost_level')) {
            $query->where('cost_level', $request->cost_level);
        }
        $destinations = $query->get()->unique('destination');
        $costLevels   = ['Budget-friendly', 'Moderate', 'Pricey', 'Very Expensive'];
        return view('traveler.attractions.index', compact('destinations', 'costLevels'));
    }

    public function show(string $destination)
    {
        $destInfo   = DestinationCost::where('destination', $destination)->firstOrFail();
        $attractions = Attraction::where('destination', $destination)->orderByDesc('rating')->get();
        return view('traveler.attractions.show', compact('destInfo', 'attractions', 'destination'));
    }
}
```

- [ ] **Step 5: Update views**

`resources/views/traveler/attractions/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Attractions & Destinations')
@section('content')
<div class="page-header"><h1>Destinations</h1></div>
<form method="GET" style="display:flex;gap:8px;margin-bottom:1rem;">
    <input type="text" name="search" class="form-control" placeholder="Search destination..." value="{{ request('search') }}" style="width:auto;">
    <select name="cost_level" class="form-control" style="width:auto;">
        <option value="">All Budgets</option>
        @foreach($costLevels as $level)
            <option value="{{ $level }}" {{ request('cost_level') === $level ? 'selected' : '' }}>{{ $level }}</option>
        @endforeach
    </select>
    <button class="btn btn-secondary">Filter</button>
</form>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1rem;">
@foreach($destinations as $dest)
    <a href="{{ route('attractions.show', $dest->destination) }}" style="text-decoration:none;">
        <div class="card">
            @if($dest->image_url)
                <img src="{{ $dest->image_url }}" style="width:100%;height:160px;object-fit:cover;" alt="{{ $dest->destination }}">
            @endif
            <div class="card-body">
                <h3>{{ $dest->destination }}</h3>
                <span class="badge">{{ $dest->cost_level }}</span>
                <p style="font-size:.85rem;color:#666;">{{ $dest->description }}</p>
            </div>
        </div>
    </a>
@endforeach
</div>
@endsection
```

`resources/views/traveler/attractions/show.blade.php` (create this file):
```html
@extends('layouts.app')
@section('title', $destination)
@section('content')
<div class="page-header">
    <h1>{{ $destination }}</h1>
    <a href="{{ route('attractions.index') }}" class="btn btn-secondary">← Back</a>
</div>
<div class="card" style="margin-bottom:1rem;">
    <div class="card-body">
        <p><strong>Cost Level:</strong> {{ $destInfo->cost_level }}</p>
        <p><strong>Cost Multiplier:</strong> {{ $destInfo->multiplier }}×</p>
        @if($destInfo->description)<p>{{ $destInfo->description }}</p>@endif
    </div>
</div>
<h2>Attractions</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;">
@forelse($attractions as $attr)
    <div class="card">
        @if($attr->image)<img src="{{ asset('storage/' . $attr->image) }}" style="width:100%;height:140px;object-fit:cover;">@endif
        <div class="card-body">
            <h4>{{ $attr->name }}</h4>
            <p>⭐ {{ $attr->rating }}</p>
            <p style="font-size:.85rem;">{{ $attr->category }}</p>
        </div>
    </div>
@empty
    <p>No attractions listed yet.</p>
@endforelse
</div>
@endsection
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Destination/DestinationBrowseTest.php
```

---

### Task 2: Destination Cost Comparison

**Files:**
- Create: `app/Http/Controllers/Traveler/ComparisonController.php`
- Create: `resources/views/traveler/comparison/index.blade.php`
- Modify: `routes/web.php` — add comparison routes
- Test: `tests/Feature/Destination/ComparisonTest.php`

**Interfaces:**
- Produces: `GET /compare` shows blank comparison form; `GET /compare?destinations[]=A&destinations[]=B` shows side-by-side cost breakdown

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Destination/ComparisonTest.php
namespace Tests\Feature\Destination;

use App\Models\DestinationCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_comparison_page_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/compare')->assertStatus(200);
    }

    public function test_comparison_shows_selected_destinations(): void
    {
        $user = User::factory()->create();
        DestinationCost::create(['destination' => 'Bohol',   'cost_level' => 'Budget-friendly', 'multiplier' => 0.900]);
        DestinationCost::create(['destination' => 'Siargao', 'cost_level' => 'Moderate',        'multiplier' => 1.100]);

        $response = $this->actingAs($user)
            ->get('/compare?destinations[]=Bohol&destinations[]=Siargao&days=5&travelers=2');

        $response->assertStatus(200);
        $response->assertSee('Bohol');
        $response->assertSee('Siargao');
    }

    public function test_comparison_requires_auth(): void
    {
        $this->get('/compare')->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Destination/ComparisonTest.php
```

- [ ] **Step 3: Add comparison routes to routes/web.php inside auth group**

```php
Route::get('/compare', [Traveler\ComparisonController::class, 'index'])->name('compare.index');
```

- [ ] **Step 4: Create ComparisonController**

```php
<?php
// app/Http/Controllers/Traveler/ComparisonController.php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\DestinationCost;
use Illuminate\Http\Request;

class ComparisonController extends Controller
{
    private const BASE_DAILY_COST = 2500; // PHP per person per day
    private const CATEGORIES = ['Transportation','Accommodation','Food','Tourist Attractions','Shopping','Emergency Funds'];

    public function index(Request $request)
    {
        $allDestinations = DestinationCost::orderBy('destination')->pluck('destination')->unique()->values();
        $selected        = $request->input('destinations', []);
        $days            = max(1, (int) $request->input('days', 5));
        $travelers       = max(1, (int) $request->input('travelers', 1));
        $comparisons     = [];

        foreach (array_slice($selected, 0, 3) as $destName) {
            $dest = DestinationCost::where('destination', $destName)->first();
            if (!$dest) continue;

            $baseTotal     = self::BASE_DAILY_COST * $travelers * $days * $dest->multiplier;
            $comparisons[] = [
                'destination' => $dest->destination,
                'cost_level'  => $dest->cost_level,
                'multiplier'  => $dest->multiplier,
                'total'       => round($baseTotal, 2),
                'per_day'     => round($baseTotal / $days, 2),
            ];
        }

        return view('traveler.comparison.index', compact('allDestinations', 'selected', 'days', 'travelers', 'comparisons'));
    }
}
```

- [ ] **Step 5: Create the comparison view**

Create directory `resources/views/traveler/comparison/` and the view:

```html
{{-- resources/views/traveler/comparison/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Compare Destinations')
@section('content')
<h1>Compare Destination Costs</h1>
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body">
        <form method="GET" action="{{ route('compare.index') }}">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto auto;gap:12px;align-items:end;">
                @for($i = 0; $i < 3; $i++)
                    <div class="form-group" style="margin:0;">
                        <label>Destination {{ $i+1 }}</label>
                        <select name="destinations[]" class="form-control">
                            <option value="">— None —</option>
                            @foreach($allDestinations as $d)
                                <option value="{{ $d }}" {{ isset($selected[$i]) && $selected[$i] === $d ? 'selected' : '' }}>{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                @endfor
                <div class="form-group" style="margin:0;">
                    <label>Days</label>
                    <input type="number" name="days" class="form-control" value="{{ $days }}" min="1" max="30">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Travelers</label>
                    <input type="number" name="travelers" class="form-control" value="{{ $travelers }}" min="1" max="20">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:12px;">Compare</button>
        </form>
    </div>
</div>

@if(count($comparisons))
<div style="display:grid;grid-template-columns:repeat({{ count($comparisons) }},1fr);gap:1rem;">
    @foreach($comparisons as $comp)
        <div class="card">
            <div class="card-body" style="text-align:center;">
                <h3>{{ $comp['destination'] }}</h3>
                <span class="badge">{{ $comp['cost_level'] }}</span>
                <p style="font-size:2rem;font-weight:800;margin:1rem 0;">₱{{ number_format($comp['total'], 0) }}</p>
                <p style="color:#666;">Total for {{ $days }} days, {{ $travelers }} traveler(s)</p>
                <p>₱{{ number_format($comp['per_day'], 0) }} / day</p>
                <p style="font-size:.85rem;">Multiplier: {{ $comp['multiplier'] }}×</p>
                <a href="{{ route('trips.create') }}?destination={{ urlencode($comp['destination']) }}"
                   class="btn btn-primary btn-sm">Plan This Trip</a>
            </div>
        </div>
    @endforeach
</div>
@endif
@endsection
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Destination/ComparisonTest.php
```

---

### Task 3: Trip Cost Estimator (estimate page)

**Files:**
- Modify: `app/Http/Controllers/Traveler/TripController.php` — update `estimate()` method
- Modify: `app/Services/KlookService.php`
- Modify: `resources/views/traveler/trips/estimate.blade.php`
- Test: `tests/Feature/Destination/EstimatorTest.php`

**Interfaces:**
- Consumes: `Trip` model, `DestinationCost` model, `KlookService`
- Produces: `GET /trips/{trip}/estimate` renders estimate breakdown + optional Klook activities

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Destination/EstimatorTest.php
namespace Tests\Feature\Destination;

use App\Models\DestinationCost;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimate_page_loads_for_trip_owner(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id, 'destination' => 'Cebu']);
        DestinationCost::create(['destination' => 'Cebu', 'cost_level' => 'Moderate', 'multiplier' => 1.100]);

        $this->actingAs($user)->get("/trips/{$trip->id}/estimate")->assertStatus(200)->assertSee('Cebu');
    }

    public function test_estimate_page_blocked_for_non_owner(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $trip  = Trip::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->get("/trips/{$trip->id}/estimate")->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Destination/EstimatorTest.php
```

- [ ] **Step 3: Implement KlookService stub**

```php
<?php
// app/Services/KlookService.php
namespace App\Services;

use App\Models\AppConfig;
use Illuminate\Support\Facades\Http;

class KlookService
{
    public function getActivities(string $destination, int $limit = 5): array
    {
        $apiKey = AppConfig::where('config_key', 'klook_api_key')->value('config_value');

        if (empty($apiKey)) {
            return $this->mockActivities($destination, $limit);
        }

        // Real Klook API integration goes here when key is configured
        // Endpoint and auth method depend on Klook partner API docs
        return $this->mockActivities($destination, $limit);
    }

    private function mockActivities(string $destination, int $limit): array
    {
        $activities = [
            ['name' => "Island Hopping at {$destination}", 'price' => 1200, 'currency' => 'PHP', 'rating' => 4.8],
            ['name' => "City Tour of {$destination}",       'price' => 800,  'currency' => 'PHP', 'rating' => 4.5],
            ['name' => "Sunset Cruise {$destination}",      'price' => 1500, 'currency' => 'PHP', 'rating' => 4.7],
            ['name' => "Snorkeling at {$destination}",      'price' => 950,  'currency' => 'PHP', 'rating' => 4.6],
            ['name' => "Cultural Tour {$destination}",      'price' => 700,  'currency' => 'PHP', 'rating' => 4.3],
        ];
        return array_slice($activities, 0, $limit);
    }
}
```

- [ ] **Step 4: Update TripController estimate() method**

```php
public function estimate(Trip $trip, \App\Services\KlookService $klook)
{
    abort_if($trip->user_id !== auth()->id(), 403);

    $destInfo   = \App\Models\DestinationCost::where('destination', $trip->destination)->first();
    $multiplier = $destInfo ? $destInfo->multiplier : 1.0;
    $days       = max(1, (int) \Carbon\Carbon::parse($trip->start_date)->diffInDays($trip->end_date));
    $travelers  = $trip->num_travelers;
    $baseDaily  = 2500;

    $categories = [
        'Transportation'   => round($baseDaily * 0.20 * $multiplier * $travelers * $days, 2),
        'Accommodation'    => round($baseDaily * 0.30 * $multiplier * $travelers * $days, 2),
        'Food'             => round($baseDaily * 0.20 * $multiplier * $travelers * $days, 2),
        'Tourist Attractions' => round($baseDaily * 0.15 * $multiplier * $travelers * $days, 2),
        'Shopping'         => round($baseDaily * 0.10 * $multiplier * $travelers * $days, 2),
        'Emergency Funds'  => round($baseDaily * 0.05 * $multiplier * $travelers * $days, 2),
    ];
    $total      = array_sum($categories);
    $activities = $klook->getActivities($trip->destination);

    return view('traveler.trips.estimate', compact('trip', 'destInfo', 'categories', 'total', 'activities', 'days'));
}
```

- [ ] **Step 5: Update estimate view**

`resources/views/traveler/trips/estimate.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Cost Estimate')
@section('content')
<div class="page-header">
    <h1>Cost Estimate: {{ $trip->destination }}</h1>
    <a href="{{ route('trips.show', $trip) }}" class="btn btn-secondary">← Back to Trip</a>
</div>
<p>{{ $days }} days &bull; {{ $trip->num_travelers }} traveler(s) &bull; {{ $trip->travel_type }}</p>

<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body">
        <h3>Estimated Budget Breakdown</h3>
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr>
                <th style="text-align:left;padding:8px;border-bottom:2px solid #eee;">Category</th>
                <th style="text-align:right;padding:8px;border-bottom:2px solid #eee;">Estimated (₱)</th>
            </tr></thead>
            <tbody>
            @foreach($categories as $cat => $amount)
                <tr>
                    <td style="padding:8px;border-bottom:1px solid #f0f0f0;">{{ $cat }}</td>
                    <td style="padding:8px;border-bottom:1px solid #f0f0f0;text-align:right;">{{ number_format($amount, 2) }}</td>
                </tr>
            @endforeach
            <tr style="font-weight:800;">
                <td style="padding:8px;">TOTAL</td>
                <td style="padding:8px;text-align:right;">₱{{ number_format($total, 2) }}</td>
            </tr>
            </tbody>
        </table>
        <form method="POST" action="{{ route('trips.budgetStore', $trip) }}" style="margin-top:1rem;">
            @csrf
            @foreach($categories as $cat => $amount)
                <input type="hidden" name="estimated_cost[{{ $cat }}]" value="{{ $amount }}">
            @endforeach
            <button type="submit" class="btn btn-primary">Apply Estimates to Budget</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3>Activities via Klook <span style="font-size:.8rem;font-weight:400;color:#999;">(estimated prices)</span></h3>
        @foreach($activities as $activity)
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f0;">
                <div>{{ $activity['name'] }} <span style="color:#f5a623;">⭐ {{ $activity['rating'] }}</span></div>
                <div>₱{{ number_format($activity['price'], 0) }}</div>
            </div>
        @endforeach
    </div>
</div>
@endsection
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Destination/EstimatorTest.php
```

---

### Task 4: Itinerary CRUD

**Files:**
- Modify: `app/Http/Controllers/Traveler/ItineraryController.php`
- Modify: `resources/views/traveler/itinerary/index.blade.php`
- Create: `resources/views/traveler/itinerary/create.blade.php`
- Modify: `routes/web.php` — add itinerary edit/update route
- Test: `tests/Feature/Itinerary/ItineraryCrudTest.php`

**Interfaces:**
- Consumes: `Trip::factory()` from Plan 2
- Produces: `POST /itinerary` creates item; `GET /itinerary` lists items for user's trips grouped by date; `DELETE /itinerary/{item}` deletes item

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Itinerary/ItineraryCrudTest.php
namespace Tests\Feature\Itinerary;

use App\Models\Itinerary;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItineraryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_itinerary_index_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/itinerary')->assertStatus(200);
    }

    public function test_user_can_add_itinerary_item(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post('/itinerary', [
            'trip_id'        => $trip->id,
            'title'          => 'Fly to Cebu',
            'type'           => 'Flight',
            'start_datetime' => '2026-08-01 08:00:00',
            'location'       => 'NAIA Terminal 3',
        ]);

        $response->assertRedirect(route('itinerary.index'));
        $this->assertDatabaseHas('itinerary', ['title' => 'Fly to Cebu', 'trip_id' => $trip->id]);
    }

    public function test_user_can_delete_their_itinerary_item(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $item = Itinerary::create([
            'trip_id' => $trip->id, 'title' => 'Hotel Check-in',
            'type' => 'Hotel', 'start_datetime' => '2026-08-01 14:00:00',
        ]);

        $this->actingAs($user)->delete("/itinerary/{$item->id}")->assertRedirect(route('itinerary.index'));
        $this->assertDatabaseMissing('itinerary', ['id' => $item->id]);
    }

    public function test_user_cannot_delete_another_users_itinerary_item(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $trip  = Trip::factory()->create(['user_id' => $other->id]);
        $item  = Itinerary::create([
            'trip_id' => $trip->id, 'title' => 'Activity',
            'type' => 'Activity', 'start_datetime' => '2026-08-02 10:00:00',
        ]);

        $this->actingAs($user)->delete("/itinerary/{$item->id}")->assertStatus(403);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/itinerary', [])->assertSessionHasErrors(['trip_id', 'title', 'type', 'start_datetime']);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Itinerary/ItineraryCrudTest.php
```

- [ ] **Step 3: Implement ItineraryController**

```php
<?php
// app/Http/Controllers/Traveler/ItineraryController.php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Models\Trip;
use Illuminate\Http\Request;

class ItineraryController extends Controller
{
    private const TYPES = ['Flight', 'Hotel', 'Activity', 'Transportation'];

    public function index()
    {
        $trips = auth()->user()->trips()->with(['itineraries' => fn($q) => $q->orderBy('start_datetime')])->latest()->get();
        $types = self::TYPES;
        return view('traveler.itinerary.index', compact('trips', 'types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'trip_id'        => 'required|exists:trips,id',
            'title'          => 'required|string|max:255',
            'type'           => 'required|in:' . implode(',', self::TYPES),
            'start_datetime' => 'required|date',
            'end_datetime'   => 'nullable|date|after_or_equal:start_datetime',
            'location'       => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:1000',
        ]);

        abort_if(!auth()->user()->trips()->where('id', $validated['trip_id'])->exists(), 403);

        Itinerary::create($validated);
        return redirect()->route('itinerary.index')->with('success', 'Itinerary item added.');
    }

    public function destroy(Itinerary $item)
    {
        abort_if($item->trip->user_id !== auth()->id(), 403);
        $item->delete();
        return redirect()->route('itinerary.index')->with('success', 'Item removed.');
    }
}
```

- [ ] **Step 4: Verify Itinerary model has trip relationship**

Open `app/Models/Itinerary.php` and verify (or add):

```php
public function trip() { return $this->belongsTo(Trip::class); }
```

- [ ] **Step 5: Update itinerary views**

`resources/views/traveler/itinerary/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Itinerary')
@section('content')
<div class="page-header">
    <h1>My Itinerary</h1>
    <button onclick="document.getElementById('add-modal').style.display='flex'" class="btn btn-primary">+ Add Item</button>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

@foreach($trips as $trip)
    @if($trip->itineraries->count())
        <h3 style="margin-top:1.5rem;">{{ $trip->destination }}</h3>
        @foreach($trip->itineraries->groupBy(fn($i) => substr($i->start_datetime, 0, 10)) as $date => $items)
            <div style="margin-bottom:1rem;">
                <div style="font-weight:600;color:#666;padding:4px 0;">{{ \Carbon\Carbon::parse($date)->format('D, M j Y') }}</div>
                @foreach($items as $item)
                    <div class="card" style="margin-bottom:8px;">
                        <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;padding:12px;">
                            <div>
                                <span class="badge">{{ $item->type }}</span>
                                <strong>{{ $item->title }}</strong>
                                @if($item->location) <span style="color:#666;font-size:.85rem;">📍 {{ $item->location }}</span> @endif
                                <br><small>{{ \Carbon\Carbon::parse($item->start_datetime)->format('g:i A') }}
                                @if($item->end_datetime) – {{ \Carbon\Carbon::parse($item->end_datetime)->format('g:i A') }} @endif</small>
                            </div>
                            <form method="POST" action="{{ route('itinerary.destroy', $item) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Remove?')">×</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif
@endforeach

{{-- Add item modal --}}
<div id="add-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);justify-content:center;align-items:center;z-index:1000;">
    <div class="card" style="width:500px;max-width:90%;">
        <div class="card-body">
            <h3>Add Itinerary Item</h3>
            <form method="POST" action="{{ route('itinerary.store') }}">
                @csrf
                <div class="form-group">
                    <label>Trip</label>
                    <select name="trip_id" class="form-control" required>
                        @foreach($trips as $t)<option value="{{ $t->id }}">{{ $t->destination }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" class="form-control" required>
                        @foreach($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Date & Time</label>
                    <input type="datetime-local" name="start_datetime" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>End Date & Time (optional)</label>
                    <input type="datetime-local" name="end_datetime" class="form-control">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" class="form-control">
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary">Add</button>
                    <button type="button" onclick="document.getElementById('add-modal').style.display='none'" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Itinerary/ItineraryCrudTest.php
```

- [ ] **Step 7: Run full P2 test suite**

```
php artisan test tests/Feature/Destination/ tests/Feature/Itinerary/
```

Expected: all PASS.
