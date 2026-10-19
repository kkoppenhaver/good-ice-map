<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Services\GooglePlacesScraperService;
use Illuminate\Console\Command;

class ScrapeCityByName extends Command
{
    protected $signature = 'scrape:city {city} {--radius=8000} {--min-score=1} {--auto-approve} {--dry-run}';

    protected $description = 'Scrape a specific US city for good ice locations by name';

    public function handle(GooglePlacesScraperService $scraper)
    {
        $cityName = $this->argument('city');
        $cities = config('cities');

        // Find city (case-insensitive)
        $cityKey = null;
        foreach (array_keys($cities) as $key) {
            if (strcasecmp($key, $cityName) === 0) {
                $cityKey = $key;
                break;
            }
        }

        if (!$cityKey) {
            $this->error("City '{$cityName}' not found in database.");
            $this->newLine();
            $this->info("Available cities:");
            foreach (array_keys($cities) as $city) {
                $this->line("  - {$city}");
            }
            return 1;
        }

        $city = $cities[$cityKey];
        $lat = $city['lat'];
        $lng = $city['lng'];
        $state = $city['state'];

        $radius = (int) $this->option('radius');
        $minScore = (int) $this->option('min-score');
        $autoApprove = $this->option('auto-approve');
        $dryRun = $this->option('dry-run');

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🏙️  Scraping: {$cityKey}, {$state}");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("   Coordinates: {$lat}, {$lng}");
        $this->line("   Radius: {$radius}m (~" . round($radius / 1609, 1) . " miles)");
        $this->line("   Min ice score: {$minScore}");
        $this->line("   Auto-approve: " . ($autoApprove ? 'Yes' : 'No'));
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No data will be saved');
            $this->newLine();
        }

        if (!$dryRun && !$this->confirm('Proceed with scraping? This will use ~60-100 API searches.', true)) {
            $this->info('Cancelled.');
            return 0;
        }

        $this->newLine();

        // Search these business types, then filter their reviews for ice keywords
        $types = [
            'restaurant',        // All restaurant types (fast food, sit-down, etc.)
            'gas station',       // Gas stations (many have fountain drinks)
            'convenience store', // 7-Eleven, Circle K, Wawa, etc.
        ];
        
        $this->info("   Searching " . count($types) . " business types for ice mentions...");
        $results = $scraper->findGoodIceLocations($lat, $lng, $types, $radius);

        $this->newLine();
        $this->info("Found " . count($results) . " locations with ice mentions");
        $this->newLine();

        $saved = 0;
        $skipped = 0;
        $filtered = 0;

        foreach ($results as $result) {
            if ($result['ice_score'] < $minScore) {
                $filtered++;
                continue;
            }

            $this->info("🧊 {$result['name']} (Ice Score: {$result['ice_score']})");
            $this->line("   📍 {$result['address']}");
            $this->line("   🏢 Type: {$result['business_type']}");
            $this->line("   ⭐ Rating: {$result['rating']} ({$result['reviews_count']} reviews)");
            
            // Show matched reviews for debugging
            if (isset($result['matched_reviews']) && count($result['matched_reviews']) > 0) {
                $this->line("   💬 Matched Reviews:");
                foreach ($result['matched_reviews'] as $idx => $review) {
                    $num = $idx + 1;
                    $this->line("      #{$num} Keyword: \"{$review['keyword']}\"");
                    $snippet = strlen($review['snippet']) > 120 
                        ? substr($review['snippet'], 0, 120) . '...' 
                        : $review['snippet'];
                    $this->line("         \"" . $snippet . "\"");
                }
            }

            if ($dryRun) {
                $this->line("   [DRY RUN] Would save this location");
                $this->newLine();
                $saved++;
                continue;
            }

            // Check if location already exists
            if ($result['place_id']) {
                $existing = Location::where('place_id', $result['place_id'])->first();
                if ($existing) {
                    $this->line("   ⏭️  Already exists - skipping");
                    $this->newLine();
                    $skipped++;
                    continue;
                }
            }

            // Save location
            try {
                $location = Location::create([
                    'name' => $result['name'],
                    'address' => $result['address'],
                    'latitude' => $result['latitude'],
                    'longitude' => $result['longitude'],
                    'place_id' => $result['place_id'],
                    'source' => 'scraped',
                    'scraped_at' => now(),
                    'ice_score' => $result['ice_score'],
                    'business_type' => $result['business_type'],
                    'status' => $autoApprove ? 'approved' : 'pending',
                    'average_rating' => $result['rating'],
                    'submitted_by' => null,
                ]);

                $this->line("   ✅ Saved (ID: {$location->id}, Status: {$location->status})");
                $this->newLine();
                $saved++;
            } catch (\Exception $e) {
                $this->error("   ❌ Failed to save: " . $e->getMessage());
                $this->newLine();
            }
        }

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 Summary for {$cityKey}, {$state}");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("   Total found: " . count($results));
        $this->line("   Filtered (score < {$minScore}): {$filtered}");
        $this->line("   Saved: {$saved}");
        $this->line("   Skipped (duplicates): {$skipped}");
        $this->newLine();

        if (!$dryRun && $saved > 0) {
            $this->info("🎉 Successfully scraped {$saved} new locations from {$cityKey}!");
            if (!$autoApprove) {
                $this->newLine();
                $this->warn("⚠️  Locations are PENDING approval.");
                $this->info("To approve all from this city:");
                $this->line("   UPDATE locations SET status = 'approved' WHERE source = 'scraped' AND status = 'pending';");
            }
        } elseif (!$dryRun && $saved === 0) {
            $this->warn("No new locations found in {$cityKey}.");
            if ($skipped > 0) {
                $this->info("All {$skipped} locations already exist in database.");
            }
        }

        return 0;
    }
}
