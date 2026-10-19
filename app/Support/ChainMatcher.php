<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Resolves a submitted location name to a chain slug from config/chains.php.
 */
class ChainMatcher
{
    public static function match(?string $name): ?string
    {
        $normalized = self::normalize($name);
        if ($normalized === '') {
            return null;
        }

        foreach (self::aliases() as [$alias, $slug]) {
            if ($normalized === $alias || str_starts_with($normalized, $alias.' ')) {
                return $slug;
            }
        }

        return null;
    }

    public static function normalize(?string $value): string
    {
        $value = Str::lower(Str::ascii((string) $value));
        $value = str_replace(['&'], [' and '], $value);
        $value = preg_replace("/['\x{2019}`]/u", '', $value) ?? '';
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    /**
     * All aliases, longest first so "sonic drive in" wins over "sonic".
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private static function aliases(): array
    {
        static $cache = null;

        if ($cache === null) {
            $cache = [];
            foreach (config('chains', []) as $slug => $chain) {
                foreach ($chain['aliases'] ?? [] as $alias) {
                    $cache[] = [self::normalize($alias), $slug];
                }
            }
            usort($cache, fn ($a, $b) => strlen($b[0]) <=> strlen($a[0]));
        }

        return $cache;
    }
}
