<?php
namespace App\Livewire\Traveler;

use App\Models\Trip;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['active' => 'saved-trips'])]
class SavedTrips extends Component
{
    public ?int $detailTripId  = null;
    public ?int $deleteTripId  = null;
    public string $deleteTripName = '';

    public function showDetail(int $id): void
    {
        $this->detailTripId = $id;
    }

    public function closeDetail(): void
    {
        $this->detailTripId = null;
    }

    public function confirmDelete(int $id): void
    {
        $trip = $this->trips->firstWhere('id', $id);
        $origin = $trip->origin ?? 'Manila';
        $dest   = $trip->destination ?? 'destination';
        $this->deleteTripId   = $id;
        $this->deleteTripName = $origin . ' to ' . $dest;
    }

    public function cancelDelete(): void
    {
        $this->deleteTripId   = null;
        $this->deleteTripName = '';
    }

    public function deleteTrip(): void
    {
        if (!$this->deleteTripId) return;
        $trip = Trip::find($this->deleteTripId);
        if ($trip && $trip->user_id === auth()->id()) {
            $trip->delete();
        }
        if ($this->detailTripId === $this->deleteTripId) {
            $this->detailTripId = null;
        }
        $this->deleteTripId   = null;
        $this->deleteTripName = '';
    }

    public function getTripsProperty()
    {
        return auth()->user()->trips()
            ->latest('created_at')
            ->get()
            ->map(function (Trip $trip) {
                $today = Carbon::today();
                $days  = max(1, (int) $trip->start_date->diffInDays($trip->end_date));
                $trip->setAttribute('days', $days);
                $trip->setAttribute('status',
                    $trip->start_date->gt($today) ? 'upcoming' :
                    ($trip->end_date->lt($today)  ? 'past'     : 'active'));
                return $trip;
            });
    }

    public function getDetailTripProperty(): ?Trip
    {
        if (!$this->detailTripId) return null;
        $trip = $this->trips->firstWhere('id', $this->detailTripId);
        return $trip ?: null;
    }

    public function render()
    {
        return view('livewire.traveler.saved-trips');
    }
}
