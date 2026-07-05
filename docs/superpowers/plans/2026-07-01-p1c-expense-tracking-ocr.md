# Expense Tracking & OCR Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement manual expense entry, expense editing/deletion with budget sync, OCR receipt upload that pre-fills the expense form, and the OcrService.

**Architecture:** ExpenseController handles CRUD. Every create/delete syncs `actual_spent` on the matching `trip_budgets` row via an `Expense` model observer. OcrService uploads a receipt file, calls an OCR API (Google Cloud Vision), parses the result, and returns a structured array `[amount, date, description]`. The expense categories in `expenses` differ slightly from `trip_budgets` categories — the mapping is defined in `BudgetService`.

**Tech Stack:** Laravel 13.x, Blade, PHP 8.3+, MySQL, Google Cloud Vision API (or Tesseract fallback).

## Global Constraints

- Project root: `c:\phpsite\Capstone - Budgetra`
- Laravel 13.18.0, PHP 8.3.12
- Requires Plan 1 (Auth) and Plan 2 (Trip Planner) complete
- `expenses` columns: `trip_id`, `user_id`, `amount` (decimal 10,2), `category` (enum: `Transportation`|`Accommodation`|`Food`|`Activities`|`Shopping`|`Emergency Expenses`), `description` (nullable), `receipt_path` (nullable), `expense_date` (date)
- `ocr_logs` columns: `user_id` (nullable), `filename` (nullable), `status` (enum: success|failed|partial), `confidence` (decimal 5,2, nullable), `error_message` (nullable)
- Budget sync mapping (expense category → trip_budget category):
  - `Transportation` → `Transportation`
  - `Accommodation` → `Accommodation`
  - `Food` → `Food`
  - `Activities` → `Tourist Attractions`
  - `Shopping` → `Shopping`
  - `Emergency Expenses` → `Emergency Funds`
- Receipt files stored in `storage/app/public/receipts/` (`Storage::disk('public')`, path prefix `receipts/`)
- `EXPENSE_CATEGORIES` constant = `['Transportation','Accommodation','Food','Activities','Shopping','Emergency Expenses']`
- OCR API key stored in `config/services.php` under `google_vision.key` (reads from `GOOGLE_VISION_KEY` env var)
- Skip git commit steps

---

### Task 1: Expense CRUD + Budget Sync

**Files:**
- Modify: `app/Http/Controllers/Traveler/ExpenseController.php`
- Create: `app/Observers/ExpenseObserver.php`
- Modify: `app/Providers/AppServiceProvider.php` — register observer
- Modify: `resources/views/traveler/expenses/index.blade.php`
- Modify: `resources/views/traveler/expenses/create.blade.php`
- Create: `resources/views/traveler/expenses/edit.blade.php`
- Test: `tests/Feature/Expense/ExpenseCrudTest.php`

**Interfaces:**
- Consumes: `Trip::factory()` from Plan 2; auth user
- Produces: `POST /expenses` creates expense, updates `trip_budgets.actual_spent`; `DELETE /expenses/{expense}` deletes and reverses sync

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Expense/ExpenseCrudTest.php
namespace Tests\Feature\Expense;

