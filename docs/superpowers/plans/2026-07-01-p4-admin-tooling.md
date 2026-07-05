# Admin Tooling Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the full admin panel — user management, destination/attraction CRUD, review moderation, Klook API configuration, OCR log monitor, and system reports.

**Architecture:** All admin routes are protected by the `admin` middleware (checks `role === 'admin'`). Admin controllers live in `App\Http\Controllers\Admin\`. Views use the same shared `layouts/app.blade.php`. The admin navigation renders the admin sidebar only when the authenticated user is an admin (checked via `auth()->user()->role === 'admin'`).

**Tech Stack:** Laravel 13.x, Blade, PHP 8.3+, MySQL.

## Global Constraints

- Project root: `c:\phpsite\Capstone - Budgetra`
- Laravel 13.18.0, PHP 8.3.12
- Requires Plans 1–3 complete
- Admin middleware: `app/Http/Middleware/AdminMiddleware.php` — aborts 403 if `auth()->user()?->role !== 'admin'`
- `destination_costs` columns: `destination`, `base_daily_cost`, `multiplier`, `country`, `notes`
- `attractions` columns: `destination`, `name`, `description`, `image_path`, `category`, `price_estimate`
- `reviews.status`: enum `active|hidden` (NOT pending/approved/rejected)
- `app_config`: `config_key` (unique), `config_value` — Klook key stored as `klook_api_key`
- `ocr_logs`: `user_id` (nullable), `filename`, `status` (success|failed|partial), `confidence`, `error_message`
- Skip git commit steps

---

### Task 1: Admin User Management

**Files:**
- Create: `app/Http/Controllers/Admin/UserController.php`
- Create: `resources/views/admin/users/index.blade.php`
- Create: `resources/views/admin/users/show.blade.php`
- Modify: `routes/web.php` — add admin user management routes
- Test: `tests/Feature/Admin/AdminUserManagementTest.php`

**Interfaces:**
- Consumes: `User` model, `admin` middleware
- Produces: `GET /admin/users` lists users; `GET /admin/users/{user}` shows detail; `PATCH /admin/users/{user}/ban` toggles ban (sets role to banned or back to traveler); `DELETE /admin/users/{user}` deletes account

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Admin/AdminUserManagementTest.php
namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_view_user_list(): void
    {
        $admin = $this->admin();
        User::factory()->count(3)->create();
        $this->actingAs($admin)->get('/admin/users')->assertStatus(200)->assertSee('Users');
    }

    public function test_traveler_cannot_access_user_list(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin/users')->assertStatus(403);
    }

    public function test_admin_can_ban_user(): void
    {
        $admin = $this->admin();
        $user  = User::factory()->create(['role' => 'traveler']);

        $this->actingAs($admin)->patch("/admin/users/{$user->id}/ban")->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'banned']);
    }

    public function test_admin_can_unban_user(): void
    {
        $admin = $this->admin();
        $user  = User::factory()->create(['role' => 'banned']);

        $this->actingAs($admin)->patch("/admin/users/{$user->id}/ban")->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'traveler']);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = $this->admin();
        $user  = User::factory()->create();

        $this->actingAs($admin)->delete("/admin/users/{$user->id}")->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Admin/AdminUserManagementTest.php
```

- [ ] **Step 3: Add user management routes inside admin middleware group in routes/web.php**

```php
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users',                           [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}',                    [Admin\UserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/ban',              [Admin\UserController::class, 'ban'])->name('users.ban');
    Route::delete('/users/{user}',                 [Admin\UserController::class, 'destroy'])->name('users.destroy');
});
```

- [ ] **Step 4: Implement AdminUserController**

```php
<?php
// app/Http/Controllers/Admin/UserController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('full_name', 'like', "%$s%")->orWhere('email', 'like', "%$s%"));
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        $users = $query->latest()->paginate(25)->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['trips', 'reviews', 'expenses']);
        return view('admin.users.show', compact('user'));
    }

    public function ban(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Cannot ban yourself.');
        $user->update(['role' => $user->role === 'banned' ? 'traveler' : 'banned']);
        $action = $user->role === 'banned' ? 'banned' : 'unbanned';
        return back()->with('success', "User {$action}.");
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Cannot delete yourself.');
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
```

