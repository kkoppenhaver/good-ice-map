<?php

namespace App\Jobs;

use App\Models\Location;
use App\Services\ReverseGeocoder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Fills city / state / country for a location whose address could not be
 * parsed, by reverse geocoding its coordinates. Transient Nominatim failures
 * throw GeocodingUnavailableException so the queue retries with backoff;
 * after-response dispatch has no retry, and the backfill command picks those
 * rows up later.
 */
class GeocodeLocation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    /** The location may be deleted before a queued job runs; just drop it. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Location $location) {}

    public function handle(ReverseGeocoder $geocoder): void
    {
        $location = $this->location->fresh();

        if (! $location || $location->city !== null) {
            return;
        }

        $result = $geocoder->lookup((float) $location->latitude, (float) $location->longitude);

        if (! $result || ! $result['city']) {
            return;
        }

        $location->forceFill([
            'city' => $result['city'],
            'state' => $location->state ?? $result['state'],
            'country' => $location->country ?? $result['country'],
        ])->save();
    }
}
