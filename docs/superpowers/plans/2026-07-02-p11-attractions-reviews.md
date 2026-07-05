# P11: Attractions + Per-Attraction Reviews Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** (1) Add a nullable `attraction_id` FK to the `reviews` table so reviews can be linked to specific attractions. (2) Build the `AttractionBrowser` Livewire component for `/attractions` with filterable card grid. (3) Build the attraction detail view at `/attractions/{attraction}` with reviews and an add-review form.

**Architecture:** A new migration adds `attraction_id` (nullable, FK to `attractions`) to `reviews`. The `Review` model and `ReviewController` are updated to accept and store `attraction_id`. Existing reviews keep `attraction_id = null`. The existing `AttractionBrowser` Livewire stub is fully replaced. The existing `AttractionController::show()` serves the attraction detail view. The existing `/attractions/{destination}` route signature changes to work with an `Attraction` model binding (route parameter renamed from `{destination}` to `{attraction}`).

**Tech Stack:** Laravel 13, Livewire 3, MySQL migration, `public/css/budgetra.css`

## Global Constraints

- Laravel 13.18.0, PHP 8.3.12, MySQL
- No git — skip ALL git/commit steps
- `Attraction` model: `$fillable = ['name','destination','category','image','rating','description']`
- `Review` model current `$fillable`: `['user_id','destination','rating','body','status']` — add `'attraction_id'` to it
- `ReviewController::store()` currently validates `destination`, `rating`, `body` — update to also accept optional `attraction_id`
- Route `/attractions/{destination}` currently uses a string parameter — update to `/attractions/{attraction}` with model binding
- Route name stays `attractions.show`
- `AttractionController::show($attraction)` currently receives a `$destination` string — update signature to `show(Attraction $attraction)`
- Run tests: `php artisan test <test-file>`

---

## File Structure

- **CREATE:** `database/migrations/YYYY_MM_DD_HHMMSS_add_attraction_id_to_reviews_table.php`
- **MODIFY:** `app/Models/Review.php` — add `attraction_id` to `$fillable`, add `attraction()` relation
- **MODIFY:** `app/Http/Controllers/Traveler/ReviewController.php` — accept `attraction_id` in `store()`
- **MODIFY:** `app/Http/Controllers/Traveler/AttractionController.php` — change `show($destination)` to `show(Attraction $attraction)`
- **MODIFY:** `routes/web.php` — change `{destination}` to `{attraction}` for `attractions.show`
- **REPLACE:** `app/Livewire/Traveler/AttractionBrowser.php`
- **REPLACE:** `resources/views/livewire/traveler/attraction-browser.blade.php`
- **REPLACE:** `resources/views/traveler/attractions/index.blade.php`
- **REPLACE:** `resources/views/traveler/attractions/show.blade.php`
- **CREATE:** `tests/Feature/AttractionReviewTest.php`

---

### Task 1: Migration + Model + Controller Updates

**Files:**
- Create: `database/migrations/..._add_attraction_id_to_reviews_table.php`
- Modify: `app/Models/Review.php`
- Modify: `app/Http/Controllers/Traveler/ReviewController.php`
- Modify: `app/Http/Controllers/Traveler/AttractionController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AttractionReviewTest.php`

**Interfaces:**
- Produces: `reviews.attraction_id` nullable FK; `ReviewController::store()` accepts optional `attraction_id`; `AttractionController::show(Attraction $attraction)` returns `traveler.attractions.show` view

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AttractionReviewTest.php`:

```php
<?php
namespace Tests\Feature;

