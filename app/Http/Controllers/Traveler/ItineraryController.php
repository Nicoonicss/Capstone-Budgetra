<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Models\Trip;
use Illuminate\Http\Request;

class ItineraryController extends Controller
{
    public function index()
    {
        $trips = auth()->user()
            ->trips()
            ->with(['itinerary' => fn($q) => $q->orderBy('start_datetime')])
            ->latest()
            ->get();

        return view('traveler.itinerary.index', compact('trips'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'trip_id'        => ['required', 'integer', 'exists:trips,id'],
            'title'          => ['required', 'string', 'max:255'],
            'type'           => ['required', 'in:Flight,Hotel,Activity,Transportation'],
            'start_datetime' => ['required', 'date'],
            'end_datetime'   => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'location'       => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string'],
        ]);

        $trip = Trip::findOrFail($data['trip_id']);
        abort_if($trip->user_id !== auth()->id(), 403);

        Itinerary::create($data);

        return redirect()->route('itinerary.index')->with('success', 'Itinerary item added.');
    }

    public function destroy(Itinerary $item)
    {
        abort_if($item->trip->user_id !== auth()->id(), 403);
        $item->delete();
        return redirect()->route('itinerary.index')->with('success', 'Item removed.');
    }
}
