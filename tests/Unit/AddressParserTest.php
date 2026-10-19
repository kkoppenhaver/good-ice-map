<?php

namespace Tests\Unit;

use App\Support\AddressParser;
use PHPUnit\Framework\TestCase;

class AddressParserTest extends TestCase
{
    public static function addresses(): array
    {
        return [
            'us with zip' => ['152 W 26th St, New York, NY 10001, USA', 'New York', 'NY', 'US'],
            'us without zip' => ['20 Astor Place, New York, NY, USA', 'New York', 'NY', 'US'],
            'us with place name prefix' => ["Raising Cane's, East Palatine Road, Arlington Heights, IL, USA", 'Arlington Heights', 'IL', 'US'],
            'us suite in street' => ['1 Pennsylvania Plaza Suite 134, New York, NY 10119, USA', 'New York', 'NY', 'US'],
            'us united states spelled out' => ['100 Elm St, Tulsa, OK 74103, United States', 'Tulsa', 'OK', 'US'],
            'us no country' => ['2810 N Clark St, Chicago, IL 60657', 'Chicago', 'IL', 'US'],
            'us city state only' => ['Chicago, IL', 'Chicago', 'IL', 'US'],
            'canada with postal code' => ['381 Mt Pleasant Rd, Toronto, ON M4S 2L5, Canada', 'Toronto', 'ON', 'CA'],
            'canada without postal code' => ['Circle K, Paul Street, Picton, ON, Canada', 'Picton', 'ON', 'CA'],
            'canada apostrophe in street' => ["18 Snyder's Rd W, Baden, ON N3A 0A7, Canada", 'Baden', 'ON', 'CA'],
            'dc' => ['1600 Pennsylvania Ave NW, Washington, DC 20500, USA', 'Washington', 'DC', 'US'],
        ];
    }

    /** @dataProvider addresses */
    public function test_parses_north_american_addresses(string $address, string $city, string $state, string $country): void
    {
        $this->assertSame(
            ['city' => $city, 'state' => $state, 'country' => $country],
            AddressParser::parse($address),
        );
    }

    public static function unparseable(): array
    {
        return [
            'uk' => ['10 Downing St, London SW1A 2AA, UK'],
            'bare name' => ['3700 N Broadway'],
            'single token' => ['Somewhere'],
            'country only' => ['Chicago, USA'],
            'bogus state' => ['123 Main St, Springfield, ZZ 12345, USA'],
            'province with us country' => ['123 Main St, Toronto, ON, USA'],
            'numeric city' => ['123, 456, IL, USA'],
            'empty' => [''],
            'null' => [null],
        ];
    }

    /** @dataProvider unparseable */
    public function test_returns_nulls_for_unparseable_addresses(?string $address): void
    {
        $this->assertSame(['city' => null, 'state' => null, 'country' => null], AddressParser::parse($address));
    }
}