use App\Models\Attraction;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttractionReviewTest extends TestCase
{
    use RefreshDatabase;

    private function makeAttraction(): Attraction
    {
        return Attraction::create([
            'name'        => 'Chocolate Hills',
            'destination' => 'Bohol',
            'category'    => 'Nature',
            'description' => 'Famous cone-shaped hills.',
            'rating'      => 4.8,
        ]);
    }

    public function test_attractions_index_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/attractions')->assertStatus(200);
    }

    public function test_attraction_detail_loads(): void
    {
        $user       = User::factory()->create();
        $attraction = $this->makeAttraction();
        $this->actingAs($user)
            ->get("/attractions/{$attraction->id}")
            ->assertStatus(200)
            ->assertSee('Chocolate Hills');
    }

    public function test_user_can_submit_attraction_review(): void
    {
        $user       = User::factory()->create();
        $attraction = $this->makeAttraction();
        $this->actingAs($user)->post('/reviews', [
            'destination'   => $attraction->destination,
            'rating'        => 5,
            'body'          => 'Amazing place to visit!',
            'attraction_id' => $attraction->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'user_id'       => $user->id,
            'attraction_id' => $attraction->id,
            'rating'        => 5,
        ]);
    }

    public function test_attraction_id_column_exists(): void
    {
        $review = Review::create([
            'user_id'       => User::factory()->create()->id,
            'destination'   => 'Bohol',
            'rating'        => 4,
            'body'          => 'Great place!',
            'status'        => 'active',
            'attraction_id' => null,
        ]);
        $this->assertNull($review->fresh()->attraction_id);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/AttractionReviewTest.php
```

Expected: FAIL — `attraction_id` column does not exist on `reviews`.

- [ ] **Step 3: Create the migration**

Run:
```
php artisan make:migration add_attraction_id_to_reviews_table
```

Open the generated file in `database/migrations/` and fill in:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('attraction_id')->nullable()->after('user_id')
                  ->constrained('attractions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Attraction::class);
            $table->dropColumn('attraction_id');
        });
    }
};
```

- [ ] **Step 4: Run migration**

```
php artisan migrate
```

Expected: migration runs without error.

- [ ] **Step 5: Update `app/Models/Review.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['user_id', 'attraction_id', 'destination', 'rating', 'body', 'status'];

    public function user()       { return $this->belongsTo(User::class); }
    public function attraction() { return $this->belongsTo(Attraction::class); }
}
```

- [ ] **Step 6: Update `app/Http/Controllers/Traveler/ReviewController.php`**

```php
<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\Attraction;
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
            'destination'   => 'required|string|max:255',
            'rating'        => 'required|integer|min:1|max:5',
            'body'          => 'required|string|min:10|max:2000',
            'attraction_id' => 'nullable|exists:attractions,id',
        ]);

        auth()->user()->reviews()->create(array_merge($validated, ['status' => 'active']));

        if ($request->filled('attraction_id')) {
            return redirect()->route('attractions.show', $request->attraction_id)
                             ->with('success', 'Review submitted!');
        }
        return redirect()->route('reviews.index')->with('success', 'Review submitted!');
    }
}
```

- [ ] **Step 7: Update `routes/web.php`**

Change the line:
```php
Route::get('/attractions/{destination}',[Traveler\AttractionController::class, 'show'])->name('attractions.show');
```
to:
```php
Route::get('/attractions/{attraction}',[Traveler\AttractionController::class, 'show'])->name('attractions.show');
```

- [ ] **Step 8: Run tests**

```
php artisan test tests/Feature/AttractionReviewTest.php
```

Expected: 4 tests, all pass.

---

### Task 2: AttractionBrowser Livewire + Attraction Views

**Files:**
- Modify: `app/Http/Controllers/Traveler/AttractionController.php`
- Replace: `app/Livewire/Traveler/AttractionBrowser.php`
- Replace: `resources/views/livewire/traveler/attraction-browser.blade.php`
- Replace: `resources/views/traveler/attractions/index.blade.php`
- Replace: `resources/views/traveler/attractions/show.blade.php`
- Test: existing `tests/Feature/AttractionReviewTest.php`

**Interfaces:**
- Consumes: `Attraction` model, `Review` model (with `attraction_id`)
- Produces: `/attractions` searchable card grid; `/attractions/{attraction}` detail + review form

- [ ] **Step 1: Update `AttractionController.php`**

```php
<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\Attraction;
use App\Models\Review;

class AttractionController extends Controller
{
    public function index()
    {
        return view('traveler.attractions.index');
    }

    public function show(Attraction $attraction)
    {
        $reviews = Review::with('user')
            ->where('attraction_id', $attraction->id)
            ->where('status', 'active')
            ->latest()
            ->get();

        $avgRating   = $reviews->avg('rating') ?? 0;
        $hasReviewed = auth()->user()->reviews()
            ->where('attraction_id', $attraction->id)
            ->exists();

        return view('traveler.attractions.show', compact('attraction', 'reviews', 'avgRating', 'hasReviewed'));
    }
}
```

- [ ] **Step 2: Replace `app/Livewire/Traveler/AttractionBrowser.php`**

```php
<?php
namespace App\Livewire\Traveler;

use App\Models\Attraction;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['active' => 'attractions'])]
class AttractionBrowser extends Component
{
    public string $search      = '';
    public string $destination = '';

    public function getAttractionsProperty()
    {
        $query = Attraction::query();
        if ($this->search)      $query->where('name', 'like', "%{$this->search}%");
        if ($this->destination) $query->where('destination', $this->destination);
        return $query->orderBy('name')->get();
    }

    public function getDestinationsProperty()
    {
        return Attraction::orderBy('destination')->pluck('destination')->unique()->values();
    }

    public function render()
    {
        return view('livewire.traveler.attraction-browser');
    }
}
```

- [ ] **Step 3: Replace `resources/views/livewire/traveler/attraction-browser.blade.php`**

```html
<div>
    <div class="bt-flex-between mb-16">
        <div>
            <h1>Attractions</h1>
            <p class="text-muted">Explore popular spots at your destinations.</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bt-grid-2 mb-24" style="max-width:560px;">
        <div>
            <input type="text" wire:model.debounce.300ms="search"
                   class="bt-input" placeholder="Search attractions...">
        </div>
        <div>
            <select wire:model.live="destination" class="bt-select">
                <option value="">All destinations</option>
                @foreach ($this->destinations as $dest)
                <option value="{{ $dest }}">{{ $dest }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Card grid --}}
    @if ($this->attractions->isEmpty())
    <div class="bt-empty">
        <div class="bt-empty-icon">🏔️</div>
        <h3>No attractions found</h3>
        <p>Try a different search or destination filter.</p>
    </div>
    @else
    <div class="bt-attraction-grid">
        @foreach ($this->attractions as $attraction)
        <a href="{{ route('attractions.show', $attraction) }}" style="text-decoration:none;color:inherit;">
            <div class="bt-attraction-card">
                <div class="bt-attraction-img">
                    @if ($attraction->image)
                    <img src="{{ asset('storage/' . $attraction->image) }}"
                         style="width:100%;height:100%;object-fit:cover;" alt="{{ $attraction->name }}">
                    @else
                    🗺️
                    @endif
                </div>
                <div class="bt-attraction-body">
                    <div style="font-size:14px;font-weight:600;">{{ $attraction->name }}</div>
                    <div class="bt-chip bt-chip-brown mt-4">{{ $attraction->destination }}</div>
                    <div class="mt-4 bt-flex" style="gap:4px;align-items:center;">
                        <span class="bt-stars">{{ str_repeat('★', round($attraction->rating)) }}{{ str_repeat('☆', 5 - round($attraction->rating)) }}</span>
                        <span class="text-muted" style="font-size:12px;">{{ number_format($attraction->rating, 1) }}</span>
                    </div>
                    <div class="bt-btn bt-btn-primary bt-btn-sm mt-8" style="font-size:11px;">View Details</div>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>
```

- [ ] **Step 4: Replace `resources/views/traveler/attractions/index.blade.php`**

```html
@extends('layouts.app')
@section('title', 'Attractions')
@section('content')
@livewire('traveler.attraction-browser')
@endsection
```

- [ ] **Step 5: Replace `resources/views/traveler/attractions/show.blade.php`**

```html
@extends('layouts.app')
@section('title', $attraction->name)
@section('content')

<a href="{{ route('attractions.index') }}" class="bt-btn bt-btn-outline bt-btn-sm mb-16">
    <i class="fa-solid fa-arrow-left"></i> All Attractions
</a>

{{-- Hero --}}
<div class="bt-card mb-24" style="position:relative;overflow:hidden;min-height:180px;background:linear-gradient(135deg,#5C2D0E,#C9A84C);color:white;border:none;">
    <div style="padding:28px;">
        <span class="bt-chip" style="background:rgba(255,255,255,0.2);color:white;margin-bottom:12px;">{{ $attraction->category }}</span>
        <h1 style="color:white;font-size:30px;margin-bottom:6px;">{{ $attraction->name }}</h1>
        <div style="font-size:14px;opacity:0.85;margin-bottom:12px;">
            <i class="fa-solid fa-location-dot"></i> {{ $attraction->destination }}
        </div>
        <div class="bt-flex" style="gap:6px;align-items:center;">
            <span style="color:#F39C12;font-size:18px;letter-spacing:2px;">
                {{ str_repeat('★', round($avgRating)) }}{{ str_repeat('☆', 5 - round($avgRating)) }}
            </span>
            <span style="font-size:15px;font-weight:600;">{{ number_format($avgRating, 1) }}</span>
            <span style="font-size:13px;opacity:0.8;">({{ $reviews->count() }} reviews)</span>
        </div>
    </div>
</div>

{{-- Description --}}
@if ($attraction->description)
<div class="bt-card mb-24">
    <h2 class="mb-12">About</h2>
    <p style="font-size:14px;line-height:1.7;color:var(--color-text-muted);">{{ $attraction->description }}</p>
</div>
@endif

<div class="bt-grid-2" style="align-items:start;">
    {{-- Reviews list --}}
    <div>
        <h2 class="mb-16">Reviews</h2>
        @if (session('success'))
        <div class="bt-alert bt-alert-success">{{ session('success') }}</div>
        @endif

        @forelse ($reviews as $review)
        <div class="bt-card mb-12">
            <div class="bt-flex-between mb-8">
                <div class="bt-flex" style="gap:10px;">
                    <div class="bt-avatar" style="width:36px;height:36px;font-size:13px;">
                        {{ strtoupper(substr($review->user->full_name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:600;">{{ $review->user->full_name ?? 'Traveler' }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $review->created_at->format('M j, Y') }}</div>
                    </div>
                </div>
                <span style="color:#F39C12;font-size:14px;">
                    {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
                </span>
            </div>
            <p style="font-size:14px;color:var(--color-text-muted);line-height:1.6;">{{ $review->body }}</p>
        </div>
        @empty
        <div class="bt-empty" style="padding:28px;">
            <div class="bt-empty-icon">💬</div>
            <p>No reviews yet. Be the first!</p>
        </div>
        @endforelse
    </div>

    {{-- Add review form --}}
    <div>
        <h2 class="mb-16">Leave a Review</h2>
        @auth
        @if ($hasReviewed)
        <div class="bt-alert bt-alert-success">You've already reviewed this attraction.</div>
        @else
        <div class="bt-card">
            @if ($errors->any())
            <div class="bt-alert bt-alert-danger">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('reviews.store') }}">
                @csrf
                <input type="hidden" name="destination"   value="{{ $attraction->destination }}">
                <input type="hidden" name="attraction_id" value="{{ $attraction->id }}">

                <div class="bt-form-group">
                    <label class="bt-label">Your Rating</label>
                    <div class="bt-star-picker">
                        @for ($i = 5; $i >= 1; $i--)
                        <input type="radio" name="rating" id="star{{ $i }}" value="{{ $i }}"
                               {{ old('rating') == $i ? 'checked' : '' }}>
                        <label for="star{{ $i }}" title="{{ $i }} star">★</label>
                        @endfor
                    </div>
                    @error('rating')<div class="bt-error">{{ $message }}</div>@enderror
                </div>

                <div class="bt-form-group">
                    <label class="bt-label">Your Review</label>
                    <textarea name="body" class="bt-textarea {{ $errors->has('body') ? 'is-invalid' : '' }}"
                              placeholder="Share your experience (min. 10 characters)..."
                              rows="4" required>{{ old('body') }}</textarea>
                    @error('body')<div class="bt-error">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="bt-btn bt-btn-primary bt-btn-block">
                    <i class="fa-solid fa-paper-plane"></i> Submit Review
                </button>
            </form>
        </div>
        @endif
        @endauth
    </div>
</div>

@endsection
```

- [ ] **Step 6: Run all tests**

```
php artisan test tests/Feature/AttractionReviewTest.php
```

Expected: 4 tests, all pass.

- [ ] **Step 7: Run full suite for regressions**

```
php artisan test
```

Expected: all previously passing tests still pass. Note: the existing P2-T1 test (`DestinationBrowseTest`) may have tested `AttractionController::show($destination)` with a string — if it fails, read the test and update the route call from `route('attractions.show', 'Bohol')` to `route('attractions.show', $attraction)`.

## Report Contract

Write your full report to:
`C:\Users\ASUS\AppData\Local\Temp\claude\c--phpsite-Capstone---Budgetra\55e34b67-7087-4f9b-8add-8becf1178a87\scratchpad\p11-report.md`

Return ONLY: status (DONE/DONE_WITH_CONCERNS/NEEDS_CONTEXT/BLOCKED), files changed, one-line test summary, concerns.
