<?php

namespace App\Console\Commands;

use App\Services\GooglePlacesScraperService;
use Illuminate\Console\Command;

class TestSerpApi extends Command
{
    protected $signature = 'test:serpapi {--lat=37.7749} {--lng=-122.4194}';

    protected $description = 'Test SerpAPI integration with Google Maps search';

    public function handle(GooglePlacesScraperService $scraper)
    {
        $lat = (float) $this->option('lat');
        $lng = (float) $this->option('lng');

        $this->info("Testing SerpAPI with coordinates: {$lat}, {$lng}");
        $this->newLine();

        // Test 1: Search for gas stations
        $this->info('1. Searching for gas stations...');
        $gasStations = $scraper->searchBusinesses('gas station', $lat, $lng, 3000);
        $this->info('Found ' . count($gasStations) . ' gas stations');
        
        if (count($gasStations) > 0) {
            $first = $gasStations[0];
            $this->line("  Example: {$first['title']}");
            $this->line("  Address: " . ($first['address'] ?? 'N/A'));
        }
        $this->newLine();

        // Test 2: Get reviews for first result
        if (count($gasStations) > 0 && isset($gasStations[0]['data_id'])) {
            $this->info('2. Fetching reviews for first result...');
            $dataId = $gasStations[0]['data_id'];
            $reviews = $scraper->getPlaceReviews($dataId);
            $this->info('Found ' . count($reviews) . ' reviews');
            
            if (count($reviews) > 0) {
                $this->line("  Example review: " . substr($reviews[0]['snippet'] ?? '', 0, 100) . '...');
            }
            $this->newLine();

            // Test 3: Filter for ice mentions
            $this->info('3. Filtering for ice-related reviews...');
            $iceReviews = $scraper->filterIceReviews($reviews);
            $this->info('Found ' . count($iceReviews) . ' ice-related reviews');
            
            foreach ($iceReviews as $iceReview) {
                $this->line("  Matched keyword: '{$iceReview['matched_keyword']}'");
                $this->line("  Review snippet: " . substr($iceReview['review']['snippet'] ?? '', 0, 150) . '...');
                $this->newLine();
            }
        }

        // Test 4: Full search test
        $this->info('4. Running full good ice search (1 business type)...');
        $results = $scraper->findGoodIceLocations($lat, $lng, ['gas station'], 3000);
        
        $this->info('Found ' . count($results) . ' locations with good ice mentions!');
        $this->newLine();

        foreach ($results as $result) {
            $this->info("🧊 {$result['name']} (Score: {$result['ice_score']})");
            $this->line("   Address: {$result['address']}");
            $this->line("   Type: {$result['business_type']}");
            $this->line("   Rating: {$result['rating']} ({$result['reviews_count']} reviews)");
            $this->newLine();
        }

        return 0;
    }
}
