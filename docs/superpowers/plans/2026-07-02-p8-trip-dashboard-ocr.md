# P8: Per-Trip Dashboard + OCR Receipt Scanning Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** (1) Build a Livewire `TripDashboard` component at `GET /trips/{trip}/dashboard` showing stat cards, Chart.js pie + line charts, budget breakdown table, and recent expenses. (2) Switch `OcrService` from Google Vision to OCR.space API. (3) Redesign the expense create page with the drag-and-drop receipt scan UI. (4) Update `DashboardController` to serve a multi-trip overview at `GET /dashboard`.

**Architecture:** `TripDashboard` is a Livewire component. The per-trip dashboard route `/trips/{trip}/dashboard` is added to `routes/web.php`. Chart.js is loaded via CDN inside `@push('scripts')`. The OCR service change is isolated to `OcrService.php` and `config/services.php` — no route changes needed. The expense create page remains a standard Blade view served by the existing `ExpenseController::create()`.

**Tech Stack:** Laravel 13, Livewire 3, Chart.js CDN (`https://cdn.jsdelivr.net/npm/chart.js`), OCR.space API, `public/css/budgetra.css`

## Global Constraints

- Laravel 13.18.0, PHP 8.3.12, MySQL
- No git — skip ALL git/commit steps
- OCR.space API key: `K82825648388957` — goes into `.env` as `OCR_API_KEY=K82825648388957`
- `config/services.php` currently has `google_vision.key` entry — ADD an `ocr` entry alongside it (do not remove `google_vision`)
- OCR.space endpoint: `POST https://api.ocr.space/parse/image` with fields: `apikey`, `base64Image` (data URI format), `isOverlayRequired=false`, `detectOrientation=true`
- `TripBudget` categories: `Transportation`, `Accommodation`, `Food`, `Tourist Attractions`, `Shopping`
- Expense categories (from `ExpenseController`): `Transportation`, `Accommodation`, `Food`, `Activities`, `Shopping`, `Emergency Expenses`
- Chart.js must be inside `wire:ignore` div to prevent Livewire from destroying it on re-render
- The existing `POST /expenses/ocr` route → `ExpenseController::ocr()` already exists and calls `OcrService::scan()` — just updating the service implementation
- Run tests: `php artisan test <test-file>`

---

## File Structure

- **MODIFY:** `routes/web.php` — add `GET /trips/{trip}/dashboard` route
- **CREATE:** `app/Livewire/Traveler/TripDashboard.php`
- **CREATE:** `resources/views/livewire/traveler/trip-dashboard.blade.php`
- **MODIFY:** `app/Http/Controllers/Traveler/DashboardController.php` — pass trips to global dashboard view
- **MODIFY:** `resources/views/traveler/dashboard/index.blade.php` — global overview redesign
- **MODIFY:** `app/Services/OcrService.php` — switch to OCR.space
- **MODIFY:** `config/services.php` — add `ocr` key
- **MODIFY:** `.env` — add `OCR_API_KEY=K82825648388957` (instruction to implementer — they must edit the `.env` file in the project root)
- **REPLACE:** `resources/views/traveler/expenses/create.blade.php` — scan UI redesign
- **CREATE:** `tests/Feature/Livewire/TripDashboardTest.php`
- **CREATE:** `tests/Feature/UI/ExpenseCreateUiTest.php`

---

### Task 1: Per-Trip Dashboard Livewire Component

**Files:**
- Modify: `routes/web.php`
- Create: `app/Livewire/Traveler/TripDashboard.php`
- Create: `resources/views/livewire/traveler/trip-dashboard.blade.php`
- Modify: `app/Http/Controllers/Traveler/DashboardController.php`
- Modify: `resources/views/traveler/dashboard/index.blade.php`
- Test: `tests/Feature/Livewire/TripDashboardTest.php`

**Interfaces:**
- Produces: `GET /trips/{trip}/dashboard` (route name `trips.dashboard`) — Livewire component that receives `$trip` model binding
- Produces: `GET /dashboard` — multi-trip overview

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Livewire/TripDashboardTest.php`:

```php
<?php
namespace Tests\Feature\Livewire;

