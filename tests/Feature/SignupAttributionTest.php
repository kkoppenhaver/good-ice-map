<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SignupAttribution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SignupAttributionTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $email = 'ice@example.com')
    {
        return $this->post('/register', [
            'name' => 'Ice Chewer',
            'email' => $email,
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ]);
    }

    public function test_first_visit_from_reddit_with_utm_is_captured(): void
    {
        $this->withHeader('Referer', 'https://www.reddit.com/r/IceChewersAnonymous/comments/abc/')
            ->get('/map?utm_source=reddit&utm_medium=comment&utm_campaign=icechewers')
            ->assertOk();

        $this->assertSame([
            'referrer' => 'reddit.com',
            'landing_page' => '/map',
            'utm_source' => 'reddit',
            'utm_medium' => 'comment',
            'utm_campaign' => 'icechewers',
        ], collect(session(SignupAttribution::SESSION_KEY))->except('first_seen_at')->all());
    }

    public function test_first_touch_is_not_overwritten_by_later_visits(): void
    {
        $this->withHeader('Referer', 'https://www.google.com/')->get('/')->assertOk();
        $this->withHeader('Referer', 'https://www.reddit.com/')->get('/map?utm_source=reddit')->assertOk();

        $this->assertSame('google.com', session(SignupAttribution::SESSION_KEY)['referrer']);
        $this->assertArrayNotHasKey('utm_source', session(SignupAttribution::SESSION_KEY));
    }

    public function test_own_site_referrer_counts_as_direct(): void
    {
        $this->withHeader('Referer', url('/map'))->get('/')->assertOk();

        $this->assertNull(session(SignupAttribution::SESSION_KEY)['referrer']);
        $this->assertSame('direct', SignupAttribution::sourceFor(session(SignupAttribution::SESSION_KEY)));
    }

    public function test_registration_stamps_attribution_onto_user(): void
    {
        $this->withHeader('Referer', 'https://www.reddit.com/r/IceChewersAnonymous/')
            ->get('/locations/create?utm_source=Reddit&utm_medium=comment&utm_campaign=icechewers');

        $this->register()->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'ice@example.com')->firstOrFail();
        $this->assertSame('reddit', $user->signup_source);
        $this->assertSame('comment', $user->signup_medium);
        $this->assertSame('icechewers', $user->signup_campaign);
        $this->assertSame('reddit.com', $user->signup_referrer);
        $this->assertSame('/locations/create', $user->signup_landing_page);
    }

    public function test_registration_without_prior_visit_is_direct(): void
    {
        $this->register();

        $user = User::where('email', 'ice@example.com')->firstOrFail();
        $this->assertSame('direct', $user->signup_source);
        $this->assertNull($user->signup_referrer);
    }

    public function test_known_referrer_hosts_map_to_short_sources(): void
    {
        $cases = [
            'reddit.com' => 'reddit',
            'old.reddit.com' => 'reddit',
            'google.com' => 'google',
            'google.co.uk' => 'google',
            'chatgpt.com' => 'chatgpt',
            'perplexity.ai' => 'perplexity',
            't.co' => 'x',
            'notreddit.com' => 'notreddit.com',
            null => 'direct',
        ];

        foreach ($cases as $host => $expected) {
            $this->assertSame($expected, SignupAttribution::sourceFor(['referrer' => $host ?: null]), "host: {$host}");
        }

        $this->assertSame('newsletter', SignupAttribution::sourceFor(['referrer' => 'reddit.com', 'utm_source' => 'Newsletter']));
    }

    public function test_google_signup_stamps_attribution(): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-9');
        $googleUser->shouldReceive('getEmail')->andReturn('g@example.com');
        $googleUser->shouldReceive('getName')->andReturn('G User');
        $googleUser->shouldReceive('getNickname')->andReturn(null);
        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $this->withHeader('Referer', 'https://chatgpt.com/')->get('/')->assertOk();
        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'))->assertSessionHas('fathom_event', 'signup');

        $this->assertSame('chatgpt', User::where('email', 'g@example.com')->firstOrFail()->signup_source);
    }

    public function test_existing_google_user_login_does_not_fire_signup_event(): void
    {
        User::factory()->create(['email' => 'back@example.com', 'google_id' => 'google-1']);
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-1');
        $googleUser->shouldReceive('getEmail')->andReturn('back@example.com');
        $googleUser->shouldReceive('getName')->andReturn('Back');
        $googleUser->shouldReceive('getNickname')->andReturn(null);
        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $this->get('/auth/google/callback')->assertRedirect()->assertSessionMissing('fathom_event');
    }
}
