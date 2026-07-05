<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attraction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttractionController extends Controller
{
    public function index(Request $request)
    {
        $query = Attraction::query();
        if ($request->filled('destination')) {
            $query->where('destination', $request->destination);
        }
        $attractions = $query->orderBy('destination')->orderBy('name')->paginate(25)->withQueryString();
        return view('admin.attractions.index', compact('attractions'));
    }

    public function create()
    {
        return view('admin.attractions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'destination' => 'required|string|max:255',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category'    => 'nullable|string|max:100',
            'rating'      => 'nullable|numeric|min:0|max:5',
            'image'       => 'nullable|image|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $filename  = Str::replace(' ', '_', $validated['name']) . '.' . $request->file('image')->getClientOriginalExtension();
            $request->file('image')->storeAs('attraction-images', $filename, 'public');
            $imagePath = 'attraction-images/' . $filename;
        }

        Attraction::create([
            'destination' => $validated['destination'],
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category'    => $validated['category'] ?? null,
            'rating'      => $validated['rating'] ?? 0,
            'image'       => $imagePath,
        ]);

        return redirect()->route('admin.attractions.index')->with('success', 'Attraction added.');
    }

    public function edit(Attraction $attraction)
    {
        return view('admin.attractions.edit', compact('attraction'));
    }

    public function update(Request $request, Attraction $attraction)
    {
        $validated = $request->validate([
            'destination' => 'required|string|max:255',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category'    => 'nullable|string|max:100',
            'rating'      => 'nullable|numeric|min:0|max:5',
            'image'       => 'nullable|image|max:5120',
        ]);

        $updateData = [
            'destination' => $validated['destination'],
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category'    => $validated['category'] ?? null,
            'rating'      => $validated['rating'] ?? $attraction->rating,
        ];

        if ($request->hasFile('image')) {
            $filename  = Str::replace(' ', '_', $validated['name']) . '.' . $request->file('image')->getClientOriginalExtension();
            $request->file('image')->storeAs('attraction-images', $filename, 'public');
            $updateData['image'] = 'attraction-images/' . $filename;
        }

        $attraction->update($updateData);
        return redirect()->route('admin.attractions.index')->with('success', 'Attraction updated.');
    }

    public function destroy(Attraction $attraction)
    {
        $attraction->delete();
        return redirect()->route('admin.attractions.index')->with('success', 'Attraction deleted.');
    }
}
