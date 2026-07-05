# Savings Goals & Database Backup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement savings goals CRUD for travelers (track progress toward a target amount with optional trip linkage) and an admin-only database backup/restore tool (on-demand mysqldump download + .sql upload restore).

**Architecture:** Savings goals are owned by the authenticated traveler. Progress is updated manually by the traveler (current_savings field). DB backup invokes `mysqldump` via `Process::run()` and streams the output as a file download. Restore uploads a `.sql` file and pipes it through `mysql` CLI.

**Tech Stack:** Laravel 13.x, Blade, PHP 8.3+, MySQL, Symfony Process (included in Laravel).

## Global Constraints

- Project root: `c:\phpsite\Capstone - Budgetra`
- Laravel 13.18.0, PHP 8.3.12
- Requires Plan 1 complete
- `savings_goals` columns: `user_id`, `trip_id` (nullable, FK to trips), `goal_name`, `target_amount` (decimal), `current_savings` (decimal, default 0), `deadline` (date)
- DB backup: use `mysqldump` — read connection credentials from `config('database.connections.mysql')`
- DB restore: accept `.sql` file upload, pipe through `mysql` CLI
- Skip git commit steps

---

### Task 1: Savings Goals CRUD

**Files:**
- Create: `app/Http/Controllers/Traveler/SavingsGoalController.php`
- Create: `resources/views/traveler/savings/` (index, create, edit)
- Modify: `routes/web.php` — add savings goals routes
- Test: `tests/Feature/SavingsGoal/SavingsGoalTest.php`

**Interfaces:**
- Consumes: `SavingsGoal` model, `Trip` model (for optional linkage), `auth` middleware
- Produces: full CRUD; `PATCH /savings/{goal}/deposit` adds to `current_savings`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/SavingsGoal/SavingsGoalTest.php
namespace Tests\Feature\SavingsGoal;

