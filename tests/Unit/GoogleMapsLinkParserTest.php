<?php

namespace Tests\Unit;

use App\Services\GoogleMapsLinkParser;
use PHPUnit\Framework\TestCase;

class GoogleMapsLinkParserTest extends TestCase
{
    private GoogleMapsLinkParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new GoogleMapsLinkParser;
    }

    public function test_detects_short_links(): void
    {
        $this->assertTrue($this->parser->isShortLink('https://maps.app.goo.gl/sm3QfwSwdX9MENHX8'));
        $this->assertTrue($this->parser->isShortLink('https://goo.gl/maps/abc123'));
        $this->assertFalse($this->parser->isShortLink('https://www.google.com/maps/place/Sonic'));
    }

    public function test_extracts_name_from_place_path(): void
    {
        $url = 'https://www.google.com/maps/place/Sonic+Drive-In/@42.1234,-87.9876,17z';
        $result = $this->parser->parse($url);

        $this->assertSame('Sonic Drive-In', $result['name']);
    }

    public function test_url_decodes_name_with_special_chars(): void
    {
        $url = 'https://www.google.com/maps/place/Caf%C3%A9+du+Monde/@29.9572,-90.0623,17z';
        $result = $this->parser->parse($url);

        $this->assertSame('Café du Monde', $result['name']);
    }

    public function test_extracts_precise_coordinates_from_3d_4d(): void
    {
        $url = 'https://www.google.com/maps/place/Spot/@42.1,-87.9,17z/data=!4m6!3m5!1sChIJabc!8m2!3d42.123456!4d-87.987654';
        $result = $this->parser->parse($url);

        $this->assertEqualsWithDelta(42.123456, $result['latitude'], 0.0001);
        $this->assertEqualsWithDelta(-87.987654, $result['longitude'], 0.0001);
    }

    public function test_falls_back_to_at_coordinates_when_no_3d_4d(): void
    {
        $url = 'https://www.google.com/maps/place/Spot/@42.1234,-87.9876,17z';
        $result = $this->parser->parse($url);

        $this->assertEqualsWithDelta(42.1234, $result['latitude'], 0.0001);
        $this->assertEqualsWithDelta(-87.9876, $result['longitude'], 0.0001);
    }

    public function test_falls_back_to_ll_param(): void
    {
        $url = 'https://maps.google.com/?ll=40.7128,-74.0060';
        $result = $this->parser->parse($url);

        $this->assertEqualsWithDelta(40.7128, $result['latitude'], 0.0001);
        $this->assertEqualsWithDelta(-74.0060, $result['longitude'], 0.0001);
    }

    public function test_falls_back_to_q_param_with_coords(): void
    {
        $url = 'https://maps.google.com/?q=40.7128,-74.0060';
        $result = $this->parser->parse($url);

        $this->assertEqualsWithDelta(40.7128, $result['latitude'], 0.0001);
        $this->assertEqualsWithDelta(-74.0060, $result['longitude'], 0.0001);
    }

    public function test_extracts_chij_place_id(): void
    {
        $url = 'https://www.google.com/maps/place/Spot/data=!4m6!3m5!1sChIJN1t_tDeuEmsRUsoyG83frY4!8m2!3d-33.8670522!4d151.1957362';
        $result = $this->parser->parse($url);

        $this->assertSame('ChIJN1t_tDeuEmsRUsoyG83frY4', $result['place_id']);
    }

    public function test_ignores_non_chij_place_ids(): void
    {
        $url = 'https://www.google.com/maps/place/Spot/data=!4m6!3m5!1s0x880fd342deadbeef!8m2!3d42.1!4d-87.9';
        $result = $this->parser->parse($url);

        $this->assertNull($result['place_id']);
    }

    public function test_returns_null_fields_for_unrecognized_url(): void
    {
        $result = $this->parser->parse('https://example.com/not-a-maps-link');

        $this->assertNull($result['name']);
        $this->assertNull($result['latitude']);
        $this->assertNull($result['longitude']);
        $this->assertNull($result['place_id']);
    }

    public function test_handles_negative_coordinates(): void
    {
        $url = 'https://www.google.com/maps/place/Sydney/@-33.8688,151.2093,12z/data=!4m1!3m1!1m1!3d-33.8688!4d151.2093';
        $result = $this->parser->parse($url);

        $this->assertEqualsWithDelta(-33.8688, $result['latitude'], 0.0001);
        $this->assertEqualsWithDelta(151.2093, $result['longitude'], 0.0001);
    }
}
