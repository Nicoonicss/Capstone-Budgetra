<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\SavingsGoal;
use App\Models\Trip;
use Illuminate\Http\Request;

class SavingsGoalController extends Controller
{
    public function index()
    {
        // Auto-create a savings goal for any trip that doesn't have one yet
        $tripsWithoutGoals = auth()->user()->trips()
            ->whereDoesntHave('savingsGoals')
            ->get();

        foreach ($tripsWithoutGoals as $trip) {
            SavingsGoal::create([
                'user_id'         => auth()->id(),
                'trip_id'         => $trip->id,
                'goal_name'       => $trip->destination . ' Trip',
                'target_amount'   => $trip->budget_limit ?: 1,
                'current_savings' => 0,
                'deadline'        => $trip->start_date,
            ]);
        }

        $goals = auth()->user()->savingsGoals()->with('trip')->whereNotNull('trip_id')->latest()->get();
        return view('traveler.savings.index', compact('goals'));
    }

    public function create()
    {
        $trips = auth()->user()->trips()->orderBy('destination')->get();
        return view('traveler.savings.create', compact('trips'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'goal_name'       => 'required|string|max:255',
            'target_amount'   => 'required|numeric|min:1',
            'current_savings' => 'nullable|numeric|min:0',
            'deadline'        => 'required|date|after:today',
            'trip_id'         => 'nullable|exists:trips,id',
        ]);

        if ($request->filled('trip_id')) {
            $trip = Trip::find($request->trip_id);
            abort_if($trip->user_id !== auth()->id(), 403);
        }

        auth()->user()->savingsGoals()->create([
            ...$validated,
            'current_savings' => $validated['current_savings'] ?? 0,
        ]);

        return redirect()->route('savings.index')->with('success', 'Savings goal created!');
    }

    public function edit(SavingsGoal $goal)
    {
        abort_if($goal->user_id !== auth()->id(), 403);
        $trips = auth()->user()->trips()->orderBy('destination')->get();
        return view('traveler.savings.edit', compact('goal', 'trips'));
    }

    public function update(Request $request, SavingsGoal $goal)
    {
        abort_if($goal->user_id !== auth()->id(), 403);
        $validated = $request->validate([
            'goal_name'       => 'required|string|max:255',
            'target_amount'   => 'required|numeric|min:1',
            'current_savings' => 'nullable|numeric|min:0',
            'deadline'        => 'required|date',
            'trip_id'         => 'nullable|exists:trips,id',
        ]);
        $goal->update([...$validated, 'current_savings' => $validated['current_savings'] ?? $goal->current_savings]);
        return redirect()->route('savings.index')->with('success', 'Goal updated.');
    }

    public function destroy(SavingsGoal $goal)
    {
        abort_if($goal->user_id !== auth()->id(), 403);
        $goal->delete();
        return redirect()->route('savings.index')->with('success', 'Goal deleted.');
    }

    public function deposit(Request $request, SavingsGoal $goal)
    {
        abort_if($goal->user_id !== auth()->id(), 403);
        $request->validate(['amount' => 'required|numeric|min:0.01']);
        $goal->increment('current_savings', $request->amount);
        return back()->with('success', 'Deposit added!');
    }
}
