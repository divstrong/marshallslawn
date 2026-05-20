<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardDateRange;
use App\Models\Estimate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    use HasDashboardDateRange;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Estimate Revenue';

    protected int | string | array $columnSpan = 2;

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        [$start, $end] = $this->getDateRange();

        // Build monthly buckets across the range
        $period = CarbonPeriod::create($start->copy()->startOfMonth(), '1 month', $end->copy()->startOfMonth());

        $labels = [];
        $data = [];

        foreach ($period as $month) {
            $labels[] = $month->format('M Y');
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $data[] = (float) Estimate::whereIn('status', ['accepted', 'sent'])
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('total');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Estimate Revenue',
                    'data' => $data,
                    'backgroundColor' => '#e00a35',
                    'borderColor' => '#e00a35',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
