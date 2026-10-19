<?php

namespace App\Services;

use App\Exceptions\GeocodingUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Reverse geocodes coordinates with OpenStreetMap's Nominatim.
 *
 * Nominatim's usage policy allows one request per second with an identifying
 * User-Agent. The throttle below is enforced through the shared cache so it
 * holds across queue workers, after-response jobs and the backfill command.
 */
class ReverseGeocoder
{
    public const ENDPOINT = 'https://nominatim.openstreetmap.org/reverse';

    private const LAST_REQUEST_KEY = 'nominatim:last_request_at';

    /** Subdivision codes are only comparable to AddressParser's output here. */
    private const STATE_COUNTRIES = ['US', 'CA'];

    /**
     * @return array{city: ?string, state: ?string, country: ?string}|null null when disabled or no result
     *
     * @throws GeocodingUnavailableException on a transient failure worth retrying
     */
    public function lookup(float $latitude, float $longitude): ?array
    {
        if (! config('services.nominatim.enabled')) {
            return null;
        }

        $response = $this->throttled(fn () => Http::timeout(10)
            ->withHeaders(['User-Agent' => config('services.nominatim.user_agent')])
            ->get(self::ENDPOINT, [
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'jsonv2',
                'zoom' => 10,
                'addressdetails' => 1,
            ]));

        if ($response->status() === 429 || $response->serverError()) {
            throw new GeocodingUnavailableException("Nominatim returned HTTP {$response->status()}");
        }

        if ($response->failed()) {
            return null;
        }

        $address = $response->json('address') ?? [];

        $city = null;
        foreach (['city', 'town', 'village', 'municipality'] as $key) {
            if (! empty($address[$key])) {
                $city = $address[$key];
                break;
            }
        }

        $country = ! empty($address['country_code']) ? strtoupper($address['country_code']) : null;

        $state = null;
        $iso = $address['ISO3166-2-lvl4'] ?? '';
        if (in_array($country, self::STATE_COUNTRIES, true) && preg_match('/^[A-Z]{2}-([A-Z]{2})$/i', $iso, $m)) {
            $state = strtoupper($m[1]);
        }

        return ['city' => $city, 'state' => $state, 'country' => $country];
    }

    /**
     * Run $request no sooner than the configured interval after the previous
     * request, whichever process made it.
     */
    private function throttled(callable $request)
    {
        $interval = (int) config('services.nominatim.min_interval_ms', 1000);
        $lock = Cache::lock('nominatim:throttle', 10);

        try {
            $lock->block(15);

            $last = (float) Cache::get(self::LAST_REQUEST_KEY, 0);
            $waitMs = (int) ceil($last + $interval - microtime(true) * 1000);
            if ($waitMs > 0) {
                usleep($waitMs * 1000);
            }

            Cache::put(self::LAST_REQUEST_KEY, microtime(true) * 1000, 60);

            return $request();
        } catch (ConnectionException $e) {
            throw new GeocodingUnavailableException('Nominatim unreachable: '.$e->getMessage(), previous: $e);
        } finally {
            $lock->release();
        }
    }
}
