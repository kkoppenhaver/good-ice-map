<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeeklyDigestTest extends TestCase
{
    use RefreshDatabase;

    private function seedWeek(): void
    {
        $reddit = User::factory()->create(['signup_source' => 'reddit']);
        $reddit2 = User::factory()->create(['signup_source' => 'reddit']);
        $google = User::factory()->create(['signup_source' => 'google']);
        $old = User::factory()->create(['signup_source' => 'reddit', 'created_at' => now()->subDays(10)]);

        $approved = Location::create(['name' => 'A', 'address' => 'x', 'latitude' => 1, 'longitude' => 1, 'submitted_by' => $reddit->id, 'status' => 'approved']);
        Location::create(['name' => 'B', 'address' => 'x', 'latitude' => 1, 'longitude' => 1, 'submitted_by' => $google->id, 'status' => 'pending']);
        Rating::create(['location_id' => $approved->id, 'user_id' => $reddit2->id, 'rating' => 5]);
        Rating::forceCreate(['location_id' => $approved->id, 'user_id' => $old->id, 'rating' => 4, 'created_at' => now()->subDays(9)]);
    }

    public function test_digest_counts_last_seven_days_by_source(): void
    {
        $this->seedWeek();
        Http::fake();
        config(['services.slack.digest_webhook_url' => 'https://hooks.slack.test/digest']);

        $this->artisan('stats:weekly-digest')->assertSuccessful();

        Http::assertSent(function ($request) {
            $text = $request->data()['text'];

            return $request->url() === 'https://hooks.slack.test/digest'
                && str_contains($text, 'Signups: *3* (reddit 2, google 1)')
                && str_contains($text, 'Locations submitted: *2* (1 still pending)')
                && str_contains($text, 'approved this week: *1*')
                && str_contains($text, 'Ratings: *1*')
                && str_contains($text, '4 users · 1 approved spots · 2 ratings');
        });
    }

    public function test_dry_run_prints_without_posting(): void
    {
        $this->seedWeek();
        Http::fake();
        config(['services.slack.digest_webhook_url' => 'https://hooks.slack.test/digest']);

        $this->artisan('stats:weekly-digest --dry-run')
            ->expectsOutputToContain('Signups: *3*')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_missing_webhook_is_a_warning_not_a_failure(): void
    {
        Http::fake();
        config(['services.slack.digest_webhook_url' => null]);

        $this->artisan('stats:weekly-digest')->assertSuccessful();

        Http::assertNothingSent();
    }
}
