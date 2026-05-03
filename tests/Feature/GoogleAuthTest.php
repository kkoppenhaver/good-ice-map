<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $id, string $email, string $name = 'Test User'): SocialiteUser
    {
        $user = Mockery::mock(SocialiteUser::class);
        $user->shouldReceive('getId')->andReturn($id);
        $user->shouldReceive('getEmail')->andReturn($email);
        $user->shouldReceive('getName')->andReturn($name);
        $user->shouldReceive('getNickname')->andReturn(null);

        return $user;
    }

    public function test_redirect_route_sends_user_to_google(): void
    {
        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    public function test_callback_creates_new_user_and_logs_in(): void
    {
        Socialite::shouldReceive('driver->user')
            ->andReturn($this->fakeGoogleUser('google-1', 'new@example.com', 'New User'));

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame('google-1', $user->google_id);
        $this->assertSame('New User', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password);
        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_links_google_id_to_existing_email_user(): void
    {
        $existing = User::factory()->create([
            'email' => 'returning@example.com',
            'google_id' => null,
        ]);

        Socialite::shouldReceive('driver->user')
            ->andReturn($this->fakeGoogleUser('google-2', 'returning@example.com'));

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));

        $existing->refresh();
        $this->assertSame('google-2', $existing->google_id);
        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::count());
    }

    public function test_callback_logs_in_existing_google_user(): void
    {
        $existing = User::factory()->create([
            'google_id' => 'google-3',
            'email' => 'old@example.com',
        ]);

        Socialite::shouldReceive('driver->user')
            ->andReturn($this->fakeGoogleUser('google-3', 'old@example.com'));

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::count());
    }

    public function test_callback_failure_redirects_to_login_with_error(): void
    {
        Socialite::shouldReceive('driver->user')
            ->andThrow(new \RuntimeException('OAuth state mismatch'));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertSame(0, User::count());
        $this->assertGuest();
    }
}
