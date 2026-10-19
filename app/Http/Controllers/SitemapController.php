<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $entries = [
                ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'],
                ['loc' => route('map'), 'changefreq' => 'daily', 'priority' => '0.9'],
            ];

            Location::query()
                ->where('status', 'approved')
                ->orderBy('id')
                ->each(function (Location $location) use (&$entries) {
                    $entries[] = [
                        'loc' => $location->url,
                        'lastmod' => $location->updated_at?->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                    ];
                });

            return view('sitemap', ['entries' => $entries])->render();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
