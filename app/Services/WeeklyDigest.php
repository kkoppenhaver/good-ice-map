<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Rating;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Growth numbers for a seven-day window: signups by source, submissions,
 * approvals and ratings. Rendered as Slack mrkdwn by the weekly digest.
 */
class WeeklyDigest
{
    public function build(?CarbonImmutable $end = null): array
    {
        $end = $end ?? CarbonImmutable::now();
        $start = $end->subDays(7);

        $signups = User::query()
            ->whereBetween('created_at', [$start, $end])
            ->get(['signup_source']);

        $bySource = $signups
            ->groupBy(fn (User $u) => $u->signup_source ?: 'unknown')
            ->map->count()
            ->sortDesc();

        $submitted = Location::query()->whereBetween('created_at', [$start, $end]);
        $approved = Location::query()
            ->where('status', 'approved')
            ->whereBetween('updated_at', [$start, $end]);

        return [
            'start' => $start,
            'end' => $end,
            'signups' => $signups->count(),
            'signups_by_source' => $bySource,
            'submissions' => (clone $submitted)->count(),
            'submissions_pending' => (clone $submitted)->where('status', 'pending')->count(),
            'approved' => $approved->count(),
            'ratings' => Rating::query()->whereBetween('created_at', [$start, $end])->count(),
            'total_users' => User::count(),
            'total_approved' => Location::where('status', 'approved')->count(),
            'total_ratings' => Rating::count(),
        ];
    }

    public function render(array $stats): string
    {
        $range = $stats['start']->format('M j').' – '.$stats['end']->format('M j');

        $sources = $stats['signups_by_source'] instanceof Collection && $stats['signups_by_source']->isNotEmpty()
            ? $stats['signups_by_source']->map(fn ($n, $src) => "{$src} {$n}")->implode(', ')
            : 'none';

        return implode("\n", [
            "*Good Ice Map — week of {$range}*",
            "• Signups: *{$stats['signups']}* ({$sources})",
            "• Locations submitted: *{$stats['submissions']}* ({$stats['submissions_pending']} still pending) · approved this week: *{$stats['approved']}*",
            "• Ratings: *{$stats['ratings']}*",
            "• Totals: {$stats['total_users']} users · {$stats['total_approved']} approved spots · {$stats['total_ratings']} ratings",
            'Traffic and referrers: <https://app.usefathom.com/|Fathom>',
        ]);
    }
}
