<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardDateRange;
use App\Models\Crew;
use App\Models\Job;
use Filament\Widgets\Widget;

/**
 * Ranks crews by the revenue of the jobs they completed in the selected date
 * range — treating each crew as its own business. Replaces the Job Service Mix
 * chart. Revenue is the sum of each completed job's Job Total.
 */
class CrewRevenueLeaderboard extends Widget
{
    use HasDashboardDateRange;

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected string $view = 'filament.widgets.crew-revenue-leaderboard';

    protected function getViewData(): array
    {
        [$start, $end] = $this->getDateRange();

        $rows = Job::query()
            ->where('status', 'completed')
            ->whereNotNull('crew_id')
            ->whereBetween('completed_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('crew_id, COUNT(*) as jobs, COALESCE(SUM(job_total), 0) as revenue')
            ->groupBy('crew_id')
            ->orderByDesc('revenue')
            ->orderByDesc('jobs')
            ->get();

        $names = Crew::whereIn('id', $rows->pluck('crew_id'))->pluck('name', 'id');

        $crews = $rows->map(fn ($r): array => [
            'crew' => $names[$r->crew_id] ?? "Crew #{$r->crew_id}",
            'jobs' => (int) $r->jobs,
            'revenue' => (float) $r->revenue,
        ])->values();

        return [
            'crews' => $crews,
            'maxRevenue' => (float) ($crews->max('revenue') ?: 1),
            'totalRevenue' => (float) $crews->sum('revenue'),
            'totalJobs' => (int) $crews->sum('jobs'),
        ];
    }
}
