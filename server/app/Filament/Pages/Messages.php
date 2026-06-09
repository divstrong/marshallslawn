<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Messages extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string | \UnitEnum | null $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.messages';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user ? $user->hasAccessTo('Messages') : false;
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
    }
}
