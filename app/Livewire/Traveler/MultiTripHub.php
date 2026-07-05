<?php
namespace App\Livewire\Traveler;

use App\Models\Trip;
use Carbon\Carbon;
use Livewire\Component;

class MultiTripHub extends Component
{
    public string $search         = '';
    public array  $compareIds     = [];
    public bool   $showComparison = false;

    public function toggleCompare(int $tripId): void
    {
        if (in_array($tripId, $this->compareIds)) {
            $this->compareIds = array_values(array_filter($this->compareIds, fn($id) => $id !== $tripId));
        } else {
            if (count($this->compareIds) < 2) {
                $this->compareIds[] = $tripId;
            }
        }
    }

    public function openComparison(): void
    {
        if (count($this->compareIds) === 2) {
            $this->showComparison = true;
        }
    }

    public function closeComparison(): void
    {
        $this->showComparison = false;
    }

    public function clearCompare(): void
    {
        $this->compareIds     = [];
        $this->showComparison = false;
    }

    public function getTripsProperty()
    {
        $query = auth()->user()->trips()->latest('start_date');
        if ($this->search) {
            $query->where('destination', 'like', "%{$this->search}%");
        }
        return $query->get()->map(function (Trip $trip) {
            $today = Carbon::today();
            $spent = $trip->expenses()->sum('amount');
            $days  = max(1, (int) $trip->start_date->diffInDays($trip->end_date));
            $trip->setAttribute('total_spent', (float) $spent);
            $trip->setAttribute('pct_used',    $trip->budget_limit > 0 ? round($spent / $trip->budget_limit * 100) : 0);
            $trip->setAttribute('days',        $days);
            $trip->setAttribute('daily_avg',   round($spent / $days, 2));
            $trip->setAttribute('status',
                $trip->start_date->gt($today) ? 'upcoming' :
                ($trip->end_date->lt($today)  ? 'past'     : 'active'));
            return $trip;
        });
    }

    public function getActiveTripsProperty()
    {
        return $this->trips->whereIn('status', ['upcoming', 'active'])->values();
    }

    public function getPastTripsProperty()
    {
        return $this->trips->where('status', 'past')->values();
    }

    public function getTotalsProperty(): array
    {
        $all = $this->trips;
        return [
            'count'  => $all->count(),
            'budget' => $all->sum('budget_limit'),
            'spent'  => $all->sum('total_spent'),
        ];
    }

    public function getCompareTripsProperty(): array
    {
        if (count($this->compareIds) !== 2) return [];
        return $this->trips->whereIn('id', $this->compareIds)->values()->toArray();
    }

    public function render()
    {
        return view('livewire.traveler.multi-trip-hub');
    }
}
