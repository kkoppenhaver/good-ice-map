<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, Location $location)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $rating = Rating::updateOrCreate(
            [
                'location_id' => $location->id,
                'user_id' => auth()->id(),
            ],
            [
                'rating' => $validated['rating'],
                'review' => $validated['review'],
            ]
        );

        // Update location's average rating
        $this->updateLocationRating($location);

        return back()->with('success', 'Rating submitted successfully!');
    }

    private function updateLocationRating(Location $location)
    {
        $ratings = $location->ratings;
        $location->update([
            'average_rating' => $ratings->avg('rating'),
            'total_ratings' => $ratings->count(),
        ]);
    }
}
