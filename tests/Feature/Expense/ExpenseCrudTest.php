<?php
namespace Tests\Feature\Expense;

use App\Models\Expense;
use App\Models\Trip;
use App\Models\TripBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_index_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/expenses')->assertStatus(200);
    }

    public function test_user_can_create_expense_and_budget_is_synced(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        TripBudget::create(['trip_id' => $trip->id, 'category' => 'Transportation', 'estimated_cost' => 5000, 'actual_spent' => 0]);

        $response = $this->actingAs($user)->post('/expenses', [
            'trip_id'      => $trip->id,
            'amount'       => 1500,
            'category'     => 'Transportation',
            'description'  => 'Bus fare',
            'expense_date' => '2026-08-03',
        ]);

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', ['amount' => 1500, 'category' => 'Transportation']);
        $this->assertDatabaseHas('trip_budgets', ['trip_id' => $trip->id, 'category' => 'Transportation', 'actual_spent' => 1500]);
    }

    public function test_deleting_expense_reverses_budget_sync(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        TripBudget::create(['trip_id' => $trip->id, 'category' => 'Food', 'estimated_cost' => 3000, 'actual_spent' => 800]);
        $expense = Expense::create([
            'trip_id' => $trip->id, 'user_id' => $user->id,
            'amount' => 800, 'category' => 'Food',
            'expense_date' => '2026-08-02',
        ]);

        $this->actingAs($user)->delete("/expenses/{$expense->id}");

        $this->assertDatabaseHas('trip_budgets', ['trip_id' => $trip->id, 'category' => 'Food', 'actual_spent' => 0]);
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_user_cannot_delete_another_users_expense(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $trip  = Trip::factory()->create(['user_id' => $other->id]);
        $expense = Expense::create([
            'trip_id' => $trip->id, 'user_id' => $other->id,
            'amount' => 100, 'category' => 'Food', 'expense_date' => '2026-08-01',
        ]);

        $this->actingAs($user)->delete("/expenses/{$expense->id}")->assertStatus(403);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/expenses', []);
        $response->assertSessionHasErrors(['trip_id', 'amount', 'category', 'expense_date']);
    }
}
