<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DestinationCost;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function index()
    {
        $destinations = DestinationCost::orderBy('destination')->paginate(25);
        return view('admin.destinations.index', compact('destinations'));
    }

    public function create()
    {
        $costLevels = ['Budget-friendly', 'Moderate', 'Pricey', 'Very Expensive'];
        return view('admin.destinations.create', compact('costLevels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'destination' => 'required|string|max:255|unique:destination_costs',
            'cost_level'  => 'required|in:Budget-friendly,Moderate,Pricey,Very Expensive',
            'multiplier'  => 'required|numeric|min:0.1|max:10',
            'category'    => 'nullable|string|max:100',
            'image_url'   => 'nullable|url|max:500',
            'description' => 'nullable|string|max:2000',
        ]);
        DestinationCost::create($validated);
        return redirect()->route('admin.destinations.index')->with('success', 'Destination added.');
    }

    public function edit(DestinationCost $destination)
    {
        $costLevels = ['Budget-friendly', 'Moderate', 'Pricey', 'Very Expensive'];
        return view('admin.destinations.edit', compact('destination', 'costLevels'));
    }

    public function update(Request $request, DestinationCost $destination)
    {
        $validated = $request->validate([
            'destination' => "required|string|max:255|unique:destination_costs,destination,{$destination->id}",
            'cost_level'  => 'required|in:Budget-friendly,Moderate,Pricey,Very Expensive',
            'multiplier'  => 'required|numeric|min:0.1|max:10',
            'category'    => 'nullable|string|max:100',
            'image_url'   => 'nullable|url|max:500',
            'description' => 'nullable|string|max:2000',
        ]);
        $destination->update($validated);
        return redirect()->route('admin.destinations.index')->with('success', 'Destination updated.');
    }

    public function destroy(DestinationCost $destination)
    {
        $destination->delete();
        return redirect()->route('admin.destinations.index')->with('success', 'Destination deleted.');
    }
}