- [ ] **Step 5: Create admin user views**

`resources/views/admin/users/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Manage Users')
@section('content')
<h1>Manage Users</h1>
<form method="GET" style="display:flex;gap:8px;margin-bottom:1rem;">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..." class="form-control" style="width:250px;">
    <select name="role" class="form-control" style="width:auto;">
        <option value="">All roles</option>
        <option value="traveler" {{ request('role') === 'traveler' ? 'selected' : '' }}>Traveler</option>
        <option value="admin"    {{ request('role') === 'admin'    ? 'selected' : '' }}>Admin</option>
        <option value="banned"   {{ request('role') === 'banned'   ? 'selected' : '' }}>Banned</option>
    </select>
    <button class="btn btn-secondary">Filter</button>
</form>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<table class="table">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Registered</th><th>Actions</th></tr></thead>
    <tbody>
    @foreach($users as $user)
        <tr>
            <td><a href="{{ route('admin.users.show', $user) }}">{{ $user->full_name }}</a></td>
            <td>{{ $user->email }}</td>
            <td><span class="badge badge-{{ $user->role === 'admin' ? 'primary' : ($user->role === 'banned' ? 'danger' : 'secondary') }}">{{ $user->role }}</span></td>
            <td>{{ $user->created_at->format('M j, Y') }}</td>
            <td style="display:flex;gap:4px;">
                @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.ban', $user) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm {{ $user->role === 'banned' ? 'btn-success' : 'btn-warning' }}">
                            {{ $user->role === 'banned' ? 'Unban' : 'Ban' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $users->links() }}
@endsection
```

`resources/views/admin/users/show.blade.php`:
```html
@extends('layouts.app')
@section('title', 'User Detail')
@section('content')
<a href="{{ route('admin.users.index') }}">&larr; Back</a>
<h1>{{ $user->full_name }}</h1>
<p>{{ $user->email }} &bull; {{ $user->phone ?? '—' }} &bull; {{ $user->country ?? '—' }} &bull; Role: <strong>{{ $user->role }}</strong></p>
<h3>Trips ({{ $user->trips->count() }})</h3>
@forelse($user->trips as $trip)
    <p>{{ $trip->destination }} ({{ $trip->start_date }} – {{ $trip->end_date }})</p>
@empty <p>None.</p>
@endforelse
<h3>Reviews ({{ $user->reviews->count() }})</h3>
@forelse($user->reviews as $review)
    <p>{{ $review->destination }} — {{ $review->rating }}★ — <em>{{ $review->status }}</em></p>
@empty <p>None.</p>
@endforelse
@endsection
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Admin/AdminUserManagementTest.php
```

---

### Task 2: Destination & Attraction CRUD

**Files:**
- Create: `app/Http/Controllers/Admin/DestinationController.php`
- Create: `app/Http/Controllers/Admin/AttractionController.php`
- Create: `resources/views/admin/destinations/` (index, create, edit)
- Create: `resources/views/admin/attractions/` (index, create, edit)
- Modify: `routes/web.php` — add destination/attraction routes to admin group
- Test: `tests/Feature/Admin/AdminDestinationTest.php`

**Interfaces:**
- Consumes: `DestinationCost`, `Attraction` models, `admin` middleware
- Produces: Full CRUD for both models; attraction images stored to `storage/app/public/attraction-images/`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Admin/AdminDestinationTest.php
namespace Tests\Feature\Admin;

