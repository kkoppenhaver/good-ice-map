<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\LocationImage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class LocationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth', except: ['index', 'show', 'api']),
        ];
    }

    /**
     * Display the map with all approved locations.
     */
    public function index()
    {
        return view('locations.index');
    }

    /**
     * Return locations as JSON for the map.
     */
    public function api(Request $request)
    {
        $locations = Location::where('status', 'approved')
            ->with(['images' => function ($query) {
                $query->where('is_primary', true);
            }])
            ->get();

        return response()->json($locations);
    }

    /**
     * Show the form for creating a new location.
     */
    public function create()
    {
        return view('locations.create');
    }

    /**
     * Store a newly created location.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $location = Location::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'address' => $validated['address'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'submitted_by' => auth()->id(),
            'status' => 'approved', // Auto-approve for MVP
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('location-images', 'public');

                LocationImage::create([
                    'location_id' => $location->id,
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        return redirect()->route('locations.show', $location)
            ->with('success', 'Location submitted successfully!');
    }

    /**
     * Display the specified location.
     */
    public function show(Location $location)
    {
        $location->load(['images', 'ratings.user', 'submittedBy']);
        return view('locations.show', compact('location'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
