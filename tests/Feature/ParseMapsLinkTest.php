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

    public function test_geocodes_by_name_for_mobile_share_links(): void
    {
        // Mobile-app share links expand to a URL with the legacy hex feature ID
        // (no ChIJ place_id, no !3d/!4d coordinates), only a /place/<full address> segment.
        $user = User::factory()->create();

        Http::fake([
            'maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'results' => [
                    [
                        'place_id' => 'ChIJ415SVCrTD4gRKUKd4i7yICg',
                        'geometry' => ['location' => ['lat' => 41.9540394, 'lng' => -87.6505912]],
                    ],
                ],
            ]),
            'maps.googleapis.com/maps/api/place/details/json*' => Http::response([
                'result' => [
                    'name' => 'TRIO',
                    'formatted_address' => '841 W Irving Park Rd, Chicago, IL 60613, USA',
                    'geometry' => ['location' => ['lat' => 41.9540394, 'lng' => -87.6505912]],
                ],
            ]),
        ]);

        $url = 'https://www.google.com/maps/place/TRIO,+841+W+Irving+Park+Rd,+Chicago,+IL+60613/data=!4m2!3m1!1s0x880fd32a54525ee3:0x2820f22ee29d4229';

        $this->actingAs($user)
            ->postJson('/api/parse-maps-link', ['url' => $url])
            ->assertOk()
            ->assertJsonPath('name', 'TRIO')
            ->assertJsonPath('address', '841 W Irving Park Rd, Chicago, IL 60613, USA')
            ->assertJsonPath('place_id', 'ChIJ415SVCrTD4gRKUKd4i7yICg');
    }

    public function test_prefers_place_details_name_when_url_has_no_chij_place_id(): void
    {
        // Some mobile-share URLs include lat/lng plus an address-as-name in the /place/ segment
        // but no ChIJ place_id. We must reverse-geocode and use the canonical Place Details name
        // (e.g. "Circle K") rather than the address that was embedded in the URL.
        $user = User::factory()->create();

        Http::fake([
            'maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'results' => [
                    ['place_id' => 'ChIJreverseGeocoded'],
                ],
            ]),
            'maps.googleapis.com/maps/api/place/details/json*' => Http::response([
                'result' => [
                    'name' => 'Circle K',
                    'formatted_address' => '485 Queen St W, Toronto, ON M5V 2A9, Canada',
                    'geometry' => ['location' => ['lat' => 43.6481874, 'lng' => -79.3979691]],
                ],
            ]),
        ]);

        $url = 'https://www.google.com/maps/place/485+Queen+St+W,+Toronto,+ON+M5V+2A9,+Canada/@43.6481874,-79.3979691,17z';

        $this->actingAs($user)
            ->postJson('/api/parse-maps-link', ['url' => $url])
            ->assertOk()
            ->assertJsonPath('name', 'Circle K')
            ->assertJsonPath('address', '485 Queen St W, Toronto, ON M5V 2A9, Canada');
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
