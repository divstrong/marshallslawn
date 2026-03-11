<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Dispatch extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.dispatch';
}
