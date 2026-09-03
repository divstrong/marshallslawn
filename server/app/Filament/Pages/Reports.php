<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Reports extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 1;

    /**
     * Hidden from the menu: the section has no agreed scope yet, so an empty
     * Reports page in the sidebar reads as a broken feature to the office. The
     * page and its route stay registered, so bringing it back is a one-line
     * change once the client tells us what they want reported on.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.reports';
}
