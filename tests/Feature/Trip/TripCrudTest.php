<?php
namespace Tests\Feature\Trip;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_only_users_trips(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $mine   = Trip::factory()->create(['user_id' => $user->id,  'destination' => 'Boracay']);
        $theirs = Trip::factory()->create(['user_id' => $other->id, 'destination' => 'Palawan']);

        $response = $this->actingAs($user)->get('/trips');

        $response->assertStatus(200);
        $response->assertSee('Boracay');
        $response->assertDontSee('Palawan');
    }

    public function test_user_can_create_trip(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/trips', [
            'destination'   => 'El Nido, Palawan',
            'start_date'    => '2026-08-01',
            'end_date'      => '2026-08-07',
            'num_travelers' => 2,
            'budget_limit'  => 50000,
            'travel_type'   => 'Couple',
            'notes'         => 'Anniversary trip',
        ]);

        $this->assertDatabaseHas('trips', [
            'destination' => 'El Nido, Palawan',
            'user_id'     => $user->id,
        ]);
        $trip = Trip::where('destination', 'El Nido, Palawan')->first();
        $response->assertRedirect(route('trips.show', $trip));
    }

    public function test_trip_show_page_loads(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id, 'destination' => 'Siargao']);

        $response = $this->actingAs($user)->get("/trips/{$trip->id}");

        $response->assertStatus(200);
        $response->assertSee('Siargao');
    }

    public function test_user_cannot_view_another_users_trip(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $trip  = Trip::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->get("/trips/{$trip->id}")->assertStatus(403);
    }

    public function test_user_can_update_their_trip(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/trips/{$trip->id}", [
            'destination'   => 'Updated Destination',
            'start_date'    => '2026-09-01',
            'end_date'      => '2026-09-05',
            'num_travelers' => 1,
            'travel_type'   => 'Solo',
        ]);

        $response->assertRedirect(route('trips.show', $trip));
        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'destination' => 'Updated Destination']);
    }

    public function test_user_can_delete_their_trip(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete("/trips/{$trip->id}")->assertRedirect(route('trips.index'));
        $this->assertDatabaseMissing('trips', ['id' => $trip->id]);
    }

    public function test_user_cannot_delete_another_users_trip(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $trip  = Trip::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->delete("/trips/{$trip->id}")->assertStatus(403);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/trips', []);
        $response->assertSessionHasErrors(['destination', 'start_date', 'end_date', 'travel_type']);
    }
}
