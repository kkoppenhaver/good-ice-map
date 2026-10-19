<?php

namespace Tests\Feature;

use App\Exceptions\GeocodingUnavailableException;
use App\Jobs\GeocodeLocation;
use App\Models\Location;
use App\Models\User;
use App\Services\ReverseGeocoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationGeoTest extends TestCase
{
    use RefreshDatabase;

    private function make(array $overrides = []): Location
    {
        return Location::create(array_merge([
            'name' => "Raising Cane's",
            'address' => '20 Astor Place, New York, NY, USA',
            'latitude' => 40.7296,
            'longitude' => -73.9916,
            'submitted_by' => User::factory()->create()->id,
            'status' => 'approved',
        ], $overrides));
    }

    private function nominatim(array $address): void
    {
        config(['services.nominatim.enabled' => true]);
        Http::fake([ReverseGeocoder::ENDPOINT.'*' => Http::response(['address' => $address])]);
    }

    public function test_geo_and_chain_are_derived_on_create(): void
    {
        Bus::fake();
        $location = $this->make();

        $this->assertSame('New York', $location->city);
        $this->assertSame('NY', $location->state);
        $this->assertSame('US', $location->country);
        $this->assertSame('raising-canes', $location->chain);
        $this->assertSame("Raising Cane's", $location->chain_name);
        $this->assertSame('New York, NY', $location->place_label);
        Bus::assertNotDispatched(GeocodeLocation::class);
    }

    public function test_unparseable_address_dispatches_geocode_job_after_response(): void
    {
        Bus::fake();
        $location = $this->make(['name' => 'Hotel Ice Machine', 'address' => '3700 N Broadway']);

        $this->assertNull($location->city);
        $this->assertNull($location->chain);
        $this->assertNull($location->place_label);
        Bus::assertDispatchedAfterResponse(GeocodeLocation::class, fn ($job) => $job->location->is($location));
    }

    public function test_geocode_job_fills_city_state_country(): void
    {
        Bus::fake();
        $location = $this->make(['address' => '3700 N Broadway']);
        $this->nominatim(['city' => 'Chicago', 'state' => 'Illinois', 'ISO3166-2-lvl4' => 'US-IL', 'country_code' => 'us']);

        (new GeocodeLocation($location))->handle(app(ReverseGeocoder::class));

        $location->refresh();
        $this->assertSame(['Chicago', 'IL', 'US'], [$location->city, $location->state, $location->country]);
        Http::assertSent(fn ($r) => str_starts_with($r->url(), ReverseGeocoder::ENDPOINT) && str_contains($r->header('User-Agent')[0], 'GoodIceMap'));
    }

    public function test_geocode_job_uses_town_when_no_city_and_skips_filled_rows(): void
    {
        Bus::fake();
        $this->nominatim(['town' => 'Picton', 'ISO3166-2-lvl4' => 'CA-ON', 'country_code' => 'ca']);

        $location = $this->make(['address' => 'Somewhere unparseable']);
        (new GeocodeLocation($location))->handle(app(ReverseGeocoder::class));
        $this->assertSame(['Picton', 'ON', 'CA'], [$location->fresh()->city, $location->fresh()->state, $location->fresh()->country]);

        $filled = $this->make();
        (new GeocodeLocation($filled))->handle(app(ReverseGeocoder::class));
        $this->assertSame('New York', $filled->fresh()->city);
        Http::assertSentCount(1);
    }

    public function test_geocode_no_result_leaves_row_untouched_and_does_not_loop(): void
    {
        Bus::fake();
        config(['services.nominatim.enabled' => true]);
        Http::fake([ReverseGeocoder::ENDPOINT.'*' => Http::response(['error' => 'Unable to geocode'], 404)]);
        $location = $this->make(['address' => 'Somewhere unparseable']);

        (new GeocodeLocation($location))->handle(app(ReverseGeocoder::class));

        $this->assertNull($location->fresh()->city);
        // Only the create dispatched a job; the job's own (non-)save must not re-dispatch.
        Bus::assertDispatchedAfterResponseTimes(GeocodeLocation::class, 1);
    }

    public function test_address_change_rederives_geo_and_name_change_rederives_chain(): void
    {
        Bus::fake();
        $location = $this->make();

        $location->update(['address' => '381 Mt Pleasant Rd, Toronto, ON M4S 2L5, Canada', 'name' => 'Circle K - Cobourg']);

        $this->assertSame(['Toronto', 'ON', 'CA', 'circle-k'], [$location->city, $location->state, $location->country, $location->chain]);
    }

    public function test_geocoded_city_survives_unrelated_updates(): void
    {
        Bus::fake();
        $location = $this->make(['address' => 'Somewhere unparseable']);
        $location->forceFill(['city' => 'Chicago', 'state' => 'IL', 'country' => 'US'])->save();

        $location->update(['description' => 'Great ice']);

        $this->assertSame('Chicago', $location->fresh()->city);
    }

    public function test_explicit_chain_is_not_overridden(): void
    {
        Bus::fake();
        $location = $this->make(['name' => 'The Corner Store', 'chain' => 'sonic']);

        $this->assertSame('sonic', $location->chain);
    }

    public function test_api_exposes_geo_and_chain(): void
    {
        Bus::fake();
        $location = $this->make();

        $this->getJson('/api/locations')->assertOk()->assertJsonFragment([
            'city' => 'New York', 'state' => 'NY', 'country' => 'US', 'chain' => 'raising-canes',
        ]);
    }

    public function test_backfill_command_derives_missing_fields_and_geocodes(): void
    {
        Bus::fake();
        $parsed = $this->make();
        $stale = $this->make(['name' => '7-Eleven', 'address' => '800 6th Ave, New York, NY 10001, USA']);
        $unparsed = $this->make(['name' => 'Hotel', 'address' => 'Somewhere unparseable']);

        // Simulate rows that pre-date the columns.
        Location::query()->update(['city' => null, 'state' => null, 'country' => null, 'chain' => null]);
        $this->nominatim(['city' => 'Chicago', 'ISO3166-2-lvl4' => 'US-IL', 'country_code' => 'us']);

        $this->artisan('locations:derive-geo --geocode')
            ->expectsOutputToContain('Parsed 3 locations, updated 2.')
            ->expectsOutputToContain('Still missing a city: 1.')
            ->expectsOutputToContain('Reverse geocoded 1.')
            ->assertSuccessful();

        $this->assertSame(['New York', 'raising-canes'], [$parsed->fresh()->city, $parsed->fresh()->chain]);
        $this->assertSame(['New York', '7-eleven'], [$stale->fresh()->city, $stale->fresh()->chain]);
        $this->assertSame(['Chicago', 'IL', 'US', null], [$unparsed->fresh()->city, $unparsed->fresh()->state, $unparsed->fresh()->country, $unparsed->fresh()->chain]);
        Http::assertSentCount(1);
    }

    public function test_only_one_geocode_job_per_instance_within_a_request(): void
    {
        Bus::fake();
        $location = $this->make(['address' => 'Somewhere unparseable']);
        $location->update(['description' => 'saved again, still no city']);
        $location->save();

        Bus::assertDispatchedAfterResponseTimes(GeocodeLocation::class, 1);
    }

    public function test_manual_city_wins_over_address_change_in_same_save(): void
    {
        Bus::fake();
        $location = $this->make();

        $location->update(['address' => '100 Elm St, Tulsa, OK 74103, USA', 'city' => 'Broken Arrow', 'state' => 'OK', 'country' => 'US']);

        $this->assertSame(['Broken Arrow', 'OK'], [$location->city, $location->state]);
    }

    public function test_geocode_job_skips_missing_model(): void
    {
        Bus::fake();
        Http::fake();
        $location = $this->make(['address' => 'Somewhere unparseable']);
        $job = new GeocodeLocation($location);
        $location->delete();

        $job->handle(app(ReverseGeocoder::class));

        Http::assertNothingSent();
        $this->assertTrue($job->deleteWhenMissingModels);
        $this->assertSame(3, $job->tries);
    }

    public function test_geocode_job_lets_transient_failures_bubble_for_retry(): void
    {
        Bus::fake();
        config(['services.nominatim.enabled' => true]);
        Http::fake([ReverseGeocoder::ENDPOINT.'*' => Http::response('', 429)]);
        $location = $this->make(['address' => 'Somewhere unparseable']);

        $this->expectException(GeocodingUnavailableException::class);
        (new GeocodeLocation($location))->handle(app(ReverseGeocoder::class));
    }

    public function test_backfill_stops_cleanly_when_nominatim_is_unavailable(): void
    {
        Bus::fake();
        $this->make(['name' => 'Hotel', 'address' => 'Somewhere unparseable']);
        $this->make(['name' => 'Hotel 2', 'address' => 'Also unparseable']);
        config(['services.nominatim.enabled' => true]);
        Http::fake([ReverseGeocoder::ENDPOINT.'*' => Http::response('', 503)]);

        $this->artisan('locations:derive-geo --geocode')
            ->expectsOutputToContain('stopping; re-run later')
            ->expectsOutputToContain('Reverse geocoded 0.')
            ->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_backfill_force_recomputes_existing_values(): void
    {
        Bus::fake();
        $location = $this->make();
        Location::query()->update(['city' => 'Wrong', 'chain' => 'sonic']);

        $this->artisan('locations:derive-geo')->assertSuccessful();
        $this->assertSame(['Wrong', 'sonic'], [$location->fresh()->city, $location->fresh()->chain]);

        $this->artisan('locations:derive-geo --force')->assertSuccessful();
        $this->assertSame(['New York', 'raising-canes'], [$location->fresh()->city, $location->fresh()->chain]);
    }
}
