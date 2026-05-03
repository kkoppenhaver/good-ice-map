<?php

namespace App\Services;

class GoogleMapsLinkParser
{
    public function isShortLink(string $url): bool
    {
        return str_contains($url, 'goo.gl') || str_contains($url, 'maps.app.goo.gl');
    }

    /**
     * Extract what we can from a (long-form) Google Maps URL.
     *
     * @return array{name: ?string, latitude: ?float, longitude: ?float, place_id: ?string}
     */
    public function parse(string $url): array
    {
        return [
            'name' => $this->extractName($url),
            'latitude' => $this->extractCoordinates($url)[0],
            'longitude' => $this->extractCoordinates($url)[1],
            'place_id' => $this->extractPlaceId($url),
        ];
    }

    private function extractName(string $url): ?string
    {
        if (preg_match('~/place/([^/@?#]+)~', $url, $match)) {
            return urldecode(str_replace('+', ' ', $match[1]));
        }

        return null;
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    private function extractCoordinates(string $url): array
    {
        // !3dLAT!4dLNG — most accurate (precise place coordinates)
        if (preg_match('/!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/', $url, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }

        // @LAT,LNG,ZOOM — viewport center
        if (preg_match('/@(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }

        // ll=LAT,LNG
        if (preg_match('/[?&]ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }

        // q=LAT,LNG
        if (preg_match('/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }

        return [null, null];
    }

    private function extractPlaceId(string $url): ?string
    {
        // Pattern 1: !1s[ChIJ...]
        if (preg_match('/!1s(ChIJ[A-Za-z0-9_-]+)/', $url, $m)) {
            return $m[1];
        }

        // Pattern 2: data=...!4m...!1s[ChIJ...]
        if (preg_match('/data=[^!]*!4m[^!]*!1s(ChIJ[A-Za-z0-9_-]+)/', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}
