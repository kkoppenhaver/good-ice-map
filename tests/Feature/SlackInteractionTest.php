<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\LocationImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SlackInteractionTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-signing-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.slack.signing_secret' => self::SECRET]);
        Storage::fake('r2');
    }

    public function test_missing_signature_is_rejected(): void
    {
        $this->post('/api/slack/interactions', ['payload' => '{}'])
            ->assertStatus(401);
    }

    public function test_stale_timestamp_is_rejected(): void
    {
        $body = http_build_query(['payload' => '{}']);
        $ts = (string) (time() - 60 * 10);
        $sig = $this->signature($ts, $body);

        $this->postRaw($body, $ts, $sig)->assertStatus(401);
    }

    public function test_bad_signature_is_rejected(): void
    {
        $ts = (string) time();
        $body = http_build_query(['payload' => '{}']);

        $this->postRaw($body, $ts, 'v0=deadbeef')->assertStatus(401);
    }

    public function test_approve_action_flips_status_and_replaces_message(): void
    {
        Http::fake();

        $location = $this->makePendingLocation();
        $payload = json_encode([
            'actions' => [['action_id' => 'approve_location', 'value' => (string) $location->id]],
            'user' => ['name' => 'kk'],
            'response_url' => 'https://hooks.slack.com/actions/RESPONSE_URL',
        ]);

        $this->signedPost($payload)->assertStatus(200);

        $this->assertSame('approved', $location->fresh()->status);

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://hooks.slack.com/actions/RESPONSE_URL') {
                return false;
            }
            $body = $request->data();
            if (($body['replace_original'] ?? null) !== true) {
                return false;
            }
            $blocks = $body['blocks'] ?? [];
            $hasBanner = collect($blocks)->contains(fn ($b) => str_contains(json_encode($b), 'Approved by @kk'));
            $noActions = ! collect($blocks)->contains(fn ($b) => ($b['type'] ?? null) === 'actions');

            return $hasBanner && $noActions;
        });
    }

    public function test_reject_action_flips_status(): void
    {
        Http::fake();

        $location = $this->makePendingLocation();
        $payload = json_encode([
            'actions' => [['action_id' => 'reject_location', 'value' => (string) $location->id]],
            'user' => ['name' => 'kk'],
            'response_url' => 'https://hooks.slack.com/actions/RR',
        ]);

        $this->signedPost($payload)->assertStatus(200);

        $this->assertSame('rejected', $location->fresh()->status);
    }

    public function test_view_action_is_a_no_op(): void
    {
        Http::fake();

        $location = $this->makePendingLocation();
        $payload = json_encode([
            'actions' => [['action_id' => 'view_location', 'value' => (string) $location->id]],
            'user' => ['name' => 'kk'],
            'response_url' => 'https://hooks.slack.com/actions/RR',
        ]);

        $this->signedPost($payload)->assertStatus(200);

        $this->assertSame('pending', $location->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_already_acted_on_location_shows_warning(): void
    {
        Http::fake();

        $location = $this->makePendingLocation();
        $location->update(['status' => 'approved']);

        $payload = json_encode([
            'actions' => [['action_id' => 'approve_location', 'value' => (string) $location->id]],
            'user' => ['name' => 'kk'],
            'response_url' => 'https://hooks.slack.com/actions/RR',
        ]);

        $this->signedPost($payload)->assertStatus(200);

        Http::assertSent(function ($request) {
            $blocks = $request->data()['blocks'] ?? [];

            return collect($blocks)->contains(fn ($b) => str_contains(json_encode($b), 'Already approved'));
        });
    }

    private function makePendingLocation(): Location
    {
        $user = User::factory()->create(['name' => 'Alice']);
        $location = Location::create([
            'name' => 'Hidden Gem',
            'description' => 'Crisp clear cubes',
            'address' => '500 Cool St, NYC',
            'latitude' => 40.7,
            'longitude' => -74.0,
            'submitted_by' => $user->id,
            'status' => 'pending',
        ]);

        LocationImage::create([
            'location_id' => $location->id,
            'image_path' => 'location-images/fake.jpg',
            'is_primary' => true,
            'uploaded_by' => $user->id,
        ]);

        return $location;
    }

    private function signedPost(string $payloadJson): \Illuminate\Testing\TestResponse
    {
        $body = http_build_query(['payload' => $payloadJson]);
        $ts = (string) time();
        $sig = $this->signature($ts, $body);

        return $this->postRaw($body, $ts, $sig);
    }

    private function postRaw(string $body, string $ts, string $sig): \Illuminate\Testing\TestResponse
    {
        parse_str($body, $params);

        return $this->call(
            'POST',
            '/api/slack/interactions',
            $params,
            [],
            [],
            [
                'HTTP_X-Slack-Request-Timestamp' => $ts,
                'HTTP_X-Slack-Signature' => $sig,
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ],
            $body,
        );
    }

    private function signature(string $ts, string $body): string
    {
        return 'v0='.hash_hmac('sha256', 'v0:'.$ts.':'.$body, self::SECRET);
    }
}
