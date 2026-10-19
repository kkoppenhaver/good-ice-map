<?php

namespace App\Console\Commands;

use App\Services\RedditScraperService;
use Illuminate\Console\Command;

class ScrapeReddit extends Command
{
    protected $signature = 'scrape:reddit {--subreddit=all} {--limit=100}';

    protected $description = 'Search Reddit for good ice mentions and location recommendations';

    public function handle(RedditScraperService $reddit)
    {
        $subreddit = $this->option('subreddit');
        $limit = (int) $this->option('limit');

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🔍 Searching Reddit for Good Ice Mentions");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("   Subreddit: r/{$subreddit}");
        $this->line("   Limit: {$limit} posts per keyword");
        $this->newLine();

        $this->info("Searching for ice-related posts...");
        $this->newLine();

        $posts = $reddit->searchForGoodIce($subreddit, $limit);

        if (empty($posts)) {
            $this->warn("No posts found!");
            return 0;
        }

        $this->info("Found " . count($posts) . " posts mentioning good ice");
        $this->newLine();

        $locationMentions = [];

        foreach ($posts as $idx => $post) {
            if ($idx >= 20) { // Limit output
                $this->line("... and " . (count($posts) - 20) . " more posts");
                break;
            }

            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->line("📝 {$post['title']}");
            $this->line("   r/{$post['subreddit']} · u/{$post['author']} · {$post['score']}↑ · {$post['num_comments']} comments");
            $this->line("   Keyword: \"{$post['matched_keyword']}\"");
            
            // Extract locations from title and body
            $text = $post['title'] . ' ' . $post['selftext'];
            $locations = $reddit->extractLocations($text);
            
            if (!empty($locations)) {
                $this->line("   🏪 Mentions: " . implode(', ', $locations));
                foreach ($locations as $loc) {
                    $locationMentions[$loc] = ($locationMentions[$loc] ?? 0) + 1;
                }
            }
            
            if (!empty($post['selftext'])) {
                $snippet = strlen($post['selftext']) > 200 
                    ? substr($post['selftext'], 0, 200) . '...'
                    : $post['selftext'];
                $this->line("   💬 \"{$snippet}\"");
            }
            
            $this->line("   🔗 {$post['url']}");
            $this->newLine();
        }

        // Summary of most mentioned locations
        if (!empty($locationMentions)) {
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("📊 Most Mentioned Locations");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            arsort($locationMentions);
            foreach (array_slice($locationMentions, 0, 15) as $location => $count) {
                $this->line("   {$location}: {$count} mentions");
            }
            $this->newLine();
            
            $this->info("💡 Tip: Use these locations with scrape:chains command");
            $this->line("   Example: php artisan scrape:chains \"Austin\" --chains=\"Sonic\" --chains=\"QuikTrip\"");
        }

        return 0;
    }
}
