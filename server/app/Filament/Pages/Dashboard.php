<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;

class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    private const PERIODS = [
        'today'        => 'Today',
        'this_week'    => 'This Week',
        'this_month'   => 'This Month',
        'this_quarter' => 'This Quarter',
        'this_year'    => 'This Year',
        'last_month'   => 'Last Month',
        'last_quarter' => 'Last Quarter',
        'last_year'    => 'Last Year',
        'custom'       => 'Custom Range',
    ];

    public function getColumns(): int|array
    {
        return 3;
    }

    /**
     * Render the period filter as a header action (top-right of the Dashboard
     * title row) rather than a full-width form row, reclaiming the vertical
     * space. The button shows the currently selected period.
     */
    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->icon('heroicon-m-calendar')
                ->label(fn (): string => self::PERIODS[data_get($this->filters, 'period', 'this_year')] ?? 'Period')
                ->schema([
                    Select::make('period')
                        ->options(self::PERIODS)
                        ->default('this_year')
                        ->reactive(),
                    DatePicker::make('start_date')
                        ->label('Start Date')
                        ->visible(fn (callable $get) => $get('period') === 'custom'),
                    DatePicker::make('end_date')
                        ->label('End Date')
                        ->visible(fn (callable $get) => $get('period') === 'custom'),
                ]),
        ];
    }
}
