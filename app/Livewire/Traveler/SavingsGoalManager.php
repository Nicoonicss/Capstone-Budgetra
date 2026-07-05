<?php
namespace App\Livewire\Traveler;

use App\Models\SavingsGoal;
use Carbon\Carbon;
use Livewire\Component;

class SavingsGoalManager extends Component
{
    public SavingsGoal $goal;

    public bool  $showDeposit    = false;
    public bool  $showProjection = false;
    public float $depositAmount  = 0;

    public function openDeposit(): void
    {
        $this->depositAmount = 0;
        $this->showDeposit   = true;
    }

    public function closeDeposit(): void
    {
        $this->showDeposit = false;
    }

    public function openProjection(): void
    {
        $this->showProjection = true;
    }

    public function closeProjection(): void
    {
        $this->showProjection = false;
    }

    public function submitDeposit(): void
    {
        abort_if($this->goal->user_id !== auth()->id(), 403);
        $this->validate(['depositAmount' => 'required|numeric|min:0.01']);
        $this->goal->increment('current_savings', $this->depositAmount);
        $this->goal->refresh();
        $this->depositAmount = 0;
        $this->showDeposit   = false;
        $this->dispatch('goalUpdated');
    }

    public function getPctProperty(): float
    {
        if (!$this->goal->target_amount) return 0;
        return min(100, round($this->goal->current_savings / $this->goal->target_amount * 100, 1));
    }

    public function getDailyNeededProperty(): float
    {
        $remaining = $this->goal->target_amount - $this->goal->current_savings;
        if ($remaining <= 0) return 0;
        $days = max(1, (int) Carbon::today()->diffInDays($this->goal->deadline, false));
        return $days > 0 ? round($remaining / $days, 2) : $remaining;
    }

    public function getDaysLeftProperty(): int
    {
        return max(0, (int) Carbon::today()->diffInDays($this->goal->deadline, false));
    }

    public function getIsCompletedProperty(): bool
    {
        return $this->goal->current_savings >= $this->goal->target_amount;
    }

    public function render()
    {
        return view('livewire.traveler.savings-goal-manager');
    }
}
