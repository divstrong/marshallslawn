<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class JobTypeMixChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Job Type Mix';

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'data' => [35, 25, 15, 12, 8, 5],
                    'backgroundColor' => [
                        '#e00a35',
                        '#1e293b',
                        '#f87171',
                        '#64748b',
                        '#fca5a5',
                        '#94a3b8',
                    ],
                ],
            ],
            'labels' => ['Lawn Care', 'Landscaping', 'Chemical', 'Irrigation', 'Snow Removal', 'Other'],
        ];
    }
}
