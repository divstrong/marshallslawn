<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Customers', '248')
                ->description('12 new this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Active Jobs', '64')
                ->description('8 completed this week')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary')
                ->chart([3, 5, 7, 6, 8, 4, 6]),

            Stat::make('Open Estimates', '18')
                ->description('$24,500 pending value')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning')
                ->chart([4, 6, 2, 5, 3, 7, 4]),
        ];
    }
}
