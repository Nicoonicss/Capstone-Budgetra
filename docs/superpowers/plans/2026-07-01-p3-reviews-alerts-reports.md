# Reviews, Alerts & Reports Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement traveler destination reviews, budget threshold alerts/notifications, per-trip budget summary reports, spending breakdown chart data, and PDF download via DomPDF.

**Architecture:** Reviews are submitted by travelers, stored with `status='active'`, and visible to all. Admin moderation is in Plan 6. Notifications are created by the `ExpenseObserver` when `actual_spent` exceeds 80% of `estimated_cost`. The report page loads summary data from `BudgetService`. PDF generation uses `barryvdh/laravel-dompdf`.

**Tech Stack:** Laravel 13.x, Blade, PHP 8.3+, MySQL, `barryvdh/laravel-dompdf`.

## Global Constraints

- Project root: `c:\phpsite\Capstone - Budgetra`
- Laravel 13.18.0, PHP 8.3.12
- Requires Plans 1, 2, 3 complete
- `reviews` columns: `user_id`, `destination`, `rating` (tinyInteger 1–5), `body` (text), `status` (enum: active|hidden, default active)
- `notifications` columns: `user_id`, `trip_id` (nullable), `type` (varchar 50), `message` (text), `is_read` (boolean default false)
- Budget alert threshold: 80% of `estimated_cost` per category
- DomPDF package: `barryvdh/laravel-dompdf`
- Skip git commit steps

---

### Task 1: Traveler Reviews

**Files:**
- Modify: `app/Http/Controllers/Traveler/AttractionController.php` — add `storeReview()` method
- Create: `resources/views/traveler/reviews/index.blade.php`
- Modify: `routes/web.php` — add review routes
- Test: `tests/Feature/Review/TravelerReviewTest.php`

