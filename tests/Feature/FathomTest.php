<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FathomTest extends TestCase
{
    use RefreshDatabase;

    public function test_script_renders_only_in_production(): void
    {
        $this->get('/')->assertOk()->assertDontSee('cdn.usefathom.com', false);

        $this->app->detectEnvironment(fn () => 'production');

        $this->get('/')->assertOk()
            ->assertSee('<script src="https://cdn.usefathom.com/script.js" data-site="ZIAKTFFD" defer></script>', false);
    }

    public function test_script_is_omitted_when_site_id_is_blank(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['services.fathom.site_id' => '']);

        $this->get('/')->assertOk()->assertDontSee('usefathom', false);
    }

    public function test_registration_flashes_signup_event_and_next_page_tracks_it(): void
    {
        $this->post('/register', [
            'name' => 'Ice Chewer',
            'email' => 'ice@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertSessionHas('fathom_event', 'signup');

        $this->get('/dashboard')->assertOk()->assertSee('window.fathom.trackEvent("signup")', false);

        // The flash is consumed by that one page.
        $this->get('/dashboard')->assertOk()->assertDontSee('trackEvent', false);
    }

    public function test_rating_flashes_event(): void
    {
        $user = User::factory()->create();
        $location = Location::create([
            'name' => 'Spot',
            'address' => '1 Ice St',
            'latitude' => 40.0,
            'longitude' => -75.0,
            'submitted_by' => $user->id,
            'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->post("/locations/{$location->id}/rate", ['rating' => 5])
            ->assertSessionHas('fathom_event', 'rating_submitted');
    }

    public function test_event_name_is_json_encoded(): void
    {
        $response = $this->withSession(['fathom_event' => 'x"</script>'])
            ->get('/')
            ->assertOk()
            ->assertDontSee('x"</script>', false);

        preg_match('/trackEvent\\((.+?)\\); \\}/', $response->getContent(), $m);
        $this->assertSame('x"</script>', json_decode($m[1]));
    }
}
