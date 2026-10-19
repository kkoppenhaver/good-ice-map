<?php

namespace Tests\Feature;

use App\Support\ChainMatcher;
use Tests\TestCase;

class ChainMatcherTest extends TestCase
{
    public static function names(): array
    {
        return [
            ["Raising Cane's", 'raising-canes'],
            ["Raising Cane's Chicken Fingers", 'raising-canes'],
            ["Cane's", 'raising-canes'],
            ['Sonic Drive-In', 'sonic'],
            ['SONIC', 'sonic'],
            ['Chick-fil-A', 'chick-fil-a'],
            ['Chick Fil A Downtown', 'chick-fil-a'],
            ['7-Eleven', '7-eleven'],
            ['Circle K - Cobourg', 'circle-k'],
            ["Casey's General Store", 'caseys'],
            ['Caseys', 'caseys'],
            ["Love's Travel Stop", 'loves'],
            ['Mod Pizza', 'mod-pizza'],
            ["Jimmy John's", 'jimmy-johns'],
            ['Nando’s', 'nandos'],
            ['Buc-ee\'s', 'buc-ees'],
            ['QT', 'quiktrip'],
            ['Dutch Bros Coffee', 'dutch-bros'],
            ['Hilton New York Fashion District', null],
            ['Sonicare Dental', null],
            ['La Serre', null],
            ['', null],
            [null, null],
        ];
    }

    /** @dataProvider names */
    public function test_matches_names_to_chain_slugs(?string $name, ?string $expected): void
    {
        $this->assertSame($expected, ChainMatcher::match($name));
    }

    public function test_every_configured_chain_matches_its_own_display_name(): void
    {
        foreach (config('chains') as $slug => $chain) {
            $this->assertSame($slug, ChainMatcher::match($chain['name']), "display name for {$slug}");
        }
    }
}
