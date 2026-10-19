<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = ['User-agent: *'];

        if (app()->environment('production')) {
            $lines = array_merge($lines, [
                'Disallow: /admin',
                'Disallow: /dashboard',
                'Disallow: /profile',
                'Disallow: /locations/create',
                'Disallow: /api/',
                'Allow: /',
                '',
                'Sitemap: '.route('sitemap'),
            ]);
        } else {
            // Staging and local builds must never be indexed.
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
