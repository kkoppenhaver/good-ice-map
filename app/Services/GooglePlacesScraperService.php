<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesScraperService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('serpapi.api_key');
        $this->baseUrl = config('serpapi.base_url');
    }

    /**
     * Search for businesses by type and location
     */
    public function searchBusinesses(
        string $query,
        float $latitude,
        float $longitude,
        int $radius = 5000
    ): array {
        $response = Http::timeout(30)->get($this->baseUrl, [
            'engine' => 'google_maps',
            'q' => $query,
            'll' => "@{$latitude},{$longitude},{$radius}m",
            'type' => 'search',
            'api_key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::error('SerpAPI search failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        return $response->json('local_results', []);
    }

    /**
     * Get reviews for a specific place
     */
    public function getPlaceReviews(string $dataId): array
    {
        $response = Http::timeout(30)->get($this->baseUrl, [
            'engine' => 'google_maps_reviews',
            'data_id' => $dataId,
            'api_key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::error('SerpAPI reviews fetch failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'data_id' => $dataId,
            ]);
            return [];
        }

        return $response->json('reviews', []);
    }

    /**
     * Filter reviews for ice-related keywords with confidence scoring
     */
    public function filterIceReviews(array $reviews): array
    {
        // High-confidence keywords (specific ice types and brands)
        $specificKeywords = [
            'nugget ice',
            'pellet ice',
            'pebble ice',
            'chewblet',
            'chewy ice',
            'sonic ice',
            'follett',
            'scotsman',
            'manitowoc',
        ];

        // Positive sentiment indicators
        $positiveSentiment = [
            'love the ice',
            'great ice',
            'favorite ice',
            'favourite ice',
            'best ice',
            'excellent ice',
            'amazing ice',
            'perfect ice',
            'good ice',
            'chewable ice',
        ];

        $excludeKeywords = [
            'ice cream',
            'ice-cream',
            'icecream',
            'iced coffee',
            'iced tea',
            'iced drink',
            'iced latte',
        ];

        $filtered = [];

        foreach ($reviews as $review) {
            $snippet = strtolower($review['snippet'] ?? '');
            
            // Check for exclusions first
            $hasExclusion = false;
            foreach ($excludeKeywords as $exclude) {
                if (str_contains($snippet, $exclude)) {
                    $hasExclusion = true;
                    break;
                }
            }

            if ($hasExclusion) {
                continue;
            }

            $confidenceScore = 0;
            $matchedKeyword = null;

            // High confidence: Specific ice type mentioned
            foreach ($specificKeywords as $keyword) {
                if (str_contains($snippet, $keyword)) {
                    $confidenceScore = 5;
                    $matchedKeyword = $keyword;
                    
                    // Bonus if also has positive sentiment
                    foreach ($positiveSentiment as $sentiment) {
                        if (str_contains($snippet, $sentiment)) {
                            $confidenceScore = 10;
                            break;
                        }
                    }
                    break;
                }
            }

            // Medium confidence: Positive sentiment about ice
            if ($confidenceScore === 0) {
                foreach ($positiveSentiment as $sentiment) {
                    if (str_contains($snippet, $sentiment)) {
                        $confidenceScore = 3;
                        $matchedKeyword = $sentiment;
                        break;
                    }
                }
            }

            if ($confidenceScore > 0) {
                $filtered[] = [
                    'review' => $review,
                    'matched_keyword' => $matchedKeyword,
                    'confidence_score' => $confidenceScore,
                ];
            }
        }

        return $filtered;
    }

    /**
     * Calculate ice score based on matching reviews with confidence weighting
     */
    public function calculateIceScore(array $filteredReviews): int
    {
        $totalScore = 0;
        foreach ($filteredReviews as $match) {
            $totalScore += $match['confidence_score'] ?? 1;
        }
        return $totalScore;
    }

    /**
     * Search for good ice locations
     */
    public function findGoodIceLocations(
        float $latitude,
        float $longitude,
        array $businessTypes = ['gas station', 'restaurant', 'convenience store'],
        int $radius = 5000
    ): array {
        $results = [];

        foreach ($businessTypes as $type) {
            Log::info("Searching for: {$type}");
            
            $businesses = $this->searchBusinesses($type, $latitude, $longitude, $radius);

            foreach ($businesses as $business) {
                if (!isset($business['data_id'])) {
                    continue;
                }

                Log::info("Checking reviews for: {$business['title']}");

                $reviews = $this->getPlaceReviews($business['data_id']);
                $filteredReviews = $this->filterIceReviews($reviews);
                $iceScore = $this->calculateIceScore($filteredReviews);

                if ($iceScore > 0) {
                    // Extract just the snippets and keywords for debugging
                    $matchedReviews = array_map(function($item) {
                        return [
                            'snippet' => $item['review']['snippet'] ?? '',
                            'keyword' => $item['matched_keyword'],
                            'rating' => $item['review']['rating'] ?? null,
                            'date' => $item['review']['date'] ?? null,
                        ];
                    }, $filteredReviews);

                    $results[] = [
                        'name' => $business['title'],
                        'address' => $business['address'] ?? null,
                        'latitude' => $business['gps_coordinates']['latitude'] ?? null,
                        'longitude' => $business['gps_coordinates']['longitude'] ?? null,
                        'place_id' => $business['place_id'] ?? null,
                        'data_id' => $business['data_id'],
                        'business_type' => $type,
                        'ice_score' => $iceScore,
                        'rating' => $business['rating'] ?? null,
                        'reviews_count' => $business['reviews'] ?? 0,
                        'matched_reviews' => $matchedReviews,
                    ];

                    Log::info("Found good ice location: {$business['title']} (score: {$iceScore})");
                }
            }
        }

        return $results;
    }
}
