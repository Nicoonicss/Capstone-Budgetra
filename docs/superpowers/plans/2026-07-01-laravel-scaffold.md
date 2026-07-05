# Budgetra Laravel Scaffold Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the existing flat-PHP project with a fully scaffolded Laravel 11 + Livewire 3 project — all migrations, models, routes, controllers, layouts, view stubs, and Livewire stubs in place, app boots cleanly, every route resolves to a 200.

**Architecture:** Two role zones (`/` for Traveler, `/admin` for Admin). Thin controllers return stub views. Livewire components are empty stubs. All middleware passes through. No auth or feature logic yet.

**Tech Stack:** Laravel 11, Livewire 3, Blade, MySQL (`budgetra` DB), Font Awesome 6 (CDN), existing `style.css` ported to `public/css/`.

## Global Constraints

- PHP 8.3+ (8.3.12 installed)
- Laravel 13.x (13.18.0 installed — latest stable as of July 2026)
- Livewire 4.x (4.3.3 installed — current major, successor to v3)
- MySQL database name: `budgetra`
- All controllers extend `App\Http\Controllers\Controller`
- All Livewire components extend `Livewire\Component`
- Middleware stubs always call `$next($request)` — no auth enforcement yet
- Controllers only call `return view(...)` — no DB queries yet
- Never modify `public/css/style.css` — it is ported verbatim from the old project

---

### Task 1: Install Laravel 11 + Livewire 3

**Files:**
- Replace: entire `c:\phpsite\Capstone - Budgetra\` directory
- Create: `.env` (configured for MySQL)
- Modify: `bootstrap/app.php` (register `routes/admin.php` + `admin` middleware alias)

**Interfaces:**
- Produces: working `php artisan` command, `APP_KEY` set, DB connection configured

- [ ] **Step 1: Verify Composer is installed**

```powershell
composer --version
```
Expected: `Composer version 2.x.x`  
If missing: download from https://getcomposer.org/Composer-Setup.exe and install.

- [ ] **Step 2: Back up the spec/plan docs AND the CSS file to a temp location**

```powershell
Copy-Item "C:\phpsite\Capstone - Budgetra\docs" -Destination "C:\Users\ASUS\AppData\Local\Temp\budgetra-docs-backup" -Recurse -Force
Copy-Item "C:\phpsite\Capstone - Budgetra\public\css\style.css" -Destination "C:\Users\ASUS\AppData\Local\Temp\budgetra-style.css" -Force
```

- [ ] **Step 3: Delete all existing project files**

```powershell
Get-ChildItem "C:\phpsite\Capstone - Budgetra" -Force | Remove-Item -Recurse -Force
```

- [ ] **Step 4: Install Laravel 11 into the now-empty directory**

```powershell
cd "C:\phpsite\Capstone - Budgetra"
composer create-project laravel/laravel . --prefer-dist
```
Expected: `Application key set successfully.` at the end of output.

- [ ] **Step 5: Install Livewire 3**

```powershell
composer require livewire/livewire
```
Expected: `livewire/livewire` appears in `composer.json` requires.

- [ ] **Step 6: Configure .env for MySQL**

Open `.env` and set these values (leave everything else as-is):
```
APP_NAME=Budgetra
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=budgetra
DB_USERNAME=root
DB_PASSWORD=
```

- [ ] **Step 7: Create the `budgetra` database if it doesn't exist**

```powershell
php -r "new PDO('mysql:host=127.0.0.1;port=3306', 'root', '')->exec('CREATE DATABASE IF NOT EXISTS budgetra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');"
```
Expected: no output (success).

- [ ] **Step 8: Delete Laravel's default users and password-reset migrations** (we replace them in Task 2)

```powershell
Remove-Item "database\migrations\0001_01_01_000000_create_users_table.php"
Remove-Item "database\migrations\0001_01_01_000001_create_cache_table.php"
Remove-Item "database\migrations\0001_01_01_000002_create_jobs_table.php"
```

- [ ] **Step 9: Register `routes/admin.php` and `admin` middleware alias in `bootstrap/app.php`**

Replace the entire contents of `bootstrap/app.php` with:
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                 ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

- [ ] **Step 10: Restore the docs folder**

```powershell
Copy-Item "C:\Users\ASUS\AppData\Local\Temp\budgetra-docs-backup" -Destination "C:\phpsite\Capstone - Budgetra\docs" -Recurse -Force
```

- [ ] **Step 11: Verify Laravel boots**

```powershell
php artisan about
```
Expected: table showing `Laravel 11.x`, `Environment: local`, `PHP: 8.2+`

- [ ] **Step 12: Commit**

```powershell
git init
git add .
git commit -m "chore: scaffold Laravel 11 + Livewire 3 base install"
```

---

### Task 2: Database Migrations

**Files:**
- Create: `database/migrations/2026_07_01_000001_create_users_table.php`
- Create: `database/migrations/2026_07_01_000002_create_trips_table.php`
- Create: `database/migrations/2026_07_01_000003_create_trip_budgets_table.php`
- Create: `database/migrations/2026_07_01_000004_create_expenses_table.php`
- Create: `database/migrations/2026_07_01_000005_create_savings_goals_table.php`
- Create: `database/migrations/2026_07_01_000006_create_notifications_table.php`
- Create: `database/migrations/2026_07_01_000007_create_itinerary_table.php`
- Create: `database/migrations/2026_07_01_000008_create_destination_costs_table.php`
- Create: `database/migrations/2026_07_01_000009_create_attractions_table.php`
- Create: `database/migrations/2026_07_01_000010_create_reviews_table.php`
- Create: `database/migrations/2026_07_01_000011_create_ocr_logs_table.php`
- Create: `database/migrations/2026_07_01_000012_create_app_config_table.php`
- Create: `database/migrations/2026_07_01_000013_create_password_reset_tokens_table.php`

**Interfaces:**
- Consumes: DB connection configured in Task 1
- Produces: 13 tables in `budgetra` DB; `php artisan migrate` exits 0

- [ ] **Step 1: Create `2026_07_01_000001_create_users_table.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('currency_code', 10)->default('USD');
            $table->string('currency_symbol', 10)->default('$');
            $table->enum('role', ['traveler', 'admin'])->default('traveler');
            $table->string('profile_photo')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('users'); }
};
```

- [ ] **Step 2: Create `2026_07_01_000002_create_trips_table.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('destination');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedTinyInteger('num_travelers')->default(1);
            $table->decimal('budget_limit', 10, 2)->nullable();
            $table->enum('travel_type', ['Solo', 'Family', 'Couple', 'Friends']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('trips'); }
};
```

- [ ] **Step 3: Create `2026_07_01_000003_create_trip_budgets_table.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trip_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->enum('category', [
                'Transportation', 'Accommodation', 'Food',
                'Tourist Attractions', 'Shopping', 'Emergency Funds',
            ]);
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->decimal('actual_spent', 10, 2)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('trip_budgets'); }
};
```

- [ ] **Step 4: Create `2026_07_01_000004_create_expenses_table.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('category', [
                'Transportation', 'Accommodation', 'Food',
                'Activities', 'Shopping', 'Emergency Expenses',
            ]);
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->date('expense_date');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('expenses'); }
};
```

- [ ] **Step 5: Create `2026_07_01_000005_create_savings_goals_table.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('savings_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->string('goal_name');
            $table->decimal('target_amount', 10, 2);
            $table->decimal('current_savings', 10, 2)->default(0);
            $table->date('deadline');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('savings_goals'); }
};
```

- [ ] **Step 6: Create `2026_07_01_000006_create_notifications_table.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50);
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('notifications'); }
};
```

- [ ] **Step 7: Create `2026_07_01_000007_create_itinerary_table.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('itinerary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['Flight', 'Hotel', 'Activity', 'Transportation']);
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->string('location', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('itinerary'); }
};
```

- [ ] **Step 8: Create `2026_07_01_000008_create_destination_costs_table.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('destination_costs', function (Blueprint $table) {
            $table->id();
            $table->string('destination');
            $table->string('category', 100)->nullable();
            $table->enum('cost_level', ['Budget-friendly', 'Moderate', 'Pricey', 'Very Expensive'])->default('Moderate');
            $table->decimal('multiplier', 4, 3)->default(1.000);
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('destination_costs'); }
};
```

- [ ] **Step 9: Create `2026_07_01_000009_create_attractions_table.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attractions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('destination');
            $table->string('category', 100)->nullable();
            $table->string('image')->nullable();
            $table->decimal('rating', 3, 1)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('attractions'); }
};
```

- [ ] **Step 10: Create `2026_07_01_000010_create_reviews_table.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('destination');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('body');
            $table->enum('status', ['active', 'hidden'])->default('active');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('reviews'); }
};
```

- [ ] **Step 11: Create `2026_07_01_000011_create_ocr_logs_table.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ocr_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename')->nullable();
            $table->enum('status', ['success', 'failed', 'partial'])->default('success');
            $table->decimal('confidence', 5, 2)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ocr_logs'); }
};
```

- [ ] **Step 12: Create `2026_07_01_000012_create_app_config_table.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_config', function (Blueprint $table) {
            $table->id();
            $table->string('config_key')->unique();
            $table->text('config_value')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('app_config'); }
};
```

- [ ] **Step 13: Create `2026_07_01_000013_create_password_reset_tokens_table.php`**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('password_reset_tokens'); }
};
```

