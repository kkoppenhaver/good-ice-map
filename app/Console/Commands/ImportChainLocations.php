<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Services\GooglePlacesScraperService;
use Illuminate\Console\Command;

class ImportChainLocations extends Command
{
    protected $signature = 'import:chains
                            {--chain= : Specific chain to import (e.g., sonic, chick-fil-a)}
                            {--all : Import all known good ice chains}
                            {--cities= : Comma-separated list of cities, or "all" for all cities}
                            {--limit=50 : Max locations per chain per city}
                            {--dry-run : Preview without saving}';

    protected $description = 'Import chain locations known for good ice (Sonic, Chick-fil-A, etc.)';

    // Chains known for having good ice - these get auto-approved with ice_score=10
    protected array $goodIceChains = [
        'sonic' => [
            'search_name' => 'Sonic Drive-In',
            'ice_type' => 'nugget',
            'confidence' => 'high',
        ],
        'chick-fil-a' => [
            'search_name' => 'Chick-fil-A',
            'ice_type' => 'pebble',
            'confidence' => 'high',
        ],
        'zaxbys' => [
            'search_name' => 'Zaxby\'s',
            'ice_type' => 'nugget',
            'confidence' => 'high',
        ],
        'raising-canes' => [
            'search_name' => 'Raising Cane\'s',
            'ice_type' => 'pebble',
            'confidence' => 'high',
        ],
        'cookout' => [
            'search_name' => 'Cook Out',
            'ice_type' => 'nugget',
            'confidence' => 'high',
        ],
        'quiktrip' => [
            'search_name' => 'QuikTrip',
            'ice_type' => 'nugget',
            'confidence' => 'high',
        ],
        'buc-ees' => [
            'search_name' => 'Buc-ee\'s',
            'ice_type' => 'nugget',
            'confidence' => 'high',
        ],
        'dutch-bros' => [
            'search_name' => 'Dutch Bros Coffee',
            'ice_type' => 'nugget',
            'confidence' => 'medium',
        ],
        'jersey-mikes' => [
            'search_name' => 'Jersey Mike\'s',
            'ice_type' => 'nugget',
            'confidence' => 'medium',
        ],
    ];

