<?php
namespace App\Livewire\Traveler;

use App\Models\Destination;
use App\Models\SavingsGoal;
use App\Models\Trip;
use App\Models\TripBudget;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['active' => 'trips'])]
class TripPlannerWizard extends Component
{
    // ── Trips list view (shown when user already has trips) ─
    public bool   $showList      = false;
    public ?int   $tripToDelete  = null;

    // ── Navigation ─────────────────────────────────────────
    public int    $step     = 1;

    // ── Step 1: scope ──────────────────────────────────────
    public string $tripScope = '';

    // ── Step 2: destination ────────────────────────────────
    public ?int   $destinationId   = null;
    public string $destinationName = '';
    public string $destSearch      = '';

    // ── Step 3: calendar ───────────────────────────────────
    public string $startDate  = '';
    public string $endDate    = '';
    public int    $calYear;
    public int    $calMonth;

    // ── Step 4: group + budget ─────────────────────────────
    public string $groupType  = '';
    public int    $travelers  = 1;
    public string $budgetTier = '';

    // ── Step 5: editable cost categories ──────────────────
    public float  $transportation = 0;
    public float  $accommodation  = 0;
    public float  $food           = 0;
    public float  $attractions    = 0;
    public float  $shopping       = 0;
    public float  $emergency      = 0;
    public float  $budgetLimit    = 0;
    public string $editingCategory = '';

    // ── Cost rate tables (₱ per trip) ─────────────────────
    private const RATES = [
        'local' => [
            'Shoestring' => ['transport_base' => 2000,   'transport_daily' => 100,  'accommodation_night' => 800,   'food_day' => 350,  'attractions_day' => 150,  'shopping_per_person' => 800  ],
            'Mid-range'  => ['transport_base' => 8000,   'transport_daily' => 400,  'accommodation_night' => 3000,  'food_day' => 1200, 'attractions_day' => 600,  'shopping_per_person' => 3000 ],
            'Luxury'     => ['transport_base' => 20000,  'transport_daily' => 1000, 'accommodation_night' => 8000,  'food_day' => 3000, 'attractions_day' => 1500, 'shopping_per_person' => 10000],
        ],
        'international' => [
            'Shoestring' => ['transport_base' => 25000,  'transport_daily' => 500,  'accommodation_night' => 2000,  'food_day' => 1000, 'attractions_day' => 500,  'shopping_per_person' => 2000 ],
            'Mid-range'  => ['transport_base' => 55000,  'transport_daily' => 1000, 'accommodation_night' => 5000,  'food_day' => 2500, 'attractions_day' => 1500, 'shopping_per_person' => 7000 ],
            'Luxury'     => ['transport_base' => 150000, 'transport_daily' => 3000, 'accommodation_night' => 15000, 'food_day' => 6000, 'attractions_day' => 4000, 'shopping_per_person' => 25000],
        ],
    ];

    public function mount(): void
    {
        $this->calYear  = (int) date('Y');
        $this->calMonth = (int) date('n');
        // /trips/plan always starts the wizard fresh; /trips shows the list if trips exist
        $this->showList = !request()->routeIs('trips.plan')
                        && auth()->user()->trips()->exists();
    }

    public function startNewTrip(): mixed
    {
        return $this->redirect(route('trips.plan'), navigate: true);
    }

    public function confirmDelete(int $id): void
    {
        $this->tripToDelete = $id;
    }

    public function cancelDelete(): void
    {
        $this->tripToDelete = null;
    }

    public function deleteTrip(): void
    {
        if (!$this->tripToDelete) return;
        $trip = Trip::where('id', $this->tripToDelete)->where('user_id', auth()->id())->firstOrFail();
        $trip->delete();
        $this->tripToDelete = null;
        if (!auth()->user()->trips()->exists()) {
            $this->showList = false;
            $this->step     = 1;
        }
    }

    public function getMyTripsProperty()
    {
        return auth()->user()->trips()
            ->withSum('budgets as total_estimated', 'estimated_cost')
            ->withSum('expenses as total_spent', 'amount')
            ->latest('start_date')
            ->get();
    }

    // ── Step 1 ─────────────────────────────────────────────
    public function selectScope(string $scope): void
    {
        $this->tripScope = $scope;
        $this->step = 2;
    }