**Interfaces:**
- Produces: `GET /reviews` lists all active reviews; `POST /reviews` creates review; review is immediately visible (status=active, admin can hide via Plan 6)

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Review/TravelerReviewTest.php
namespace Tests\Feature\Review;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelerReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviews_page_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/reviews')->assertStatus(200);
    }

    public function test_user_can_submit_review(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/reviews', [
            'destination' => 'Boracay',
            'rating'      => 5,
            'body'        => 'Amazing beach! Highly recommend.',
        ]);

        $response->assertRedirect(route('reviews.index'));
        $this->assertDatabaseHas('reviews', [
            'destination' => 'Boracay',
            'user_id'     => $user->id,
            'status'      => 'active',
        ]);
    }

    public function test_reviews_index_shows_active_reviews_only(): void
    {
        $user = User::factory()->create();
        Review::create(['user_id' => $user->id, 'destination' => 'Palawan', 'rating' => 4, 'body' => 'Visible', 'status' => 'active']);
        Review::create(['user_id' => $user->id, 'destination' => 'Palawan', 'rating' => 1, 'body' => 'Hidden',  'status' => 'hidden']);

        $this->actingAs($user)->get('/reviews')->assertSee('Visible')->assertDontSee('Hidden');
    }

    public function test_review_requires_valid_rating(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/reviews', [
            'destination' => 'Bohol', 'rating' => 6, 'body' => 'Test',
        ]);
        $response->assertSessionHasErrors('rating');
    }

    public function test_review_requires_auth(): void
    {
        $this->post('/reviews', [])->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Review/TravelerReviewTest.php
```

- [ ] **Step 3: Add review routes to routes/web.php inside auth group**

```php
Route::get('/reviews',  [Traveler\ReviewController::class, 'index'])->name('reviews.index');
Route::post('/reviews', [Traveler\ReviewController::class, 'store'])->name('reviews.store');
```

- [ ] **Step 4: Create ReviewController**

```php
<?php
// app/Http/Controllers/Traveler/ReviewController.php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\DestinationCost;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query  = Review::with('user')->where('status', 'active')->latest();
        if ($request->filled('destination')) {
            $query->where('destination', $request->destination);
        }
        $reviews      = $query->paginate(15)->withQueryString();
        $destinations = DestinationCost::orderBy('destination')->pluck('destination')->unique()->values();
        return view('traveler.reviews.index', compact('reviews', 'destinations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'destination' => 'required|string|max:255',
            'rating'      => 'required|integer|min:1|max:5',
            'body'        => 'required|string|min:10|max:2000',
        ]);

        auth()->user()->reviews()->create(array_merge($validated, ['status' => 'active']));

        return redirect()->route('reviews.index')->with('success', 'Review submitted!');
    }
}
```

- [ ] **Step 5: Create review view**

Create directory `resources/views/traveler/reviews/`:

```html
{{-- resources/views/traveler/reviews/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Traveler Reviews')
@section('content')
<div class="page-header">
    <h1>Traveler Reviews</h1>
    <button onclick="document.getElementById('review-modal').style.display='flex'" class="btn btn-primary">+ Write Review</button>
</div>

<form method="GET" style="margin-bottom:1rem;">
    <select name="destination" class="form-control" style="width:auto;display:inline-block;" onchange="this.form.submit()">
        <option value="">All Destinations</option>
        @foreach($destinations as $d)
            <option value="{{ $d }}" {{ request('destination') === $d ? 'selected' : '' }}>{{ $d }}</option>
        @endforeach
    </select>
</form>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

@forelse($reviews as $review)
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body">
            <div style="display:flex;justify-content:space-between;">
                <div>
                    <strong>{{ $review->destination }}</strong>
                    <span style="color:#f5a623;margin-left:8px;">{{ str_repeat('★',$review->rating) }}{{ str_repeat('☆',5-$review->rating) }}</span>
                </div>
                <small style="color:#999;">{{ $review->user->full_name }} &bull; {{ $review->created_at->diffForHumans() }}</small>
            </div>
            <p style="margin-top:.5rem;">{{ $review->body }}</p>
        </div>
    </div>
@empty
    <p>No reviews yet. Be the first to share your experience!</p>
@endforelse
{{ $reviews->links() }}

{{-- Submit review modal --}}
<div id="review-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);justify-content:center;align-items:center;z-index:1000;">
    <div class="card" style="width:500px;max-width:90%;">
        <div class="card-body">
            <h3>Write a Review</h3>
            <form method="POST" action="{{ route('reviews.store') }}">
                @csrf
                <div class="form-group">
                    <label>Destination</label>
                    <select name="destination" class="form-control" required>
                        <option value="">Select...</option>
                        @foreach($destinations as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Rating</label>
                    <select name="rating" class="form-control" required>
                        @for($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}">{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                        @endfor
                    </select>
                </div>
                <div class="form-group">
                    <label>Your Experience</label>
                    <textarea name="body" class="form-control" rows="4" minlength="10" required></textarea>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <button type="button" onclick="document.getElementById('review-modal').style.display='none'" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Review/TravelerReviewTest.php
```

---

### Task 2: Budget Alerts & Notifications

**Files:**
- Modify: `app/Observers/ExpenseObserver.php` — add alert creation logic
- Modify: `app/Http/Controllers/Traveler/AlertController.php`
- Modify: `resources/views/traveler/alerts/index.blade.php`
- Modify: `routes/web.php` — add alert mark-read route
- Test: `tests/Feature/Alert/AlertTest.php`

**Interfaces:**
- Consumes: `ExpenseObserver` from Plan 3; `Notification` model
- Produces: notification created when `actual_spent / estimated_cost >= 0.80`; `PATCH /alerts/{notification}/read` marks as read

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Alert/AlertTest.php
namespace Tests\Feature\Alert;

use App\Models\Expense;
use App\Models\Notification;
use App\Models\Trip;
use App\Models\TripBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerts_page_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/alerts')->assertStatus(200);
    }

    public function test_alert_is_created_when_budget_threshold_exceeded(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        TripBudget::create(['trip_id' => $trip->id, 'category' => 'Food', 'estimated_cost' => 1000, 'actual_spent' => 0]);

        // Add expense that pushes actual_spent to 800 (80%)
        $this->actingAs($user)->post('/expenses', [
            'trip_id'      => $trip->id,
            'amount'       => 800,
            'category'     => 'Food',
            'expense_date' => '2026-08-01',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type'    => 'budget_alert',
        ]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $notif = Notification::create([
            'user_id' => $user->id, 'trip_id' => $trip->id,
            'type' => 'budget_alert', 'message' => 'Test alert', 'is_read' => false,
        ]);

        $this->actingAs($user)->patch("/alerts/{$notif->id}/read")->assertRedirect(route('alerts.index'));
        $this->assertDatabaseHas('notifications', ['id' => $notif->id, 'is_read' => true]);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Alert/AlertTest.php
```

- [ ] **Step 3: Update ExpenseObserver to create budget alerts**

Add to `app/Observers/ExpenseObserver.php` — update the `adjustActualSpent` method:

```php
private function adjustActualSpent(int $tripId, string $expenseCategory, float $delta): void
{
    $budgetCategory = self::CATEGORY_MAP[$expenseCategory] ?? null;
    if (!$budgetCategory) return;

    $budget = TripBudget::where('trip_id', $tripId)->where('category', $budgetCategory)->first();
    if (!$budget) return;

    $budget->increment('actual_spent', $delta);
    $budget->refresh();

    if ($delta > 0 && $budget->estimated_cost > 0) {
        $pct = $budget->actual_spent / $budget->estimated_cost;
        if ($pct >= 0.80) {
            $trip = \App\Models\Trip::find($tripId);
            if (!$trip) return;
            $existing = \App\Models\Notification::where('user_id', $trip->user_id)
                ->where('trip_id', $tripId)
                ->where('type', 'budget_alert')
                ->whereRaw("message LIKE ?", ["%{$budgetCategory}%"])
                ->where('is_read', false)
                ->exists();
            if (!$existing) {
                \App\Models\Notification::create([
                    'user_id' => $trip->user_id,
                    'trip_id' => $tripId,
                    'type'    => 'budget_alert',
                    'message' => "Warning: Your {$budgetCategory} budget for {$trip->destination} has reached " . round($pct * 100) . "% of the estimated amount.",
                ]);
            }
        }
    }
}
```

Also remove the old `TripBudget::where(...)->increment(...)` line (it's now inside the updated method).

- [ ] **Step 4: Add alert mark-read route to routes/web.php inside auth group**

```php
Route::patch('/alerts/{notification}/read', [Traveler\AlertController::class, 'markRead'])->name('alerts.read');
```

- [ ] **Step 5: Implement AlertController**

```php
<?php
// app/Http/Controllers/Traveler/AlertController.php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\Notification;

class AlertController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->with('trip')->latest()->paginate(20);
        return view('traveler.alerts.index', compact('notifications'));
    }

    public function markRead(Notification $notification)
    {
        abort_if($notification->user_id !== auth()->id(), 403);
        $notification->update(['is_read' => true]);
        return redirect()->route('alerts.index');
    }
}
```

- [ ] **Step 6: Update alerts view**

`resources/views/traveler/alerts/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Alerts')
@section('content')
<h1>Alerts & Notifications</h1>
@forelse($notifications as $notif)
    <div class="card" style="margin-bottom:.5rem;{{ $notif->is_read ? 'opacity:.6;' : '' }}">
        <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <span style="font-size:.8rem;font-weight:600;text-transform:uppercase;color:{{ $notif->type === 'budget_alert' ? '#e74c3c' : '#3498db' }};">
                    {{ str_replace('_', ' ', $notif->type) }}
                </span>
                <p style="margin:.25rem 0;">{{ $notif->message }}</p>
                <small style="color:#999;">{{ $notif->created_at->diffForHumans() }}
                @if($notif->trip) &bull; {{ $notif->trip->destination }} @endif</small>
            </div>
            @if(!$notif->is_read)
                <form method="POST" action="{{ route('alerts.read', $notif) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-secondary">Mark Read</button>
                </form>
            @endif
        </div>
    </div>
@empty
    <p>No notifications yet.</p>
@endforelse
{{ $notifications->links() }}
@endsection
```

- [ ] **Step 7: Run tests — expect PASS**

```
php artisan test tests/Feature/Alert/AlertTest.php
```

---

### Task 3: Budget Reports & PDF Download

**Files:**
- Modify: `app/Services/ReportService.php`
- Modify: `app/Http/Controllers/Traveler/ReportController.php`
- Modify: `resources/views/traveler/reports/index.blade.php`
- Create: `resources/views/traveler/reports/pdf.blade.php`
- Test: `tests/Feature/Report/ReportTest.php`

**Interfaces:**
- Consumes: `BudgetService::summary()` from Plan 2; `Trip`, `Expense` models
- Produces: `GET /reports` shows report dashboard; `GET /reports/download?trip_id=X` returns PDF response

- [ ] **Step 1: Install DomPDF**

```
composer require barryvdh/laravel-dompdf
```

Expected: installs without error.

- [ ] **Step 2: Write the failing test**

```php
<?php
// tests/Feature/Report/ReportTest.php
namespace Tests\Feature\Report;

use App\Models\Expense;
use App\Models\Trip;
use App\Models\TripBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_page_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/reports')->assertStatus(200);
    }

    public function test_reports_page_shows_user_trips(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id, 'destination' => 'Bohol']);

        $this->actingAs($user)->get('/reports')->assertSee('Bohol');
    }

    public function test_pdf_download_returns_pdf(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/reports/download?trip_id={$trip->id}");

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_pdf_download_blocked_for_another_users_trip(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $trip  = Trip::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->get("/reports/download?trip_id={$trip->id}")->assertStatus(403);
    }

    public function test_reports_page_requires_auth(): void
    {
        $this->get('/reports')->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 3: Run test — expect FAIL**

```
php artisan test tests/Feature/Report/ReportTest.php
```

- [ ] **Step 4: Implement ReportService**

```php
<?php
// app/Services/ReportService.php
namespace App\Services;

use App\Models\Trip;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportService
{
    public function __construct(private BudgetService $budget) {}

    public function generatePdf(Trip $trip): \Barryvdh\DomPDF\PDF
    {
        $summary  = $this->budget->summary($trip->load('budgets'));
        $expenses = $trip->expenses()->orderBy('expense_date')->get();

        $pdf = Pdf::loadView('traveler.reports.pdf', compact('trip', 'summary', 'expenses'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf;
    }
}
```

- [ ] **Step 5: Implement ReportController**

```php
<?php
// app/Http/Controllers/Traveler/ReportController.php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\BudgetService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(BudgetService $budgetService)
    {
        $trips = auth()->user()->trips()->with('budgets')->latest()->get();
        $summaries = $trips->mapWithKeys(fn($trip) => [
            $trip->id => $budgetService->summary($trip),
        ]);
        return view('traveler.reports.index', compact('trips', 'summaries'));
    }

    public function download(Request $request, ReportService $reportService)
    {
        $request->validate(['trip_id' => 'required|exists:trips,id']);
        $trip = Trip::findOrFail($request->trip_id);
        abort_if($trip->user_id !== auth()->id(), 403);

        $pdf = $reportService->generatePdf($trip);
        $filename = 'budget-report-' . \Str::slug($trip->destination) . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
}
```

- [ ] **Step 6: Create PDF blade view**

`resources/views/traveler/reports/pdf.blade.php`:
```html
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
    h1 { color: #2c3e50; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th { background: #2c3e50; color: white; padding: 8px; text-align: left; }
    td { padding: 6px 8px; border-bottom: 1px solid #eee; }
    .totals td { font-weight: bold; background: #f8f9fa; }
    .header { border-bottom: 2px solid #2c3e50; padding-bottom: 12px; margin-bottom: 16px; }
</style>
</head>
<body>
<div class="header">
    <h1>Budget Report — {{ $trip->destination }}</h1>
    <p>{{ $trip->start_date }} to {{ $trip->end_date }} &bull; {{ $trip->travel_type }} &bull; {{ $trip->num_travelers }} traveler(s)</p>
    <p>Generated: {{ now()->format('F j, Y') }}</p>
</div>

<h3>Budget Summary</h3>
<table>
    <thead><tr><th>Category</th><th>Estimated</th><th>Spent</th><th>Remaining</th></tr></thead>
    <tbody>
    @foreach($summary['categories'] as $cat)
        <tr>
            <td>{{ $cat['category'] }}</td>
            <td>₱{{ number_format($cat['estimated_cost'], 2) }}</td>
            <td>₱{{ number_format($cat['actual_spent'], 2) }}</td>
            <td style="{{ $cat['remaining'] < 0 ? 'color:red' : '' }}">₱{{ number_format($cat['remaining'], 2) }}</td>
        </tr>
    @endforeach
    <tr class="totals">
        <td>TOTAL</td>
        <td>₱{{ number_format($summary['total_estimated'], 2) }}</td>
        <td>₱{{ number_format($summary['total_spent'], 2) }}</td>
        <td>₱{{ number_format($summary['remaining'], 2) }}</td>
    </tr>
    </tbody>
</table>

<h3 style="margin-top:24px;">Expense Log</h3>
<table>
    <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th></tr></thead>
    <tbody>
    @forelse($expenses as $exp)
        <tr>
            <td>{{ $exp->expense_date }}</td>
            <td>{{ $exp->category }}</td>
            <td>{{ $exp->description ?? '—' }}</td>
            <td>₱{{ number_format($exp->amount, 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="4">No expenses recorded.</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
```

- [ ] **Step 7: Update reports index view**

`resources/views/traveler/reports/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Budget Reports')
@section('content')
<h1>Budget Reports</h1>
@forelse($trips as $trip)
    @php $s = $summaries[$trip->id]; @endphp
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h3>{{ $trip->destination }}</h3>
                    <p>{{ $trip->start_date }} → {{ $trip->end_date }}</p>
                    <p>
                        Estimated: <strong>₱{{ number_format($s['total_estimated'], 2) }}</strong> &bull;
                        Spent: <strong>₱{{ number_format($s['total_spent'], 2) }}</strong> &bull;
                        Remaining: <strong style="{{ $s['remaining'] < 0 ? 'color:red' : 'color:green' }}">₱{{ number_format($s['remaining'], 2) }}</strong>
                    </p>
                </div>
                <a href="{{ route('reports.download') }}?trip_id={{ $trip->id }}" class="btn btn-primary">
                    📄 Download PDF
                </a>
            </div>
            @if(count($s['categories']))
                <div style="margin-top:1rem;">
                    @foreach($s['categories'] as $cat)
                        @php $pct = $cat['estimated_cost'] > 0 ? min(100, ($cat['actual_spent'] / $cat['estimated_cost']) * 100) : 0; @endphp
                        <div style="margin-bottom:8px;">
                            <div style="display:flex;justify-content:space-between;font-size:.85rem;">
                                <span>{{ $cat['category'] }}</span>
                                <span>₱{{ number_format($cat['actual_spent'], 0) }} / ₱{{ number_format($cat['estimated_cost'], 0) }}</span>
                            </div>
                            <div style="height:8px;background:#eee;border-radius:4px;">
                                <div style="height:100%;width:{{ $pct }}%;background:{{ $pct >= 100 ? '#e74c3c' : ($pct >= 80 ? '#f39c12' : '#2ecc71') }};border-radius:4px;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@empty
    <p>No trips to report on yet.</p>
@endforelse
@endsection
```

- [ ] **Step 8: Run tests — expect PASS**

```
php artisan test tests/Feature/Report/ReportTest.php
```

- [ ] **Step 9: Run full P3 test suite**

```
php artisan test tests/Feature/Review/ tests/Feature/Alert/ tests/Feature/Report/
```

Expected: all PASS.
