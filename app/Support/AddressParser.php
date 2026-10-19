<?php

namespace App\Support;

/**
 * Pulls city / state / country out of the comma-separated addresses Google
 * Maps hands us, e.g. "152 W 26th St, New York, NY 10001, USA" or
 * "Circle K, Paul Street, Picton, ON, Canada". Only US and Canadian formats
 * are recognised; anything else returns nulls so the caller can fall back
 * to reverse geocoding.
 */
class AddressParser
{
    private const COUNTRIES = [
        'usa' => 'US', 'us' => 'US', 'united states' => 'US', 'united states of america' => 'US',
        'canada' => 'CA', 'ca' => 'CA',
    ];

    private const US_STATES = [
        'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY',
        'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND',
        'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY', 'DC', 'PR',
    ];

    private const CA_PROVINCES = ['AB', 'BC', 'MB', 'NB', 'NL', 'NS', 'NT', 'NU', 'ON', 'PE', 'QC', 'SK', 'YT'];

    private const STATE_NAMES = [
        'alabama' => 'AL', 'alaska' => 'AK', 'arizona' => 'AZ', 'arkansas' => 'AR', 'california' => 'CA',
        'colorado' => 'CO', 'connecticut' => 'CT', 'delaware' => 'DE', 'florida' => 'FL', 'georgia' => 'GA',
        'hawaii' => 'HI', 'idaho' => 'ID', 'illinois' => 'IL', 'indiana' => 'IN', 'iowa' => 'IA', 'kansas' => 'KS',
        'kentucky' => 'KY', 'louisiana' => 'LA', 'maine' => 'ME', 'maryland' => 'MD', 'massachusetts' => 'MA',
        'michigan' => 'MI', 'minnesota' => 'MN', 'mississippi' => 'MS', 'missouri' => 'MO', 'montana' => 'MT',
        'nebraska' => 'NE', 'nevada' => 'NV', 'new hampshire' => 'NH', 'new jersey' => 'NJ', 'new mexico' => 'NM',
        'new york' => 'NY', 'north carolina' => 'NC', 'north dakota' => 'ND', 'ohio' => 'OH', 'oklahoma' => 'OK',
        'oregon' => 'OR', 'pennsylvania' => 'PA', 'rhode island' => 'RI', 'south carolina' => 'SC',
        'south dakota' => 'SD', 'tennessee' => 'TN', 'texas' => 'TX', 'utah' => 'UT', 'vermont' => 'VT',
        'virginia' => 'VA', 'washington' => 'WA', 'west virginia' => 'WV', 'wisconsin' => 'WI', 'wyoming' => 'WY',
        'district of columbia' => 'DC', 'puerto rico' => 'PR',
        'alberta' => 'AB', 'british columbia' => 'BC', 'manitoba' => 'MB', 'new brunswick' => 'NB',
        'newfoundland and labrador' => 'NL', 'nova scotia' => 'NS', 'northwest territories' => 'NT',
        'nunavut' => 'NU', 'ontario' => 'ON', 'prince edward island' => 'PE', 'quebec' => 'QC',
        'québec' => 'QC', 'saskatchewan' => 'SK', 'yukon' => 'YT',
    ];

    /**
     * @return array{city: ?string, state: ?string, country: ?string}
     */
    public static function parse(?string $address): array
    {
        $none = ['city' => null, 'state' => null, 'country' => null];

        $parts = array_values(array_filter(array_map('trim', explode(',', (string) $address)), fn ($p) => $p !== ''));
        if (count($parts) < 2) {
            return $none;
        }

        $country = self::COUNTRIES[strtolower(end($parts))] ?? null;
        if ($country) {
            array_pop($parts);
        }

        if (count($parts) < 2) {
            return $none;
        }

        // The last remaining part is "ST", "ST 12345", "ON M4S 2L5" or a full name like "Ontario".
        $region = end($parts);
        if (preg_match('/^([A-Za-z]{2})(?:\s+[A-Za-z0-9][A-Za-z0-9 -]{2,10})?$/', $region, $m)) {
            $state = strtoupper($m[1]);
        } elseif (isset(self::STATE_NAMES[mb_strtolower($region)])) {
            $state = self::STATE_NAMES[mb_strtolower($region)];
        } else {
            return $none;
        }

        if ($country === null) {
            $country = in_array($state, self::US_STATES, true) ? 'US'
                : (in_array($state, self::CA_PROVINCES, true) ? 'CA' : null);
        }

        $valid = match ($country) {
            'US' => in_array($state, self::US_STATES, true),
            'CA' => in_array($state, self::CA_PROVINCES, true),
            default => false,
        };

        if (! $valid) {
            return $none;
        }

        array_pop($parts);
        $city = end($parts);

        // A bare street number or something numeric is not a city.
        if ($city === false || preg_match('/^\d/', $city)) {
            return $none;
        }

        return ['city' => $city, 'state' => $state, 'country' => $country];
    }
}
