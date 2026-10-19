<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reverse geocodes coordinates with OpenStreetMap's Nominatim (free; usage
 * policy is one request per second with an identifying User-Agent).
 */
class ReverseGeocoder
{
    public const ENDPOINT = 'https://nominatim.openstreetmap.org/reverse';

    /**
     * @return array{city: ?string, state: ?string, country: ?string}|null null when the lookup fails
     */
    public function lookup(float $latitude, float $longitude): ?array
    {
        if (! config('services.nominatim.enabled')) {
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'GoodIceMap/1.0 (https://goodicemap.com)'])
                ->get(self::ENDPOINT, [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'jsonv2',
                    'zoom' => 10,
                    'addressdetails' => 1,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Reverse geocode request failed', ['error' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('Reverse geocode returned an error', ['status' => $response->status()]);

            return null;
        }

        $address = $response->json('address') ?? [];

        $city = null;
        foreach (['city', 'town', 'village', 'municipality', 'county'] as $key) {
            if (! empty($address[$key])) {
                $city = $address[$key];
                break;
            }
        }

        $state = null;
        if (! empty($address['ISO3166-2-lvl4']) && str_contains($address['ISO3166-2-lvl4'], '-')) {
            $state = strtoupper(substr($address['ISO3166-2-lvl4'], strpos($address['ISO3166-2-lvl4'], '-') + 1));
        }

        $country = ! empty($address['country_code']) ? strtoupper($address['country_code']) : null;

        return ['city' => $city, 'state' => $state, 'country' => $country];
    }
}
