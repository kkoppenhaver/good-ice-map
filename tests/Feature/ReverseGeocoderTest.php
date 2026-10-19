<?php

namespace Tests\Feature;

use App\Services\ReverseGeocoder;
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

    public function test_connection_failure_returns_null(): void
    {
        config(['services.nominatim.enabled' => true]);
        Http::fake([ReverseGeocoder::ENDPOINT.'*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout')]);

        $this->assertNull(app(ReverseGeocoder::class)->lookup(41.9, -87.6));
    }
}
