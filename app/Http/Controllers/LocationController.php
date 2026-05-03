<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\LocationImage;
use App\Models\Rating;
use App\Models\User;
use App\Services\GoogleMapsResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class LocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show', 'api']);
    }

    /**
     * Display the map for authenticated users, or the landing page for guests.
     */
    public function index()
    {
        if (auth()->check()) {
            return view('locations.index');
        }

        $stats = Cache::remember('landing_stats', 300, fn () => [
            'locations' => Location::where('status', 'approved')->count(),
            'ratings' => Rating::count(),
            'contributors' => User::whereHas('locations', fn ($q) => $q->where('status', 'approved'))->count(),
        ]);

        $recentImages = LocationImage::whereHas('location', function ($query) {
            $query->where('status', 'approved');
        })
            ->with('location:id,name')
            ->latest()
            ->take(50)
            ->get()
            ->unique(fn ($img) => strtolower(trim($img->location->name ?? '')))
            ->take(8)
            ->values();

        return view('landing', compact('stats', 'recentImages'));
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
     * Resolve a Google Maps share link into structured location data.
     */
    public function parseMapsLink(Request $request, GoogleMapsResolver $resolver)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
        ]);

        $data = $resolver->resolve($validated['url']);

        if (! $data['latitude'] || ! $data['longitude']) {
            return response()->json([
                'success' => false,
                'error' => 'Could not extract location data from this link.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'name' => $data['name'],
            'address' => $data['address'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'place_id' => $data['place_id'],
        ]);
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
            'address' => 'nullable|string|max:500',
            'manual_address' => 'nullable|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'google_maps_link' => 'nullable|url|max:2048',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // Determine the address to use (either from hidden field or manual_address)
        $address = $validated['address'] ?? $validated['manual_address'] ?? null;

        // Use the name as fallback if no address is provided
        if (empty($address)) {
            $address = $validated['name'];
        }

        if ($duplicate = Location::findDuplicateByAddress($address)) {
            return redirect()->route('locations.show', $duplicate)
                ->with('success', 'This location is already on the map — leave a rating below!');
        }

        $location = Location::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'address' => $address,
            'google_maps_link' => $validated['google_maps_link'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'submitted_by' => auth()->id(),
            'status' => 'pending',
        ]);

        $path = $request->file('image')->store('location-images', 'r2');

        LocationImage::create([
            'location_id' => $location->id,
            'image_path' => $path,
            'is_primary' => true,
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('dashboard')
            ->with('success', "Submitted! It'll appear on the map after a quick review.");
    }

    /**
     * Display the specified location.
     */
    public function show(Location $location)
    {
        $this->authorize('view', $location);

        $location->load(['images', 'ratings.user', 'submittedBy']);

        return view('locations.show', compact('location'));
    }

    /**
     * Show the form for editing the specified location.
     */
    public function edit(Location $location)
    {
        $this->authorize('update', $location);

        return view('locations.edit', compact('location'));
    }

    /**
     * Update the name and description of the specified location.
     */
    public function update(Request $request, Location $location)
    {
        $this->authorize('update', $location);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $location->update($validated);

        return redirect()->route('locations.show', $location)
            ->with('success', 'Location updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        $this->authorize('delete', $location);

        // Delete images from R2 storage
        foreach ($location->images as $image) {
            Storage::disk('r2')->delete($image->image_path);
            $image->delete();
        }

        $location->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Location deleted successfully.');
    }
}
