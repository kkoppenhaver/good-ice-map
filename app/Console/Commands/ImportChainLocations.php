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
                            {--dry-run : Preview without saving}';

    protected $description = 'Import chain locations known for good ice using OpenStreetMap (FREE, no API key needed)';

    // Chains known for having good ice - these get auto-approved with ice_score=10
    // The 'osm_brand' is the brand name as it appears in OpenStreetMap
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

    public function handle(OpenStreetMapService $osm)
    {
        $chainOption = $this->option('chain');
        $importAll = $this->option('all');
        $state = $this->option('state');
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

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🧊 Chain Import - OpenStreetMap (FREE)");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("   Chains: " . count($chainsToImport));
        $this->line("   State filter: " . ($state ?: 'All US'));
        $this->line("   Data source: OpenStreetMap (free, no API key)");
        $this->newLine();

        if ($dryRun) {
            $this->warn("⚠️  DRY RUN MODE - No data will be saved");
            $this->newLine();
        }

        $totalSaved = 0;
        $totalSkipped = 0;
        $totalFound = 0;

        foreach ($chainsToImport as $chainKey => $chainInfo) {
            $this->newLine();
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("🏪 Importing: {$chainInfo['display_name']} ({$chainInfo['ice_type']} ice)");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            $this->line("   Querying OpenStreetMap...");

            $locations = $osm->findChainLocations($chainInfo['osm_brand'], $state);
            $found = count($locations);
            $totalFound += $found;

            $this->line("   Found {$found} locations");

            if ($found === 0) {
                continue;
            }

            $chainSaved = 0;
            $chainSkipped = 0;

            foreach ($locations as $loc) {
                $osmId = $loc['osm_id'];

                // Check for duplicates by OSM ID (stored in place_id field)
                $existing = Location::where('place_id', $osmId)->first();
                if ($existing) {
                    $chainSkipped++;
                    $totalSkipped++;
                    continue;
                }

                // Also check by coordinates (within ~100m) to avoid near-duplicates
                $nearbyExists = Location::where('latitude', '>=', $loc['latitude'] - 0.001)
                    ->where('latitude', '<=', $loc['latitude'] + 0.001)
                    ->where('longitude', '>=', $loc['longitude'] - 0.001)
                    ->where('longitude', '<=', $loc['longitude'] + 0.001)
                    ->exists();

                if ($nearbyExists) {
                    $chainSkipped++;
                    $totalSkipped++;
                    continue;
                }

                if ($dryRun) {
                    $addr = $loc['address'] ?: "{$loc['city']}, {$loc['state']}";
                    $this->line("   ✓ [DRY RUN] {$loc['name']} - {$addr}");
                    $chainSaved++;
                    $totalSaved++;
                    continue;
                }

                // Save the location
                try {
                    $address = $loc['address'];
                    if (!$address && $loc['city'] && $loc['state']) {
                        $address = "{$loc['city']}, {$loc['state']}";
                    }

                    $location = Location::create([
                        'name' => $loc['name'] ?: $chainInfo['display_name'],
                        'address' => $address,
                        'latitude' => $loc['latitude'],
                        'longitude' => $loc['longitude'],
                        'place_id' => $osmId, // Store OSM ID for deduplication
                        'source' => 'scraped',
                        'scraped_at' => now(),
                        'ice_score' => 10, // Known good ice chain
                        'business_type' => $chainInfo['display_name'],
                        'status' => 'approved', // Auto-approve known chains
                        'submitted_by' => null,
                        'description' => "Known for {$chainInfo['ice_type']} ice.",
                    ]);

                    $chainSaved++;
                    $totalSaved++;
                } catch (\Exception $e) {
                    $this->error("   ❌ Failed to save {$loc['name']}: " . $e->getMessage());
                }
            }

            $this->line("   ✅ Saved: {$chainSaved}, Skipped: {$chainSkipped} (duplicates)");
        }

        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 Import Complete");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("   Total found: {$totalFound}");
        $this->line("   Total saved: {$totalSaved}");
        $this->line("   Total skipped (duplicates): {$totalSkipped}");
        $this->newLine();

        if (!$dryRun && $totalSaved > 0) {
            $this->info("🎉 Successfully imported {$totalSaved} chain locations!");
        }

        return 0;
    }
}
