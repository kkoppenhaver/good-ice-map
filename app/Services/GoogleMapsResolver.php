<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleMapsResolver
{
    public function __construct(private readonly GoogleMapsLinkParser $parser) {}

    /**
     * Resolve a Google Maps share link into structured location data,
     * including a formatted_address from the Place Details API.
     *
     * @return array{name: ?string, address: ?string, latitude: ?float, longitude: ?float, place_id: ?string}
     */
    public function resolve(string $url): array
    {
        $longUrl = $this->parser->isShortLink($url) ? $this->expand($url) : $url;
        $parsed = $this->parser->parse($longUrl);

        $address = null;
        if ($parsed['place_id'] || ($parsed['latitude'] !== null && $parsed['longitude'] !== null)) {
            $details = $this->fetchPlaceDetails($parsed);
            $address = $details['address'] ?? null;
            $parsed['name'] = $parsed['name'] ?? ($details['name'] ?? null);
            $parsed['place_id'] = $parsed['place_id'] ?? ($details['place_id'] ?? null);
            $parsed['latitude'] = $parsed['latitude'] ?? ($details['latitude'] ?? null);
            $parsed['longitude'] = $parsed['longitude'] ?? ($details['longitude'] ?? null);
        }

        return [
            'name' => $parsed['name'],
            'address' => $address,
            'latitude' => $parsed['latitude'],
            'longitude' => $parsed['longitude'],
            'place_id' => $parsed['place_id'],
        ];
    }

    private function expand(string $url): string
    {
        $response = Http::withOptions([
            'allow_redirects' => ['track_redirects' => true, 'max' => 10],
            'timeout' => 10,
        ])->get($url);

        $history = $response->getHeader('X-Guzzle-Redirect-History');

        return ! empty($history) ? (string) end($history) : $url;
    }

    /**
     * @param  array{place_id: ?string, latitude: ?float, longitude: ?float}  $parsed
     * @return array{name: ?string, address: ?string, latitude: ?float, longitude: ?float, place_id: ?string}
     */
    private function fetchPlaceDetails(array $parsed): array
    {
        $apiKey = config('services.google.places_api_key');
        if (empty($apiKey)) {
            return ['name' => null, 'address' => null, 'latitude' => null, 'longitude' => null, 'place_id' => null];
        }

        $placeId = $parsed['place_id'];

        if (! $placeId && $parsed['latitude'] !== null && $parsed['longitude'] !== null) {
            $geocode = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => $parsed['latitude'].','.$parsed['longitude'],
                'result_type' => 'street_address|premise|establishment',
                'key' => $apiKey,
            ]);

            if ($geocode->ok() && ! empty($geocode->json('results'))) {
                $placeId = $geocode->json('results.0.place_id');
            }
        }

        if (! $placeId) {
            return ['name' => null, 'address' => null, 'latitude' => null, 'longitude' => null, 'place_id' => null];
        }

        $details = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $placeId,
            'fields' => 'name,formatted_address,geometry',
            'key' => $apiKey,
        ]);

        if (! $details->ok() || empty($details->json('result'))) {
            return ['name' => null, 'address' => null, 'latitude' => null, 'longitude' => null, 'place_id' => $placeId];
        }

        $result = $details->json('result');

        return [
            'name' => $result['name'] ?? null,
            'address' => $result['formatted_address'] ?? null,
            'latitude' => $result['geometry']['location']['lat'] ?? null,
            'longitude' => $result['geometry']['location']['lng'] ?? null,
            'place_id' => $placeId,
        ];
    }
}
