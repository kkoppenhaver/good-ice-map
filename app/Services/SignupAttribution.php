<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * First-touch attribution: remember where a visitor came from on their first
 * request, then stamp it onto their user row when they sign up. This is what
 * lets "signups by source" be answered from our own database.
 */
class SignupAttribution
{
    public const SESSION_KEY = 'attribution';

    private const UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    /**
     * Known referrer hosts mapped to a short source label.
     */
    private const HOST_SOURCES = [
        'reddit.com' => 'reddit',
        'redd.it' => 'reddit',
        'google.' => 'google',
        'bing.com' => 'bing',
        'duckduckgo.com' => 'duckduckgo',
        'chatgpt.com' => 'chatgpt',
        'openai.com' => 'chatgpt',
        'perplexity.ai' => 'perplexity',
        'claude.ai' => 'claude',
        'x.com' => 'x',
        'twitter.com' => 'x',
        't.co' => 'x',
        'facebook.com' => 'facebook',
        'instagram.com' => 'instagram',
        'tiktok.com' => 'tiktok',
        'youtube.com' => 'youtube',
    ];

    /**
     * Store first-touch data in the session if none exists yet.
     */
    public function capture(Request $request): void
    {
        if ($request->session()->has(self::SESSION_KEY)) {
            return;
        }

        $data = [
            'referrer' => $this->externalReferrer($request),
            'landing_page' => Str::limit('/'.ltrim($request->path(), '/'), 255, ''),
            'first_seen_at' => now()->toIso8601String(),
        ];

        foreach (self::UTM_KEYS as $key) {
            $value = $request->query($key);
            if (is_string($value) && $value !== '') {
                $data[$key] = Str::limit(trim($value), 100, '');
            }
        }

        $request->session()->put(self::SESSION_KEY, $data);
    }

    /**
     * Copy the session's first-touch data onto a newly registered user.
     */
    public function apply(User $user, Request $request): void
    {
        $data = $request->session()->get(self::SESSION_KEY, []);

        $user->forceFill([
            'signup_source' => Str::limit(self::sourceFor($data), 100, ''),
            'signup_medium' => $data['utm_medium'] ?? null,
            'signup_campaign' => isset($data['utm_campaign']) ? Str::limit($data['utm_campaign'], 150, '') : null,
            'signup_referrer' => $data['referrer'] ?? null,
            'signup_landing_page' => $data['landing_page'] ?? null,
        ])->save();
    }

    /**
     * Resolve a short source label: utm_source wins, then a known referrer
     * host, then the bare referrer host, then "direct".
     */
    public static function sourceFor(array $data): string
    {
        if (! empty($data['utm_source'])) {
            return Str::lower($data['utm_source']);
        }

        $host = $data['referrer'] ?? null;
        if (! $host) {
            return 'direct';
        }

        foreach (self::HOST_SOURCES as $needle => $source) {
            if (str_ends_with($needle, '.') ? str_contains($host, $needle) : ($host === $needle || str_ends_with($host, '.'.$needle))) {
                return $source;
            }
        }

        return $host;
    }

    /**
     * Referrer host, minus a leading "www.", or null when it is our own site
     * or missing.
     */
    private function externalReferrer(Request $request): ?string
    {
        $referer = $request->headers->get('referer');
        if (! $referer) {
            return null;
        }

        $host = Str::lower((string) parse_url($referer, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host) ?? '';

        if ($host === '' || $host === preg_replace('/^www\./', '', Str::lower($request->getHost()))) {
            return null;
        }

        return Str::limit($host, 255, '');
    }
}