use App\Models\Attraction;
use App\Models\DestinationCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDestinationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User { return User::factory()->admin()->create(); }

    public function test_admin_can_view_destinations(): void
    {
        $admin = $this->admin();
        DestinationCost::create(['destination' => 'Palawan', 'base_daily_cost' => 2000, 'multiplier' => 1.0, 'country' => 'Philippines']);
        $this->actingAs($admin)->get('/admin/destinations')->assertStatus(200)->assertSee('Palawan');
    }

    public function test_admin_can_create_destination(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/destinations', [
            'destination'    => 'Siargao',
            'base_daily_cost'=> 1800,
            'multiplier'     => 1.1,
            'country'        => 'Philippines',
        ])->assertRedirect(route('admin.destinations.index'));
        $this->assertDatabaseHas('destination_costs', ['destination' => 'Siargao']);
    }

    public function test_admin_can_delete_destination(): void
    {
        $admin = $this->admin();
        $dest  = DestinationCost::create(['destination' => 'Test', 'base_daily_cost' => 500, 'multiplier' => 1.0, 'country' => 'PH']);
        $this->actingAs($admin)->delete("/admin/destinations/{$dest->id}")->assertRedirect();
        $this->assertDatabaseMissing('destination_costs', ['id' => $dest->id]);
    }

    public function test_admin_can_create_attraction_with_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/attractions', [
            'destination'    => 'Bohol',
            'name'           => 'Chocolate Hills',
            'description'    => 'Iconic hills.',
            'category'       => 'Nature',
            'price_estimate' => 200,
            'image'          => UploadedFile::fake()->image('hills.jpg'),
        ])->assertRedirect(route('admin.attractions.index'));

        $this->assertDatabaseHas('attractions', ['name' => 'Chocolate Hills']);
        Storage::disk('public')->assertExists('attraction-images/Chocolate_Hills.jpg');
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Admin/AdminDestinationTest.php
```

- [ ] **Step 3: Add routes to admin group in routes/web.php**

```php
Route::resource('destinations', Admin\DestinationController::class)->except(['show']);
Route::resource('attractions',  Admin\AttractionController::class)->except(['show']);
```

(inside the existing `Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')` group)

- [ ] **Step 4: Implement AdminDestinationController**

```php
<?php
// app/Http/Controllers/Admin/DestinationController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DestinationCost;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function index()
    {
        $destinations = DestinationCost::orderBy('destination')->paginate(25);
        return view('admin.destinations.index', compact('destinations'));
    }

    public function create()
    {
        return view('admin.destinations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'destination'     => 'required|string|max:255|unique:destination_costs',
            'base_daily_cost' => 'required|numeric|min:0',
            'multiplier'      => 'required|numeric|min:0.1|max:10',
            'country'         => 'required|string|max:255',
            'notes'           => 'nullable|string|max:1000',
        ]);
        DestinationCost::create($validated);
        return redirect()->route('admin.destinations.index')->with('success', 'Destination added.');
    }

    public function edit(DestinationCost $destination)
    {
        return view('admin.destinations.edit', compact('destination'));
    }

    public function update(Request $request, DestinationCost $destination)
    {
        $validated = $request->validate([
            'destination'     => "required|string|max:255|unique:destination_costs,destination,{$destination->id}",
            'base_daily_cost' => 'required|numeric|min:0',
            'multiplier'      => 'required|numeric|min:0.1|max:10',
            'country'         => 'required|string|max:255',
            'notes'           => 'nullable|string|max:1000',
        ]);
        $destination->update($validated);
        return redirect()->route('admin.destinations.index')->with('success', 'Destination updated.');
    }

    public function destroy(DestinationCost $destination)
    {
        $destination->delete();
        return redirect()->route('admin.destinations.index')->with('success', 'Destination deleted.');
    }
}
```

- [ ] **Step 5: Implement AdminAttractionController**

