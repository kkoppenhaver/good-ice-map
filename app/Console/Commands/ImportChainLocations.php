<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Services\OpenStreetMapService;
use Illuminate\Console\Command;

class ImportChainLocations extends Command
{
    protected $signature = 'import:chains
                            {--chain= : Specific chain to import (e.g., sonic, chick-fil-a)}
                            {--all : Import all known good ice chains}
                            {--state= : Limit to a specific US state (e.g., TX, CA)}
                            {--city= : Limit to a specific city (e.g., Chicago, Austin)}
                            {--delay=5 : Seconds to wait between states (default: 5)}
                            {--dry-run : Preview without saving}';

    protected $description = 'Import chain locations known for good ice using OpenStreetMap (FREE, no API key needed)';

    // Chains known for having good ice - these get auto-approved with ice_score=10
    protected array $goodIceChains = [
        'sonic' => [
            'osm_brand' => 'Sonic',
            'display_name' => 'Sonic Drive-In',
            'ice_type' => 'nugget',
        ],
        'chick-fil-a' => [
            'osm_brand' => 'Chick-fil-A',
            'display_name' => 'Chick-fil-A',
            'ice_type' => 'pebble',
        ],
        'zaxbys' => [
            'osm_brand' => "Zaxby's",
            'display_name' => "Zaxby's",
            'ice_type' => 'nugget',
        ],
        'raising-canes' => [
            'osm_brand' => "Raising Cane's",
            'display_name' => "Raising Cane's",
            'ice_type' => 'pebble',
        ],
        'cookout' => [
            'osm_brand' => 'Cook Out',
            'display_name' => 'Cook Out',
            'ice_type' => 'nugget',
        ],
        'quiktrip' => [
            'osm_brand' => 'QuikTrip',
            'display_name' => 'QuikTrip',
            'ice_type' => 'nugget',
        ],
        'buc-ees' => [
            'osm_brand' => "Buc-ee's",
            'display_name' => "Buc-ee's",
            'ice_type' => 'nugget',
        ],
        'dutch-bros' => [
            'osm_brand' => 'Dutch Bros',
            'display_name' => 'Dutch Bros Coffee',
            'ice_type' => 'nugget',
        ],
    ];

    // All US states for full country import
    protected array $allStates = [
        'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
        'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
        'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
        'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
        'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY', 'DC',
    ];