- [ ] **Step 14: Run migrations**

```powershell
php artisan migrate
```
Expected: all 13 tables listed as `DONE`, exit code 0.

- [ ] **Step 15: Commit**

```powershell
git add database/migrations
git commit -m "feat: add all 13 database migrations"
```

---

### Task 3: Eloquent Models

**Files:**
- Modify: `app/Models/User.php`
- Create: `app/Models/Trip.php`
- Create: `app/Models/TripBudget.php`
- Create: `app/Models/Expense.php`
- Create: `app/Models/SavingsGoal.php`
- Create: `app/Models/Notification.php`
- Create: `app/Models/Itinerary.php`
- Create: `app/Models/DestinationCost.php`
- Create: `app/Models/Attraction.php`
- Create: `app/Models/Review.php`
- Create: `app/Models/OcrLog.php`
- Create: `app/Models/AppConfig.php`

**Interfaces:**
- Produces: 12 model classes usable via Eloquent; `User::class`, `Trip::class` etc. accessible by controllers and seeders

- [ ] **Step 1: Replace `app/Models/User.php`**

```php
<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'full_name', 'email', 'password', 'phone', 'country',
        'currency_code', 'currency_symbol', 'role', 'profile_photo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function trips()       { return $this->hasMany(Trip::class); }
    public function expenses()    { return $this->hasMany(Expense::class); }
    public function savingsGoals(){ return $this->hasMany(SavingsGoal::class); }
    public function notifications(){ return $this->hasMany(Notification::class); }
    public function reviews()     { return $this->hasMany(Review::class); }
    public function ocrLogs()     { return $this->hasMany(OcrLog::class); }
}
```

- [ ] **Step 2: Create `app/Models/Trip.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'user_id', 'destination', 'start_date', 'end_date',
        'num_travelers', 'budget_limit', 'travel_type', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date'   => 'date',
            'end_date'     => 'date',
            'budget_limit' => 'decimal:2',
        ];
    }

    public function user()        { return $this->belongsTo(User::class); }
    public function budgets()     { return $this->hasMany(TripBudget::class); }
    public function expenses()    { return $this->hasMany(Expense::class); }
    public function itinerary()   { return $this->hasMany(Itinerary::class); }
    public function savingsGoals(){ return $this->hasMany(SavingsGoal::class); }
}
```

- [ ] **Step 3: Create `app/Models/TripBudget.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripBudget extends Model
{
    protected $fillable = ['trip_id', 'category', 'estimated_cost', 'actual_spent'];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'actual_spent'   => 'decimal:2',
        ];
    }

    public function trip() { return $this->belongsTo(Trip::class); }
}
```

- [ ] **Step 4: Create `app/Models/Expense.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'trip_id', 'user_id', 'amount', 'category',
        'description', 'receipt_path', 'expense_date',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function trip() { return $this->belongsTo(Trip::class); }
    public function user() { return $this->belongsTo(User::class); }
}
```

- [ ] **Step 5: Create `app/Models/SavingsGoal.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsGoal extends Model
{
    protected $fillable = [
        'user_id', 'trip_id', 'goal_name',
        'target_amount', 'current_savings', 'deadline',
    ];

    protected function casts(): array
    {
        return [
            'target_amount'   => 'decimal:2',
            'current_savings' => 'decimal:2',
            'deadline'        => 'date',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function trip() { return $this->belongsTo(Trip::class); }
}
```

- [ ] **Step 6: Create `app/Models/Notification.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['user_id', 'trip_id', 'type', 'message', 'is_read'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function trip() { return $this->belongsTo(Trip::class); }
}
```

- [ ] **Step 7: Create `app/Models/Itinerary.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    protected $fillable = [
        'trip_id', 'title', 'type',
        'start_datetime', 'end_datetime', 'location', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime'   => 'datetime',
        ];
    }

    public function trip() { return $this->belongsTo(Trip::class); }
}
```

- [ ] **Step 8: Create `app/Models/DestinationCost.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DestinationCost extends Model
{
    protected $fillable = [
        'destination', 'category', 'cost_level',
        'multiplier', 'image_url', 'description',
    ];

    protected function casts(): array
    {
        return ['multiplier' => 'decimal:3'];
    }
}
```

- [ ] **Step 9: Create `app/Models/Attraction.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attraction extends Model
{
    protected $fillable = [
        'name', 'destination', 'category', 'image', 'rating', 'description',
    ];

    protected function casts(): array
    {
        return ['rating' => 'decimal:1'];
    }
}
```

- [ ] **Step 10: Create `app/Models/Review.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['user_id', 'destination', 'rating', 'body', 'status'];

    public function user() { return $this->belongsTo(User::class); }
}
```

- [ ] **Step 11: Create `app/Models/OcrLog.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OcrLog extends Model
{
    protected $fillable = [
        'user_id', 'filename', 'status', 'confidence', 'error_message',
    ];

    protected function casts(): array
    {
        return ['confidence' => 'decimal:2'];
    }

    public function user() { return $this->belongsTo(User::class); }
}
```