```php
<?php
// app/Http/Controllers/Admin/AttractionController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attraction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttractionController extends Controller
{
    public function index(Request $request)
    {
        $query = Attraction::query();
        if ($request->filled('destination')) {
            $query->where('destination', $request->destination);
        }
        $attractions = $query->orderBy('destination')->orderBy('name')->paginate(25)->withQueryString();
        return view('admin.attractions.index', compact('attractions'));
    }

    public function create()
    {
        return view('admin.attractions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'destination'    => 'required|string|max:255',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:2000',
            'category'       => 'nullable|string|max:100',
            'price_estimate' => 'nullable|numeric|min:0',
            'image'          => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $filename = Str::replace(' ', '_', $validated['name']) . '.' . $request->file('image')->getClientOriginalExtension();
            $request->file('image')->storeAs('attraction-images', $filename, 'public');
            $validated['image_path'] = 'attraction-images/' . $filename;
        }
        unset($validated['image']);

        Attraction::create($validated);
        return redirect()->route('admin.attractions.index')->with('success', 'Attraction added.');
    }

    public function edit(Attraction $attraction)
    {
        return view('admin.attractions.edit', compact('attraction'));
    }

    public function update(Request $request, Attraction $attraction)
    {
        $validated = $request->validate([
            'destination'    => 'required|string|max:255',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:2000',
            'category'       => 'nullable|string|max:100',
            'price_estimate' => 'nullable|numeric|min:0',
            'image'          => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $filename = Str::replace(' ', '_', $validated['name']) . '.' . $request->file('image')->getClientOriginalExtension();
            $request->file('image')->storeAs('attraction-images', $filename, 'public');
            $validated['image_path'] = 'attraction-images/' . $filename;
        }
        unset($validated['image']);

        $attraction->update($validated);
        return redirect()->route('admin.attractions.index')->with('success', 'Attraction updated.');
    }

    public function destroy(Attraction $attraction)
    {
        $attraction->delete();
        return redirect()->route('admin.attractions.index')->with('success', 'Attraction deleted.');
    }
}
```

- [ ] **Step 6: Create stub views for destinations and attractions**

`resources/views/admin/destinations/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Destinations')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;">
    <h1>Destinations</h1>
    <a href="{{ route('admin.destinations.create') }}" class="btn btn-primary">+ Add Destination</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<table class="table">
    <thead><tr><th>Destination</th><th>Country</th><th>Base Daily Cost</th><th>Multiplier</th><th>Actions</th></tr></thead>
    <tbody>
    @foreach($destinations as $dest)
        <tr>
            <td>{{ $dest->destination }}</td>
            <td>{{ $dest->country }}</td>
            <td>₱{{ number_format($dest->base_daily_cost, 2) }}</td>
            <td>{{ $dest->multiplier }}×</td>
            <td>
                <a href="{{ route('admin.destinations.edit', $dest) }}" class="btn btn-sm btn-secondary">Edit</a>
                <form method="POST" action="{{ route('admin.destinations.destroy', $dest) }}" style="display:inline;" onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $destinations->links() }}
@endsection
```

`resources/views/admin/destinations/create.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Add Destination')
@section('content')
<a href="{{ route('admin.destinations.index') }}">&larr; Back</a>
<h1>Add Destination</h1>
<form method="POST" action="{{ route('admin.destinations.store') }}">
    @csrf
    @foreach(['destination' => 'Destination Name', 'country' => 'Country', 'base_daily_cost' => 'Base Daily Cost (₱)', 'multiplier' => 'Multiplier'] as $field => $label)
        <div class="form-group">
            <label>{{ $label }}</label>
            <input type="{{ in_array($field, ['base_daily_cost','multiplier']) ? 'number' : 'text' }}" step="{{ in_array($field, ['base_daily_cost','multiplier']) ? '0.01' : '' }}" name="{{ $field }}" value="{{ old($field) }}" class="form-control @error($field) is-invalid @enderror" required>
            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endforeach
    <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control">{{ old('notes') }}</textarea></div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection
```

`resources/views/admin/destinations/edit.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Edit Destination')
@section('content')
<a href="{{ route('admin.destinations.index') }}">&larr; Back</a>
<h1>Edit: {{ $destination->destination }}</h1>
<form method="POST" action="{{ route('admin.destinations.update', $destination) }}">
    @csrf @method('PUT')
    @foreach(['destination' => 'Destination Name', 'country' => 'Country', 'base_daily_cost' => 'Base Daily Cost (₱)', 'multiplier' => 'Multiplier'] as $field => $label)
        <div class="form-group">
            <label>{{ $label }}</label>
            <input type="{{ in_array($field, ['base_daily_cost','multiplier']) ? 'number' : 'text' }}" step="{{ in_array($field, ['base_daily_cost','multiplier']) ? '0.01' : '' }}" name="{{ $field }}" value="{{ old($field, $destination->$field) }}" class="form-control" required>
            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endforeach
    <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control">{{ old('notes', $destination->notes) }}</textarea></div>
    <button class="btn btn-primary">Update</button>
</form>
@endsection
```

