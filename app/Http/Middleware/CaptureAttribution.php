<?php

namespace App\Http\Middleware;

use App\Services\SignupAttribution;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureAttribution
{
    public function __construct(private SignupAttribution $attribution) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Only ordinary page views count as a first touch.
        if ($request->isMethod('GET') && ! $request->expectsJson() && $request->hasSession()) {
            $this->attribution->capture($request);
        }

        return $next($request);
    }
}
