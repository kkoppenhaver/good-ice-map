<?php

namespace App\Support;

/**
 * Builds schema.org JSON-LD payloads. Kept out of Blade because Blade
 * treats a literal "@context" inside a template as a directive.
 */
class StructuredData
{
    public const CONTEXT = 'https://schema.org';

    public static function website(): array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'WebSite',
            'name' => 'Good Ice Map',
            'url' => url('/'),
            'description' => 'A community-built map of places that serve nugget, pellet, sonic, and hospital ice.',
        ];
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $crumbs
     */
    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn (array $crumb, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ])->all(),
        ];
    }

    public static function toJson(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