`resources/views/admin/attractions/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Attractions')
@section('content')
<div style="display:flex;justify-content:space-between;">
    <h1>Attractions</h1>
    <a href="{{ route('admin.attractions.create') }}" class="btn btn-primary">+ Add Attraction</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<table class="table">
    <thead><tr><th>Destination</th><th>Name</th><th>Category</th><th>Est. Price</th><th>Actions</th></tr></thead>
    <tbody>
    @foreach($attractions as $attr)
        <tr>
            <td>{{ $attr->destination }}</td>
            <td>{{ $attr->name }}</td>
            <td>{{ $attr->category ?? '—' }}</td>
            <td>{{ $attr->price_estimate ? '₱'.number_format($attr->price_estimate, 0) : '—' }}</td>
            <td>
                <a href="{{ route('admin.attractions.edit', $attr) }}" class="btn btn-sm btn-secondary">Edit</a>
                <form method="POST" action="{{ route('admin.attractions.destroy', $attr) }}" style="display:inline;" onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $attractions->links() }}
@endsection
```

`resources/views/admin/attractions/create.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Add Attraction')
@section('content')
<a href="{{ route('admin.attractions.index') }}">&larr; Back</a>
<h1>Add Attraction</h1>
<form method="POST" action="{{ route('admin.attractions.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="form-group"><label>Destination</label><input type="text" name="destination" class="form-control" required></div>
    <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required></div>
    <div class="form-group"><label>Category</label><input type="text" name="category" class="form-control"></div>
    <div class="form-group"><label>Price Estimate (₱)</label><input type="number" step="0.01" name="price_estimate" class="form-control"></div>
    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
    <div class="form-group"><label>Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection
```

`resources/views/admin/attractions/edit.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Edit Attraction')
@section('content')
<a href="{{ route('admin.attractions.index') }}">&larr; Back</a>
<h1>Edit: {{ $attraction->name }}</h1>
<form method="POST" action="{{ route('admin.attractions.update', $attraction) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="form-group"><label>Destination</label><input type="text" name="destination" value="{{ old('destination', $attraction->destination) }}" class="form-control" required></div>
    <div class="form-group"><label>Name</label><input type="text" name="name" value="{{ old('name', $attraction->name) }}" class="form-control" required></div>
    <div class="form-group"><label>Category</label><input type="text" name="category" value="{{ old('category', $attraction->category) }}" class="form-control"></div>
    <div class="form-group"><label>Price Estimate</label><input type="number" step="0.01" name="price_estimate" value="{{ old('price_estimate', $attraction->price_estimate) }}" class="form-control"></div>
    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3">{{ old('description', $attraction->description) }}</textarea></div>
    <div class="form-group">
        <label>Image (leave blank to keep existing)</label>
        @if($attraction->image_path)
            <img src="{{ asset('storage/'.$attraction->image_path) }}" style="height:80px;display:block;margin-bottom:8px;">
        @endif
        <input type="file" name="image" class="form-control" accept="image/*">
    </div>
    <button class="btn btn-primary">Update</button>
</form>
@endsection
```

- [ ] **Step 7: Run tests — expect PASS**

```
php artisan test tests/Feature/Admin/AdminDestinationTest.php
```

---

### Task 3: Review Moderation

**Files:**
- Create: `app/Http/Controllers/Admin/ReviewModerationController.php`
- Create: `resources/views/admin/reviews/index.blade.php`
- Modify: `routes/web.php` — add review moderation routes to admin group
- Test: `tests/Feature/Admin/AdminReviewModerationTest.php`

