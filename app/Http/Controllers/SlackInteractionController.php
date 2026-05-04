<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\SlackLocationBlocks;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackInteractionController extends Controller
{
    private const ACTION_APPROVE = 'approve_location';

    private const ACTION_REJECT = 'reject_location';

    private const ACTION_VIEW = 'view_location';

    public function handle(Request $request): Response
    {
        $payload = json_decode((string) $request->input('payload'), true);
        if (! is_array($payload)) {
            return response('Invalid payload', 400);
        }

        $action = $payload['actions'][0] ?? null;
        if (! $action) {
            return response('No action', 400);
        }

        $actionId = $action['action_id'] ?? null;
        if ($actionId === self::ACTION_VIEW) {
            return response('', 200);
        }

        if (! in_array($actionId, [self::ACTION_APPROVE, self::ACTION_REJECT], true)) {
            return response('Unknown action', 400);
        }

        $locationId = $action['value'] ?? null;
        $location = $locationId ? Location::find($locationId) : null;
        $userName = $payload['user']['name'] ?? $payload['user']['username'] ?? 'someone';
        $responseUrl = $payload['response_url'] ?? null;

        if (! $location) {
            $this->postReplacement($responseUrl, [
                [
                    'type' => 'section',
                    'text' => ['type' => 'mrkdwn', 'text' => '⚠️ This location no longer exists.'],
                ],
            ]);

            return response('', 200);
        }

        if ($location->status !== 'pending') {
            $this->postReplacement(
                $responseUrl,
                SlackLocationBlocks::build($location, sprintf('⚠️ Already %s.', $location->status)),
            );

            return response('', 200);
        }

        $newStatus = $actionId === self::ACTION_APPROVE ? 'approved' : 'rejected';
        $location->update(['status' => $newStatus]);

        $banner = $newStatus === 'approved'
            ? sprintf('✅ Approved by @%s at %s', $userName, now()->format('M j, g:i A T'))
            : sprintf('❌ Rejected by @%s at %s', $userName, now()->format('M j, g:i A T'));

        $this->postReplacement($responseUrl, SlackLocationBlocks::build($location, $banner));

        return response('', 200);
    }

    private function postReplacement(?string $responseUrl, array $blocks): void
    {
        if (empty($responseUrl)) {
            return;
        }

        try {
            Http::timeout(5)->post($responseUrl, [
                'replace_original' => true,
                'blocks' => $blocks,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Slack response_url update failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
