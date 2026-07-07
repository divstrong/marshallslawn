<?php

namespace App\Filament\Portal\Pages;

use App\Filament\Portal\Widgets\CustomerStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -2;

    public function getColumns(): int | array
    {
        return 3;
    }

    public function getWidgets(): array
    {
        return [
            CustomerStatsOverview::class,
        ];
    }
}