    // ── Step 2 ─────────────────────────────────────────────
    public function selectDestination(int $id): void
    {
        $dest = Destination::findOrFail($id);
        $this->destinationId   = $dest->id;
        $this->destinationName = $dest->name;
        $this->step = 3;
    }

    // ── Step 3: calendar ───────────────────────────────────
    public function prevMonth(): void
    {
        if ($this->calMonth === 1) { $this->calMonth = 12; $this->calYear--; }
        else $this->calMonth--;
    }

    public function nextMonth(): void
    {
        if ($this->calMonth === 12) { $this->calMonth = 1; $this->calYear++; }
        else $this->calMonth++;
    }

    public function selectDay(string $date): void
    {
        if ($date < date('Y-m-d')) return;

        if (!$this->startDate || ($this->startDate && $this->endDate)) {
            $this->startDate = $date;
            $this->endDate   = '';
        } elseif ($date < $this->startDate) {
            $this->endDate   = $this->startDate;
            $this->startDate = $date;
        } else {
            $this->endDate = $date;
        }
    }

    public function proceedFromCalendar(): void
    {
        if (empty($this->startDate)) {
            $this->dispatch('calendar-validation-error');
            return;
        }

        if (empty($this->endDate)) {
            $this->endDate = $this->startDate;
        }

        $this->step = 4;
    }

    // ── Step 4: group + budget ─────────────────────────────
    public function selectGroup(string $group): void
    {
        if ($this->groupType === $group) {
            $this->groupType = '';
            $this->travelers = 1;
            return;
        }
        $this->groupType = $group;
        $this->travelers = match ($group) {
            'Solo'   => 1,
            'Couple' => 2,
            default  => max($this->travelers, 2),
        };
    }

    public function incrementTravelers(): void
    {
        if ($this->travelers < 20) $this->travelers++;
    }

    public function decrementTravelers(): void
    {
        $min = in_array($this->groupType, ['Family', 'Friends']) ? 2 : 1;
        if ($this->travelers > $min) $this->travelers--;
    }

    public function selectBudgetTier(string $tier): void
    {
        $this->budgetTier = $this->budgetTier === $tier ? '' : $tier;
    }

    public function calculateAndProceed(): void
    {
        $missingGroup  = empty($this->groupType);
        $missingBudget = empty($this->budgetTier);

        if ($missingGroup || $missingBudget) {
            $this->dispatch('validation-error',
                missingGroup:  $missingGroup,
                missingBudget: $missingBudget,
            );
            return;
        }

        $this->calculateEstimate();
        $this->step = 5;
    }

    // ── Step 5: inline category editing ───────────────────
    public function startEditing(string $category): void
    {
        $this->editingCategory = $category;
    }

    public function stopEditing(): void
    {
        $this->editingCategory = '';
        $subtotal = $this->transportation + $this->accommodation + $this->food
                  + $this->attractions + $this->shopping;
        $this->emergency   = round($subtotal * 0.05, 2);
        $this->budgetLimit = round($subtotal + $this->emergency, 2);
    }

    // ── Confirm ────────────────────────────────────────────
    public function confirm(): mixed
    {
        $this->validate([
            'destinationName' => 'required|string',
            'startDate'       => 'required|date',
            'endDate'         => 'required|date|after_or_equal:startDate',
            'groupType'       => 'required|in:Solo,Couple,Family,Friends',
            'budgetTier'      => 'required|in:Shoestring,Mid-range,Luxury',
            'budgetLimit'     => 'required|numeric|min:1',
        ]);

        $trip = Trip::create([
            'user_id'       => auth()->id(),
            'destination'   => $this->destinationName,
            'start_date'    => $this->startDate,
            'end_date'      => $this->endDate,
            'num_travelers' => $this->travelers,
            'budget_limit'  => $this->budgetLimit,
            'travel_type'   => $this->groupType,
            'notes'         => "Budget tier: {$this->budgetTier}; Scope: {$this->tripScope}",
        ]);

        foreach ([
            'Transportation'      => $this->transportation,
            'Accommodation'       => $this->accommodation,
            'Food'                => $this->food,
            'Tourist Attractions' => $this->attractions,
            'Shopping'            => $this->shopping,
            'Emergency Funds'     => $this->emergency,
        ] as $cat => $amount) {
            TripBudget::create([
                'trip_id'        => $trip->id,
                'category'       => $cat,
                'estimated_cost' => $amount,
                'actual_spent'   => 0,
            ]);
        }

        SavingsGoal::create([
            'user_id'         => auth()->id(),
            'trip_id'         => $trip->id,
            'goal_name'       => $this->destinationName . ' Trip',
            'target_amount'   => $this->budgetLimit,
            'current_savings' => 0,
            'deadline'        => $this->startDate,
        ]);

        return $this->redirect(route('trips.dashboard', $trip), navigate: true);
    }

