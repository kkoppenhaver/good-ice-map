<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParseMapsLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.places_api_key' => 'test-key']);
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/parse-maps-link', ['url' => 'https://maps.google.com/?q=42,-87'])
            ->assertUnauthorized();
    }

    public function test_returns_formatted_address_from_place_details(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'maps.googleapis.com/maps/api/place/details/json*' => Http::response([
                'result' => [
                    'name' => 'Hilton New York Fashion District',
                    'formatted_address' => '152 W 26th St, New York, NY 10001, USA',
                    'geometry' => ['location' => ['lat' => 40.7456, 'lng' => -73.9939]],
                ],
            ]),
        ]);

        $url = 'https://www.google.com/maps/place/Hilton+New+York+Fashion+District/data=!4m6!3m5!1sChIJabc123def456!8m2!3d40.7456!4d-73.9939';

        $response = $this->actingAs($user)
            ->postJson('/api/parse-maps-link', ['url' => $url])
            ->assertOk();

        $response->assertJson([
            'success' => true,
            'name' => 'Hilton New York Fashion District',
            'address' => '152 W 26th St, New York, NY 10001, USA',
            'place_id' => 'ChIJabc123def456',
        ]);
        $response->assertJsonPath('latitude', 40.7456);
        $response->assertJsonPath('longitude', -73.9939);
    }

    public function test_geocodes_when_url_has_no_place_id(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'results' => [
                    ['place_id' => 'ChIJfromGeocode'],
                ],
            ]),
            'maps.googleapis.com/maps/api/place/details/json*' => Http::response([
                'result' => [
                    'name' => 'Sonic Drive-In',
                    'formatted_address' => '850 N Rand Rd, Lake Zurich, IL 60047, USA',
                    'geometry' => ['location' => ['lat' => 42.1956, 'lng' => -88.0884]],
                ],
            ]),
        ]);

        $url = 'https://www.google.com/maps/place/Sonic+Drive-In/@42.1956,-88.0884,17z';

        $this->actingAs($user)
            ->postJson('/api/parse-maps-link', ['url' => $url])
            ->assertOk()
            ->assertJsonPath('address', '850 N Rand Rd, Lake Zurich, IL 60047, USA')
            ->assertJsonPath('place_id', 'ChIJfromGeocode');
    }

    public function test_returns_422_when_url_has_no_extractable_coordinates(): void
    {
        $user = User::factory()->create();
        Http::fake();

        $this->actingAs($user)
            ->postJson('/api/parse-maps-link', ['url' => 'https://example.com/not-a-maps-link'])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_validates_url_field(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/parse-maps-link', ['url' => 'not-a-url'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_address_never_falls_back_to_place_name(): void
    {
        // Regression test: the original bug stored the place name in the address column.
        $user = User::factory()->create();

        Http::fake([
            'maps.googleapis.com/maps/api/place/details/json*' => Http::response([
                'result' => [
                    'name' => 'Hilton New York Fashion District',
                    'formatted_address' => '152 W 26th St, New York, NY 10001, USA',
                    'geometry' => ['location' => ['lat' => 40.7456, 'lng' => -73.9939]],
                ],
            ]),
        ]);

        $url = 'https://www.google.com/maps/place/Hilton+New+York+Fashion+District/data=!4m6!3m5!1sChIJabc!8m2!3d40.7456!4d-73.9939';

        $response = $this->actingAs($user)
            ->postJson('/api/parse-maps-link', ['url' => $url])
            ->assertOk();

        $address = $response->json('address');
        $this->assertNotSame('Hilton New York Fashion District', $address);
        $this->assertStringContainsString('W 26th St', $address);
    }
}
