<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySlackSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.slack.signing_secret');
        if (empty($secret)) {
            abort(500, 'Slack signing secret is not configured.');
        }

        $timestamp = $request->header('X-Slack-Request-Timestamp');
        $signature = $request->header('X-Slack-Signature');

        if (! $timestamp || ! $signature) {
            abort(401, 'Missing Slack signature headers.');
        }

        if (abs(time() - (int) $timestamp) > 60 * 5) {
            abort(401, 'Slack request timestamp is stale.');
        }

        $base = 'v0:'.$timestamp.':'.$request->getContent();
        $expected = 'v0='.hash_hmac('sha256', $base, $secret);

        if (! hash_equals($expected, $signature)) {
            abort(401, 'Slack signature mismatch.');
        }

        return $next($request);
    }
}
