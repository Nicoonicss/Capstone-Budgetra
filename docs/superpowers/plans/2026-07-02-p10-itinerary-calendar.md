# P10: Calendar-Based Itinerary Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the itinerary page as a Livewire `ItineraryManager` component with a horizontal day-strip calendar, a day content area showing items for the selected date, and a slide-up "Add Item" panel.

**Architecture:** The existing `ItineraryManager` Livewire stub (at `app/Livewire/Traveler/ItineraryManager.php`) is replaced in full. The existing `ItineraryController` stays unchanged — items are still created via `POST /itinerary` and deleted via `DELETE /itinerary/{item}`. The Livewire component handles the calendar UI, date selection, and the add-item slide panel entirely client-side with Livewire properties; it embeds standard HTML forms that POST to existing controller routes. The itinerary index view (`resources/views/traveler/itinerary/index.blade.php`) becomes a thin wrapper that embeds the Livewire component.

**Tech Stack:** Laravel 13, Livewire 3, `public/css/budgetra.css`

## Global Constraints

- Laravel 13.18.0, PHP 8.3.12, MySQL
- No git — skip ALL git/commit steps
- `Itinerary` model table: `itinerary` (singular). Fields: `trip_id`, `title`, `type`, `start_datetime` (datetime cast), `end_datetime` (datetime cast), `location`, `notes`
- `Itinerary` does NOT have a `date` column — use `start_datetime->toDateString()` to group by date
- `Itinerary` does NOT have an `attraction_id` column yet (added in P11) — do NOT reference it here
- Existing routes (all exist, do NOT add new ones):
  - `POST /itinerary` → `ItineraryController::store()` — fields: `trip_id`, `title`, `type`, `start_datetime`, `notes`
  - `DELETE /itinerary/{item}` → `ItineraryController::destroy()`
- Valid `type` values (from existing controller tests): Activity, Transport, Accommodation, Meal
- The existing `Itinerary` model uses `$table = 'itinerary'`
- Run tests: `php artisan test <test-file>`

---

## File Structure

- **REPLACE:** `app/Livewire/Traveler/ItineraryManager.php`
- **REPLACE:** `resources/views/livewire/traveler/itinerary-manager.blade.php`
- **REPLACE:** `resources/views/traveler/itinerary/index.blade.php`
- **CREATE:** `tests/Feature/Livewire/ItineraryManagerTest.php`

---

### Task 1: ItineraryManager Livewire Component

**Files:**
- Replace: `app/Livewire/Traveler/ItineraryManager.php`
- Replace: `resources/views/livewire/traveler/itinerary-manager.blade.php`
- Replace: `resources/views/traveler/itinerary/index.blade.php`
- Test: `tests/Feature/Livewire/ItineraryManagerTest.php`

**Interfaces:**
- Produces: `/itinerary` page with calendar day strip + day item list + add item panel
- Add item panel posts to `POST /itinerary` (existing route) via a standard form embedded in the Livewire view

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Livewire/ItineraryManagerTest.php`:

```php
<?php
namespace Tests\Feature\Livewire;

