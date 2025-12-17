<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenStreetMapService
{
    protected string $overpassUrl = 'https://overpass-api.de/api/interpreter';

    // State abbreviation to full name mapping
    protected array $stateNames = [
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
        'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
        'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
        'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
        'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
        'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
        'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
        'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
        'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
        'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
        'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
        'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
        'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'District of Columbia',
    ];

    /**
     * Search for chain locations across the entire US using OpenStreetMap data.
     * This is FREE and doesn't require any API key.
     */
    public function findChainLocations(string $brandName, ?string $state = null, int $limit = 1000): array
    {
        // Convert state abbreviation to full name if needed
        $stateName = $state;
        if ($state && strlen($state) === 2) {
            $stateName = $this->stateNames[strtoupper($state)] ?? $state;
        }

        // Build Overpass QL query
        $areaFilter = $stateName
            ? "area[\"name\"=\"{$stateName}\"][\"admin_level\"=\"4\"]->.searchArea;"
            : 'area["ISO3166-1"="US"]->.searchArea;';

        $query = <<<OVERPASS
[out:json][timeout:60];
{$areaFilter}
(
  node["brand"~"{$brandName}",i]["amenity"](area.searchArea);
  way["brand"~"{$brandName}",i]["amenity"](area.searchArea);
);
out center {$limit};
OVERPASS;

        Log::info("Overpass query for {$brandName}", ['state' => $state]);

        $response = Http::timeout(90)
            ->withBody($query, 'text/plain')
            ->post($this->overpassUrl);

        if ($response->failed()) {
            Log::error('Overpass API failed', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            return [];
        }

        $data = $response->json();
        $elements = $data['elements'] ?? [];

        $locations = [];
        foreach ($elements as $element) {
            // Get coordinates (nodes have lat/lon directly, ways have center)
            $lat = $element['lat'] ?? $element['center']['lat'] ?? null;
            $lon = $element['lon'] ?? $element['center']['lon'] ?? null;

            if (!$lat || !$lon) {
                continue;
            }

            $tags = $element['tags'] ?? [];

            // Build address from tags
            $addressParts = [];
            if (!empty($tags['addr:housenumber'])) {
                $addressParts[] = $tags['addr:housenumber'];
            }
            if (!empty($tags['addr:street'])) {
                $addressParts[] = $tags['addr:street'];
            }
            $streetAddress = implode(' ', $addressParts);

            $cityState = [];
            if (!empty($tags['addr:city'])) {
                $cityState[] = $tags['addr:city'];
            }
            if (!empty($tags['addr:state'])) {
                $cityState[] = $tags['addr:state'];
            }
            if (!empty($tags['addr:postcode'])) {
                $cityState[] = $tags['addr:postcode'];
            }

            $fullAddress = $streetAddress;
            if (!empty($cityState)) {
                $fullAddress .= ($streetAddress ? ', ' : '') . implode(', ', $cityState);
            }

            $locations[] = [
                'osm_id' => $element['type'] . '/' . $element['id'],
                'name' => $tags['name'] ?? $brandName,
                'brand' => $tags['brand'] ?? $brandName,
                'address' => $fullAddress ?: null,
                'city' => $tags['addr:city'] ?? null,
                'state' => $tags['addr:state'] ?? null,
                'postcode' => $tags['addr:postcode'] ?? null,
                'latitude' => $lat,
                'longitude' => $lon,
                'phone' => $tags['phone'] ?? null,
                'website' => $tags['website'] ?? null,
                'opening_hours' => $tags['opening_hours'] ?? null,
            ];
        }

        Log::info("Found {$brandName} locations", ['count' => count($locations), 'state' => $state]);

        return $locations;
    }

    /**
     * Get all chain locations for multiple brands.
     */
    public function findMultipleChains(array $brandNames, ?string $state = null): array
    {
        $allLocations = [];

        foreach ($brandNames as $brand) {
            $locations = $this->findChainLocations($brand, $state);
            $allLocations[$brand] = $locations;

            // Be nice to the free API - add a small delay between requests
            if (count($brandNames) > 1) {
                sleep(2);
            }
        }

        return $allLocations;
    }
}
