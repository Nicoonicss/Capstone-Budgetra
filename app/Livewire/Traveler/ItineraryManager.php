<?php
namespace App\Livewire\Traveler;

use App\Models\Attraction;
use App\Models\Itinerary;
use App\Models\Trip;
use Carbon\CarbonPeriod;
use Livewire\Component;

class ItineraryManager extends Component
{
    public ?int    $selectedTripId    = null;
    public ?string $selectedDate      = null;
    public bool    $showGenerateModal = false;
    public bool    $showDayModal      = false;

    public function mount(): void
    {
        $trips = $this->getTripsProperty();
        if ($trips->count() === 1) {
            $this->selectedTripId = $trips->first()->id;
        }
    }

    public function updatedSelectedTripId(): void
    {
        $this->selectedDate      = null;
        $this->showGenerateModal = false;
        $this->showDayModal      = false;
        if ($this->selectedTripId) {
            $trip = $this->getSelectedTripProperty();
            if ($trip) {
                $this->dispatch('trip-selected',
                    start:  $trip->start_date->toDateString(),
                    end:    $trip->end_date->clone()->addDay()->toDateString(),
                    events: $this->getEventsProperty(),
                );
            }
        } else {
            $this->dispatch('trip-cleared');
        }
    }

    public function selectTrip(int $tripId): void
    {
        $trip = Trip::where('id', $tripId)->where('user_id', auth()->id())->firstOrFail();
        $this->selectedTripId    = $trip->id;
        $this->selectedDate      = null;
        $this->showGenerateModal = false;
        $this->showDayModal      = false;
        $this->dispatch('trip-selected',
            start:  $trip->start_date->toDateString(),
            end:    $trip->end_date->clone()->addDay()->toDateString(),
            events: $this->getEventsProperty(),
        );
    }

    public function goBack(): void
    {
        $this->selectedTripId  = null;
        $this->selectedDate    = null;
        $this->showGenerateModal = false;
        $this->showDayModal    = false;
    }

    public function selectDay(string $date): void
    {
        $this->selectedDate = $date;

        $hasItems = Itinerary::where('trip_id', $this->selectedTripId)
            ->whereDate('start_datetime', $date)
            ->exists();

        if ($hasItems) {
            $this->showDayModal      = true;
            $this->showGenerateModal = false;
        } else {
            $this->showGenerateModal = true;
            $this->showDayModal      = false;
        }
    }

    public function closeModals(): void
    {
        $this->showGenerateModal = false;
        $this->showDayModal      = false;
    }

    public function deleteItem(int $itemId): void
    {
        Itinerary::where('id', $itemId)
            ->where('trip_id', $this->selectedTripId)
            ->delete();

        // If no items left, close day modal
        if (!Itinerary::where('trip_id', $this->selectedTripId)
                ->whereDate('start_datetime', $this->selectedDate)
                ->exists()) {
            $this->showDayModal = false;
        }

        $this->dispatch('trip-changed',
            start:  $this->selectedTrip->start_date->toDateString(),
            end:    $this->selectedTrip->end_date->clone()->addDay()->toDateString(),
            events: $this->getEventsProperty(),
        );
    }