use App\Livewire\Traveler\TripDashboard;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\TripBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TripDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeTrip(User $user): Trip
    {
        return Trip::factory()->create([
            'user_id'      => $user->id,
            'budget_limit' => 50000,
            'start_date'   => now()->toDateString(),
            'end_date'     => now()->addDays(4)->toDateString(),
        ]);
    }

    public function test_trip_dashboard_page_loads(): void
    {
        $user = User::factory()->create();
        $trip = $this->makeTrip($user);
        $this->actingAs($user)->get("/trips/{$trip->id}/dashboard")->assertStatus(200);
    }

    public function test_dashboard_shows_trip_budget(): void
    {
        $user = User::factory()->create();
        $trip = $this->makeTrip($user);
        Livewire::actingAs($user)
            ->test(TripDashboard::class, ['trip' => $trip])
            ->assertSee('50,000');
    }

    public function test_dashboard_shows_spent_amount(): void
    {
        $user = User::factory()->create();
        $trip = $this->makeTrip($user);
        Expense::create([
            'trip_id'      => $trip->id,
            'user_id'      => $user->id,
            'amount'       => 1500,
            'category'     => 'Food',
            'description'  => 'Lunch',
            'expense_date' => now()->toDateString(),
        ]);
        Livewire::actingAs($user)
            ->test(TripDashboard::class, ['trip' => $trip])
            ->assertSee('1,500');
    }

    public function test_other_user_cannot_view_trip_dashboard(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $trip  = $this->makeTrip($user);
        $this->actingAs($other)->get("/trips/{$trip->id}/dashboard")->assertStatus(403);
    }

    public function test_global_dashboard_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertStatus(200);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Livewire/TripDashboardTest.php
```

Expected: FAIL — `TripDashboard` class does not exist, route 404.

- [ ] **Step 3: Add route to `routes/web.php`**

Inside the auth middleware group, add this line after `Route::get('/trips/{trip}/budget', ...)`:

```php
Route::get('/trips/{trip}/dashboard', \App\Livewire\Traveler\TripDashboard::class)->name('trips.dashboard');
```

- [ ] **Step 4: Create `app/Livewire/Traveler/TripDashboard.php`**

```php
<?php
namespace App\Livewire\Traveler;

use App\Models\Expense;
use App\Models\Trip;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['active' => 'trips'])]
class TripDashboard extends Component
{
    public Trip $trip;

    public function mount(Trip $trip): void
    {
        abort_if($trip->user_id !== auth()->id(), 403);
        $this->trip = $trip;
    }

    public function getTotalSpentProperty(): float
    {
        return (float) $this->trip->expenses()->sum('amount');
    }

    public function getRemainingProperty(): float
    {
        return (float) $this->trip->budget_limit - $this->totalSpent;
    }

    public function getSpentPctProperty(): float
    {
        if (!$this->trip->budget_limit) return 0;
        return round($this->totalSpent / $this->trip->budget_limit * 100, 1);
    }

    public function getDaysProperty(): int
    {
        return max(1, (int) $this->trip->start_date->diffInDays($this->trip->end_date));
    }

    public function getCategorySpendProperty(): array
    {
        return $this->trip->expenses()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();
    }

    public function getDailySpendProperty(): array
    {
        return $this->trip->expenses()
            ->selectRaw('expense_date, SUM(amount) as total')
            ->groupBy('expense_date')
            ->orderBy('expense_date')
            ->pluck('total', 'expense_date')
            ->toArray();
    }