    public function handle(OpenStreetMapService $osm)
    {
        $chainOption = $this->option('chain');
        $importAll = $this->option('all');
        $state = $this->option('state');
        $city = $this->option('city');
        $delay = (int) $this->option('delay');
        $dryRun = $this->option('dry-run');

        // Determine which chains to import
        $chainsToImport = [];
        if ($importAll) {
            $chainsToImport = $this->goodIceChains;
        } elseif ($chainOption) {
            $chainKey = strtolower($chainOption);
            if (!isset($this->goodIceChains[$chainKey])) {
                $this->error("Unknown chain: {$chainOption}");
                $this->newLine();
                $this->info("Available chains:");
                foreach ($this->goodIceChains as $key => $chain) {
                    $this->line("  - {$key} ({$chain['display_name']}) - {$chain['ice_type']} ice");
                }
                return 1;
            }
            $chainsToImport[$chainKey] = $this->goodIceChains[$chainKey];
        } else {
            $this->error("Please specify --chain=<name> or --all");
            $this->newLine();
            $this->info("Available chains:");
            foreach ($this->goodIceChains as $key => $chain) {
                $this->line("  - {$key} ({$chain['display_name']}) - {$chain['ice_type']} ice");
            }
            return 1;
        }

        // Determine if we need to loop through states
        $loopStates = !$state && !$city;
        $statesToProcess = $loopStates ? $this->allStates : [$state];

        // Build location filter display
        $locationFilter = 'All US (looping through states)';
        if ($city) {
            $locationFilter = $city . ($state ? ", {$state}" : '');
            $statesToProcess = [null]; // Single pass with city filter
        } elseif ($state) {
            $locationFilter = $state;
        }

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🧊 Chain Import - OpenStreetMap (FREE)");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("   Chains: " . count($chainsToImport));
        $this->line("   Location filter: " . $locationFilter);
        if ($loopStates) {
            $this->line("   States to process: " . count($this->allStates));
            $this->line("   Delay between states: {$delay} seconds");
        }
        $this->line("   Data source: OpenStreetMap (free, no API key)");
        $this->newLine();

        if ($dryRun) {
            $this->warn("⚠️  DRY RUN MODE - No data will be saved");
            $this->newLine();
        }

        $totalSaved = 0;
        $totalSkipped = 0;
        $totalFound = 0;
        $totalErrors = 0;

        foreach ($chainsToImport as $chainKey => $chainInfo) {
            $this->newLine();
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("🏪 Importing: {$chainInfo['display_name']} ({$chainInfo['ice_type']} ice)");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            $chainSaved = 0;
            $chainSkipped = 0;
            $chainFound = 0;

            $stateCount = count($statesToProcess);
            foreach ($statesToProcess as $stateIndex => $currentState) {
                $stateNum = $stateIndex + 1;

                if ($loopStates) {
                    $this->line("   [{$stateNum}/{$stateCount}] Querying {$currentState}...");
                } else {
                    $this->line("   Querying OpenStreetMap...");
                }

                try {
                    $locations = $osm->findChainLocations($chainInfo['osm_brand'], $currentState, $city);
                    $found = count($locations);
                    $chainFound += $found;
                    $totalFound += $found;

                    if ($found > 0) {
                        $this->line("   Found {$found} locations" . ($loopStates ? " in {$currentState}" : ""));
                    }

                    foreach ($locations as $loc) {
                        $result = $this->saveLocation($loc, $chainInfo, $dryRun);
                        if ($result === 'saved') {
                            $chainSaved++;
                            $totalSaved++;
                        } elseif ($result === 'skipped') {
                            $chainSkipped++;
                            $totalSkipped++;
                        }
                    }

                } catch (\Exception $e) {
                    $totalErrors++;
                    $stateLabel = $currentState ?: 'query';
                    $this->warn("   ⚠️  Error querying {$stateLabel}: " . class_basename($e));
                }

                // Delay between states (except for the last one)
                if ($loopStates && $stateIndex < $stateCount - 1 && $delay > 0) {
                    sleep($delay);
                }
            }

            $this->line("   ✅ Chain total - Saved: {$chainSaved}, Skipped: {$chainSkipped} (duplicates)");
        }

        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 Import Complete");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("   Total found: {$totalFound}");
        $this->line("   Total saved: {$totalSaved}");
        $this->line("   Total skipped (duplicates): {$totalSkipped}");
        if ($totalErrors > 0) {
            $this->line("   Total errors: {$totalErrors}");
        }
        $this->newLine();

        if (!$dryRun && $totalSaved > 0) {
            $this->info("🎉 Successfully imported {$totalSaved} chain locations!");
        }

        return 0;
    }

    /**
     * Save a single location, handling duplicates.
     */
    protected function saveLocation(array $loc, array $chainInfo, bool $dryRun): string
    {
        $osmId = $loc['osm_id'];

        // Check for duplicates by OSM ID
        if (Location::where('place_id', $osmId)->exists()) {
            return 'skipped';
        }

        // Check by coordinates (within ~100m) to avoid near-duplicates
        $nearbyExists = Location::where('latitude', '>=', $loc['latitude'] - 0.001)
            ->where('latitude', '<=', $loc['latitude'] + 0.001)
            ->where('longitude', '>=', $loc['longitude'] - 0.001)
            ->where('longitude', '<=', $loc['longitude'] + 0.001)
            ->exists();

        if ($nearbyExists) {
            return 'skipped';
        }

        if ($dryRun) {
            $addr = $loc['address'] ?: "{$loc['city']}, {$loc['state']}";
            $this->line("   ✓ [DRY RUN] {$loc['name']} - {$addr}");
            return 'saved';
        }

        try {
            $address = $loc['address'];
            if (!$address && $loc['city'] && $loc['state']) {
                $address = "{$loc['city']}, {$loc['state']}";
            }

            Location::create([
                'name' => $loc['name'] ?: $chainInfo['display_name'],
                'address' => $address,
                'latitude' => $loc['latitude'],
                'longitude' => $loc['longitude'],
                'place_id' => $osmId,
                'source' => 'scraped',
                'scraped_at' => now(),
                'ice_score' => 10,
                'business_type' => $chainInfo['display_name'],
                'status' => 'approved',
                'submitted_by' => null,
                'description' => "Known for {$chainInfo['ice_type']} ice.",
            ]);

            return 'saved';
        } catch (\Exception $e) {
            $this->error("   ❌ Failed to save {$loc['name']}: " . $e->getMessage());
            return 'error';
        }
    }
}