**Interfaces:**
- Consumes: `Review` model (status: active|hidden), `admin` middleware
- Produces: `GET /admin/reviews` lists all reviews; `PATCH /admin/reviews/{review}/hide` sets status=hidden; `PATCH /admin/reviews/{review}/show` sets status=active

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Admin/AdminReviewModerationTest.php
namespace Tests\Feature\Admin;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewModerationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User { return User::factory()->admin()->create(); }

    public function test_admin_can_view_all_reviews(): void
    {
        $admin = $this->admin();
        $user  = User::factory()->create();
        Review::create(['user_id' => $user->id, 'destination' => 'Bohol', 'rating' => 5, 'body' => 'Great!', 'status' => 'active']);
        $this->actingAs($admin)->get('/admin/reviews')->assertStatus(200)->assertSee('Bohol');
    }

    public function test_admin_can_hide_review(): void
    {
        $admin  = $this->admin();
        $user   = User::factory()->create();
        $review = Review::create(['user_id' => $user->id, 'destination' => 'Bohol', 'rating' => 5, 'body' => 'Nice', 'status' => 'active']);

        $this->actingAs($admin)->patch("/admin/reviews/{$review->id}/hide")->assertRedirect();
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'hidden']);
    }

    public function test_admin_can_show_review(): void
    {
        $admin  = $this->admin();
        $user   = User::factory()->create();
        $review = Review::create(['user_id' => $user->id, 'destination' => 'Bohol', 'rating' => 5, 'body' => 'Bad', 'status' => 'hidden']);

        $this->actingAs($admin)->patch("/admin/reviews/{$review->id}/show")->assertRedirect();
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'active']);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Admin/AdminReviewModerationTest.php
```

- [ ] **Step 3: Add moderation routes to admin group**

```php
Route::get('/reviews',                         [Admin\ReviewModerationController::class, 'index'])->name('reviews.index');
Route::patch('/reviews/{review}/hide',         [Admin\ReviewModerationController::class, 'hide'])->name('reviews.hide');
Route::patch('/reviews/{review}/show',         [Admin\ReviewModerationController::class, 'show'])->name('reviews.show');
```

- [ ] **Step 4: Implement AdminReviewModerationController**

```php
<?php
// app/Http/Controllers/Admin/ReviewModerationController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewModerationController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with('user')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('destination')) {
            $query->where('destination', $request->destination);
        }
        $reviews = $query->paginate(25)->withQueryString();
        return view('admin.reviews.index', compact('reviews'));
    }

    public function hide(Review $review)
    {
        $review->update(['status' => 'hidden']);
        return back()->with('success', 'Review hidden.');
    }

    public function show(Review $review)
    {
        $review->update(['status' => 'active']);
        return back()->with('success', 'Review is now active.');
    }
}
```

- [ ] **Step 5: Create review moderation view**

`resources/views/admin/reviews/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'Review Moderation')
@section('content')
<h1>Review Moderation</h1>
<form method="GET" style="display:flex;gap:8px;margin-bottom:1rem;">
    <select name="status" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">All</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
        <option value="hidden" {{ request('status') === 'hidden' ? 'selected' : '' }}>Hidden</option>
    </select>
</form>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@forelse($reviews as $review)
    <div class="card" style="margin-bottom:.75rem;{{ $review->status === 'hidden' ? 'opacity:.6;' : '' }}">
        <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <strong>{{ $review->destination }}</strong>
                    <span style="color:#f5a623;">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5-$review->rating) }}</span>
                    <span class="badge badge-{{ $review->status === 'active' ? 'success' : 'danger' }}" style="margin-left:8px;">{{ $review->status }}</span>
                    <p style="margin:.5rem 0;">{{ $review->body }}</p>
                    <small>{{ $review->user->full_name }} &bull; {{ $review->created_at->format('M j, Y') }}</small>
                </div>
                <div style="display:flex;gap:4px;">
                    @if($review->status === 'active')
                        <form method="POST" action="{{ route('admin.reviews.hide', $review) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-warning">Hide</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.reviews.show', $review) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-success">Show</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@empty
    <p>No reviews found.</p>