    public function getBudgetBreakdownProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->trip->budgets()->get();
    }

    public function getRecentExpensesProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->trip->expenses()->latest('expense_date')->limit(5)->get();
    }

    public function render()
    {
        return view('livewire.traveler.trip-dashboard');
    }
}
```

- [ ] **Step 5: Create `resources/views/livewire/traveler/trip-dashboard.blade.php`**

```html
<div>
    {{-- Scan Expense Banner --}}
    <div class="bt-scan-banner">
        <div class="bt-scan-banner-left">
            <div class="bt-scan-banner-icon"><i class="fa-solid fa-receipt"></i></div>
            <div>
                <h3>Track Your Spending</h3>
                <p>Scan a receipt to keep your budget up to date.</p>
            </div>
        </div>
        <a href="{{ route('expenses.create') }}?trip_id={{ $trip->id }}" class="bt-btn bt-btn-primary">
            <i class="fa-solid fa-camera"></i> Scan Expense
        </a>
    </div>

    {{-- Trip title --}}
    <div class="bt-flex-between mb-16">
        <div>
            <h1>{{ $trip->destination }}</h1>
            <p class="text-muted">{{ $trip->start_date->format('M j') }} – {{ $trip->end_date->format('M j, Y') }} · {{ $this->days }} days</p>
        </div>
        <a href="{{ route('trips.index') }}" class="bt-btn bt-btn-outline bt-btn-sm">← All Trips</a>
    </div>

    {{-- Stat cards --}}
    <div class="bt-grid-3 mb-24">
        <div class="bt-stat-card primary">
            <div class="bt-stat-label"><i class="fa-regular fa-calendar"></i> Total Budget</div>
            <div class="bt-stat-value">₱{{ number_format($trip->budget_limit, 2) }}</div>
            <div class="bt-stat-sub">Allocated for {{ $this->days }} days</div>
            <div class="bt-stat-bar mt-8">
                <div class="bt-progress">
                    <div class="bt-progress-bar" style="width:100%;"></div>
                </div>
            </div>
        </div>
        <div class="bt-stat-card gold">
            <div class="bt-stat-label"><i class="fa-regular fa-credit-card"></i> Amount Spent</div>
            <div class="bt-stat-value">₱{{ number_format($this->totalSpent, 2) }}</div>
            <div class="bt-stat-sub">{{ $this->spentPct }}% of total budget</div>
            <div class="bt-stat-bar mt-8">
                <div class="bt-progress">
                    <div class="bt-progress-bar gold" style="width:{{ min(100, $this->spentPct) }}%;"></div>
                </div>
            </div>
        </div>
        <div class="bt-stat-card blue">
            <div class="bt-stat-label"><i class="fa-solid fa-building-columns"></i> Remaining Funds</div>
            <div class="bt-stat-value {{ $this->remaining < 0 ? 'text-danger' : '' }}">
                ₱{{ number_format(abs($this->remaining), 2) }}{{ $this->remaining < 0 ? ' over' : '' }}
            </div>
            <div class="bt-stat-sub">{{ $this->remaining >= 0 ? 'Stay on budget!' : 'You are over budget!' }}</div>
            <div class="bt-stat-bar mt-8">
                <div class="bt-progress">
                    <div class="bt-progress-bar {{ $this->spentPct >= 100 ? 'danger' : 'blue' }}"
                         style="width:{{ min(100, $this->spentPct) }}%;background:var(--color-blue);"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts row --}}
    <div class="bt-grid-2 mb-24">
        <div class="bt-card">
            <h3 class="mb-16">Spending by Category</h3>
            <div wire:ignore>
                <canvas id="pieChart" height="220"></canvas>
            </div>
        </div>
        <div class="bt-card">
            <h3 class="mb-16">Daily Spend Trend</h3>
            <div wire:ignore>
                <canvas id="lineChart" height="220"></canvas>
            </div>
        </div>
    </div>

    {{-- Budget Breakdown table --}}
    <div class="bt-card mb-24">
        <h3 class="mb-16">Budget Breakdown</h3>
        @if ($this->budgetBreakdown->isEmpty())
        <p class="text-muted">No budget categories set. <a href="{{ route('trips.budget', $trip) }}" style="color:var(--color-primary);">Set up budget →</a></p>
        @else
        <table class="bt-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Budgeted</th>
                    <th>Spent</th>
                    <th>Remaining</th>
                    <th>% Used</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->budgetBreakdown as $b)
                @php $pct = $b->estimated_cost > 0 ? round($b->actual_spent / $b->estimated_cost * 100) : 0; @endphp
                <tr>
                    <td>{{ $b->category }}</td>
                    <td>₱{{ number_format($b->estimated_cost, 2) }}</td>
                    <td>₱{{ number_format($b->actual_spent, 2) }}</td>
                    <td class="{{ $b->estimated_cost - $b->actual_spent < 0 ? 'text-danger' : '' }}">
                        ₱{{ number_format($b->estimated_cost - $b->actual_spent, 2) }}
                    </td>
                    <td>
                        <span class="bt-chip {{ $pct >= 100 ? 'bt-chip-red' : ($pct >= 80 ? 'bt-chip-gold' : 'bt-chip-green') }}">
                            {{ $pct }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Recent Expenses --}}
    <div class="bt-card">
        <div class="bt-card-header">
            <h3>Recent Expenses</h3>
            <a href="{{ route('expenses.index') }}?trip_id={{ $trip->id }}" class="bt-btn bt-btn-outline bt-btn-sm">View All</a>
        </div>
        @if ($this->recentExpenses->isEmpty())
        <div class="bt-empty" style="padding:24px;">
            <div class="bt-empty-icon">🧾</div>
            <p>No expenses yet. <a href="{{ route('expenses.create') }}?trip_id={{ $trip->id }}" style="color:var(--color-primary);">Add your first one →</a></p>
        </div>
        @else
        <table class="bt-table">
            <thead>
                <tr><th>Date</th><th>Description</th><th>Category</th><th class="text-right">Amount</th></tr>
            </thead>
            <tbody>
                @foreach ($this->recentExpenses as $exp)
                <tr>
                    <td>{{ $exp->expense_date->format('M j') }}</td>
                    <td>{{ $exp->description ?: '—' }}</td>
                    <td><span class="bt-chip bt-chip-brown">{{ $exp->category }}</span></td>
                    <td class="text-right">₱{{ number_format($exp->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    var categoryData = @json($this->categorySpend);
    var dailyData    = @json($this->dailySpend);

    var pieLabels  = Object.keys(categoryData);
    var pieValues  = Object.values(categoryData);
    var lineLabels = Object.keys(dailyData);
    var lineValues = Object.values(dailyData);

    var colors = ['#5C2D0E','#C9A84C','#1A4C8B','#2ECC71','#E74C3C','#F39C12'];

    if (document.getElementById('pieChart')) {
        new Chart(document.getElementById('pieChart'), {
            type: 'pie',
            data: {
                labels: pieLabels.length ? pieLabels : ['No expenses yet'],
                datasets: [{ data: pieValues.length ? pieValues : [1], backgroundColor: colors }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    if (document.getElementById('lineChart')) {
        new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: {
                labels: lineLabels,
                datasets: [{
                    label: 'Daily Spend (₱)',
                    data: lineValues,
                    borderColor: '#5C2D0E',
                    backgroundColor: 'rgba(92,45,14,0.08)',
                    fill: true, tension: 0.3, pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            }
        });
    }
})();
</script>
@endpush
```

- [ ] **Step 6: Update `DashboardController.php`**

```php
<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user  = auth()->user();
        $trips = $user->trips()->latest()->get()->map(function (Trip $trip) {
            $spent = $trip->expenses()->sum('amount');
            $today = Carbon::today();
            $trip->setAttribute('total_spent', $spent);
            $trip->setAttribute('pct_used', $trip->budget_limit > 0 ? round($spent / $trip->budget_limit * 100) : 0);
            $trip->setAttribute('status',
                $trip->start_date->gt($today) ? 'upcoming' :
                ($trip->end_date->lt($today) ? 'past' : 'active'));
            return $trip;
        });
        $totalBudget = $trips->sum('budget_limit');
        $totalSpent  = $trips->sum('total_spent');
        return view('traveler.dashboard.index', compact('trips', 'totalBudget', 'totalSpent'));
    }
}
```

- [ ] **Step 7: Replace `resources/views/traveler/dashboard/index.blade.php`**

```html
@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

<div class="bt-flex-between mb-24">
    <div>
        <h1>Dashboard</h1>
        <p class="text-muted">Welcome back, {{ auth()->user()->full_name }}!</p>
    </div>
    <a href="{{ route('trips.plan') }}" class="bt-btn bt-btn-primary">
        <i class="fa-solid fa-plus"></i> Plan New Trip
    </a>
</div>

{{-- Aggregate stats --}}
<div class="bt-grid-3 mb-24">
    <div class="bt-stat-card primary">
        <div class="bt-stat-label"><i class="fa-solid fa-suitcase-rolling"></i> Total Trips</div>
        <div class="bt-stat-value">{{ $trips->count() }}</div>
        <div class="bt-stat-sub">All time</div>
    </div>
    <div class="bt-stat-card gold">
        <div class="bt-stat-label"><i class="fa-solid fa-coins"></i> Total Budget</div>
        <div class="bt-stat-value">₱{{ number_format($totalBudget, 0) }}</div>
        <div class="bt-stat-sub">Across all trips</div>
    </div>
    <div class="bt-stat-card blue">
        <div class="bt-stat-label"><i class="fa-regular fa-credit-card"></i> Total Spent</div>
        <div class="bt-stat-value">₱{{ number_format($totalSpent, 0) }}</div>
        <div class="bt-stat-sub">Across all trips</div>
    </div>
</div>

{{-- Active trips --}}
@php $activeTrips = $trips->whereIn('status', ['upcoming','active']); @endphp
@if ($activeTrips->isNotEmpty())
<h2 class="mb-16">Active Trips</h2>
<div class="bt-trip-grid mb-24">
    @foreach ($activeTrips as $trip)
    <div class="bt-trip-card">
        <div class="bt-trip-card-overlay"></div>
        <span class="bt-trip-card-status {{ $trip->status }}">{{ ucfirst($trip->status) }}</span>
        <div class="bt-trip-card-body">
            <div class="bt-trip-card-dest">{{ $trip->destination }}</div>
            <div class="bt-trip-card-dates">{{ $trip->start_date->format('M j') }} – {{ $trip->end_date->format('M j, Y') }}</div>
            <div class="bt-trip-card-budget">Spent ₱{{ number_format($trip->total_spent,0) }} / ₱{{ number_format($trip->budget_limit,0) }}</div>
            <div class="bt-progress mt-8" style="background:rgba(255,255,255,0.2);">
                <div class="bt-progress-bar" style="background:rgba(255,255,255,0.8);width:{{ min(100,$trip->pct_used) }}%;"></div>
            </div>
            <div class="bt-trip-card-actions mt-8">
                <a href="{{ route('trips.dashboard', $trip) }}">Dashboard</a>
                <a href="{{ route('expenses.index') }}?trip_id={{ $trip->id }}">Expenses</a>
            </div>
        </div>
    </div>
    @endforeach
    <a href="{{ route('trips.plan') }}" class="bt-trip-new-card">
        <i class="fa-solid fa-plus" style="font-size:28px;"></i>
        <span style="font-size:14px;font-weight:600;">Plan New Adventure</span>
    </a>
</div>
@else
<div class="bt-empty">
    <div class="bt-empty-icon">✈️</div>
    <h3>No trips yet</h3>
    <p>Start planning your first adventure!</p>
    <a href="{{ route('trips.plan') }}" class="bt-btn bt-btn-primary bt-btn-lg">Plan Your First Trip</a>
</div>
@endif

@endsection
```

- [ ] **Step 8: Run tests**

```
php artisan test tests/Feature/Livewire/TripDashboardTest.php
```

Expected: 5 tests, all pass.

---

### Task 2: OCR.space Service + Expense Create UI

**Files:**
- Modify: `config/services.php`
- Modify: `.env` (add `OCR_API_KEY=K82825648388957`)
- Modify: `app/Services/OcrService.php`
- Replace: `resources/views/traveler/expenses/create.blade.php`
- Test: `tests/Feature/UI/ExpenseCreateUiTest.php`

**Interfaces:**
- Consumes: `ExpenseController::ocr()` at `POST /expenses/ocr` → calls `OcrService::scan()`
- Produces: `OcrService::scan()` now calls OCR.space instead of Google Vision; returns same array shape `['amount', 'date', 'description', 'confidence']`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/UI/ExpenseCreateUiTest.php`:

```php
<?php
namespace Tests\Feature\UI;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseCreateUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_create_page_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/expenses/create')->assertStatus(200)->assertSee('Scan Your Receipt');
    }

    public function test_expense_create_preselects_trip_from_query_param(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->get("/expenses/create?trip_id={$trip->id}")
            ->assertStatus(200)
            ->assertSee($trip->destination);
    }

    public function test_ocr_endpoint_returns_json(): void
    {
        Storage::fake('public');
        Http::fake([
            'api.ocr.space/*' => Http::response([
                'ParsedResults' => [[
                    'ParsedText' => "Restaurant ABC\nTotal: ₱350.00\nDate: 2026-07-02",
                ]],
                'OCRExitCode' => 1,
            ], 200),
        ]);

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('receipt.jpg');
        $response = $this->actingAs($user)->post('/expenses/ocr', ['receipt' => $file]);
        $response->assertStatus(200)->assertJsonStructure(['amount', 'date', 'description']);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/UI/ExpenseCreateUiTest.php
```

Expected: `test_expense_create_page_loads` FAILS (view doesn't contain "Scan Your Receipt" yet). OCR test may pass if mock works.

- [ ] **Step 3: Update `config/services.php`**

Add the `ocr` block alongside the existing entries:

```php
'ocr' => [
    'key'      => env('OCR_API_KEY', ''),
    'endpoint' => 'https://api.ocr.space/parse/image',
],
```

The full `return [...]` array should now include this plus the existing `google_vision`, `postmark`, etc.

- [ ] **Step 4: Add `OCR_API_KEY` to `.env`**

Open `c:\phpsite\Capstone - Budgetra\.env` and add this line (if it doesn't already exist):

```
OCR_API_KEY=K82825648388957
```

- [ ] **Step 5: Replace `app/Services/OcrService.php`**

```php
<?php
namespace App\Services;

use App\Models\OcrLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OcrService
{
    public function scan(UploadedFile $file, int $userId): array
    {
        $path     = $file->store('receipts', 'public');
        $filename = basename($path);
        $apiKey   = config('services.ocr.key');

        if (empty($apiKey)) {
            OcrLog::create([
                'user_id'       => $userId,
                'filename'      => $filename,
                'status'        => 'failed',
                'error_message' => 'OCR API key not configured.',
            ]);
            return ['amount' => null, 'date' => null, 'description' => null, 'confidence' => 0];
        }

        $imageContent = Storage::disk('public')->get($path);
        $base64       = 'data:' . $file->getMimeType() . ';base64,' . base64_encode($imageContent);

        $response = Http::asForm()->post(config('services.ocr.endpoint'), [
            'apikey'            => $apiKey,
            'base64Image'       => $base64,
            'isOverlayRequired' => 'false',
            'detectOrientation' => 'true',
            'scale'             => 'true',
            'OCREngine'         => '2',
        ]);

        if (!$response->successful() || ($response->json('OCRExitCode') ?? 0) < 1) {
            OcrLog::create([
                'user_id'       => $userId,
                'filename'      => $filename,
                'status'        => 'failed',
                'error_message' => 'OCR API error: ' . $response->status(),
            ]);
            return ['amount' => null, 'date' => null, 'description' => null, 'confidence' => 0];
        }

        $text       = $response->json('ParsedResults.0.ParsedText', '');
        $parsed     = $this->parseReceiptText($text);
        $confidence = $parsed['amount'] ? 85.0 : 30.0;

        OcrLog::create([
            'user_id'    => $userId,
            'filename'   => $filename,
            'status'     => $parsed['amount'] ? 'success' : 'partial',
            'confidence' => $confidence,
        ]);

        return array_merge($parsed, ['confidence' => $confidence]);
    }

    private function parseReceiptText(string $text): array
    {
        $amount = $date = $description = null;

        if (preg_match('/(?:TOTAL|AMOUNT|SUBTOTAL|DUE|GRAND TOTAL)[:\s]*[₱$]?\s*([\d,]+\.?\d{0,2})/i', $text, $m)) {
            $amount = (float) str_replace(',', '', $m[1]);
        } elseif (preg_match('/[₱$]\s*([\d,]+\.?\d{0,2})/', $text, $m)) {
            $amount = (float) str_replace(',', '', $m[1]);
        }

        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $text, $m)) {
            $date = $m[1];
        } elseif (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $text, $m)) {
            $date = "{$m[3]}-" . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT);
        }

        $lines       = array_filter(array_map('trim', explode("\n", $text)));
        $description = reset($lines) ?: null;

        return compact('amount', 'date', 'description');
    }
}
```

- [ ] **Step 6: Replace `resources/views/traveler/expenses/create.blade.php`**

```html
@extends('layouts.app')
@section('title', 'Scan Expense')
@section('content')

<div class="bt-flex-between mb-24">
    <div>
        <h1>Scan Your Receipt</h1>
        <p class="text-muted">Upload a photo of your receipt to automatically extract and track expenses.</p>
    </div>
    <a href="{{ route('expenses.index') }}" class="bt-btn bt-btn-outline">← Back</a>
</div>

<div class="bt-grid-2" style="grid-template-columns:1fr 320px;gap:24px;align-items:start;">
    {{-- Left: drop zone + form --}}
    <div>
        {{-- Drop zone --}}
        <div class="bt-drop-zone" id="dropZone">
            <div class="drop-icon"><i class="fa-solid fa-camera"></i></div>
            <h3>Drag & Drop Receipt</h3>
            <p>or click to browse from your computer</p>
            <button type="button" class="bt-btn bt-btn-primary" onclick="document.getElementById('receiptFile').click()">
                <i class="fa-solid fa-upload"></i> Select File
            </button>
            <input type="file" id="receiptFile" accept="image/*,application/pdf" style="display:none;">
        </div>

        <div id="ocrStatus" style="display:none;" class="bt-alert bt-alert-warning mt-8">
            <i class="fa-solid fa-spinner fa-spin"></i> Scanning receipt...
        </div>

        {{-- Expense Details form --}}
        <div class="bt-card mt-16">
            <h3 class="mb-4">Expense Details</h3>
            <p class="text-muted mb-16" style="font-size:13px;">Fill in details below or let OCR auto-fill after scanning.</p>

            @if (session('success'))
            <div class="bt-alert bt-alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
            <div class="bt-alert bt-alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="receipt" id="receiptHidden">

                <div class="bt-grid-2">
                    <div class="bt-form-group">
                        <label class="bt-label" for="trip_id">Trip</label>
                        <select name="trip_id" id="trip_id"
                                class="bt-select {{ $errors->has('trip_id') ? 'is-invalid' : '' }}" required>
                            <option value="">Select trip</option>
                            @foreach ($trips as $trip)
                            <option value="{{ $trip->id }}"
                                {{ (old('trip_id', request('trip_id')) == $trip->id) ? 'selected' : '' }}>
                                {{ $trip->destination }} ({{ $trip->start_date->format('M Y') }})
                            </option>
                            @endforeach
                        </select>
                        @error('trip_id')<div class="bt-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="bt-form-group">
                        <label class="bt-label" for="amount">Amount (₱)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01"
                               value="{{ old('amount') }}"
                               class="bt-input {{ $errors->has('amount') ? 'is-invalid' : '' }}"
                               placeholder="0.00" required>
                        @error('amount')<div class="bt-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="bt-grid-2">
                    <div class="bt-form-group">
                        <label class="bt-label" for="category">Category</label>
                        <select name="category" id="category"
                                class="bt-select {{ $errors->has('category') ? 'is-invalid' : '' }}" required>
                            <option value="">Select category</option>
                            @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        @error('category')<div class="bt-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="bt-form-group">
                        <label class="bt-label" for="expense_date">Date</label>
                        <input type="date" id="expense_date" name="expense_date"
                               value="{{ old('expense_date', date('Y-m-d')) }}"
                               class="bt-input {{ $errors->has('expense_date') ? 'is-invalid' : '' }}" required>
                        @error('expense_date')<div class="bt-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="bt-form-group">
                    <label class="bt-label" for="description">Description / Merchant</label>
                    <input type="text" id="description" name="description"
                           value="{{ old('description') }}"
                           class="bt-input" placeholder="e.g. L'Osteria Pizza">
                </div>

                {{-- Hidden real file input for form submission --}}
                <input type="file" name="receipt" id="receiptSubmit" style="display:none;" accept="image/*,application/pdf">

                <button type="submit" class="bt-btn bt-btn-primary bt-btn-lg bt-btn-block mt-8">
                    <i class="fa-solid fa-floppy-disk"></i> Save Expense
                </button>
            </form>
        </div>
    </div>

    {{-- Right sidebar --}}
    <div>
        {{-- Camera option --}}
        <div class="bt-card mb-16" style="background:var(--color-blue);color:white;border-color:var(--color-blue);">
            <div class="bt-flex-between">
                <div>
                    <div style="font-weight:600;font-size:14px;">On the go?</div>
                    <div style="font-size:12px;opacity:0.85;">Use mobile camera</div>
                </div>
                <button type="button"
                        onclick="document.getElementById('mobileCamera').click()"
                        style="background:rgba(255,255,255,0.2);border:none;border-radius:50%;width:40px;height:40px;cursor:pointer;color:white;font-size:18px;">
                    <i class="fa-solid fa-camera"></i>
                </button>
            </div>
            <input type="file" id="mobileCamera" accept="image/*" capture="environment" style="display:none;">
        </div>

        {{-- Recent Scans --}}
        <div class="bt-card mb-16">
            <div class="bt-flex-between mb-12">
                <h3 style="font-size:15px;">Recent Scans</h3>
                <a href="{{ route('expenses.index') }}" style="font-size:12px;color:var(--color-primary);">View All</a>
            </div>
            @php $recentExpenses = auth()->user()->expenses()->with('trip')->latest('expense_date')->limit(3)->get(); @endphp
            @forelse ($recentExpenses as $exp)
            <div style="padding:8px 0;border-bottom:1px solid #F5F0EB;font-size:13px;">
                <div style="font-weight:500;">{{ $exp->description ?: $exp->category }}</div>
                <div class="text-muted" style="font-size:11px;">₱{{ number_format($exp->amount,2) }} · {{ $exp->expense_date->format('M j') }}</div>
            </div>
            @empty
            <p class="text-muted" style="font-size:13px;">No expenses yet. Add one above.</p>
            <div style="margin-top:8px;">
                <span class="bt-chip bt-chip-brown">#Travel</span>
                <span class="bt-chip bt-chip-gold">#Budget</span>
                <span class="bt-chip bt-chip-blue">#Expenses</span>
            </div>
            @endforelse
        </div>

        {{-- Active trip card --}}
        @php $latestTrip = auth()->user()->trips()->latest()->first(); @endphp
        @if ($latestTrip)
        <div class="bt-card" style="background:var(--color-gold);border-color:var(--color-gold);color:white;">
            <div style="font-size:20px;margin-bottom:8px;">🎒</div>
            <div style="font-weight:700;font-size:15px;">{{ $latestTrip->destination }}</div>
            @php $saved = $latestTrip->savingsGoals()->sum('current_savings'); $target = $latestTrip->budget_limit; @endphp
            <div class="bt-progress mt-8" style="background:rgba(255,255,255,0.3);">
                <div class="bt-progress-bar" style="background:white;width:{{ $target > 0 ? min(100, round($saved/$target*100)) : 0 }}%;"></div>
            </div>
            <div style="font-size:12px;opacity:0.9;margin-top:6px;">₱{{ number_format($saved,2) }} of ₱{{ number_format($target,2) }} saved</div>
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var dropZone    = document.getElementById('dropZone');
    var fileInput   = document.getElementById('receiptFile');
    var mobileInput = document.getElementById('mobileCamera');
    var submitInput = document.getElementById('receiptSubmit');
    var statusDiv   = document.getElementById('ocrStatus');
    var amountInput = document.getElementById('amount');
    var dateInput   = document.getElementById('expense_date');
    var descInput   = document.getElementById('description');

    function handleFile(file) {
        if (!file) return;
        // Copy file to the real submit input
        var dt = new DataTransfer();
        dt.items.add(file);
        submitInput.files = dt.files;

        // Show scanning status
        statusDiv.style.display = '';

        var reader = new FileReader();
        reader.onload = function (e) {
            var formData = new FormData();
            formData.append('receipt', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            fetch('/expenses/ocr', { method: 'POST', body: formData })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    statusDiv.style.display = 'none';
                    if (data.amount)      amountInput.value = data.amount;
                    if (data.date)        dateInput.value   = data.date;
                    if (data.description) descInput.value   = data.description;
                })
                .catch(function () { statusDiv.style.display = 'none'; });
        };
        reader.readAsDataURL(file);
    }

    // Click to browse
    fileInput.addEventListener('change', function () { handleFile(this.files[0]); });
    mobileInput.addEventListener('change', function () { handleFile(this.files[0]); });

    // Drag and drop
    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault(); this.classList.add('dragover');
    });
    dropZone.addEventListener('dragleave', function () { this.classList.remove('dragover'); });
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault(); this.classList.remove('dragover');
        handleFile(e.dataTransfer.files[0]);
    });
})();
</script>
@endpush
```

- [ ] **Step 7: Run all tests**

```
php artisan test tests/Feature/UI/ExpenseCreateUiTest.php
php artisan test tests/Feature/Livewire/TripDashboardTest.php
```

Expected: all pass.

- [ ] **Step 8: Run full suite for regressions**

```
php artisan test
```

Expected: all previously passing tests still pass.

## Report Contract

Write your full report to:
`C:\Users\ASUS\AppData\Local\Temp\claude\c--phpsite-Capstone---Budgetra\55e34b67-7087-4f9b-8add-8becf1178a87\scratchpad\p8-report.md`

Return ONLY: status (DONE/DONE_WITH_CONCERNS/NEEDS_CONTEXT/BLOCKED), files changed, one-line test summary, concerns.