use App\Livewire\Traveler\ItineraryManager;
use App\Models\Itinerary;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ItineraryManagerTest extends TestCase
{
    use RefreshDatabase;

    private function makeTrip(User $user): Trip
    {
        return Trip::factory()->create([
            'user_id'    => $user->id,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addDays(3)->toDateString(),
        ]);
    }

    public function test_itinerary_page_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/itinerary')->assertStatus(200);
    }

    public function test_component_mounts_with_first_trip(): void
    {
        $user = User::factory()->create();
        $trip = $this->makeTrip($user);
        Livewire::actingAs($user)
            ->test(ItineraryManager::class)
            ->assertSet('selectedTripId', $trip->id);
    }

    public function test_selecting_trip_loads_days(): void
    {
        $user = User::factory()->create();
        $trip = $this->makeTrip($user);
        Livewire::actingAs($user)
            ->test(ItineraryManager::class)
            ->call('selectTrip', $trip->id)
            ->assertSet('selectedTripId', $trip->id);
    }

    public function test_selecting_a_day(): void
    {
        $user = User::factory()->create();
        $trip = $this->makeTrip($user);
        $date = now()->toDateString();
        Livewire::actingAs($user)
            ->test(ItineraryManager::class)
            ->call('selectDay', $date)
            ->assertSet('selectedDate', $date);
    }

    public function test_toggle_add_panel(): void
    {
        $user = User::factory()->create();
        Livewire::actingAs($user)
            ->test(ItineraryManager::class)
            ->assertSet('showPanel', false)
            ->call('togglePanel')
            ->assertSet('showPanel', true);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```
php artisan test tests/Feature/Livewire/ItineraryManagerTest.php
```

Expected: FAIL — `ItineraryManager` has no `$selectedTripId` property, no `selectTrip` method.

- [ ] **Step 3: Replace `app/Livewire/Traveler/ItineraryManager.php`**

```php
<?php
namespace App\Livewire\Traveler;

use App\Models\Itinerary;
use App\Models\Trip;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['active' => 'itinerary'])]
class ItineraryManager extends Component
{
    public ?int    $selectedTripId = null;
    public ?string $selectedDate   = null;
    public bool    $showPanel      = false;

    // Add item form fields
    public string $newTime        = '09:00';
    public string $newTitle       = '';
    public string $newType        = 'Activity';
    public string $newNotes       = '';

    public function mount(): void
    {
        $trip = auth()->user()->trips()->latest()->first();
        if ($trip) {
            $this->selectedTripId = $trip->id;
            $this->selectedDate   = $trip->start_date->toDateString();
        }
    }

    public function selectTrip(int $tripId): void
    {
        $trip = Trip::where('id', $tripId)->where('user_id', auth()->id())->firstOrFail();
        $this->selectedTripId = $trip->id;
        $this->selectedDate   = $trip->start_date->toDateString();
        $this->showPanel      = false;
    }

    public function selectDay(string $date): void
    {
        $this->selectedDate = $date;
        $this->showPanel    = false;
    }

    public function togglePanel(): void
    {
        $this->showPanel  = !$this->showPanel;
        $this->newTime    = '09:00';
        $this->newTitle   = '';
        $this->newNotes   = '';
    }

    public function getTripsProperty()
    {
        return auth()->user()->trips()->orderByDesc('start_date')->get();
    }

    public function getSelectedTripProperty(): ?Trip
    {
        if (!$this->selectedTripId) return null;
        return Trip::where('id', $this->selectedTripId)
                   ->where('user_id', auth()->id())
                   ->first();
    }

    public function getDaysProperty(): array
    {
        $trip = $this->selectedTrip;
        if (!$trip) return [];

        $items    = Itinerary::where('trip_id', $trip->id)->get();
        $datesDot = $items->map(fn($i) => $i->start_datetime->toDateString())->unique()->values();

        $period = CarbonPeriod::create($trip->start_date, $trip->end_date);
        $days   = [];
        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $days[]  = [
                'date'     => $dateStr,
                'dayName'  => $date->format('D'),
                'dayNum'   => $date->format('j'),
                'monthAbb' => $date->format('M'),
                'hasItems' => $datesDot->contains($dateStr),
            ];
        }
        return $days;
    }

    public function getDayItemsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->selectedTripId || !$this->selectedDate) {
            return collect();
        }
        return Itinerary::where('trip_id', $this->selectedTripId)
            ->whereDate('start_datetime', $this->selectedDate)
            ->orderBy('start_datetime')
            ->get();
    }

    public function render()
    {
        return view('livewire.traveler.itinerary-manager');
    }
}
```

- [ ] **Step 4: Replace `resources/views/livewire/traveler/itinerary-manager.blade.php`**

```html
<div>
    <div class="bt-flex-between mb-24">
        <div>
            <h1>Itinerary</h1>
            <p class="text-muted">Plan your day-by-day activities.</p>
        </div>
        @if ($this->selectedTrip)
        <button class="bt-btn bt-btn-primary" wire:click="togglePanel">
            <i class="fa-solid {{ $showPanel ? 'fa-xmark' : 'fa-plus' }}"></i>
            {{ $showPanel ? 'Cancel' : 'Add Item' }}
        </button>
        @endif
    </div>

    {{-- Trip selector --}}
    @if ($this->trips->isEmpty())
    <div class="bt-empty">
        <div class="bt-empty-icon">📅</div>
        <h3>No trips yet</h3>
        <p>Create a trip first to build your itinerary.</p>
        <a href="{{ route('trips.plan') }}" class="bt-btn bt-btn-primary">Plan a Trip</a>
    </div>
    @else

    <div class="bt-form-group mb-16" style="max-width:320px;">
        <label class="bt-label">Select Trip</label>
        <select class="bt-select" wire:model.live="selectedTripId" wire:change="selectTrip($event.target.value)">
            @foreach ($this->trips as $trip)
            <option value="{{ $trip->id }}" {{ $selectedTripId == $trip->id ? 'selected' : '' }}>
                {{ $trip->destination }} · {{ $trip->start_date->format('M j') }}–{{ $trip->end_date->format('M j, Y') }}
            </option>
            @endforeach
        </select>
    </div>

    @if ($this->selectedTrip)

    {{-- Day strip --}}
    <div class="bt-day-strip">
        @foreach ($this->days as $day)
        <div class="bt-day-pill {{ $selectedDate === $day['date'] ? 'active' : '' }} {{ $day['hasItems'] ? 'has-items' : '' }}"
             wire:click="selectDay('{{ $day['date'] }}')">
            <div class="day-name">{{ $day['dayName'] }}</div>
            <div class="day-num">{{ $day['dayNum'] }}</div>
            <div class="day-name">{{ $day['monthAbb'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Day heading --}}
    @if ($selectedDate)
    <div class="bt-flex-between mb-16">
        <h2>{{ \Carbon\Carbon::parse($selectedDate)->format('l, F j') }}</h2>
        <span class="text-muted" style="font-size:13px;">{{ count($this->dayItems) }} items</span>
    </div>

    {{-- Day items --}}
    @forelse ($this->dayItems as $item)
    <div class="bt-itinerary-item">
        <div class="bt-itinerary-time">{{ $item->start_datetime->format('g:i A') }}</div>
        <div class="bt-itinerary-body">
            <div class="bt-itinerary-title">{{ $item->title }}</div>
            @if ($item->notes)
            <div class="bt-itinerary-notes">{{ $item->notes }}</div>
            @endif
        </div>
        <span class="bt-chip bt-chip-brown" style="flex-shrink:0;">{{ $item->type }}</span>
        <form method="POST" action="{{ route('itinerary.destroy', $item) }}"
              onsubmit="return confirm('Remove this item?')">
            @csrf @method('DELETE')
            <button class="bt-btn bt-btn-outline bt-btn-sm" type="submit" title="Remove">
                <i class="fa-solid fa-trash" style="font-size:11px;"></i>
            </button>
        </form>
    </div>
    @empty
    <div class="bt-empty" style="padding:32px 24px;">
        <div class="bt-empty-icon">📌</div>
        <p>No items for this day. Add one above!</p>
    </div>
    @endforelse

    {{-- Add item slide panel --}}
    @if ($showPanel)
    <div class="bt-slide-panel">
        <h3 class="mb-16">Add Item — {{ \Carbon\Carbon::parse($selectedDate)->format('M j') }}</h3>
        <form method="POST" action="{{ route('itinerary.store') }}">
            @csrf
            <input type="hidden" name="trip_id" value="{{ $selectedTripId }}">
            {{-- Combine selectedDate + newTime into start_datetime --}}
            <input type="hidden" name="start_datetime" value="{{ $selectedDate }} {{ $newTime }}:00">

            <div class="bt-grid-2">
                <div class="bt-form-group">
                    <label class="bt-label">Time</label>
                    <input type="time" wire:model="newTime"
                           class="bt-input" value="{{ $newTime }}">
                </div>
                <div class="bt-form-group">
                    <label class="bt-label">Type</label>
                    <select wire:model="newType" name="type" class="bt-select">
                        <option>Activity</option>
                        <option>Transport</option>
                        <option>Accommodation</option>
                        <option>Meal</option>
                    </select>
                </div>
            </div>

            <div class="bt-form-group">
                <label class="bt-label">Title</label>
                <input type="text" wire:model="newTitle" name="title"
                       class="bt-input" placeholder="e.g. Visit Intramuros" required>
            </div>

            <div class="bt-form-group">
                <label class="bt-label">Notes (optional)</label>
                <textarea wire:model="newNotes" name="notes"
                          class="bt-textarea" placeholder="Any notes or reminders..."></textarea>
            </div>

            <div class="bt-flex-between">
                <button type="button" class="bt-btn bt-btn-outline" wire:click="togglePanel">Cancel</button>
                <button type="submit" class="bt-btn bt-btn-primary">
                    <i class="fa-solid fa-plus"></i> Add to Itinerary
                </button>
            </div>
        </form>
    </div>
    @endif

    @endif {{-- selectedDate --}}
    @endif {{-- selectedTrip --}}
    @endif {{-- trips not empty --}}
</div>
```

- [ ] **Step 5: Replace `resources/views/traveler/itinerary/index.blade.php`**

```html
@extends('layouts.app')
@section('title', 'Itinerary')
@section('content')
@livewire('traveler.itinerary-manager')
@endsection
```

- [ ] **Step 6: Run tests — expect PASS**

```
php artisan test tests/Feature/Livewire/ItineraryManagerTest.php
```

Expected: 5 tests, all pass.

- [ ] **Step 7: Run existing itinerary tests**

```
php artisan test tests/Feature/Itinerary
```

Expected: all pass (controller is unchanged).

- [ ] **Step 8: Run full suite for regressions**

```
php artisan test
```

Expected: all previously passing tests still pass.

## Report Contract

Write your full report to:
`C:\Users\ASUS\AppData\Local\Temp\claude\c--phpsite-Capstone---Budgetra\55e34b67-7087-4f9b-8add-8becf1178a87\scratchpad\p10-report.md`

Return ONLY: status (DONE/DONE_WITH_CONCERNS/NEEDS_CONTEXT/BLOCKED), files changed, one-line test summary, concerns.