    public function generateItinerary(): void
    {
        $trip = $this->selectedTrip;
        if (!$trip || !$this->selectedDate) return;

        $dateStr = $this->selectedDate;
        $dest    = $trip->destination;

        Itinerary::where('trip_id', $trip->id)
            ->whereDate('start_datetime', $dateStr)
            ->delete();

        $attractions = Attraction::where('destination', 'LIKE', '%' . $dest . '%')
            ->orWhere('name', 'LIKE', '%' . $dest . '%')
            ->orderBy('rating', 'desc')
            ->get();

        $isFirst = $dateStr === $trip->start_date->toDateString();
        $isLast  = $dateStr === $trip->end_date->toDateString();
        $attrIdx = 0;

        $dinners    = ['Dinner at local restaurant', 'Seafood dinner', 'Traditional dinner'];

        if ($isFirst) {
            $this->createItem($trip->id, $dateStr, '16:00', 'Arrival in ' . $dest, 'Flight', 'Welcome to ' . $dest . '!');
            $this->createItem($trip->id, $dateStr, '17:00', 'Hotel Check-in', 'Hotel', 'Settle in and rest up.');
            $this->createItem($trip->id, $dateStr, '19:00', $dinners[0], 'Activity', null);
        } elseif ($isLast) {
            $this->createItem($trip->id, $dateStr, '07:00', 'Buffet breakfast', 'Activity', null);
            $this->createItem($trip->id, $dateStr, '09:00', 'Hotel Check-out', 'Hotel', 'Pack up and check out.');
            $this->createItem($trip->id, $dateStr, '12:00', 'Departure from ' . $dest, 'Flight', 'Safe travels home!');
        } else {
            $this->createItem($trip->id, $dateStr, '07:30', 'Breakfast at hotel in ' . $dest, 'Activity', null);
            $a1 = $this->nextAttraction($attractions, $attrIdx++, $dest);
            $this->createItem($trip->id, $dateStr, '10:00', $a1['title'], 'Activity', $a1['note']);
            $this->createItem($trip->id, $dateStr, '12:30', 'Lunch at local restaurant', 'Activity', null);
            $a2 = $this->nextAttraction($attractions, $attrIdx++, $dest);
            $this->createItem($trip->id, $dateStr, '14:00', $a2['title'], 'Activity', $a2['note']);
            $a3 = $this->nextAttraction($attractions, $attrIdx++, $dest);
            $this->createItem($trip->id, $dateStr, '16:30', $a3['title'], 'Activity', $a3['note']);
            $this->createItem($trip->id, $dateStr, '19:00', $dinners[0], 'Activity', null);
        }

        $this->showGenerateModal = false;
        $this->showDayModal      = false;

        $this->dispatch('trip-changed',
            start:  $trip->start_date->toDateString(),
            end:    $trip->end_date->clone()->addDay()->toDateString(),
            events: $this->getEventsProperty(),
        );
    }

    private function createItem(int $tripId, string $date, string $time, string $title, string $type, ?string $notes): void
    {
        Itinerary::create([
            'trip_id'        => $tripId,
            'title'          => $title,
            'type'           => $type,
            'start_datetime' => $date . ' ' . $time . ':00',
            'notes'          => $notes,
        ]);
    }

    private function nextAttraction($attractions, int $idx, string $dest): array
    {
        if ($attractions->isEmpty()) {
            return ['title' => 'Sightseeing in ' . $dest, 'note' => null];
        }
        $attr = $attractions->get($idx % $attractions->count());
        return [
            'title' => 'Visit ' . $attr->name,
            'note'  => $attr->description ? \Str::limit($attr->description, 80) : null,
        ];
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

    public function getEventsProperty(): array
    {
        if (!$this->selectedTripId) return [];

        $colorMap = [
            'Flight'         => ['bg' => '#EFF6FF', 'border' => '#1D4ED8', 'text' => '#1D4ED8', 'icon' => 'plane'],
            'Hotel'          => ['bg' => '#F0FDF4', 'border' => '#16A34A', 'text' => '#16A34A', 'icon' => 'bed'],
            'Transportation' => ['bg' => '#FFF7ED', 'border' => '#D97706', 'text' => '#D97706', 'icon' => 'car'],
            'Activity'       => ['bg' => '#F5EDE7', 'border' => '#8B3A10', 'text' => '#8B3A10', 'icon' => 'camera'],
        ];

        return Itinerary::where('trip_id', $this->selectedTripId)
            ->orderBy('start_datetime')
            ->get()
            ->map(function ($i) use ($colorMap) {
                $c = $colorMap[$i->type] ?? $colorMap['Activity'];
                return [
                    'title'           => $i->title,
                    'start'           => $i->start_datetime->format('Y-m-d\TH:i:s'),
                    'backgroundColor' => $c['bg'],
                    'borderColor'     => $c['border'],
                    'textColor'       => $c['text'],
                    'extendedProps'   => ['icon' => $c['icon'], 'type' => $i->type],
                    'display'         => 'block',
                ];
            })
            ->toArray();
    }

    public function getDayItemsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->selectedTripId || !$this->selectedDate) return collect();
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
