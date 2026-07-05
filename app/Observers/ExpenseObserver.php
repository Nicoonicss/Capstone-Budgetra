<?php
namespace App\Observers;

use App\Models\Expense;
use App\Models\Notification;
use App\Models\Trip;
use App\Models\TripBudget;

class ExpenseObserver
{
    private const CATEGORY_MAP = [
        'Transportation'     => 'Transportation',
        'Accommodation'      => 'Accommodation',
        'Food'               => 'Food',
        'Activities'         => 'Tourist Attractions',
        'Shopping'           => 'Shopping',
        'Emergency Expenses' => 'Emergency Funds',
    ];

    public function deleted(Expense $expense): void
    {
        $this->adjustActualSpent($expense->trip_id, $expense->category, -$expense->amount);
    }

    public static function syncBudgetForExpense(Expense $expense): void
    {
        $budgetCategory = self::CATEGORY_MAP[$expense->category] ?? null;
        if (!$budgetCategory) return;

        $budget = TripBudget::where('trip_id', $expense->trip_id)
                             ->where('category', $budgetCategory)
                             ->first();
        if (!$budget) return;

        $budget->increment('actual_spent', $expense->amount);
        $budget->refresh();

        if ($budget->estimated_cost <= 0) return;

        $pct  = $budget->actual_spent / $budget->estimated_cost;
        $trip = Trip::find($expense->trip_id);
        if (!$trip) return;

        // 50% threshold — fires budget_warning (only when below 80%)
        if ($pct >= 0.50 && $pct < 0.80) {
            $exists = Notification::where('user_id', $trip->user_id)
                ->where('trip_id', $expense->trip_id)
                ->where('type', 'budget_warning')
                ->whereRaw("message LIKE ?", ["%{$budgetCategory}%"])
                ->exists();
            if (!$exists) {
                Notification::create([
                    'user_id' => $trip->user_id,
                    'trip_id' => $expense->trip_id,
                    'type'    => 'budget_warning',
                    'message' => "Heads up: Your {$budgetCategory} budget for {$trip->destination} has reached " . round($pct * 100) . "% of the estimated amount.",
                    'is_read' => false,
                ]);
            }
        }

        // 80% threshold — fires budget_alert
        if ($pct >= 0.80) {
            $exists = Notification::where('user_id', $trip->user_id)
                ->where('trip_id', $expense->trip_id)
                ->where('type', 'budget_alert')
                ->whereRaw("message LIKE ?", ["%{$budgetCategory}%"])
                ->exists();
            if (!$exists) {
                Notification::create([
                    'user_id' => $trip->user_id,
                    'trip_id' => $expense->trip_id,
                    'type'    => 'budget_alert',
                    'message' => "Warning: Your {$budgetCategory} budget for {$trip->destination} has reached " . round($pct * 100) . "% of the estimated amount.",
                    'is_read' => false,
                ]);
            }
        }
    }

    private function adjustActualSpent(int $tripId, string $expenseCategory, float $delta): void
    {
        $budgetCategory = self::CATEGORY_MAP[$expenseCategory] ?? null;
        if (!$budgetCategory) return;

        TripBudget::where('trip_id', $tripId)
                  ->where('category', $budgetCategory)
                  ->increment('actual_spent', $delta);
    }
}
