<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardDateRange;
use App\Models\Job;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class JobTypeMixChart extends ChartWidget
{
    use HasDashboardDateRange;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Job Status Mix';

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        [$start, $end] = $this->getDateRange();

        $statuses = Job::select('status', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $colors = [
            'active'    => '#e00a35',
            'scheduled' => '#1e293b',
            'completed' => '#22c55e',
            'cancelled' => '#64748b',
            'pending'   => '#f59e0b',
        ];

        $labels = [];
        $data = [];
        $bgColors = [];

        foreach ($statuses as $status => $count) {
            $labels[] = ucfirst($status);
            $data[] = $count;
            $bgColors[] = $colors[$status] ?? '#94a3b8';
        }

        // If no data, show placeholder
        if (empty($data)) {
            $labels = ['No Jobs'];
            $data = [1];
            $bgColors = ['#e5e7eb'];
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $bgColors,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
