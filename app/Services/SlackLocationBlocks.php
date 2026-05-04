<?php

namespace App\Services;

use App\Models\Location;

class SlackLocationBlocks
{
    public static function build(Location $location, ?string $statusBanner = null): array
    {
        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '🆕 New ice spot pending review',
                    'emoji' => true,
                ],
            ],
        ];

        if ($statusBanner !== null) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $statusBanner,
                ],
            ];
        }

        $descriptionLine = $location->description
            ? "\n\n".self::escape(self::truncate($location->description, 500))
            : '';
        $submitter = $location->submittedBy?->name ?? 'Unknown';

        $blocks[] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => sprintf(
                    "*%s*\n_%s_%s\n\n📍 %s, %s\n👤 Submitted by %s",
                    self::escape($location->name),
                    self::escape($location->address),
                    $descriptionLine,
                    number_format((float) $location->latitude, 5),
                    number_format((float) $location->longitude, 5),
                    self::escape($submitter),
                ),
            ],
        ];

        $imageUrl = $location->primaryImage()?->url;
        if ($imageUrl) {
            $blocks[] = [
                'type' => 'image',
                'image_url' => $imageUrl,
                'alt_text' => 'Submitted photo of '.$location->name,
            ];
        }

        if ($statusBanner === null) {
            $blocks[] = [
                'type' => 'actions',
                'elements' => [
                    [
                        'type' => 'button',
                        'text' => ['type' => 'plain_text', 'text' => 'Approve', 'emoji' => true],
                        'style' => 'primary',
                        'action_id' => 'approve_location',
                        'value' => (string) $location->id,
                    ],
                    [
                        'type' => 'button',
                        'text' => ['type' => 'plain_text', 'text' => 'Reject', 'emoji' => true],
                        'style' => 'danger',
                        'action_id' => 'reject_location',
                        'value' => (string) $location->id,
                        'confirm' => [
                            'title' => ['type' => 'plain_text', 'text' => 'Reject this location?'],
                            'text' => ['type' => 'plain_text', 'text' => 'It will be hidden from the public map.'],
                            'confirm' => ['type' => 'plain_text', 'text' => 'Reject'],
                            'deny' => ['type' => 'plain_text', 'text' => 'Cancel'],
                            'style' => 'danger',
                        ],
                    ],
                    [
                        'type' => 'button',
                        'text' => ['type' => 'plain_text', 'text' => 'View on map', 'emoji' => true],
                        'action_id' => 'view_location',
                        'url' => route('locations.show', $location),
                    ],
                ],
            ];
        }

        return $blocks;
    }

    public static function fallbackText(Location $location): string
    {
        $submitter = $location->submittedBy?->name ?? 'Unknown';

        return sprintf(
            'New ice spot pending review: %s — %s — submitted by %s',
            $location->name,
            $location->address,
            $submitter,
        );
    }

    private static function escape(string $text): string
    {
        return strtr($text, ['&' => '&amp;', '<' => '&lt;', '>' => '&gt;']);
    }

    private static function truncate(string $text, int $limit): string
    {
        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit - 1).'…' : $text;
    }
}
