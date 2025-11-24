<?php

namespace App\Console\Commands;

use App\Services\GooglePlacesScraperService;
use Illuminate\Console\Command;

class BrowseReviews extends Command
{
    protected $signature = 'browse:reviews {query} {--lat=30.2672} {--lng=-97.7431} {--limit=5}';

    protected $description = 'Browse actual Google reviews to see what people say';

    public function handle(GooglePlacesScraperService $scraper)
    {
        $query = $this->argument('query');
        $lat = (float) $this->option('lat');
        $lng = (float) $this->option('lng');
        $limit = (int) $this->option('limit');

        $this->info("Searching for: {$query}");
        $this->info("Location: {$lat}, {$lng}");
        $this->newLine();

        $businesses = $scraper->searchBusinesses($query, $lat, $lng, 5000);
        
        if (empty($businesses)) {
            $this->warn("No businesses found!");
            return 0;
        }

        $this->info("Found " . count($businesses) . " businesses");
        $this->newLine();

        $checked = 0;
        foreach ($businesses as $business) {
            if ($checked >= $limit) {
                break;
            }

            if (!isset($business['data_id'])) {
                continue;
            }

            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("📍 {$business['title']}");
            $address = $business['address'] ?? 'No address';
            $this->line("   {$address}");
            $this->line("   Rating: {$business['rating']} ({$business['reviews']} reviews)");
            $this->newLine();

            $reviews = $scraper->getPlaceReviews($business['data_id']);
            
            if (empty($reviews)) {
                $this->line("   No reviews available");
                $this->newLine();
                $checked++;
                continue;
            }

            $this->line("   💬 Sample Reviews:");
            foreach (array_slice($reviews, 0, 3) as $idx => $review) {
                $num = $idx + 1;
                $rating = $review['rating'] ?? 'N/A';
                $this->line("   #{$num} ({$rating}★) {$review['snippet']}");
                $this->newLine();
            }

            // Check for ice mentions
            $snippet = strtolower(implode(' ', array_column($reviews, 'snippet')));
            $iceCount = substr_count($snippet, 'ice');
            if ($iceCount > 0) {
                $this->warn("   🧊 FOUND {$iceCount} MENTIONS OF 'ICE' IN REVIEWS!");
            }

            $checked++;
        }

        return 0;
    }
}
