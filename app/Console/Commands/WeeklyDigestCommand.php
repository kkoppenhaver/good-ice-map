<?php

namespace App\Console\Commands;

use App\Services\WeeklyDigest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class WeeklyDigestCommand extends Command
{
    protected $signature = 'stats:weekly-digest {--dry-run : Print the digest instead of posting it to Slack}';

    protected $description = 'Post last week\'s signups, submissions and ratings (by source) to Slack';

    public function handle(WeeklyDigest $digest): int
    {
        $text = $digest->render($digest->build());

        $this->line($text);

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $url = config('services.slack.digest_webhook_url');
        if (empty($url)) {
            $this->warn('No SLACK_DIGEST_WEBHOOK_URL or SLACK_MODERATION_WEBHOOK_URL configured; nothing posted.');

            return self::SUCCESS;
        }

        $response = Http::timeout(5)->post($url, ['text' => $text]);

        if ($response->failed()) {
            $this->error('Slack webhook failed with status '.$response->status());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