@endforelse
{{ $reviews->links() }}
@endsection
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Admin/AdminReviewModerationTest.php
```

---

### Task 4: Klook API Config & OCR Monitor

**Files:**
- Create: `app/Http/Controllers/Admin/ConfigController.php`
- Create: `resources/views/admin/config/index.blade.php`
- Create: `resources/views/admin/ocr/index.blade.php`
- Modify: `routes/web.php` — add config/OCR routes to admin group
- Test: `tests/Feature/Admin/AdminConfigTest.php`

**Interfaces:**
- Consumes: `app_config` table (key=klook_api_key), `ocr_logs` table, `admin` middleware
- Produces: `GET /admin/config` shows config form; `POST /admin/config` saves config; `GET /admin/ocr-logs` shows OCR usage

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Admin/AdminConfigTest.php
namespace Tests\Feature\Admin;

use App\Models\AppConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminConfigTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User { return User::factory()->admin()->create(); }

    public function test_config_page_loads(): void
    {
        $this->actingAs($this->admin())->get('/admin/config')->assertStatus(200);
    }

    public function test_admin_can_save_klook_api_key(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/config', [
            'klook_api_key' => 'test-klook-key-123',
        ])->assertRedirect(route('admin.config.index'));

        $this->assertDatabaseHas('app_config', [
            'config_key'   => 'klook_api_key',
            'config_value' => 'test-klook-key-123',
        ]);
    }

    public function test_ocr_logs_page_loads(): void
    {
        $this->actingAs($this->admin())->get('/admin/ocr-logs')->assertStatus(200);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Admin/AdminConfigTest.php
```

- [ ] **Step 3: Add routes to admin group**

```php
Route::get('/config',          [Admin\ConfigController::class, 'index'])->name('config.index');
Route::post('/config',         [Admin\ConfigController::class, 'store'])->name('config.store');
Route::get('/ocr-logs',        [Admin\ConfigController::class, 'ocrLogs'])->name('ocr.index');
```

- [ ] **Step 4: Implement AdminConfigController**

```php
<?php
// app/Http/Controllers/Admin/ConfigController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppConfig;
use App\Models\OcrLog;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function index()
    {
        $klookKey = AppConfig::where('config_key', 'klook_api_key')->value('config_value') ?? '';
        return view('admin.config.index', compact('klookKey'));
    }

    public function store(Request $request)
    {
        $request->validate(['klook_api_key' => 'nullable|string|max:500']);

        AppConfig::updateOrCreate(
            ['config_key' => 'klook_api_key'],
            ['config_value' => $request->klook_api_key ?? '']
        );

        return redirect()->route('admin.config.index')->with('success', 'Klook API key saved.');
    }

    public function ocrLogs(Request $request)
    {
        $query = OcrLog::with('user')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $logs = $query->paginate(30)->withQueryString();
        return view('admin.ocr.index', compact('logs'));
    }
}
```

- [ ] **Step 5: Create views**

`resources/views/admin/config/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'System Config')
@section('content')
<h1>System Configuration</h1>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card" style="max-width:500px;">
    <div class="card-body">
        <h3>Klook API</h3>
        <form method="POST" action="{{ route('admin.config.store') }}">
            @csrf
            <div class="form-group">
                <label>Klook API Key</label>
                <input type="text" name="klook_api_key" value="{{ old('klook_api_key', $klookKey) }}" class="form-control" placeholder="Leave empty to use mock data">
            </div>
            <button class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
@endsection
```

`resources/views/admin/ocr/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'OCR Logs')
@section('content')
<h1>OCR Monitor</h1>
<form method="GET" style="margin-bottom:1rem;">
    <select name="status" class="form-control" style="width:auto;display:inline-block;" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="success"  {{ request('status') === 'success'  ? 'selected' : '' }}>Success</option>
        <option value="failed"   {{ request('status') === 'failed'   ? 'selected' : '' }}>Failed</option>
        <option value="partial"  {{ request('status') === 'partial'  ? 'selected' : '' }}>Partial</option>
    </select>
</form>
<table class="table">
    <thead><tr><th>User</th><th>File</th><th>Status</th><th>Confidence</th><th>Error</th><th>Date</th></tr></thead>
    <tbody>
    @forelse($logs as $log)
        <tr>
            <td>{{ $log->user?->full_name ?? '—' }}</td>
            <td>{{ $log->filename }}</td>
            <td><span class="badge badge-{{ $log->status === 'success' ? 'success' : ($log->status === 'failed' ? 'danger' : 'warning') }}">{{ $log->status }}</span></td>
            <td>{{ $log->confidence !== null ? round($log->confidence * 100).'%' : '—' }}</td>
            <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $log->error_message ?? '—' }}</td>
            <td>{{ $log->created_at->format('M j, Y H:i') }}</td>
        </tr>
    @empty
        <tr><td colspan="6">No OCR logs yet.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $logs->links() }}
@endsection
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Admin/AdminConfigTest.php
```

