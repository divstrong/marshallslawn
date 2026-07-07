<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardDateRange;
use App\Models\Estimate;
use App\Models\Job;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    use HasDashboardDateRange;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Revenue';

    protected int | string | array $columnSpan = 2;

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        [$start, $end] = $this->getDateRange();

        // Build monthly buckets across the range.
        $period = CarbonPeriod::create($start->copy()->startOfMonth(), '1 month', $end->copy()->startOfMonth());

        // A completed job's revenue = sum of its service line prices, falling back
        // to the direct price when it has no lines (same rule as the crew leaderboard).
        $lineSum = '(SELECT COALESCE(SUM(js.price), 0) FROM job_services js WHERE js.job_id = service_jobs.id)';
        $jobTotal = "CASE WHEN {$lineSum} > 0 THEN {$lineSum} ELSE COALESCE(service_jobs.price, 0) END";

        $labels = [];
        $estimated = [];
        $completed = [];

        foreach ($period as $month) {
            $labels[] = $month->format('M Y');
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            // Estimated $: total value of estimates issued this month.
            $estimated[] = (float) Estimate::whereIn('status', ['accepted', 'sent'])
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('total');

            // Actual revenue: total value of jobs completed this month.
            $completed[] = (float) Job::query()
                ->where('status', 'completed')
                ->whereBetween('completed_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->selectRaw("COALESCE(SUM({$jobTotal}), 0) as rev")
                ->value('rev');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Estimated',
                    'data' => $estimated,
                    'backgroundColor' => '#2563eb',
                    'borderColor' => '#2563eb',
                ],
                [
                    'label' => 'Completed (Actual)',
                    'data' => $completed,
                    'backgroundColor' => '#16a34a',
                    'borderColor' => '#16a34a',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
