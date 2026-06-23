<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardDateRange;
use App\Models\Job;
use App\Support\ServiceGroup;
use Filament\Widgets\ChartWidget;

class JobServiceMixChart extends ChartWidget
{
    use HasDashboardDateRange;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Job Service Mix';

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        [$start, $end] = $this->getDateRange();

        $counts = array_fill_keys(array_keys(ServiceGroup::LABELS), 0);

        Job::query()
            ->whereBetween('created_at', [$start, $end])
            ->with('jobServices.service:id,service_group')
            ->select(['id', 'title', 'description'])
            ->chunk(500, function ($jobs) use (&$counts): void {
                foreach ($jobs as $job) {
                    $counts[$job->serviceGroup()]++;
                }
            });

        $labels = [];
        $data = [];
        $bgColors = [];

        foreach (ServiceGroup::LABELS as $key => $label) {
            $labels[] = $label;
            $data[] = $counts[$key];
            $bgColors[] = ServiceGroup::COLORS[$key];
        }

        // If no jobs in range, show a single placeholder slice.
        if (array_sum($data) === 0) {
            return [
                'datasets' => [['data' => [1], 'backgroundColor' => ['#e5e7eb']]],
                'labels' => ['No Jobs'],
            ];
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