    public function handle(GooglePlacesScraperService $scraper)
    {
        $chainOption = $this->option('chain');
        $importAll = $this->option('all');
        $citiesOption = $this->option('cities');
        $limit = (int) $this->option('limit');
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
                foreach (array_keys($this->goodIceChains) as $key) {
                    $chain = $this->goodIceChains[$key];
                    $this->line("  - {$key} ({$chain['search_name']}) - {$chain['ice_type']} ice");
                }
                return 1;
            }
            $chainsToImport[$chainKey] = $this->goodIceChains[$chainKey];
        } else {
            $this->error("Please specify --chain=<name> or --all");
            $this->newLine();
            $this->info("Available chains:");
            foreach (array_keys($this->goodIceChains) as $key) {
                $chain = $this->goodIceChains[$key];
                $this->line("  - {$key} ({$chain['search_name']}) - {$chain['ice_type']} ice");
            }
            return 1;
        }

        // Determine which cities to search
        $allCities = config('cities');
        $citiesToSearch = [];

        if (!$citiesOption || $citiesOption === 'all') {
            $citiesToSearch = $allCities;
        } else {
            $cityNames = array_map('trim', explode(',', $citiesOption));
            foreach ($cityNames as $cityName) {
                foreach (array_keys($allCities) as $key) {
                    if (strcasecmp($key, $cityName) === 0) {
                        $citiesToSearch[$key] = $allCities[$key];
                        break;
                    }
                }
            }

            if (empty($citiesToSearch)) {
                $this->error("No valid cities found in: {$citiesOption}");
                return 1;
            }
        }

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🧊 Chain Import - Known Good Ice Locations");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("   Chains: " . count($chainsToImport));
        $this->line("   Cities: " . count($citiesToSearch));
        $this->line("   Limit per chain/city: {$limit}");
        $this->newLine();

        if ($dryRun) {
            $this->warn("⚠️  DRY RUN MODE - No data will be saved");
            $this->newLine();
        }

        // Estimate API calls
        $estimatedCalls = count($chainsToImport) * count($citiesToSearch);
        $this->line("   Estimated API calls: ~{$estimatedCalls}");
        $this->newLine();

        if (!$dryRun && !$this->confirm("Proceed with import? This will use ~{$estimatedCalls} API searches.", true)) {
            $this->info('Cancelled.');
            return 0;
        }

        $totalSaved = 0;
        $totalSkipped = 0;
        $totalFound = 0;

        foreach ($chainsToImport as $chainKey => $chainInfo) {
            $this->newLine();
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("🏪 Importing: {$chainInfo['search_name']} ({$chainInfo['ice_type']} ice)");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            $chainSaved = 0;
            $chainSkipped = 0;

            foreach ($citiesToSearch as $cityName => $cityData) {
                $this->line("   📍 Searching {$cityName}, {$cityData['state']}...");

                $results = $scraper->searchBusinesses(
                    $chainInfo['search_name'],
                    $cityData['lat'],
                    $cityData['lng'],
                    15000 // 15km radius to cover metro areas
                );

                $found = count($results);
                $totalFound += $found;

                if ($found === 0) {
                    $this->line("      No locations found");
                    continue;
                }

                $this->line("      Found {$found} locations");

                $cityCount = 0;
                foreach ($results as $result) {
                    if ($cityCount >= $limit) {
                        break;
                    }

                    // Verify it's actually the chain we're looking for (not a similarly named business)
                    $resultName = strtolower($result['title'] ?? '');
                    $searchName = strtolower($chainInfo['search_name']);

                    // Check if the result name contains key parts of the chain name
                    $chainParts = explode(' ', $searchName);
                    $mainPart = $chainParts[0]; // e.g., "sonic", "chick-fil-a", "zaxby's"

                    if (!str_contains($resultName, $mainPart)) {
                        continue;
                    }

                    $placeId = $result['place_id'] ?? null;

                    // Check for duplicates
                    if ($placeId) {
                        $existing = Location::where('place_id', $placeId)->first();
                        if ($existing) {
                            $chainSkipped++;
                            $totalSkipped++;
                            continue;
                        }
                    }

                    if ($dryRun) {
                        $this->line("      ✓ [DRY RUN] Would save: {$result['title']}");
                        $chainSaved++;
                        $totalSaved++;
                        $cityCount++;
                        continue;
                    }

                    // Save the location
                    try {
                        $location = Location::create([
                            'name' => $result['title'],
                            'address' => $result['address'] ?? "{$cityName}, {$cityData['state']}",
                            'latitude' => $result['gps_coordinates']['latitude'] ?? $cityData['lat'],
                            'longitude' => $result['gps_coordinates']['longitude'] ?? $cityData['lng'],
                            'place_id' => $placeId,
                            'source' => 'scraped',
                            'scraped_at' => now(),
                            'ice_score' => 10, // Known good ice chain
                            'business_type' => $chainInfo['search_name'],
                            'status' => 'approved', // Auto-approve known chains
                            'average_rating' => $result['rating'] ?? null,
                            'submitted_by' => null,
                            'description' => "Known for {$chainInfo['ice_type']} ice.",
                        ]);

                        $this->line("      ✅ Saved: {$result['title']} (ID: {$location->id})");
                        $chainSaved++;
                        $totalSaved++;
                        $cityCount++;
                    } catch (\Exception $e) {
                        $this->error("      ❌ Failed: " . $e->getMessage());
                    }
                }
            }

            $this->newLine();
            $this->line("   Chain summary: {$chainSaved} saved, {$chainSkipped} skipped (duplicates)");
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
