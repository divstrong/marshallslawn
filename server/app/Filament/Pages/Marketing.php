<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Marketing extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-megaphone';

    protected static string | \UnitEnum | null $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.marketing';
}
