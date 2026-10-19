<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RedditScraperService
{
    /**
     * Search Reddit for good ice mentions
     */
    public function searchForGoodIce(string $subreddit = 'all', int $limit = 100): array
    {
        $keywords = [
            'good ice',
            'best ice',
            'nugget ice',
            'pebble ice',
            'sonic ice',
            'chewable ice',
        ];

        $results = [];

        foreach ($keywords as $keyword) {
            $posts = $this->searchSubreddit($subreddit, $keyword, $limit);
            $results = array_merge($results, $posts);
        }

        return $results;
    }

    /**
     * Search a subreddit for specific keyword
     */
    public function searchSubreddit(string $subreddit, string $query, int $limit = 25): array
    {
        try {
            $url = "https://www.reddit.com/r/{$subreddit}/search.json";
            
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'GoodIceMap/1.0'])
                ->get($url, [
                    'q' => $query,
                    'restrict_sr' => 'on',
                    'limit' => $limit,
                    'sort' => 'relevance',
                ]);

            if ($response->failed()) {
                Log::error('Reddit search failed', [
                    'status' => $response->status(),
                    'subreddit' => $subreddit,
                    'query' => $query,
                ]);
                return [];
            }

            $data = $response->json();
            $posts = [];

            foreach ($data['data']['children'] ?? [] as $child) {
                $post = $child['data'];
                $posts[] = [
                    'title' => $post['title'],
                    'selftext' => $post['selftext'] ?? '',
                    'url' => 'https://reddit.com' . $post['permalink'],
                    'author' => $post['author'],
                    'subreddit' => $post['subreddit'],
                    'score' => $post['score'],
                    'num_comments' => $post['num_comments'],
                    'created_utc' => $post['created_utc'],
                    'matched_keyword' => $query,
                ];
            }

            return $posts;

        } catch (\Exception $e) {
            Log::error('Reddit API error', ['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get comments from a post
     */
    public function getPostComments(string $permalink): array
    {
        try {
            $url = "https://www.reddit.com{$permalink}.json";
            
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'GoodIceMap/1.0'])
                ->get($url);

            if ($response->failed()) {
                return [];
            }

            $data = $response->json();
            $comments = [];

            if (isset($data[1]['data']['children'])) {
                foreach ($data[1]['data']['children'] as $child) {
                    if ($child['kind'] === 't1') { // t1 = comment
                        $comment = $child['data'];
                        $comments[] = [
                            'body' => $comment['body'] ?? '',
                            'author' => $comment['author'] ?? '',
                            'score' => $comment['score'] ?? 0,
                        ];
                    }
                }
            }

            return $comments;

        } catch (\Exception $e) {
            Log::error('Reddit comments error', ['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extract location mentions from text
     */
    public function extractLocations(string $text): array
    {
        $locations = [];

        // Common chain patterns
        $chains = [
            'Sonic', 'QuikTrip', 'QT', 'Buc-ee\'s', 'Bucees', 'Sheetz', 
            'Wawa', 'RaceTrac', '7-Eleven', 'Circle K', 'Chick-fil-A',
            'Raising Cane\'s', 'Canes', 'Jimmy John\'s', 'Whataburger',
        ];

        foreach ($chains as $chain) {
            if (stripos($text, $chain) !== false) {
                $locations[] = $chain;
            }
        }

        // City/state patterns (simplified)
        if (preg_match_all('/\b([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*),?\s+([A-Z]{2})\b/', $text, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $locations[] = $matches[1][$i] . ', ' . $matches[2][$i];
            }
        }

        return array_unique($locations);
    }
}
