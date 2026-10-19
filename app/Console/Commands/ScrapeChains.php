<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Services\GooglePlacesScraperService;
use Illuminate\Console\Command;

class ScrapeChains extends Command
{
    protected $signature = 'scrape:chains {city} {--chains=*} {--radius=10000} {--auto-approve} {--dry-run}';

    protected $description = 'Scrape specific chains known for good ice';

    public function handle(GooglePlacesScraperService $scraper)
    {
        $cityName = $this->argument('city');
        $cities = config('cities');

        // Find city
        $cityKey = null;
        foreach (array_keys($cities) as $key) {
            if (strcasecmp($key, $cityName) === 0) {
                $cityKey = $key;
                break;
            }
        }

        if (!$cityKey) {
            $this->error("City '{$cityName}' not found.");
            return 1;
        }

        $city = $cities[$cityKey];
        $lat = $city['lat'];
        $lng = $city['lng'];
        $state = $city['state'];
        $radius = (int) $this->option('radius');
        $autoApprove = $this->option('auto-approve');
        $dryRun = $this->option('dry-run');

        // Get chains to search
        $allChains = config('ice_chains');
        $requestedChains = $this->option('chains');
        
        if (!empty($requestedChains)) {
            $chains = array_intersect_key($allChains, array_flip($requestedChains));
        } else {
            $chains = $allChains;
        }

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🏪 Scraping Chains in {$cityKey}, {$state}");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("   Coordinates: {$lat}, {$lng}");
        $this->line("   Radius: {$radius}m (~" . round($radius / 1609, 1) . " miles)");
        $this->line("   Chains to search: " . count($chains));
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No data will be saved');
            $this->newLine();
        }

        if (!$dryRun && !$this->confirm('Proceed? This will use ~' . count($chains) . ' API searches.', true)) {
            return 0;
        }

        $this->newLine();

        $saved = 0;
        $skipped = 0;
        $totalFound = 0;

        foreach ($chains as $chainName => $chainInfo) {
            $this->info("Searching for: {$chainName}");
            
            $businesses = $scraper->searchBusinesses($chainName, $lat, $lng, $radius);
            $found = count($businesses);
            $totalFound += $found;
            
            $this->line("   Found {$found} locations");

            foreach ($businesses as $business) {
                $name = $business['title'];
                $address = $business['address'] ?? 'No address';
                $rating = $business['rating'] ?? 'N/A';
                
                // Fetch reviews and check for ice mentions
                $dataId = $business['data_id'] ?? null;
                if (!$dataId) {
                    $this->line("   ⏭️  {$name} - No data_id, skipping");
                    continue;
                }

                $reviews = $scraper->getPlaceReviews($dataId);
                $filteredReviews = $scraper->filterIceReviews($reviews);
                $iceScore = count($filteredReviews);

                // Skip if no ice mentions
                if ($iceScore === 0) {
                    $this->line("   ⏭️  {$name} - No ice mentions in reviews");
                    continue;
                }

                $this->line("   📍 {$name} - {$address} ({$rating}★) [Ice Score: {$iceScore}]");
                
                // Show matched reviews
                foreach (array_slice($filteredReviews, 0, 2) as $idx => $match) {
                    $snippet = substr($match['review']['snippet'] ?? '', 0, 80);
                    $this->line("      💬 \"{$match['matched_keyword']}\": {$snippet}...");
                }

                if ($dryRun) {
                    $this->line("      [DRY RUN] Would save");
                    $saved++;
                    continue;
                }

                // Check if exists
                $placeId = $business['place_id'] ?? null;
                if ($placeId) {
                    $existing = Location::where('place_id', $placeId)->first();
                    if ($existing) {
                        $this->line("      ⏭️  Already exists");
                        $skipped++;
                        continue;
                    }
                }

                // Save location
                try {
                    $location = Location::create([
                        'name' => $name,
                        'address' => $address,
                        'latitude' => $business['gps_coordinates']['latitude'] ?? null,
                        'longitude' => $business['gps_coordinates']['longitude'] ?? null,
                        'place_id' => $placeId,
                        'source' => 'scraped',
                        'scraped_at' => now(),
                        'ice_score' => $iceScore,
                        'business_type' => $chainInfo['type'],
                        'status' => $autoApprove ? 'approved' : 'pending',
                        'average_rating' => $rating,
                        'submitted_by' => null,
                        'description' => "Known for {$chainInfo['ice_type']} ice",
                    ]);

                    $this->line("      ✅ Saved (ID: {$location->id})");
                    $saved++;
                } catch (\Exception $e) {
                    $this->error("      ❌ Failed: " . $e->getMessage());
                }
            }
            $this->newLine();
        }

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 Summary for {$cityKey}, {$state}");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("   Chains searched: " . count($chains));
        $this->line("   Locations found: {$totalFound}");
        $this->line("   Saved: {$saved}");
        $this->line("   Skipped (duplicates): {$skipped}");
        $this->newLine();

        if (!$dryRun && $saved > 0) {
            $this->info("🎉 Added {$saved} chain locations!");
        }

        return 0;
    }
}
