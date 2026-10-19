<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Services\ReverseGeocoder;
use Illuminate\Console\Command;

class DeriveLocationGeo extends Command
{
    protected $signature = 'locations:derive-geo
                            {--force : Recompute city/state/country/chain even when already set}
                            {--geocode : Reverse geocode rows whose address could not be parsed}
                            {--delay=1 : Seconds to wait between geocode requests (Nominatim allows 1/sec)}';

    protected $description = 'Backfill city, state, country and chain on locations from their address and name';

    public function handle(ReverseGeocoder $geocoder): int
    {
        $force = (bool) $this->option('force');
        $updated = 0;
        $total = 0;

        Location::query()->orderBy('id')->each(function (Location $location) use ($force, &$updated, &$total) {
            $total++;
            if ($location->deriveGeoFields($force)) {
                $location->saveQuietly();
                $updated++;
            }
        });

        $this->info("Parsed {$total} locations, updated {$updated}.");

        $missing = Location::query()->whereNull('city')->orderBy('id');
        $this->line("Still missing a city: {$missing->count()}.");

        if ($this->option('geocode')) {
            $delay = max(0, (int) $this->option('delay'));
            $geocoded = 0;

            foreach ($missing->get() as $location) {
                $result = $geocoder->lookup((float) $location->latitude, (float) $location->longitude);

                if ($result && $result['city']) {
                    $location->forceFill([
                        'city' => $result['city'],
                        'state' => $location->state ?? $result['state'],
                        'country' => $location->country ?? $result['country'],
                    ])->saveQuietly();
                    $geocoded++;
                    $this->line("  ✓ #{$location->id} {$location->name} → {$location->place_label}");
                } else {
                    $this->warn("  ✗ #{$location->id} {$location->name}: no result");
                }

                sleep($delay);
            }

            $this->info("Reverse geocoded {$geocoded}.");
        }

        $this->newLine();
        $this->table(
            ['Chain', 'Locations'],
            Location::query()->whereNotNull('chain')->selectRaw('chain, count(*) as n')->groupBy('chain')->orderByDesc('n')->get()
                ->map(fn ($row) => [config("chains.{$row->chain}.name", $row->chain), $row->n])->all(),
        );

        return self::SUCCESS;
    }
}
