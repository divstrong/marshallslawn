<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Scheduling extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.scheduling';
}
