<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ModerationNotifier
{
    public function notifyPending(Location $location): void
    {
        $url = config('services.slack.moderation_webhook_url');
        if (empty($url)) {
            return;
        }

        $payload = [
            'text' => SlackLocationBlocks::fallbackText($location),
            'blocks' => SlackLocationBlocks::build($location),
        ];

        try {
            Http::timeout(5)->post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('Slack moderation webhook failed', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