- [ ] **Step 12: Create `app/Models/AppConfig.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppConfig extends Model
{
    protected $fillable = ['config_key', 'config_value'];
}
```

- [ ] **Step 13: Verify models load without error**

```powershell
php artisan tinker --execute="echo App\Models\User::class . PHP_EOL;"
```
Expected: `App\Models\User`

- [ ] **Step 14: Commit**

```powershell
git add app/Models
git commit -m "feat: add all 12 Eloquent models with relationships"
```

---

### Task 4: Middleware + Route Files

**Files:**
- Create: `app/Http/Middleware/AdminMiddleware.php`
- Modify: `routes/web.php`
- Create: `routes/admin.php`

**Interfaces:**
- Produces: `php artisan route:list` shows all traveler and admin routes; `admin` middleware alias registered in `bootstrap/app.php` (done in Task 1 Step 9)

- [ ] **Step 1: Create `app/Http/Middleware/AdminMiddleware.php`**

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Stub — enforced during auth implementation phase
        return $next($request);
    }
}
```

- [ ] **Step 2: Replace `routes/web.php`**

```php
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Traveler;

Route::get('/', fn() => redirect('/dashboard'));

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', Traveler\DashboardController::class)->name('dashboard');

    Route::get('/trips', [Traveler\TripController::class, 'index'])->name('trips.index');
    Route::get('/trips/type', [Traveler\TripController::class, 'type'])->name('trips.type');
    Route::get('/trips/create', [Traveler\TripController::class, 'create'])->name('trips.create');
    Route::post('/trips', [Traveler\TripController::class, 'store'])->name('trips.store');
    Route::get('/trips/{trip}/estimate', [Traveler\TripController::class, 'estimate'])->name('trips.estimate');

    Route::get('/expenses', [Traveler\ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/create', [Traveler\ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [Traveler\ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/expenses/{expense}', [Traveler\ExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::get('/savings', [Traveler\SavingsController::class, 'index'])->name('savings.index');
    Route::post('/savings', [Traveler\SavingsController::class, 'store'])->name('savings.store');
    Route::post('/savings/{goal}/add', [Traveler\SavingsController::class, 'addAmount'])->name('savings.add');

    Route::get('/itinerary', [Traveler\ItineraryController::class, 'index'])->name('itinerary.index');
    Route::post('/itinerary', [Traveler\ItineraryController::class, 'store'])->name('itinerary.store');
    Route::delete('/itinerary/{item}', [Traveler\ItineraryController::class, 'destroy'])->name('itinerary.destroy');

    Route::get('/attractions', [Traveler\AttractionController::class, 'index'])->name('attractions.index');
    Route::get('/alerts', [Traveler\AlertController::class, 'index'])->name('alerts.index');
    Route::get('/reports', [Traveler\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/download', [Traveler\ReportController::class, 'download'])->name('reports.download');
});
```

- [ ] **Step 3: Create `routes/admin.php`**

```php
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/destinations', [Admin\DestinationController::class, 'index'])->name('destinations.index');
    Route::post('/destinations', [Admin\DestinationController::class, 'store'])->name('destinations.store');
    Route::put('/destinations/{dest}', [Admin\DestinationController::class, 'update'])->name('destinations.update');
    Route::delete('/destinations/{dest}', [Admin\DestinationController::class, 'destroy'])->name('destinations.destroy');

    Route::get('/attractions', [Admin\AttractionController::class, 'index'])->name('attractions.index');
    Route::post('/attractions', [Admin\AttractionController::class, 'store'])->name('attractions.store');
    Route::put('/attractions/{attr}', [Admin\AttractionController::class, 'update'])->name('attractions.update');
    Route::delete('/attractions/{attr}', [Admin\AttractionController::class, 'destroy'])->name('attractions.destroy');

    Route::get('/reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}', [Admin\ReviewController::class, 'updateStatus'])->name('reviews.updateStatus');
    Route::delete('/reviews/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('/integrations', [Admin\IntegrationController::class, 'index'])->name('integrations.index');
    Route::post('/integrations/klook', [Admin\IntegrationController::class, 'saveKlook'])->name('integrations.klook');
    Route::post('/integrations/test', [Admin\IntegrationController::class, 'testKlook'])->name('integrations.test');

    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/download', [Admin\ReportController::class, 'download'])->name('reports.download');
});
```

- [ ] **Step 4: Verify route count (controllers created in Task 5 — skip this step until after Task 5)**

Run after Task 5 Step 13:
```powershell
php artisan route:list
```
Expected: 35+ routes listed, no errors.

- [ ] **Step 5: Commit**

```powershell
git add app/Http/Middleware routes/web.php routes/admin.php
git commit -m "feat: add AdminMiddleware stub and all route definitions"
```

---

### Task 5: Controllers (All Stubs)

**Files:**
- Create: `app/Http/Controllers/Admin/DashboardController.php`
- Create: `app/Http/Controllers/Admin/UserController.php`
- Create: `app/Http/Controllers/Admin/DestinationController.php`
- Create: `app/Http/Controllers/Admin/AttractionController.php`
- Create: `app/Http/Controllers/Admin/ReviewController.php`
- Create: `app/Http/Controllers/Admin/IntegrationController.php`
- Create: `app/Http/Controllers/Admin/ReportController.php`
- Create: `app/Http/Controllers/Traveler/DashboardController.php`
- Create: `app/Http/Controllers/Traveler/TripController.php`
- Create: `app/Http/Controllers/Traveler/ExpenseController.php`
- Create: `app/Http/Controllers/Traveler/SavingsController.php`
- Create: `app/Http/Controllers/Traveler/ItineraryController.php`
- Create: `app/Http/Controllers/Traveler/AttractionController.php`
- Create: `app/Http/Controllers/Traveler/AlertController.php`
- Create: `app/Http/Controllers/Traveler/ReportController.php`

**Interfaces:**
- Consumes: view stubs from Tasks 9 + 10 (views must exist before controllers can return them)
- Produces: all controller classes resolvable by the router

> **Note:** Create view stub directories now so controllers can return views without errors. Full view content added in Tasks 9 and 10.

- [ ] **Step 1: Pre-create all view stub directories**

```powershell
New-Item -ItemType Directory -Force -Path "resources\views\admin\users"
New-Item -ItemType Directory -Force -Path "resources\views\admin\destinations"
New-Item -ItemType Directory -Force -Path "resources\views\admin\attractions"
New-Item -ItemType Directory -Force -Path "resources\views\admin\reviews"
New-Item -ItemType Directory -Force -Path "resources\views\admin\integrations"
New-Item -ItemType Directory -Force -Path "resources\views\admin\reports"
New-Item -ItemType Directory -Force -Path "resources\views\traveler\dashboard"
New-Item -ItemType Directory -Force -Path "resources\views\traveler\trips"
New-Item -ItemType Directory -Force -Path "resources\views\traveler\expenses"
New-Item -ItemType Directory -Force -Path "resources\views\traveler\savings"
New-Item -ItemType Directory -Force -Path "resources\views\traveler\itinerary"
New-Item -ItemType Directory -Force -Path "resources\views\traveler\attractions"
New-Item -ItemType Directory -Force -Path "resources\views\traveler\alerts"
New-Item -ItemType Directory -Force -Path "resources\views\traveler\reports"
```

- [ ] **Step 2: Pre-create minimal blade stubs so views exist**

Run this PowerShell block to create empty blade files for all pages:
```powershell
$views = @(
    "resources\views\admin\dashboard.blade.php",
    "resources\views\admin\users\index.blade.php",
    "resources\views\admin\destinations\index.blade.php",
    "resources\views\admin\attractions\index.blade.php",
    "resources\views\admin\reviews\index.blade.php",
    "resources\views\admin\integrations\index.blade.php",
    "resources\views\admin\reports\index.blade.php",
    "resources\views\traveler\dashboard\index.blade.php",
    "resources\views\traveler\trips\index.blade.php",
    "resources\views\traveler\trips\type.blade.php",
    "resources\views\traveler\trips\create.blade.php",
    "resources\views\traveler\trips\estimate.blade.php",
    "resources\views\traveler\expenses\index.blade.php",
    "resources\views\traveler\expenses\create.blade.php",
    "resources\views\traveler\savings\index.blade.php",
    "resources\views\traveler\savings\create.blade.php",
    "resources\views\traveler\itinerary\index.blade.php",
    "resources\views\traveler\attractions\index.blade.php",
    "resources\views\traveler\alerts\index.blade.php",
    "resources\views\traveler\reports\index.blade.php"
)
foreach ($v in $views) {
    if (-not (Test-Path $v)) { New-Item -ItemType File -Force -Path $v | Out-Null }
}
Write-Host "All view stubs created."
```

- [ ] **Step 3: Create `app/Http/Controllers/Admin/DashboardController.php`**

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard');
    }
}
```

- [ ] **Step 4: Create `app/Http/Controllers/Admin/UserController.php`**

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index()   { return view('admin.users.index'); }
    public function destroy() { return back(); }
}
```

- [ ] **Step 5: Create `app/Http/Controllers/Admin/DestinationController.php`**

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DestinationController extends Controller
{
    public function index()   { return view('admin.destinations.index'); }
    public function store()   { return back(); }
    public function update()  { return back(); }
    public function destroy() { return back(); }
}
```

- [ ] **Step 6: Create `app/Http/Controllers/Admin/AttractionController.php`**

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AttractionController extends Controller
{
    public function index()   { return view('admin.attractions.index'); }
    public function store()   { return back(); }
    public function update()  { return back(); }
    public function destroy() { return back(); }
}
```

- [ ] **Step 7: Create `app/Http/Controllers/Admin/ReviewController.php`**

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ReviewController extends Controller
{
    public function index()        { return view('admin.reviews.index'); }
    public function updateStatus() { return back(); }
    public function destroy()      { return back(); }
}
```

- [ ] **Step 8: Create `app/Http/Controllers/Admin/IntegrationController.php`**

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class IntegrationController extends Controller
{
    public function index()     { return view('admin.integrations.index'); }
    public function saveKlook() { return back(); }
    public function testKlook() { return back(); }
}
```

- [ ] **Step 9: Create `app/Http/Controllers/Admin/ReportController.php`**

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ReportController extends Controller
{
    public function index()    { return view('admin.reports.index'); }
    public function download() { return back(); }
}
```

- [ ] **Step 10: Create `app/Http/Controllers/Traveler/DashboardController.php`**

```php
<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('traveler.dashboard.index');
    }
}
```

- [ ] **Step 11: Create `app/Http/Controllers/Traveler/TripController.php`**

```php
<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;

class TripController extends Controller
{
    public function index()    { return view('traveler.trips.index'); }
    public function type()     { return view('traveler.trips.type'); }
    public function create()   { return view('traveler.trips.create'); }
    public function store()    { return back(); }
    public function estimate() { return view('traveler.trips.estimate'); }
}
```

- [ ] **Step 12: Create `app/Http/Controllers/Traveler/ExpenseController.php`**

```php
<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;

class ExpenseController extends Controller
{
    public function index()   { return view('traveler.expenses.index'); }
    public function create()  { return view('traveler.expenses.create'); }
    public function store()   { return back(); }
    public function destroy() { return back(); }
}
```

- [ ] **Step 13: Create `app/Http/Controllers/Traveler/SavingsController.php`**

```php
<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;

class SavingsController extends Controller
{
    public function index()     { return view('traveler.savings.index'); }
    public function store()     { return back(); }
    public function addAmount() { return back(); }
}
```

- [ ] **Step 14: Create `app/Http/Controllers/Traveler/ItineraryController.php`**

```php
<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;

class ItineraryController extends Controller
{
    public function index()   { return view('traveler.itinerary.index'); }
    public function store()   { return back(); }
    public function destroy() { return back(); }
}
```

- [ ] **Step 15: Create `app/Http/Controllers/Traveler/AttractionController.php`**

```php
<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;

class AttractionController extends Controller
{
    public function index() { return view('traveler.attractions.index'); }
}
```

- [ ] **Step 16: Create `app/Http/Controllers/Traveler/AlertController.php`**

```php
<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;

class AlertController extends Controller
{
    public function index() { return view('traveler.alerts.index'); }
}
```

- [ ] **Step 17: Create `app/Http/Controllers/Traveler/ReportController.php`**

```php
<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;

class ReportController extends Controller
{
    public function index()    { return view('traveler.reports.index'); }
    public function download() { return back(); }
}
```

- [ ] **Step 18: Verify route list resolves all controllers**

```powershell
php artisan route:list
```
Expected: all routes listed with their controller methods, no "Target class does not exist" errors.

- [ ] **Step 19: Commit**

```powershell
git add app/Http/Controllers resources/views
git commit -m "feat: add all 15 controller stubs and pre-create view directories"
```

---

### Task 6: Service Class Stubs

**Files:**
- Create: `app/Services/BudgetService.php`
- Create: `app/Services/OcrService.php`
- Create: `app/Services/KlookService.php`
- Create: `app/Services/ReportService.php`

**Interfaces:**
- Produces: 4 service classes in `App\Services` namespace, injectable via Laravel's container

- [ ] **Step 1: Create `app/Services/BudgetService.php`**

```php
<?php
namespace App\Services;

class BudgetService
{
    // Budget calculation logic — implemented in feature phase
}
```

- [ ] **Step 2: Create `app/Services/OcrService.php`**

```php
<?php
namespace App\Services;

class OcrService
{
    // OCR receipt scanning logic — implemented in feature phase
}
```

- [ ] **Step 3: Create `app/Services/KlookService.php`**

```php
<?php
namespace App\Services;

class KlookService
{
    // Klook API integration logic — implemented in feature phase
}
```

- [ ] **Step 4: Create `app/Services/ReportService.php`**

```php
<?php
namespace App\Services;

class ReportService
{
    // PDF report generation logic — implemented in feature phase
}
```

- [ ] **Step 5: Verify services autoload**

```powershell
php artisan tinker --execute="echo app(App\Services\BudgetService::class) ? 'OK' : 'FAIL';"
```
Expected: `OK`

- [ ] **Step 6: Commit**

```powershell
git add app/Services
git commit -m "feat: add 4 service class stubs"
```

---

### Task 7: Blade Layouts

**Files:**
- Create: `resources/views/layouts/app.blade.php`
- Create: `resources/views/layouts/admin.blade.php`
- Create: `resources/views/layouts/guest.blade.php`

**Interfaces:**
- Produces: three layout files usable via `@extends('layouts.app')`, `@extends('layouts.admin')`, `@extends('layouts.guest')`; Livewire assets injected via `@livewireStyles` / `@livewireScripts`

- [ ] **Step 1: Create `resources/views/layouts/app.blade.php`** (Traveler shell)

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Budgetra</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @livewireStyles
</head>
<body class="dashboard-body">
    <div class="dashboard-wrapper">
        <x-sidebar :active="$active ?? ''" />
        <div class="dash-main">
            <div class="app-content">
                @yield('content')
            </div>
        </div>
    </div>
    @livewireScripts
</body>
</html>
```

- [ ] **Step 2: Create `resources/views/layouts/admin.blade.php`** (Admin shell)

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Budgetra Admin</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @livewireStyles
</head>
<body class="dashboard-body">
    <div class="dashboard-wrapper">
        <x-admin-sidebar :active="$active ?? ''" />
        <div class="dash-main">
            <div class="app-content">
                @yield('content')
            </div>
        </div>
    </div>
    @livewireScripts
</body>
</html>
```

- [ ] **Step 3: Create `resources/views/layouts/guest.blade.php`** (Auth shell — future use)

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Budgetra</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-body">
    @yield('content')
</body>
</html>
```

- [ ] **Step 4: Commit**

```powershell
git add resources/views/layouts
git commit -m "feat: add three Blade layout shells (app, admin, guest)"
```

---

### Task 8: Blade Components

**Files:**
- Create: `resources/views/components/sidebar.blade.php`
- Create: `resources/views/components/admin-sidebar.blade.php`
- Create: `resources/views/components/stat-card.blade.php`
- Create: `resources/views/components/modal.blade.php`

**Interfaces:**
- Produces: `<x-sidebar :active="'dashboard'">`, `<x-admin-sidebar :active="'dashboard'">`, `<x-stat-card icon="..." color="..." bg="..." label="..." value="...">`, `<x-modal id="...">...</x-modal>`

- [ ] **Step 1: Create `resources/views/components/sidebar.blade.php`**

```blade
@props(['active' => ''])
<aside class="sidebar" id="appSidebar">
    <button class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle sidebar">
        <i class="fa-solid fa-bars" id="sidebarToggleIcon"></i>
    </button>
    <div class="sidebar-brand">
        <div class="sidebar-trip-badge"><span>Active Workspace</span></div>
        <div class="sidebar-trip-name"><span>My Trip</span></div>
        <div class="sidebar-trip-status"><span>Budgetra</span></div>
    </div>
    <nav class="sidebar-nav">
        @php
        $links = [
            ['href' => url('/dashboard'),   'icon' => 'fa-solid fa-house',            'label' => 'Dashboard',    'key' => 'dashboard'],
            ['href' => url('/trips'),        'icon' => 'fa-solid fa-map-location-dot', 'label' => 'Planner',      'key' => 'planner'],
            ['href' => url('/savings'),      'icon' => 'fa-regular fa-circle-dot',     'label' => 'Savings Goal', 'key' => 'savings'],
            ['href' => url('/itinerary'),    'icon' => 'fa-regular fa-calendar-days',  'label' => 'Itinerary',    'key' => 'itinerary'],
            ['href' => url('/attractions'),  'icon' => 'fa-solid fa-mountain-sun',     'label' => 'Attractions',  'key' => 'attractions'],
            ['href' => url('/alerts'),       'icon' => 'fa-regular fa-bell',           'label' => 'Alerts',       'key' => 'alerts'],
            ['href' => url('/trips'),        'icon' => 'fa-solid fa-suitcase-rolling', 'label' => 'Multi-Trip',   'key' => 'trips'],
        ];
        @endphp
        @foreach ($links as $link)
        <a href="{{ $link['href'] }}"
           class="sidebar-link {{ $active === $link['key'] ? 'active' : '' }}"
           title="{{ $link['label'] }}">
            <i class="{{ $link['icon'] }}"></i>
            <span class="sidebar-link-label">{{ $link['label'] }}</span>
        </a>
        @endforeach
    </nav>
    <div class="sidebar-avatar-wrap">
        <div class="sidebar-avatar">U</div>
        <div class="sidebar-user-details">
            <div class="sidebar-user-name">Traveler</div>
            <div class="sidebar-user-email">traveler@budgetra.com</div>
        </div>
    </div>
</aside>
<script>
(function () {
    var wrap = document.querySelector('.dashboard-wrapper');
    var btn  = document.getElementById('sidebarToggle');
    var icon = document.getElementById('sidebarToggleIcon');
    if (!wrap || !btn) return;
    function applyState(c) {
        wrap.classList.toggle('sidebar-collapsed', c);
        icon.className = c ? 'fa-solid fa-chevron-right' : 'fa-solid fa-bars';
    }
    applyState(localStorage.getItem('sidebarCollapsed') === '1');
    btn.addEventListener('click', function () {
        var c = !wrap.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', c ? '1' : '0');
        applyState(c);
    });
})();
</script>
```

- [ ] **Step 2: Create `resources/views/components/admin-sidebar.blade.php`**

```blade
@props(['active' => ''])
<aside class="sidebar" id="appSidebar">
    <button class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle sidebar">
        <i class="fa-solid fa-bars" id="sidebarToggleIcon"></i>
    </button>
    <div class="sidebar-brand">
        <div class="sidebar-trip-badge"><span>Admin Panel</span></div>
        <div class="sidebar-trip-name"><span>Budgetra</span></div>
        <div class="sidebar-trip-status"><span>Management Dashboard</span></div>
    </div>
    <nav class="sidebar-nav" style="overflow-y:auto;max-height:calc(100vh - 220px);">
        @php
        $links = [
            ['href' => url('/admin'),              'icon' => 'fa-solid fa-gauge-high',       'label' => 'Overview',      'key' => 'dashboard'],
            ['href' => url('/admin/users'),         'icon' => 'fa-solid fa-users',            'label' => 'Users',         'key' => 'users'],
            ['href' => url('/admin/destinations'),  'icon' => 'fa-solid fa-map-pin',          'label' => 'Destinations',  'key' => 'destinations'],
            ['href' => url('/admin/attractions'),   'icon' => 'fa-solid fa-mountain-sun',     'label' => 'Attractions',   'key' => 'attractions'],
            ['href' => url('/admin/integrations'),  'icon' => 'fa-solid fa-plug-circle-bolt', 'label' => 'Integrations',  'key' => 'integrations'],
            ['href' => url('/admin/reports'),       'icon' => 'fa-solid fa-chart-column',     'label' => 'Reports',       'key' => 'reports'],
            ['href' => url('/admin/reviews'),       'icon' => 'fa-solid fa-star-half-stroke', 'label' => 'Reviews',       'key' => 'reviews'],
        ];
        @endphp
        @foreach ($links as $link)
        <a href="{{ $link['href'] }}"
           class="sidebar-link {{ $active === $link['key'] ? 'active' : '' }}"
           title="{{ $link['label'] }}">
            <i class="{{ $link['icon'] }}"></i>
            <span class="sidebar-link-label">{{ $link['label'] }}</span>
        </a>
        @endforeach
    </nav>
    <div class="sidebar-avatar-wrap">
        <div class="sidebar-avatar">A</div>
        <div class="sidebar-user-details">
            <div class="sidebar-user-name">Admin</div>
            <div class="sidebar-user-email">Administrator</div>
        </div>
    </div>
</aside>
<style>
.sidebar-nav { scrollbar-width: none; }
.sidebar-nav::-webkit-scrollbar { display: none; }
</style>
<script>
(function () {
    var wrap = document.querySelector('.dashboard-wrapper');
    var btn  = document.getElementById('sidebarToggle');
    var icon = document.getElementById('sidebarToggleIcon');
    if (!wrap || !btn) return;
    function applyState(c) {
        wrap.classList.toggle('sidebar-collapsed', c);
        icon.className = c ? 'fa-solid fa-chevron-right' : 'fa-solid fa-bars';
    }
    applyState(localStorage.getItem('sidebarCollapsed') === '1');
    btn.addEventListener('click', function () {
        var c = !wrap.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', c ? '1' : '0');
        applyState(c);
    });
})();
</script>
```

- [ ] **Step 3: Create `resources/views/components/stat-card.blade.php`**

```blade
@props(['icon', 'color', 'bg', 'label', 'value', 'sub' => null])
<div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:20px 18px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <div style="width:40px;height:40px;border-radius:10px;background:{{ $bg }};
                    display:flex;align-items:center;justify-content:center;">
            <i class="{{ $icon }}" style="color:{{ $color }};font-size:16px;"></i>
        </div>
        <div style="font-size:12px;color:var(--muted);font-weight:600;">{{ $label }}</div>
    </div>
    <div style="font-size:28px;font-weight:800;margin-bottom:4px;">{{ $value }}</div>
    @if ($sub)
    <div style="font-size:11px;color:var(--muted);">{{ $sub }}</div>
    @endif
</div>
```

- [ ] **Step 4: Create `resources/views/components/modal.blade.php`**

```blade
@props(['id'])
<div id="{{ $id }}" class="adm-modal-backdrop" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="adm-modal">
        {{ $slot }}
    </div>
</div>
```

- [ ] **Step 5: Commit**

```powershell
git add resources/views/components
git commit -m "feat: add sidebar, admin-sidebar, stat-card, modal Blade components"
```

---

### Task 9: Admin View Stubs

**Files:**
- Modify: `resources/views/admin/dashboard.blade.php`
- Modify: `resources/views/admin/users/index.blade.php`
- Modify: `resources/views/admin/destinations/index.blade.php`
- Modify: `resources/views/admin/attractions/index.blade.php`
- Modify: `resources/views/admin/reviews/index.blade.php`
- Modify: `resources/views/admin/integrations/index.blade.php`
- Modify: `resources/views/admin/reports/index.blade.php`

**Interfaces:**
- Produces: all admin views renderable via `@extends('layouts.admin')` returning HTTP 200

- [ ] **Step 1: Write all 7 admin view stubs**

`resources/views/admin/dashboard.blade.php`:
```blade
@extends('layouts.admin')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Overview</h1>
@endsection
```

`resources/views/admin/users/index.blade.php`:
```blade
@extends('layouts.admin')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Manage Users</h1>
@endsection
```

`resources/views/admin/destinations/index.blade.php`:
```blade
@extends('layouts.admin')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Destinations</h1>
@endsection
```

`resources/views/admin/attractions/index.blade.php`:
```blade
@extends('layouts.admin')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Tourist Attractions</h1>
@endsection
```

`resources/views/admin/reviews/index.blade.php`:
```blade
@extends('layouts.admin')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Traveler Reviews</h1>
@endsection
```

`resources/views/admin/integrations/index.blade.php`:
```blade
@extends('layouts.admin')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Integrations</h1>
@endsection
```

`resources/views/admin/reports/index.blade.php`:
```blade
@extends('layouts.admin')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Reports</h1>
@endsection
```

- [ ] **Step 2: Commit**

```powershell
git add resources/views/admin
git commit -m "feat: add 7 admin view stubs"
```

---

### Task 10: Traveler View Stubs

**Files:**
- Modify: all 13 files under `resources/views/traveler/`

**Interfaces:**
- Produces: all traveler views renderable via `@extends('layouts.app')` returning HTTP 200

- [ ] **Step 1: Write all 13 traveler view stubs**

`resources/views/traveler/dashboard/index.blade.php`:
```blade
@extends('layouts.app')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Dashboard</h1>
@endsection
```

`resources/views/traveler/trips/index.blade.php`:
```blade
@extends('layouts.app')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">My Trips</h1>
@endsection
```

`resources/views/traveler/trips/type.blade.php`:
```blade
@extends('layouts.app')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Plan a Trip</h1>
@endsection
```

`resources/views/traveler/trips/create.blade.php`:
```blade
@extends('layouts.app')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Create Trip</h1>
@endsection
```

`resources/views/traveler/trips/estimate.blade.php`:
```blade
@extends('layouts.app')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Trip Cost Estimator</h1>
@endsection
```

`resources/views/traveler/expenses/index.blade.php`:
```blade
@extends('layouts.app')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Expenses</h1>
@endsection
```

`resources/views/traveler/expenses/create.blade.php`:
```blade
@extends('layouts.app')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Log Expense</h1>
@endsection
```

`resources/views/traveler/savings/index.blade.php`:
```blade
@extends('layouts.app')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Savings Goals</h1>
@endsection
```

`resources/views/traveler/savings/create.blade.php`:
```blade
@extends('layouts.app')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Create Savings Goal</h1>
@endsection
```

`resources/views/traveler/itinerary/index.blade.php`:
```blade
@extends('layouts.app')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Itinerary</h1>
@endsection
```

`resources/views/traveler/attractions/index.blade.php`:
```blade
@extends('layouts.app')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Attractions &amp; Reviews</h1>
@endsection
```

`resources/views/traveler/alerts/index.blade.php`:
```blade
@extends('layouts.app')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Alerts</h1>
@endsection
```

`resources/views/traveler/reports/index.blade.php`:
```blade
@extends('layouts.app')
@section('content')
<h1 style="font-size:26px;font-weight:800;margin:0 0 24px;">Budget Reports</h1>
@endsection
```

- [ ] **Step 2: Commit**

```powershell
git add resources/views/traveler
git commit -m "feat: add 13 traveler view stubs"
```

---

### Task 11: Livewire Component Stubs

**Files (12 × PHP class + 12 × Blade view = 24 files):**
- Create: `app/Livewire/Admin/{UserTable,DestinationTable,AttractionTable,ReviewTable,OcrMonitor,KlookConfig}.php`
- Create: `app/Livewire/Traveler/{TripPlanner,ExpenseTracker,SavingsGoal,ItineraryManager,AttractionBrowser,BudgetComparison}.php`
- Create: matching views in `resources/views/livewire/admin/` and `resources/views/livewire/traveler/`

**Interfaces:**
- Produces: 12 Livewire component classes, each with `render()` returning their paired blade view

- [ ] **Step 1: Create directories**

```powershell
New-Item -ItemType Directory -Force -Path "app\Livewire\Admin"
New-Item -ItemType Directory -Force -Path "app\Livewire\Traveler"
New-Item -ItemType Directory -Force -Path "resources\views\livewire\admin"
New-Item -ItemType Directory -Force -Path "resources\views\livewire\traveler"
```

- [ ] **Step 2: Create the 6 Admin Livewire PHP stubs**

`app/Livewire/Admin/UserTable.php`:
```php
<?php
namespace App\Livewire\Admin;
use Livewire\Component;
class UserTable extends Component
{
    public function render() { return view('livewire.admin.user-table'); }
}
```

`app/Livewire/Admin/DestinationTable.php`:
```php
<?php
namespace App\Livewire\Admin;
use Livewire\Component;
class DestinationTable extends Component
{
    public function render() { return view('livewire.admin.destination-table'); }
}
```

`app/Livewire/Admin/AttractionTable.php`:
```php
<?php
namespace App\Livewire\Admin;
use Livewire\Component;
class AttractionTable extends Component
{
    public function render() { return view('livewire.admin.attraction-table'); }
}
```

`app/Livewire/Admin/ReviewTable.php`:
```php
<?php
namespace App\Livewire\Admin;
use Livewire\Component;
class ReviewTable extends Component
{
    public function render() { return view('livewire.admin.review-table'); }
}
```

`app/Livewire/Admin/OcrMonitor.php`:
```php
<?php
namespace App\Livewire\Admin;
use Livewire\Component;
class OcrMonitor extends Component
{
    public function render() { return view('livewire.admin.ocr-monitor'); }
}
```

`app/Livewire/Admin/KlookConfig.php`:
```php
<?php
namespace App\Livewire\Admin;
use Livewire\Component;
class KlookConfig extends Component
{
    public function render() { return view('livewire.admin.klook-config'); }
}
```

- [ ] **Step 3: Create the 6 Traveler Livewire PHP stubs**

`app/Livewire/Traveler/TripPlanner.php`:
```php
<?php
namespace App\Livewire\Traveler;
use Livewire\Component;
class TripPlanner extends Component
{
    public function render() { return view('livewire.traveler.trip-planner'); }
}
```

`app/Livewire/Traveler/ExpenseTracker.php`:
```php
<?php
namespace App\Livewire\Traveler;
use Livewire\Component;
class ExpenseTracker extends Component
{
    public function render() { return view('livewire.traveler.expense-tracker'); }
}
```

`app/Livewire/Traveler/SavingsGoal.php`:
```php
<?php
namespace App\Livewire\Traveler;
use Livewire\Component;
class SavingsGoal extends Component
{
    public function render() { return view('livewire.traveler.savings-goal'); }
}
```

`app/Livewire/Traveler/ItineraryManager.php`:
```php
<?php
namespace App\Livewire\Traveler;
use Livewire\Component;
class ItineraryManager extends Component
{
    public function render() { return view('livewire.traveler.itinerary-manager'); }
}
```

`app/Livewire/Traveler/AttractionBrowser.php`:
```php
<?php
namespace App\Livewire\Traveler;
use Livewire\Component;
class AttractionBrowser extends Component
{
    public function render() { return view('livewire.traveler.attraction-browser'); }
}
```

`app/Livewire/Traveler/BudgetComparison.php`:
```php
<?php
namespace App\Livewire\Traveler;
use Livewire\Component;
class BudgetComparison extends Component
{
    public function render() { return view('livewire.traveler.budget-comparison'); }
}
```

- [ ] **Step 4: Create the 12 Livewire blade view stubs**

Run this PowerShell block:
```powershell
$lv = @{
    "resources\views\livewire\admin\user-table.blade.php"         = "User Table"
    "resources\views\livewire\admin\destination-table.blade.php"  = "Destination Table"
    "resources\views\livewire\admin\attraction-table.blade.php"   = "Attraction Table"
    "resources\views\livewire\admin\review-table.blade.php"       = "Review Table"
    "resources\views\livewire\admin\ocr-monitor.blade.php"        = "OCR Monitor"
    "resources\views\livewire\admin\klook-config.blade.php"       = "Klook Config"
    "resources\views\livewire\traveler\trip-planner.blade.php"    = "Trip Planner"
    "resources\views\livewire\traveler\expense-tracker.blade.php" = "Expense Tracker"
    "resources\views\livewire\traveler\savings-goal.blade.php"    = "Savings Goal"
    "resources\views\livewire\traveler\itinerary-manager.blade.php" = "Itinerary Manager"
    "resources\views\livewire\traveler\attraction-browser.blade.php" = "Attraction Browser"
    "resources\views\livewire\traveler\budget-comparison.blade.php" = "Budget Comparison"
}
foreach ($path in $lv.Keys) {
    $label = $lv[$path]
    $content = "<div>`n    {{-- $label component — implemented in feature phase --}}`n</div>"
    New-Item -ItemType File -Force -Path $path | Out-Null
    Set-Content -Path $path -Value $content -Encoding utf8
}
Write-Host "All Livewire views created."
```

- [ ] **Step 5: Verify one Livewire component resolves**

```powershell
php artisan tinker --execute="echo (new App\Livewire\Admin\UserTable)->render() ? 'OK' : 'FAIL';"
```
Expected: `OK`

- [ ] **Step 6: Commit**

```powershell
git add app/Livewire resources/views/livewire
git commit -m "feat: add 12 Livewire component stubs (admin + traveler)"
```

---

### Task 12: CSS + Storage + Seeders

**Files:**
- Create: `public/css/style.css` (copy from old project)
- Create: `storage/app/public/receipts/.gitkeep`
- Create: `storage/app/public/attraction-images/.gitkeep`
- Create: `database/seeders/AdminUserSeeder.php`
- Create: `database/seeders/DestinationSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

**Interfaces:**
- Produces: `style.css` served at `/css/style.css`; `storage:link` creates `public/storage` symlink; `php artisan db:seed` creates admin user + destinations

- [ ] **Step 1: Restore `style.css` from the backup made in Task 1 Step 2**

```powershell
New-Item -ItemType Directory -Force -Path "public\css"
Copy-Item "C:\Users\ASUS\AppData\Local\Temp\budgetra-style.css" -Destination "public\css\style.css" -Force
```
Expected: `public/css/style.css` exists and contains the full Budgetra design system.

- [ ] **Step 2: Create storage directories**

```powershell
New-Item -ItemType Directory -Force -Path "storage\app\public\receipts"
New-Item -ItemType Directory -Force -Path "storage\app\public\attraction-images"
New-Item -ItemType File -Force -Path "storage\app\public\receipts\.gitkeep"
New-Item -ItemType File -Force -Path "storage\app\public\attraction-images\.gitkeep"
```

- [ ] **Step 3: Create storage symlink**

```powershell
php artisan storage:link
```
Expected: `The [public/storage] link has been connected to [storage/app/public].`

- [ ] **Step 4: Create `database/seeders/AdminUserSeeder.php`**

```php
<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@budgetra.com'],
            [
                'full_name'     => 'Budgetra Admin',
                'password'      => Hash::make('password'),
                'role'          => 'admin',
                'currency_code' => 'PHP',
                'currency_symbol' => '₱',
            ]
        );
    }
}
```

- [ ] **Step 5: Create `database/seeders/DestinationSeeder.php`**

```php
<?php
namespace Database\Seeders;

use App\Models\DestinationCost;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    public function run(): void
    {
        $destinations = [
            ['destination' => 'Batanes',                 'cost_level' => 'Pricey',        'multiplier' => 1.200, 'category' => 'Local'],
            ['destination' => 'Boracay',                 'cost_level' => 'Pricey',        'multiplier' => 1.200, 'category' => 'Local'],
            ['destination' => 'Bohol',                   'cost_level' => 'Moderate',      'multiplier' => 1.000, 'category' => 'Local'],
            ['destination' => 'Cebu City',               'cost_level' => 'Moderate',      'multiplier' => 1.000, 'category' => 'Local'],
            ['destination' => 'El Nido, Palawan',        'cost_level' => 'Pricey',        'multiplier' => 1.200, 'category' => 'Local'],
            ['destination' => 'Coron, Palawan',          'cost_level' => 'Pricey',        'multiplier' => 1.200, 'category' => 'Local'],
            ['destination' => 'Siargao Island',          'cost_level' => 'Moderate',      'multiplier' => 1.000, 'category' => 'Local'],
            ['destination' => 'Tagaytay',                'cost_level' => 'Budget-friendly','multiplier' => 0.800,'category' => 'Local'],
            ['destination' => 'Davao City',              'cost_level' => 'Moderate',      'multiplier' => 1.000, 'category' => 'Local'],
            ['destination' => 'Sagada',                  'cost_level' => 'Budget-friendly','multiplier' => 0.800,'category' => 'Local'],
            ['destination' => 'Bali',                    'cost_level' => 'Moderate',      'multiplier' => 1.000, 'category' => 'International'],
            ['destination' => 'Bangkok',                 'cost_level' => 'Budget-friendly','multiplier' => 0.800,'category' => 'International'],
            ['destination' => 'Tokyo',                   'cost_level' => 'Very Expensive','multiplier' => 1.500, 'category' => 'International'],
            ['destination' => 'Singapore',               'cost_level' => 'Very Expensive','multiplier' => 1.500, 'category' => 'International'],
            ['destination' => 'Kuala Lumpur',            'cost_level' => 'Budget-friendly','multiplier' => 0.800,'category' => 'International'],
            ['destination' => 'Seoul',                   'cost_level' => 'Pricey',        'multiplier' => 1.200, 'category' => 'International'],
            ['destination' => 'Ho Chi Minh City',        'cost_level' => 'Budget-friendly','multiplier' => 0.800,'category' => 'International'],
            ['destination' => 'Phuket',                  'cost_level' => 'Moderate',      'multiplier' => 1.000, 'category' => 'International'],
        ];

        foreach ($destinations as $d) {
            DestinationCost::firstOrCreate(
                ['destination' => $d['destination']],
                $d
            );
        }
    }
}
```

- [ ] **Step 6: Update `database/seeders/DatabaseSeeder.php`**

```php
<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            DestinationSeeder::class,
        ]);
    }
}
```

- [ ] **Step 7: Run seeders**

```powershell
php artisan db:seed
```
Expected:
```
Seeding: Database\Seeders\AdminUserSeeder
Seeded:  Database\Seeders\AdminUserSeeder (Xms)
Seeding: Database\Seeders\DestinationSeeder
Seeded:  Database\Seeders\DestinationSeeder (Xms)
```

- [ ] **Step 8: Verify admin user and destinations exist**

```powershell
php artisan tinker --execute="echo App\Models\User::where('role','admin')->count() . ' admin(s), ' . App\Models\DestinationCost::count() . ' destinations';"
```
Expected: `1 admin(s), 18 destinations`

- [ ] **Step 9: Commit**

```powershell
git add public/css storage/app/public database/seeders
git commit -m "feat: add CSS, storage dirs, admin user seeder, destination seeder"
```

---

### Task 13: Scaffold Verification

**Files:**
- Create: `tests/Feature/ScaffoldTest.php`

**Interfaces:**
- Consumes: all routes, controllers, views, and layouts from Tasks 4–11
- Produces: `php artisan test` exits 0; all routes return HTTP 200 (middleware bypassed); `php artisan route:list` shows 35+ routes

- [ ] **Step 1: Create `tests/Feature/ScaffoldTest.php`**

```php
<?php
namespace Tests\Feature;

use Tests\TestCase;

class ScaffoldTest extends TestCase
{
    public function test_traveler_routes_return_200(): void
    {
        $routes = [
            '/dashboard',
            '/trips',
            '/trips/type',
            '/trips/create',
            '/savings',
            '/itinerary',
            '/attractions',
            '/alerts',
            '/reports',
            '/expenses',
            '/expenses/create',
        ];
        foreach ($routes as $route) {
            $this->withoutMiddleware()
                 ->get($route)
                 ->assertStatus(200);
        }
    }

    public function test_admin_routes_return_200(): void
    {
        $routes = [
            '/admin',
            '/admin/users',
            '/admin/destinations',
            '/admin/attractions',
            '/admin/reviews',
            '/admin/integrations',
            '/admin/reports',
        ];
        foreach ($routes as $route) {
            $this->withoutMiddleware()
                 ->get($route)
                 ->assertStatus(200);
        }
    }

    public function test_root_redirects(): void
    {
        $this->withoutMiddleware()
             ->get('/')
             ->assertRedirect('/dashboard');
    }
}
```

- [ ] **Step 2: Run the tests**

```powershell
php artisan test tests/Feature/ScaffoldTest.php --verbose
```
Expected:
```
PASS  Tests\Feature\ScaffoldTest
✓ traveler routes return 200
✓ admin routes return 200
✓ root redirects
Tests: 3 passed
```

- [ ] **Step 3: Confirm route list**

```powershell
php artisan route:list --compact
```
Expected: 35+ routes, all named correctly (e.g. `dashboard`, `trips.index`, `admin.dashboard`, etc.)

- [ ] **Step 4: Start dev server and spot-check in browser**

```powershell
php artisan serve
```
Visit `http://localhost:8000/dashboard` — expect to see the traveler layout with sidebar and the "Dashboard" heading.  
Visit `http://localhost:8000/admin` — expect admin layout with admin sidebar and "Overview" heading.

- [ ] **Step 5: Final commit**

```powershell
git add tests/Feature/ScaffoldTest.php
git commit -m "feat: add scaffold verification tests — all routes return 200"
```

---

## Summary

| Task | Deliverable | Test |
|---|---|---|
| 1 | Laravel 11 + Livewire installed, .env configured | `php artisan about` |
| 2 | 13 migrations | `php artisan migrate` |
| 3 | 12 Eloquent models | `php artisan tinker` |
| 4 | Middleware + routes | `php artisan route:list` |
| 5 | 15 controller stubs | No errors in route:list |
| 6 | 4 service stubs | `php artisan tinker` |
| 7 | 3 Blade layouts | Views extend correctly |
| 8 | 4 Blade components | Rendered in layouts |
| 9 | 7 admin view stubs | HTTP 200 |
| 10 | 13 traveler view stubs | HTTP 200 |
| 11 | 12 Livewire stubs | Component resolves |
| 12 | CSS + storage + seeders | `php artisan db:seed` |
| 13 | Feature test: all routes 200 | `php artisan test` passes |
