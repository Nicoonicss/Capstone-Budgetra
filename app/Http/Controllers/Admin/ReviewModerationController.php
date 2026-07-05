<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewModerationController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with('user')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('destination')) {
            $query->where('destination', $request->destination);
        }
        $reviews = $query->paginate(25)->withQueryString();
        return view('admin.reviews.index', compact('reviews'));
    }

    public function hide(Review $review)
    {
        $review->update(['status' => 'hidden']);
        return back()->with('success', 'Review hidden.');
    }

    public function show(Review $review)
    {
        $review->update(['status' => 'active']);
        return back()->with('success', 'Review is now active.');
    }
}
