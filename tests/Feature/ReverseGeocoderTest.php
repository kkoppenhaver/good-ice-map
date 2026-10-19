<?php

namespace Tests\Feature;

use App\Exceptions\GeocodingUnavailableException;
use App\Services\ReverseGeocoder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReverseGeocoderTest extends TestCase
{
    public function test_disabled_geocoder_makes_no_request(): void
    {
        Http::fake();
        config(['services.nominatim.enabled' => false]);

        $this->assertNull(app(ReverseGeocoder::class)->lookup(41.9, -87.6));
        Http::assertNothingSent();
    }

    public function test_parses_nominatim_response(): void
    {
        config(['services.nominatim.enabled' => true]);
        Http::fake([ReverseGeocoder::ENDPOINT.'*' => Http::response([
            'address' => ['village' => 'Baden', 'ISO3166-2-lvl4' => 'CA-ON', 'country_code' => 'ca'],
        ])]);

        $this->assertSame(
            ['city' => 'Baden', 'state' => 'ON', 'country' => 'CA'],
            app(ReverseGeocoder::class)->lookup(43.4, -80.7),
        );
        Http::assertSent(fn ($r) => $r['zoom'] == 10 && $r['format'] === 'jsonv2');
    }

    public function test_connection_failure_throws_for_retry(): void
    {
        config(['services.nominatim.enabled' => true]);
        Http::fake([ReverseGeocoder::ENDPOINT.'*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout')]);

        $this->expectException(GeocodingUnavailableException::class);
        app(ReverseGeocoder::class)->lookup(41.9, -87.6);
    }

    public function test_rate_limit_and_server_errors_throw_but_not_found_does_not(): void
    {
        config(['services.nominatim.enabled' => true]);
        Http::fake([ReverseGeocoder::ENDPOINT.'*' => Http::sequence()->push('', 429)->push('', 502)->push(['error' => 'Unable to geocode'], 404)]);
        $geocoder = app(ReverseGeocoder::class);

        foreach ([429, 502] as $status) {
            try {
                $geocoder->lookup(0, 0);
                $this->fail("HTTP {$status} should throw");
            } catch (GeocodingUnavailableException $e) {
                $this->assertStringContainsString((string) $status, $e->getMessage());
            }
        }

        $this->assertNull($geocoder->lookup(0, 0));
    }

    public function test_state_is_only_kept_for_us_and_canada(): void
    {
        config(['services.nominatim.enabled' => true]);
        Http::fake([ReverseGeocoder::ENDPOINT.'*' => Http::sequence()
            ->push(['address' => ['city' => 'Paris', 'ISO3166-2-lvl4' => 'FR-IDF', 'country_code' => 'fr']])
            ->push(['address' => ['county' => 'Cook County', 'ISO3166-2-lvl4' => 'US-IL', 'country_code' => 'us']])]);
        $geocoder = app(ReverseGeocoder::class);

        $this->assertSame(['city' => 'Paris', 'state' => null, 'country' => 'FR'], $geocoder->lookup(48.85, 2.35));
        // A county is not a city.
        $this->assertSame(['city' => null, 'state' => 'IL', 'country' => 'US'], $geocoder->lookup(41.9, -87.6));
    }

    public function test_requests_are_spaced_by_the_configured_interval(): void
    {
        config(['services.nominatim.enabled' => true, 'services.nominatim.min_interval_ms' => 150]);
        Http::fake([ReverseGeocoder::ENDPOINT.'*' => Http::response(['address' => []])]);
        $geocoder = app(ReverseGeocoder::class);

        $start = microtime(true);
        $geocoder->lookup(0, 0);
        $geocoder->lookup(0, 0);
        $geocoder->lookup(0, 0);

        $this->assertGreaterThanOrEqual(0.3, microtime(true) - $start);
        $this->assertNotNull(Cache::get('nominatim:last_request_at'));
    }

    public function test_user_agent_is_configurable(): void
    {
        config(['services.nominatim.enabled' => true, 'services.nominatim.user_agent' => 'TestAgent/1 (ops@example.test)']);
        Http::fake([ReverseGeocoder::ENDPOINT.'*' => Http::response(['address' => []])]);

        app(ReverseGeocoder::class)->lookup(0, 0);

        Http::assertSent(fn ($r) => $r->header('User-Agent')[0] === 'TestAgent/1 (ops@example.test)');
    }
}