---

### Task 5: Admin System Reports

**Files:**
- Create: `app/Http/Controllers/Admin/ReportController.php`
- Create: `resources/views/admin/reports/index.blade.php`
- Modify: `routes/web.php` — add admin reports route to admin group
- Test: `tests/Feature/Admin/AdminReportTest.php`

**Interfaces:**
- Consumes: `User`, `Trip`, `Expense`, `OcrLog` models
- Produces: `GET /admin/reports` shows aggregate stats; `GET /admin/reports/download` PDF of system summary

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Admin/AdminReportTest.php
namespace Tests\Feature\Admin;

use App\Models\Expense;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User { return User::factory()->admin()->create(); }

    public function test_admin_reports_page_loads(): void
    {
        $this->actingAs($this->admin())->get('/admin/reports')->assertStatus(200);
    }

    public function test_admin_reports_shows_counts(): void
    {
        $admin = $this->admin();
        User::factory()->count(3)->create();
        $trip = Trip::factory()->create(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->get('/admin/reports');
        $response->assertStatus(200)->assertSee('Users')->assertSee('Trips');
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Admin/AdminReportTest.php
```

- [ ] **Step 3: Add admin reports route**

```php
Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
```

- [ ] **Step 4: Implement AdminReportController**

```php
<?php
// app/Http/Controllers/Admin/ReportController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\OcrLog;
use App\Models\Trip;
use App\Models\User;

class ReportController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users'    => User::where('role', 'traveler')->count(),
            'total_trips'    => Trip::count(),
            'total_expenses' => Expense::count(),
            'total_spent'    => Expense::sum('amount'),
            'ocr_success'    => OcrLog::where('status', 'success')->count(),
            'ocr_failed'     => OcrLog::where('status', 'failed')->count(),
            'new_users_30d'  => User::where('created_at', '>=', now()->subDays(30))->count(),
            'new_trips_30d'  => Trip::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $topDestinations = Trip::select('destination', \DB::raw('count(*) as count'))
            ->groupBy('destination')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return view('admin.reports.index', compact('stats', 'topDestinations'));
    }
}
```

- [ ] **Step 5: Create admin reports view**

`resources/views/admin/reports/index.blade.php`:
```html
@extends('layouts.app')
@section('title', 'System Reports')
@section('content')
<h1>System Reports</h1>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:2rem;">
    @foreach([
        ['label' => 'Travelers', 'value' => $stats['total_users']],
        ['label' => 'Total Trips', 'value' => $stats['total_trips']],
        ['label' => 'Total Expenses', 'value' => $stats['total_expenses']],
        ['label' => 'Total Spent', 'value' => '₱'.number_format($stats['total_spent'], 0)],
        ['label' => 'OCR Success', 'value' => $stats['ocr_success']],
        ['label' => 'OCR Failed', 'value' => $stats['ocr_failed']],
        ['label' => 'New Users (30d)', 'value' => $stats['new_users_30d']],
        ['label' => 'New Trips (30d)', 'value' => $stats['new_trips_30d']],
    ] as $stat)
        <div class="card">
            <div class="card-body" style="text-align:center;">
                <div style="font-size:1.8rem;font-weight:700;">{{ $stat['value'] }}</div>
                <div style="color:#666;font-size:.85rem;">{{ $stat['label'] }}</div>
            </div>
        </div>
    @endforeach
</div>

<h3>Top Destinations</h3>
<table class="table">
    <thead><tr><th>Destination</th><th>Trips</th></tr></thead>
    <tbody>
    @foreach($topDestinations as $dest)
        <tr><td>{{ $dest->destination }}</td><td>{{ $dest->count }}</td></tr>
    @endforeach
    </tbody>
</table>
@endsection
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Admin/AdminReportTest.php
```

- [ ] **Step 7: Run full P4 test suite**

```
php artisan test tests/Feature/Admin/
```

Expected: all PASS.
