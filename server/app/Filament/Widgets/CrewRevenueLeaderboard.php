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

        // Each job's total is summed from its service line prices, falling back
        // to the direct price when it has no lines — computed here rather than
        // stored, so edits to line items are reflected immediately.
        $lineSum = '(SELECT COALESCE(SUM(js.price), 0) FROM job_services js WHERE js.job_id = service_jobs.id)';
        $jobTotal = "CASE WHEN {$lineSum} > 0 THEN {$lineSum} ELSE COALESCE(service_jobs.price, 0) END";

        $rows = Job::query()
            ->where('status', 'completed')
            ->whereNotNull('crew_id')
            ->whereBetween('completed_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("crew_id, COUNT(*) as jobs, COALESCE(SUM({$jobTotal}), 0) as revenue")
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
