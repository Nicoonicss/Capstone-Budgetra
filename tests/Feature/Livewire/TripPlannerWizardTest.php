<?php
namespace Tests\Feature\Livewire;

use App\Livewire\Traveler\TripPlannerWizard;
use App\Models\Destination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TripPlannerWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_wizard_page_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/trips/plan')->assertStatus(200);
    }

    public function test_wizard_starts_at_step_one(): void
    {
        $user = User::factory()->create();
        Livewire::actingAs($user)
            ->test(TripPlannerWizard::class)
            ->assertSet('step', 1);
    }

    public function test_can_advance_to_step_two(): void
    {
        $user = User::factory()->create();
        Livewire::actingAs($user)
            ->test(TripPlannerWizard::class)
            ->set('tripScope', 'international')
            ->call('nextStep')
            ->assertSet('step', 2);
    }

    public function test_wizard_creates_trip_on_confirm(): void
    {
        $user = User::factory()->create();
        $dest = Destination::create(['name' => 'Boracay', 'country' => 'Philippines', 'description' => 'Beautiful island']);

        Livewire::actingAs($user)
            ->test(TripPlannerWizard::class)
            ->set('step', 5)
            ->set('tripScope', 'local')
            ->set('destinationId', $dest->id)
            ->set('destinationName', 'Boracay, Philippines')
            ->set('startDate', '2027-01-10')
            ->set('endDate', '2027-01-15')
            ->set('travelType', 'Solo')
            ->set('budgetTier', 'Mid-range')
            ->set('budgetLimit', 50000)
            ->call('confirm')
            ->assertRedirect();

        $this->assertDatabaseHas('trips', [
            'user_id'     => $user->id,
            'destination' => 'Boracay, Philippines',
            'travel_type' => 'Solo',
        ]);
    }

    public function test_wizard_requires_auth(): void
    {
        $this->get('/trips/plan')->assertRedirect(route('login'));
    }
}