    // ── Computed properties ────────────────────────────────
    #[Computed]
    public function destinations()
    {
        $userCountry = auth()->user()->country ?? 'Philippines';
        $query = Destination::orderBy('name');
        if ($this->tripScope === 'local') {
            $query->where('country', $userCountry);
        } else {
            $query->where('country', '!=', $userCountry);
        }
        if ($this->destSearch) {
            $query->where('name', 'like', "%{$this->destSearch}%");
        }
        return $query->get();
    }

    public function getDaysProperty(): int
    {
        if (!$this->startDate || !$this->endDate) return 0;
        return max(1, (int) Carbon::parse($this->startDate)->diffInDays($this->endDate));
    }

    public function getCalendarDaysProperty(): array
    {
        $first    = Carbon::createFromDate($this->calYear, $this->calMonth, 1);
        $total    = $first->daysInMonth;
        $startDow = $first->dayOfWeek;
        $today    = date('Y-m-d');

        $days = array_fill(0, $startDow, null);
        for ($d = 1; $d <= $total; $d++) {
            $date   = sprintf('%04d-%02d-%02d', $this->calYear, $this->calMonth, $d);
            $days[] = [
                'day'     => $d,
                'date'    => $date,
                'isPast'  => $date < $today,
                'isToday' => $date === $today,
                'isStart' => $date === $this->startDate,
                'isEnd'   => $date === $this->endDate,
                'inRange' => $this->startDate && $this->endDate
                             && $date > $this->startDate && $date < $this->endDate,
            ];
        }
        return $days;
    }

    public function getComfortLevelProperty(): string
    {
        return match ($this->budgetTier) {
            'Shoestring' => 'BUDGET',
            'Luxury'     => 'PREMIUM',
            default      => 'STANDARD',
        };
    }

    public function getSmartTipProperty(): string
    {
        return match ($this->budgetTier) {
            'Shoestring' => 'Opt for hostels, local street food, and free attractions to stretch your funds further.',
            'Luxury'     => 'Consider hiring a private guide for exclusive access and a personalized premium experience.',
            default      => 'Book accommodation 2+ weeks early to unlock better rates and ensure availability.',
        };
    }

    public function getVarianceProperty(): float
    {
        return round($this->budgetLimit * 0.05, 2);
    }

    public function getTravelersLabelProperty(): string
    {
        return $this->travelers . ' ' . match ($this->groupType) {
            'Family'  => ($this->travelers === 1 ? 'Person' : 'People'),
            'Friends' => ($this->travelers === 1 ? 'Person' : 'People'),
            'Couple'  => 'Adults',
            default   => 'Adult',
        };
    }

    private function calculateEstimate(): void
    {
        $days  = $this->getDaysProperty();
        $n     = $this->travelers;
        $scope = $this->tripScope ?: 'local';
        $r     = self::RATES[$scope][$this->budgetTier] ?? self::RATES['local']['Mid-range'];
        $rooms = max(1, (int) ceil($n / 2));

        $this->transportation = round(($r['transport_base'] + $r['transport_daily'] * $days) * $n, 2);
        $this->accommodation  = round($r['accommodation_night'] * $days * $rooms, 2);
        $this->food           = round($r['food_day'] * $days * $n, 2);
        $this->attractions    = round($r['attractions_day'] * $days * $n, 2);
        $this->shopping       = round($r['shopping_per_person'] * $n, 2);

        $subtotal = $this->transportation + $this->accommodation + $this->food
                  + $this->attractions + $this->shopping;
        $this->emergency   = round($subtotal * 0.05, 2);
        $this->budgetLimit = round($subtotal + $this->emergency, 2);
    }

    public function render()
    {
        return view('livewire.traveler.trip-planner-wizard');
    }
}
