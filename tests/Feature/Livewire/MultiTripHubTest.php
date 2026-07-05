<?php
namespace Tests\Feature\Livewire;

use App\Livewire\Traveler\MultiTripHub;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultiTripHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_trips_index_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/trips')->assertStatus(200)->assertSee('Multi-Trip Hub');
    }

    public function test_search_filters_trips(): void
    {
        $user = User::factory()->create();
        Trip::factory()->create(['user_id' => $user->id, 'destination' => 'Boracay, Philippines']);
        Trip::factory()->create(['user_id' => $user->id, 'destination' => 'Bangkok, Thailand']);

        Livewire::actingAs($user)
            ->test(MultiTripHub::class)
            ->set('search', 'Boracay')
            ->assertSee('Boracay')
            ->assertDontSee('Bangkok');
    }

    public function test_compare_ids_can_be_toggled(): void
    {
        $user  = User::factory()->create();
        $trip1 = Trip::factory()->create(['user_id' => $user->id]);
        $trip2 = Trip::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(MultiTripHub::class)
            ->call('toggleCompare', $trip1->id)
            ->assertSet('compareIds', [$trip1->id])
            ->call('toggleCompare', $trip2->id)
            ->assertSet('compareIds', [$trip1->id, $trip2->id]);
    }

    public function test_comparison_modal_opens_with_two_trips(): void
    {
        $user  = User::factory()->create();
        $trip1 = Trip::factory()->create(['user_id' => $user->id]);
        $trip2 = Trip::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(MultiTripHub::class)
            ->set('compareIds', [$trip1->id, $trip2->id])
            ->call('openComparison')
            ->assertSet('showComparison', true);
    }

    public function test_empty_state_shown_when_no_trips(): void
    {
        $user = User::factory()->create();
        Livewire::actingAs($user)
            ->test(MultiTripHub::class)
            ->assertSee('No trips yet');
    }
}
