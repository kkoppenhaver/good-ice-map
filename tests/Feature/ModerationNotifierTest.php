<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use App\Services\ModerationNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModerationNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_submission_fires_slack_webhook(): void
    {
        Storage::fake('r2');
        config(['services.slack.moderation_webhook_url' => 'https://hooks.slack.com/services/FAKE/HOOK/URL']);
        Http::fake();

        $user = User::factory()->create(['name' => 'Alice']);

        $this->actingAs($user)
            ->post('/locations', [
                'name' => 'Hidden Gem',
                'latitude' => 40.7,
                'longitude' => -74.0,
                'address' => '500 Cool St, NYC, USA',
                'image' => UploadedFile::fake()->image('ice.jpg'),
            ])
            ->assertRedirect('/dashboard');

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://hooks.slack.com/services/FAKE/HOOK/URL') {
                return false;
            }

            $body = $request->data();
            $blocks = $body['blocks'] ?? [];

            $blocksJson = json_encode($blocks);
            $hasContent = str_contains($blocksJson, 'Hidden Gem')
                && str_contains($blocksJson, '500 Cool St')
                && str_contains($blocksJson, 'Alice');

            $actionsBlock = collect($blocks)->firstWhere('type', 'actions');
            $actionIds = collect($actionsBlock['elements'] ?? [])->pluck('action_id')->all();
            $hasButtons = in_array('approve_location', $actionIds, true)
                && in_array('reject_location', $actionIds, true)
                && in_array('view_location', $actionIds, true);

            $hasImage = collect($blocks)->contains(fn ($b) => ($b['type'] ?? null) === 'image');

            return $hasContent && $hasButtons && $hasImage && ! empty($body['text']);
        });
    }

    public function test_no_webhook_fired_when_not_configured(): void
    {
        Storage::fake('r2');
        config(['services.slack.moderation_webhook_url' => null]);
        Http::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/locations', [
                'name' => 'Spot',
                'latitude' => 40.7,
                'longitude' => -74.0,
                'address' => '1 St',
                'image' => UploadedFile::fake()->image('ice.jpg'),
            ])
            ->assertRedirect();

        Http::assertNothingSent();
    }

    public function test_webhook_failure_does_not_break_submission(): void
    {
        Storage::fake('r2');
        config(['services.slack.moderation_webhook_url' => 'https://hooks.slack.com/services/BROKEN']);
        Http::fake([
            'hooks.slack.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('boom'),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/locations', [
                'name' => 'Spot',
                'latitude' => 40.7,
                'longitude' => -74.0,
                'address' => '1 St',
                'image' => UploadedFile::fake()->image('ice.jpg'),
            ])
            ->assertRedirect('/dashboard');

        $this->assertSame(1, Location::count());
    }

    public function test_notifier_skips_silently_when_url_empty(): void
    {
        config(['services.slack.moderation_webhook_url' => '']);
        Http::fake();

        $location = Location::create([
            'name' => 'Spot',
            'address' => '1 St',
            'latitude' => 40.0,
            'longitude' => -75.0,
            'submitted_by' => User::factory()->create()->id,
            'status' => 'pending',
        ]);

        app(ModerationNotifier::class)->notifyPending($location);

        Http::assertNothingSent();
    }
}