use App\Models\Expense;
use App\Models\Trip;
use App\Models\TripBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_index_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/expenses')->assertStatus(200);
    }

    public function test_user_can_create_expense_and_budget_is_synced(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        TripBudget::create(['trip_id' => $trip->id, 'category' => 'Transportation', 'estimated_cost' => 5000, 'actual_spent' => 0]);

        $response = $this->actingAs($user)->post('/expenses', [
            'trip_id'      => $trip->id,
            'amount'       => 1500,
            'category'     => 'Transportation',
            'description'  => 'Bus fare',
            'expense_date' => '2026-08-03',
        ]);

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', ['amount' => 1500, 'category' => 'Transportation']);
        $this->assertDatabaseHas('trip_budgets', ['trip_id' => $trip->id, 'category' => 'Transportation', 'actual_spent' => 1500]);
    }

    public function test_deleting_expense_reverses_budget_sync(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        TripBudget::create(['trip_id' => $trip->id, 'category' => 'Food', 'estimated_cost' => 3000, 'actual_spent' => 800]);
        $expense = Expense::create([
            'trip_id' => $trip->id, 'user_id' => $user->id,
            'amount' => 800, 'category' => 'Food',
            'expense_date' => '2026-08-02',
        ]);

        $this->actingAs($user)->delete("/expenses/{$expense->id}");

        $this->assertDatabaseHas('trip_budgets', ['trip_id' => $trip->id, 'category' => 'Food', 'actual_spent' => 0]);
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_user_cannot_delete_another_users_expense(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $trip  = Trip::factory()->create(['user_id' => $other->id]);
        $expense = Expense::create([
            'trip_id' => $trip->id, 'user_id' => $other->id,
            'amount' => 100, 'category' => 'Food', 'expense_date' => '2026-08-01',
        ]);

        $this->actingAs($user)->delete("/expenses/{$expense->id}")->assertStatus(403);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/expenses', []);
        $response->assertSessionHasErrors(['trip_id', 'amount', 'category', 'expense_date']);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Expense/ExpenseCrudTest.php
```

- [ ] **Step 3: Create ExpenseObserver**

```php
<?php
// app/Observers/ExpenseObserver.php
namespace App\Observers;

use App\Models\Expense;
use App\Models\TripBudget;

class ExpenseObserver
{
    private const CATEGORY_MAP = [
        'Transportation'    => 'Transportation',
        'Accommodation'     => 'Accommodation',
        'Food'              => 'Food',
        'Activities'        => 'Tourist Attractions',
        'Shopping'          => 'Shopping',
        'Emergency Expenses'=> 'Emergency Funds',
    ];

    public function created(Expense $expense): void
    {
        $this->adjustActualSpent($expense->trip_id, $expense->category, $expense->amount);
    }

    public function deleted(Expense $expense): void
    {
        $this->adjustActualSpent($expense->trip_id, $expense->category, -$expense->amount);
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

- [ ] **Step 4: Register observer in AppServiceProvider**

Open `app/Providers/AppServiceProvider.php` and add to the `boot()` method:

```php
use App\Models\Expense;
use App\Observers\ExpenseObserver;

public function boot(): void
{
    Expense::observe(ExpenseObserver::class);
}
```

- [ ] **Step 5: Implement ExpenseController**

```php
<?php
// app/Http/Controllers/Traveler/ExpenseController.php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    private const CATEGORIES = ['Transportation','Accommodation','Food','Activities','Shopping','Emergency Expenses'];

    public function index(Request $request)
    {
        $user  = auth()->user();
        $trips = $user->trips()->latest()->get();
        $query = $user->expenses()->with('trip')->latest('expense_date');

        if ($request->filled('trip_id'))   $query->where('trip_id', $request->trip_id);
        if ($request->filled('category'))  $query->where('category', $request->category);
        if ($request->filled('date_from')) $query->where('expense_date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->where('expense_date', '<=', $request->date_to);

        $expenses   = $query->paginate(20)->withQueryString();
        $categories = self::CATEGORIES;

        return view('traveler.expenses.index', compact('expenses', 'trips', 'categories'));
    }

    public function create()
    {
        $trips      = auth()->user()->trips()->latest()->get();
        $categories = self::CATEGORIES;
        return view('traveler.expenses.create', compact('trips', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'trip_id'      => 'required|exists:trips,id',
            'amount'       => 'required|numeric|min:0.01',
            'category'     => 'required|in:' . implode(',', self::CATEGORIES),
            'description'  => 'nullable|string|max:500',
            'expense_date' => 'required|date',
            'receipt'      => 'nullable|image|mimes:jpeg,png,jpg,webp,pdf|max:5120',
        ]);

        abort_if(
            !auth()->user()->trips()->where('id', $validated['trip_id'])->exists(),
            403
        );

        if ($request->hasFile('receipt')) {
            $validated['receipt_path'] = $request->file('receipt')
                ->store('receipts', 'public');
        }

        auth()->user()->expenses()->create($validated);

        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function edit(Expense $expense)
    {
        abort_if($expense->user_id !== auth()->id(), 403);
        $trips      = auth()->user()->trips()->latest()->get();
        $categories = self::CATEGORIES;
        return view('traveler.expenses.edit', compact('expense', 'trips', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        abort_if($expense->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'trip_id'      => 'required|exists:trips,id',
            'amount'       => 'required|numeric|min:0.01',
            'category'     => 'required|in:' . implode(',', self::CATEGORIES),
            'description'  => 'nullable|string|max:500',
            'expense_date' => 'required|date',
        ]);

        // Reverse old sync, apply new via observer
        // Observer only fires on created/deleted — update needs manual adjustment
        $oldAmount = $expense->amount;
        $oldCategory = $expense->category;
        $expense->update($validated);

        // Manual sync for amount/category changes
        $observer = new \App\Observers\ExpenseObserver();
        if ($oldCategory !== $expense->category || $oldAmount != $expense->amount) {
            // Reverse old
            $observer->deleted(tap(clone $expense, fn($e) => $e->forceFill(['amount' => $oldAmount, 'category' => $oldCategory])));
            // Apply new
            $observer->created($expense);
        }

        return redirect()->route('expenses.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        abort_if($expense->user_id !== auth()->id(), 403);

        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }
}
```

- [ ] **Step 6: Add edit/update routes to routes/web.php inside auth group**

```php
Route::get('/expenses/{expense}/edit',  [Traveler\ExpenseController::class, 'edit'])->name('expenses.edit');
Route::put('/expenses/{expense}',       [Traveler\ExpenseController::class, 'update'])->name('expenses.update');
```

- [ ] **Step 7: Create expense views**

`resources/views/traveler/expenses/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Expenses')
@section('content')
<div class="page-header">
    <h1>Expenses</h1>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary">+ Log Expense</a>
</div>

<form method="GET" action="{{ route('expenses.index') }}" style="margin-bottom:1rem;display:flex;gap:8px;flex-wrap:wrap;">
    <select name="trip_id" class="form-control" style="width:auto;">
        <option value="">All Trips</option>
        @foreach($trips as $trip)
            <option value="{{ $trip->id }}" {{ request('trip_id') == $trip->id ? 'selected' : '' }}>{{ $trip->destination }}</option>
        @endforeach
    </select>
    <select name="category" class="form-control" style="width:auto;">
        <option value="">All Categories</option>
        @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
        @endforeach
    </select>
    <input type="date" name="date_from" class="form-control" style="width:auto;" value="{{ request('date_from') }}">
    <input type="date" name="date_to" class="form-control" style="width:auto;" value="{{ request('date_to') }}">
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Clear</a>
</form>

<div class="card">
    <table style="width:100%;border-collapse:collapse;">
        <thead><tr>
            <th style="padding:8px;text-align:left;border-bottom:2px solid #eee;">Date</th>
            <th style="padding:8px;text-align:left;border-bottom:2px solid #eee;">Trip</th>
            <th style="padding:8px;text-align:left;border-bottom:2px solid #eee;">Category</th>
            <th style="padding:8px;text-align:left;border-bottom:2px solid #eee;">Description</th>
            <th style="padding:8px;text-align:right;border-bottom:2px solid #eee;">Amount</th>
            <th style="padding:8px;text-align:center;border-bottom:2px solid #eee;">Actions</th>
        </tr></thead>
        <tbody>
        @forelse($expenses as $expense)
            <tr>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;">{{ $expense->expense_date }}</td>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;">{{ $expense->trip->destination ?? '—' }}</td>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;">{{ $expense->category }}</td>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;">{{ $expense->description ?? '—' }}</td>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;text-align:right;">{{ number_format($expense->amount, 2) }}</td>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;text-align:center;">
                    <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-secondary">Edit</a>
                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Del</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" style="padding:1rem;text-align:center;">No expenses yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $expenses->links() }}
@endsection
```

`resources/views/traveler/expenses/create.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Log Expense')
@section('content')
<h1>Log Expense</h1>
@if($errors->any())<div class="alert alert-danger"><ul style="margin:0;padding-left:1.2em;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data" id="expense-form">
    @csrf
    <div class="form-group">
        <label>Trip</label>
        <select name="trip_id" class="form-control" required>
            <option value="">Select trip...</option>
            @foreach($trips as $trip)<option value="{{ $trip->id }}" {{ old('trip_id') == $trip->id ? 'selected' : '' }}>{{ $trip->destination }}</option>@endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Amount</label>
        <input type="number" step="0.01" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" required min="0.01">
    </div>
    <div class="form-group">
        <label>Category</label>
        <select name="category" id="category" class="form-control" required>
            <option value="">Select category...</option>
            @foreach($categories as $cat)<option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>@endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Description</label>
        <input type="text" name="description" id="description" class="form-control" value="{{ old('description') }}">
    </div>
    <div class="form-group">
        <label>Date</label>
        <input type="date" name="expense_date" id="expense_date" class="form-control" value="{{ old('expense_date', date('Y-m-d')) }}" required>
    </div>
    <div class="form-group">
        <label>Receipt (optional)</label>
        <input type="file" name="receipt" class="form-control" accept="image/*,application/pdf" id="receipt-input">
    </div>
    <div style="display:flex;gap:8px;">
        <button type="submit" class="btn btn-primary">Save Expense</button>
        <button type="button" class="btn btn-secondary" id="scan-btn">📷 Scan Receipt</button>
    </div>
</form>
</div></div>

<script>
document.getElementById('scan-btn').addEventListener('click', function() {
    document.getElementById('receipt-input').click();
});
document.getElementById('receipt-input').addEventListener('change', async function() {
    if (!this.files[0]) return;
    this.closest('form').querySelector('#scan-btn').textContent = 'Scanning...';
    const data = new FormData();
    data.append('receipt', this.files[0]);
    data.append('_token', '{{ csrf_token() }}');
    const res = await fetch('{{ route("expenses.ocr") }}', { method: 'POST', body: data });
    const json = await res.json();
    if (json.amount)       document.getElementById('amount').value = json.amount;
    if (json.description)  document.getElementById('description').value = json.description;
    if (json.date)         document.getElementById('expense_date').value = json.date;
    this.closest('form').querySelector('#scan-btn').textContent = '📷 Scan Receipt';
});
</script>
@endsection
```

`resources/views/traveler/expenses/edit.blade.php` (create this file):
```html
@extends('layouts.app')
@section('title', 'Edit Expense')
@section('content')
<h1>Edit Expense</h1>
@if($errors->any())<div class="alert alert-danger"><ul style="margin:0;padding-left:1.2em;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('expenses.update', $expense) }}">
    @csrf @method('PUT')
    <div class="form-group">
        <label>Trip</label>
        <select name="trip_id" class="form-control" required>
            @foreach($trips as $trip)<option value="{{ $trip->id }}" {{ old('trip_id', $expense->trip_id) == $trip->id ? 'selected' : '' }}>{{ $trip->destination }}</option>@endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Amount</label>
        <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $expense->amount) }}" required>
    </div>
    <div class="form-group">
        <label>Category</label>
        <select name="category" class="form-control" required>
            @foreach($categories as $cat)<option value="{{ $cat }}" {{ old('category', $expense->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>@endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Description</label>
        <input type="text" name="description" class="form-control" value="{{ old('description', $expense->description) }}">
    </div>
    <div class="form-group">
        <label>Date</label>
        <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', $expense->expense_date) }}" required>
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Cancel</a>
</form>
</div></div>
@endsection
```

- [ ] **Step 8: Run tests — expect PASS**

```
php artisan test tests/Feature/Expense/ExpenseCrudTest.php
```

---

### Task 2: OCR Receipt Scanner

**Files:**
- Modify: `app/Services/OcrService.php`
- Modify: `app/Http/Controllers/Traveler/ExpenseController.php` — add `ocr()` method
- Modify: `routes/web.php` — add OCR endpoint
- Modify: `config/services.php` — add google_vision entry
- Test: `tests/Feature/Expense/OcrServiceTest.php`

**Interfaces:**
- Produces: `POST /expenses/ocr` accepts multipart `receipt` file, returns JSON `{amount, date, description, confidence}`
- Produces: `OcrService::scan(UploadedFile $file, int $userId): array`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Expense/OcrServiceTest.php
namespace Tests\Feature\Expense;

use App\Models\User;
use App\Services\OcrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OcrServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ocr_endpoint_requires_auth(): void
    {
        $this->post(route('expenses.ocr'), [])->assertRedirect(route('login'));
    }

    public function test_ocr_endpoint_returns_json(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('expenses.ocr'), [
            'receipt' => UploadedFile::fake()->image('receipt.jpg', 400, 600),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['amount', 'date', 'description', 'confidence']);
    }

    public function test_ocr_endpoint_requires_receipt_file(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson(route('expenses.ocr'), []);
        $response->assertStatus(422);
    }

    public function test_ocr_log_is_recorded(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('expenses.ocr'), [
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

        $this->assertDatabaseHas('ocr_logs', ['user_id' => $user->id]);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Expense/OcrServiceTest.php
```

- [ ] **Step 3: Add google_vision to config/services.php**

Open `config/services.php` and add:

```php
'google_vision' => [
    'key' => env('GOOGLE_VISION_KEY', ''),
],
```

Add to `.env`:
```
GOOGLE_VISION_KEY=
```

- [ ] **Step 4: Implement OcrService**

```php
<?php
// app/Services/OcrService.php
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
        $apiKey   = config('services.google_vision.key');

        // If no API key configured, return empty result
        if (empty($apiKey)) {
            OcrLog::create([
                'user_id'      => $userId,
                'filename'     => $filename,
                'status'       => 'failed',
                'error_message'=> 'Google Vision API key not configured.',
            ]);
            return ['amount' => null, 'date' => null, 'description' => null, 'confidence' => 0];
        }

        $imageContent = base64_encode(Storage::disk('public')->get($path));

        $response = Http::post("https://vision.googleapis.com/v1/images:annotate?key={$apiKey}", [
            'requests' => [[
                'image'    => ['content' => $imageContent],
                'features' => [['type' => 'TEXT_DETECTION']],
            ]],
        ]);

        if (!$response->successful()) {
            OcrLog::create([
                'user_id'      => $userId,
                'filename'     => $filename,
                'status'       => 'failed',
                'error_message'=> 'API request failed: ' . $response->status(),
            ]);
            return ['amount' => null, 'date' => null, 'description' => null, 'confidence' => 0];
        }

        $text       = $response->json('responses.0.textAnnotations.0.description', '');
        $parsed     = $this->parseReceiptText($text);
        $confidence = $parsed['amount'] ? 85.0 : 30.0;

        OcrLog::create([
            'user_id'   => $userId,
            'filename'  => $filename,
            'status'    => $parsed['amount'] ? 'success' : 'partial',
            'confidence'=> $confidence,
        ]);

        return array_merge($parsed, ['confidence' => $confidence]);
    }

    private function parseReceiptText(string $text): array
    {
        $amount      = null;
        $date        = null;
        $description = null;

        // Extract amount — look for patterns like "TOTAL 1,500.00" or "₱1500"
        if (preg_match('/(?:TOTAL|AMOUNT|SUBTOTAL|DUE)[:\s]*[₱$]?\s*([\d,]+\.?\d{0,2})/i', $text, $m)) {
            $amount = (float) str_replace(',', '', $m[1]);
        } elseif (preg_match('/[₱$]\s*([\d,]+\.?\d{0,2})/', $text, $m)) {
            $amount = (float) str_replace(',', '', $m[1]);
        }

        // Extract date — look for patterns like 2026-08-01 or 08/01/2026
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $text, $m)) {
            $date = $m[1];
        } elseif (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $text, $m)) {
            $date = "{$m[3]}-" . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT);
        }

        // Extract description — first non-empty line as merchant name
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        $description = reset($lines) ?: null;

        return compact('amount', 'date', 'description');
    }
}
```

- [ ] **Step 5: Add `ocr()` method to ExpenseController**

Add this method to `app/Http/Controllers/Traveler/ExpenseController.php`:

```php
public function ocr(Request $request, \App\Services\OcrService $ocrService)
{
    $request->validate(['receipt' => 'required|file|mimes:jpeg,png,jpg,webp,pdf|max:5120']);

    $result = $ocrService->scan($request->file('receipt'), auth()->id());

    return response()->json($result);
}
```

- [ ] **Step 6: Add OCR route to routes/web.php inside auth group**

```php
Route::post('/expenses/ocr', [Traveler\ExpenseController::class, 'ocr'])->name('expenses.ocr');
```

Place this **before** the `Route::delete('/expenses/{expense}', ...)` line to avoid route conflict.

- [ ] **Step 7: Run tests — expect PASS**

```
php artisan test tests/Feature/Expense/OcrServiceTest.php
```

- [ ] **Step 8: Run full expense test suite**

```
php artisan test tests/Feature/Expense/
```

Expected: all PASS.