use App\Models\SavingsGoal;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsGoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_savings_index_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/savings')->assertStatus(200);
    }

    public function test_user_can_create_savings_goal(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/savings', [
            'goal_name'      => 'Boracay Trip Fund',
            'target_amount'  => 50000,
            'current_savings'=> 5000,
            'deadline'       => '2026-12-31',
        ])->assertRedirect(route('savings.index'));

        $this->assertDatabaseHas('savings_goals', [
            'goal_name'   => 'Boracay Trip Fund',
            'user_id'     => $user->id,
            'target_amount' => 50000,
        ]);
    }

    public function test_user_can_make_deposit(): void
    {
        $user = User::factory()->create();
        $goal = SavingsGoal::create([
            'user_id'         => $user->id,
            'goal_name'       => 'Test Fund',
            'target_amount'   => 10000,
            'current_savings' => 1000,
            'deadline'        => '2026-12-31',
        ]);

        $this->actingAs($user)->patch("/savings/{$goal->id}/deposit", ['amount' => 500])->assertRedirect();
        $this->assertDatabaseHas('savings_goals', ['id' => $goal->id, 'current_savings' => 1500]);
    }

    public function test_user_cannot_access_others_goal(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $goal  = SavingsGoal::create([
            'user_id' => $other->id, 'goal_name' => 'Other', 'target_amount' => 1000, 'current_savings' => 0, 'deadline' => '2026-12-31',
        ]);

        $this->actingAs($user)->get("/savings/{$goal->id}/edit")->assertStatus(403);
    }

    public function test_user_can_delete_goal(): void
    {
        $user = User::factory()->create();
        $goal = SavingsGoal::create([
            'user_id' => $user->id, 'goal_name' => 'Delete me', 'target_amount' => 1000, 'current_savings' => 0, 'deadline' => '2026-12-31',
        ]);

        $this->actingAs($user)->delete("/savings/{$goal->id}")->assertRedirect(route('savings.index'));
        $this->assertDatabaseMissing('savings_goals', ['id' => $goal->id]);
    }

    public function test_savings_requires_auth(): void
    {
        $this->get('/savings')->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/SavingsGoal/SavingsGoalTest.php
```

- [ ] **Step 3: Add savings routes to auth group in routes/web.php**

```php
Route::get('/savings',                       [Traveler\SavingsGoalController::class, 'index'])->name('savings.index');
Route::get('/savings/create',                [Traveler\SavingsGoalController::class, 'create'])->name('savings.create');
Route::post('/savings',                      [Traveler\SavingsGoalController::class, 'store'])->name('savings.store');
Route::get('/savings/{goal}/edit',           [Traveler\SavingsGoalController::class, 'edit'])->name('savings.edit');
Route::put('/savings/{goal}',                [Traveler\SavingsGoalController::class, 'update'])->name('savings.update');
Route::delete('/savings/{goal}',             [Traveler\SavingsGoalController::class, 'destroy'])->name('savings.destroy');
Route::patch('/savings/{goal}/deposit',      [Traveler\SavingsGoalController::class, 'deposit'])->name('savings.deposit');
```

- [ ] **Step 4: Implement SavingsGoalController**

```php
<?php
// app/Http/Controllers/Traveler/SavingsGoalController.php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\SavingsGoal;
use App\Models\Trip;
use Illuminate\Http\Request;

class SavingsGoalController extends Controller
{
    public function index()
    {
        $goals = auth()->user()->savingsGoals()->with('trip')->latest()->get();
        return view('traveler.savings.index', compact('goals'));
    }

    public function create()
    {
        $trips = auth()->user()->trips()->orderBy('destination')->get();
        return view('traveler.savings.create', compact('trips'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'goal_name'       => 'required|string|max:255',
            'target_amount'   => 'required|numeric|min:1',
            'current_savings' => 'nullable|numeric|min:0',
            'deadline'        => 'required|date|after:today',
            'trip_id'         => 'nullable|exists:trips,id',
        ]);

        if ($request->filled('trip_id')) {
            $trip = Trip::find($request->trip_id);
            abort_if($trip->user_id !== auth()->id(), 403);
        }

        auth()->user()->savingsGoals()->create([
            ...$validated,
            'current_savings' => $validated['current_savings'] ?? 0,
        ]);

        return redirect()->route('savings.index')->with('success', 'Savings goal created!');
    }

    public function edit(SavingsGoal $goal)
    {
        abort_if($goal->user_id !== auth()->id(), 403);
        $trips = auth()->user()->trips()->orderBy('destination')->get();
        return view('traveler.savings.edit', compact('goal', 'trips'));
    }

    public function update(Request $request, SavingsGoal $goal)
    {
        abort_if($goal->user_id !== auth()->id(), 403);
        $validated = $request->validate([
            'goal_name'       => 'required|string|max:255',
            'target_amount'   => 'required|numeric|min:1',
            'current_savings' => 'nullable|numeric|min:0',
            'deadline'        => 'required|date',
            'trip_id'         => 'nullable|exists:trips,id',
        ]);
        $goal->update([...$validated, 'current_savings' => $validated['current_savings'] ?? $goal->current_savings]);
        return redirect()->route('savings.index')->with('success', 'Goal updated.');
    }

    public function destroy(SavingsGoal $goal)
    {
        abort_if($goal->user_id !== auth()->id(), 403);
        $goal->delete();
        return redirect()->route('savings.index')->with('success', 'Goal deleted.');
    }

    public function deposit(Request $request, SavingsGoal $goal)
    {
        abort_if($goal->user_id !== auth()->id(), 403);
        $request->validate(['amount' => 'required|numeric|min:0.01']);
        $goal->increment('current_savings', $request->amount);
        return back()->with('success', 'Deposit added!');
    }
}
```

- [ ] **Step 5: Create savings goal views**

`resources/views/traveler/savings/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Savings Goals')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;">
    <h1>Savings Goals</h1>
    <a href="{{ route('savings.create') }}" class="btn btn-primary">+ New Goal</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@forelse($goals as $goal)
    @php
        $pct = $goal->target_amount > 0 ? min(100, ($goal->current_savings / $goal->target_amount) * 100) : 0;
        $daysLeft = now()->diffInDays($goal->deadline, false);
    @endphp
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body">
            <div style="display:flex;justify-content:space-between;">
                <div>
                    <h3>{{ $goal->goal_name }}</h3>
                    @if($goal->trip)<small>Linked to: {{ $goal->trip->destination }}</small>@endif
                    <p style="margin:.5rem 0;">
                        ₱{{ number_format($goal->current_savings, 2) }} / ₱{{ number_format($goal->target_amount, 2) }}
                        <span style="color:#999;margin-left:8px;">Deadline: {{ $goal->deadline }} ({{ $daysLeft > 0 ? $daysLeft.' days left' : 'past deadline' }})</span>
                    </p>
                    <div style="height:10px;background:#eee;border-radius:5px;width:300px;">
                        <div style="height:100%;width:{{ $pct }}%;background:{{ $pct >= 100 ? '#2ecc71' : '#3498db' }};border-radius:5px;"></div>
                    </div>
                    <small>{{ round($pct) }}% of goal</small>
                </div>
                <div style="display:flex;flex-direction:column;gap:4px;">
                    <form method="POST" action="{{ route('savings.deposit', $goal) }}" style="display:flex;gap:4px;">
                        @csrf @method('PATCH')
                        <input type="number" name="amount" step="0.01" min="0.01" placeholder="₱ amount" class="form-control" style="width:120px;" required>
                        <button class="btn btn-sm btn-success">+ Add</button>
                    </form>
                    <div style="display:flex;gap:4px;margin-top:4px;">
                        <a href="{{ route('savings.edit', $goal) }}" class="btn btn-sm btn-secondary">Edit</a>
                        <form method="POST" action="{{ route('savings.destroy', $goal) }}" onsubmit="return confirm('Delete goal?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body" style="text-align:center;padding:3rem;">
            <p>No savings goals yet. Start saving for your next trip!</p>
            <a href="{{ route('savings.create') }}" class="btn btn-primary">Create First Goal</a>
        </div>
    </div>
@endforelse
@endsection
```

`resources/views/traveler/savings/create.blade.php`:
```html
@extends('layouts.app')
@section('title', 'New Savings Goal')
@section('content')
<a href="{{ route('savings.index') }}">&larr; Back</a>
<h1>New Savings Goal</h1>
<div class="card" style="max-width:500px;">
    <div class="card-body">
        <form method="POST" action="{{ route('savings.store') }}">
            @csrf
            <div class="form-group">
                <label>Goal Name</label>
                <input type="text" name="goal_name" value="{{ old('goal_name') }}" class="form-control @error('goal_name') is-invalid @enderror" required>
                @error('goal_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Target Amount (₱)</label>
                <input type="number" step="0.01" name="target_amount" value="{{ old('target_amount') }}" class="form-control @error('target_amount') is-invalid @enderror" required>
                @error('target_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Current Savings (₱)</label>
                <input type="number" step="0.01" name="current_savings" value="{{ old('current_savings', 0) }}" class="form-control" min="0">
            </div>
            <div class="form-group">
                <label>Deadline</label>
                <input type="date" name="deadline" value="{{ old('deadline') }}" class="form-control @error('deadline') is-invalid @enderror" required>
                @error('deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Link to Trip (optional)</label>
                <select name="trip_id" class="form-control">
                    <option value="">— No trip —</option>
                    @foreach($trips as $trip)
                        <option value="{{ $trip->id }}" {{ old('trip_id') == $trip->id ? 'selected' : '' }}>{{ $trip->destination }} ({{ $trip->start_date }})</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary">Create Goal</button>
        </form>
    </div>
</div>
@endsection
```

`resources/views/traveler/savings/edit.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Edit Savings Goal')
@section('content')
<a href="{{ route('savings.index') }}">&larr; Back</a>
<h1>Edit: {{ $goal->goal_name }}</h1>
<div class="card" style="max-width:500px;">
    <div class="card-body">
        <form method="POST" action="{{ route('savings.update', $goal) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Goal Name</label>
                <input type="text" name="goal_name" value="{{ old('goal_name', $goal->goal_name) }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Target Amount (₱)</label>
                <input type="number" step="0.01" name="target_amount" value="{{ old('target_amount', $goal->target_amount) }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Current Savings (₱)</label>
                <input type="number" step="0.01" name="current_savings" value="{{ old('current_savings', $goal->current_savings) }}" class="form-control" min="0">
            </div>
            <div class="form-group">
                <label>Deadline</label>
                <input type="date" name="deadline" value="{{ old('deadline', $goal->deadline) }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Link to Trip (optional)</label>
                <select name="trip_id" class="form-control">
                    <option value="">— No trip —</option>
                    @foreach($trips as $trip)
                        <option value="{{ $trip->id }}" {{ old('trip_id', $goal->trip_id) == $trip->id ? 'selected' : '' }}>{{ $trip->destination }} ({{ $trip->start_date }})</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary">Update</button>
        </form>
    </div>
</div>
@endsection
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/SavingsGoal/SavingsGoalTest.php
```

---

### Task 2: Database Backup & Restore

**Files:**
- Create: `app/Http/Controllers/Admin/BackupController.php`
- Create: `resources/views/admin/backup/index.blade.php`
- Modify: `routes/web.php` — add backup routes to admin group
- Test: `tests/Feature/Admin/AdminBackupTest.php`

**Interfaces:**
- Consumes: `admin` middleware, Laravel `Process` facade (Illuminate\Support\Facades\Process), MySQL config
- Produces: `GET /admin/backup` shows backup page; `POST /admin/backup/download` streams mysqldump as `.sql` download; `POST /admin/backup/restore` accepts `.sql` upload and pipes to `mysql`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Admin/AdminBackupTest.php
namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class AdminBackupTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User { return User::factory()->admin()->create(); }

    public function test_backup_page_loads(): void
    {
        $this->actingAs($this->admin())->get('/admin/backup')->assertStatus(200);
    }

    public function test_non_admin_cannot_access_backup(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin/backup')->assertStatus(403);
    }

    public function test_backup_download_triggers_process(): void
    {
        Process::fake([
            'mysqldump*' => Process::result('-- MySQL dump', '', 0),
        ]);

        $admin = $this->admin();
        $response = $this->actingAs($admin)->post('/admin/backup/download');
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_restore_rejects_non_sql_file(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/backup/restore', [
            'sql_file' => UploadedFile::fake()->create('backup.txt', 100),
        ])->assertSessionHasErrors('sql_file');
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Admin/AdminBackupTest.php
```

- [ ] **Step 3: Add backup routes to admin group in routes/web.php**

```php
Route::get('/backup',           [Admin\BackupController::class, 'index'])->name('backup.index');
Route::post('/backup/download', [Admin\BackupController::class, 'download'])->name('backup.download');
Route::post('/backup/restore',  [Admin\BackupController::class, 'restore'])->name('backup.restore');
```

- [ ] **Step 4: Implement AdminBackupController**

```php
<?php
// app/Http/Controllers/Admin/BackupController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class BackupController extends Controller
{
    public function index()
    {
        return view('admin.backup.index');
    }

    public function download()
    {
        $db       = config('database.connections.mysql');
        $host     = $db['host'];
        $port     = $db['port'] ?? 3306;
        $database = $db['database'];
        $username = $db['username'];
        $password = $db['password'];

        $filename  = 'budgetra-backup-' . now()->format('Y-m-d-His') . '.sql';
        $dumpCmd   = "mysqldump --host={$host} --port={$port} --user={$username} --password={$password} {$database}";

        $result = Process::run($dumpCmd);

        if ($result->failed()) {
            return back()->with('error', 'Backup failed: ' . $result->errorOutput());
        }

        return response($result->output(), 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function restore(Request $request)
    {
        $request->validate([
            'sql_file' => [
                'required',
                'file',
                function ($attribute, $value, $fail) {
                    if ($value->getClientOriginalExtension() !== 'sql') {
                        $fail('Only .sql files are accepted.');
                    }
                },
            ],
        ]);

        $db       = config('database.connections.mysql');
        $host     = $db['host'];
        $port     = $db['port'] ?? 3306;
        $database = $db['database'];
        $username = $db['username'];
        $password = $db['password'];

        $sqlPath  = $request->file('sql_file')->getPathname();
        $restoreCmd = "mysql --host={$host} --port={$port} --user={$username} --password={$password} {$database} < {$sqlPath}";

        $result = Process::run($restoreCmd);

        if ($result->failed()) {
            return back()->with('error', 'Restore failed: ' . $result->errorOutput());
        }

        return back()->with('success', 'Database restored successfully.');
    }
}
```

- [ ] **Step 5: Create backup view**

`resources/views/admin/backup/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Database Backup')
@section('content')
<h1>Database Backup & Restore</h1>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
    <div class="card">
        <div class="card-body">
            <h3>Download Backup</h3>
            <p>Creates an on-demand SQL dump of the entire database and downloads it as a <code>.sql</code> file.</p>
            <form method="POST" action="{{ route('admin.backup.download') }}">
                @csrf
                <button class="btn btn-primary">Download Backup (.sql)</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3>Restore from Backup</h3>
            <p style="color:#e74c3c;"><strong>Warning:</strong> This will overwrite existing data. Only restore from a trusted backup file.</p>
            <form method="POST" action="{{ route('admin.backup.restore') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>Upload .sql file</label>
                    <input type="file" name="sql_file" class="form-control @error('sql_file') is-invalid @enderror" accept=".sql" required>
                    @error('sql_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-danger" onclick="return confirm('Are you sure? This will overwrite the current database.')">Restore Database</button>
            </form>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Admin/AdminBackupTest.php
```

- [ ] **Step 7: Run full P5 test suite**

```
php artisan test tests/Feature/SavingsGoal/ tests/Feature/Admin/AdminBackupTest.php
```

Expected: all PASS.
