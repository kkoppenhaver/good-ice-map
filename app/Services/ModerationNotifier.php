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

        $submitter = $location->submittedBy?->name ?? 'Unknown';
        $queueUrl = route('admin.locations.index');
        $text = sprintf(
            "🆕 New ice spot pending review: *%s*\n%s\nSubmitted by %s — <%s|Open queue>",
            $location->name,
            $location->address,
            $submitter,
            $queueUrl,
        );

        try {
            Http::timeout(5)->post($url, ['text' => $text]);
        } catch (\Throwable $e) {
            Log::warning('Slack moderation webhook failed', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
