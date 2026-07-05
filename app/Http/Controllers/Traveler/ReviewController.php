<?php
namespace App\Http\Controllers\Traveler;

use App\Http\Controllers\Controller;
use App\Models\Attraction;
use App\Models\DestinationCost;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query  = Review::with('user')->where('status', 'active')->latest();
        if ($request->filled('destination')) {
            $query->where('destination', $request->destination);
        }
        $reviews      = $query->paginate(15)->withQueryString();
        $destinations = DestinationCost::orderBy('destination')->pluck('destination')->unique()->values();
        return view('traveler.reviews.index', compact('reviews', 'destinations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'destination'   => 'required|string|max:255',
            'rating'        => 'required|integer|min:1|max:5',
            'body'          => 'required|string|min:10|max:2000',
            'attraction_id' => 'nullable|exists:attractions,id',
        ]);

        auth()->user()->reviews()->create(array_merge($validated, ['status' => 'active']));

        if ($request->filled('attraction_id')) {
            return redirect()->route('attractions.show', $request->attraction_id)
                             ->with('success', 'Review submitted!');
        }
        return redirect()->route('reviews.index')->with('success', 'Review submitted!');
    }
}
