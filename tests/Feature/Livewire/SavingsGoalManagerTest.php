<?php
namespace Tests\Feature\Livewire;

use App\Livewire\Traveler\SavingsGoalManager;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SavingsGoalManagerTest extends TestCase
{
    use RefreshDatabase;

    private function makeGoal(User $user, array $attrs = []): SavingsGoal
    {
        return SavingsGoal::create(array_merge([
            'user_id'         => $user->id,
            'goal_name'       => 'Test Fund',
            'target_amount'   => 10000,
            'current_savings' => 1000,
            'deadline'        => '2030-12-31',
        ], $attrs));
    }

    public function test_savings_index_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/savings')->assertStatus(200)->assertSee('Savings Goals');
    }

    public function test_deposit_modal_opens(): void
    {
        $user = User::factory()->create();
        $goal = $this->makeGoal($user);
        Livewire::actingAs($user)
            ->test(SavingsGoalManager::class, ['goal' => $goal])
            ->call('openDeposit')
            ->assertSet('showDeposit', true);
    }

    public function test_deposit_adds_to_savings(): void
    {
        $user = User::factory()->create();
        $goal = $this->makeGoal($user);
        Livewire::actingAs($user)
            ->test(SavingsGoalManager::class, ['goal' => $goal])
            ->set('depositAmount', 500)
            ->call('submitDeposit')
            ->assertSet('showDeposit', false);

        $this->assertDatabaseHas('savings_goals', ['id' => $goal->id, 'current_savings' => 1500]);
    }

    public function test_projection_modal_opens(): void
    {
        $user = User::factory()->create();
        $goal = $this->makeGoal($user);
        Livewire::actingAs($user)
            ->test(SavingsGoalManager::class, ['goal' => $goal])
            ->call('openProjection')
            ->assertSet('showProjection', true);
    }

    public function test_completed_goal_shows_on_index(): void
    {
        $user = User::factory()->create();
        $this->makeGoal($user, ['current_savings' => 10000, 'target_amount' => 10000]);
        $this->actingAs($user)->get('/savings')->assertStatus(200)->assertSee('COMPLETED');
    }
}
