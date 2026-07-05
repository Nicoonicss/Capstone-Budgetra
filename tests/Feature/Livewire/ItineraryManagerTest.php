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
