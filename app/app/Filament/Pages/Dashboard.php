<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function getColumns(): int|array
    {
        return 3;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('period')
                ->options([
                    'this_year'    => 'This Year',
                    'this_month'   => 'This Month',
                    'this_quarter' => 'This Quarter',
                    'last_year'    => 'Last Year',
                    'last_month'   => 'Last Month',
                    'last_quarter' => 'Last Quarter',
                    'custom'       => 'Custom Range',
                ])
                ->default('this_year')
                ->reactive(),
            DatePicker::make('start_date')
                ->label('Start Date')
                ->visible(fn (callable $get) => $get('period') === 'custom'),
            DatePicker::make('end_date')
                ->label('End Date')
                ->visible(fn (callable $get) => $get('period') === 'custom'),
        ]);
    }
}
