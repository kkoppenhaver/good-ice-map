<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Services\GooglePlacesScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScrapeGoodIceLocations extends Command
{
    protected $signature = 'scrape:good-ice {--lat=} {--lng=} {--radius=5000} {--types=*} {--min-score=1} {--auto-approve} {--dry-run}';

    protected $description = 'Scrape Google Maps for locations with good ice mentions in reviews';

    public function handle(GooglePlacesScraperService $scraper)
    {
        $lat = $this->option('lat');
        $lng = $this->option('lng');
        $radius = (int) $this->option('radius');
        $types = $this->option('types') ?: [
            'restaurant',        // All restaurant types
            'gas station',       // Gas stations
            'convenience store', // Convenience stores
        ];
        $minScore = (int) $this->option('min-score');
        $autoApprove = $this->option('auto-approve');
        $dryRun = $this->option('dry-run');

        if (!$lat || !$lng) {
            $this->error('Please provide --lat and --lng options');
            $this->info('Example: php artisan scrape:good-ice --lat=37.7749 --lng=-122.4194');
            return 1;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        $this->info("🔍 Searching for good ice locations...");
        $this->info("   Location: {$lat}, {$lng}");
        $this->info("   Radius: {$radius}m");
        $this->info("   Business types: " . implode(', ', $types));
        $this->info("   Minimum ice score: {$minScore}");
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be saved');
            $this->newLine();
        }

        $results = $scraper->findGoodIceLocations($lat, $lng, $types, $radius);

        $this->info("Found " . count($results) . " potential locations");
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
            $this->line("   Address: {$result['address']}");
            $this->line("   Type: {$result['business_type']}");
            $this->line("   Rating: {$result['rating']} ({$result['reviews_count']} reviews)");

            if ($dryRun) {
                $this->line("   [DRY RUN] Would save this location");
                $this->newLine();
                $saved++;
                continue;
            }

            // Check if location already exists by place_id
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

        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Total found: " . count($results));
        $this->info("Filtered (score < {$minScore}): {$filtered}");
        $this->info("Saved: {$saved}");
        $this->info("Skipped (already exist): {$skipped}");

        if (!$dryRun && $saved > 0) {
            $this->newLine();
            $this->info("🎉 Successfully scraped {$saved} new locations!");
            if (!$autoApprove) {
                $this->warn("Note: Locations are pending approval. Update their status to 'approved' to show on map.");
            }
        }

        return 0;
    }
}
