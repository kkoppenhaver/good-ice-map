<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\LocationImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show', 'api']);
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
     * Expand a shortened URL (e.g., goo.gl links) to get the full URL.
     */
    public function expandUrl(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
        ]);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $validated['url']);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_exec($ch);
            $expandedUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);

            return response()->json([
                'expanded_url' => $expandedUrl,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to expand URL',
                'success' => false
            ], 500);
        }
    }

    /**
     * Search for a place using Google Places Text Search API.
     */
    public function searchPlace(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|max:500',
        ]);

        $apiKey = config('services.google.places_api_key');

        if (empty($apiKey)) {
            return response()->json([
                'error' => 'Google Places API key not configured',
                'success' => false
            ], 500);
        }

        try {
            $textSearchUrl = "https://maps.googleapis.com/maps/api/place/textsearch/json?" . http_build_query([
                'query' => $validated['query'],
                'key' => $apiKey
            ]);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $textSearchUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return response()->json([
                    'error' => 'Failed to search for place',
                    'success' => false
                ], $httpCode);
            }

            $data = json_decode($response, true);

            if (empty($data['results'])) {
                return response()->json([
                    'error' => 'No places found',
                    'success' => false
                ], 404);
            }

            $place = $data['results'][0];

            return response()->json([
                'name' => $place['name'] ?? null,
                'address' => $place['formatted_address'] ?? null,
                'lat' => $place['geometry']['location']['lat'] ?? null,
                'lng' => $place['geometry']['location']['lng'] ?? null,
                'place_id' => $place['place_id'] ?? null,
                'success' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to search for place: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Fetch place details from Google Places API using coordinates.
     */
    public function fetchPlaceDetails(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'place_id' => 'nullable|string',
        ]);

        $apiKey = config('services.google.places_api_key');
        
        if (empty($apiKey)) {
            return response()->json([
                'error' => 'Google Places API key not configured',
                'success' => false
            ], 500);
        }

        try {
            $placeId = $validated['place_id'] ?? null;

            // If we don't have a Place ID, try to find it using Geocoding API for precise location
            if (empty($placeId)) {
                $lat = $validated['lat'];
                $lng = $validated['lng'];

                // Use Geocoding API to get the precise address and place_id
                $geocodeUrl = "https://maps.googleapis.com/maps/api/geocode/json?" . http_build_query([
                    'latlng' => "$lat,$lng",
                    'result_type' => 'street_address|premise|establishment',
                    'key' => $apiKey
                ]);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $geocodeUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 200) {
                    return response()->json([
                        'error' => 'Failed to fetch place details',
                        'success' => false
                    ], $httpCode);
                }

                $data = json_decode($response, true);

                if (empty($data['results'])) {
                    return response()->json([
                        'name' => null,
                        'address' => null,
                        'description' => null,
                        'place_id' => null,
                        'success' => true
                    ]);
                }

                $placeId = $data['results'][0]['place_id'];
            }
            
            // Get detailed information using Place Details API
            $detailsUrl = "https://maps.googleapis.com/maps/api/place/details/json?" . http_build_query([
                'place_id' => $placeId,
                'fields' => 'name,formatted_address,editorial_summary,types,rating,user_ratings_total,geometry',
                'key' => $apiKey
            ]);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $detailsUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $detailsResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return response()->json([
                    'error' => 'Failed to fetch place details',
                    'success' => false
                ], $httpCode);
            }

            $detailsData = json_decode($detailsResponse, true);
            
            if (empty($detailsData['result'])) {
                return response()->json([
                    'error' => 'Place not found',
                    'success' => false
                ], 404);
            }

            $details = $detailsData['result'];

            return response()->json([
                'name' => $details['name'] ?? null,
                'address' => $details['formatted_address'] ?? null,
                'description' => $details['editorial_summary']['overview'] ?? null,
                'place_id' => $placeId,
                'types' => $details['types'] ?? [],
                'rating' => $details['rating'] ?? null,
                'user_ratings_total' => $details['user_ratings_total'] ?? null,
                'lat' => $details['geometry']['location']['lat'] ?? $validated['lat'],
                'lng' => $details['geometry']['location']['lng'] ?? $validated['lng'],
                'success' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch place details: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
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
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // Determine the address to use (either from hidden field or manual_address)
        $address = $validated['address'] ?? $validated['manual_address'] ?? null;
        
        // Use the name as fallback if no address is provided
        if (empty($address)) {
            $address = $validated['name'];
        }

        $location = Location::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'address' => $address,
            'google_maps_link' => $validated['google_maps_link'] ?? null,
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
