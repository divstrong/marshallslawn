<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class SystemHealth extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-heart';

    protected static string | \UnitEnum | null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 99;

    protected static ?string $navigationLabel = 'System Health';

    protected static ?string $title = 'System Health';

    protected static ?string $slug = 'system-health';

    protected string $view = 'filament.pages.system-health';

    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public function schema(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('SystemHealth')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Overview')
                        ->icon('heroicon-o-chart-bar-square')
                        ->schema([
                            View::make('livewire.system-health-overview-embed'),
                        ]),
                    Tab::make('Environment')
                        ->icon('heroicon-o-server-stack')
                        ->schema([
                            View::make('livewire.system-health-environment-embed'),
                        ]),
                    Tab::make('Routes')
                        ->icon('heroicon-o-link')
                        ->schema([
                            View::make('livewire.system-health-routes-embed'),
                        ]),
                    Tab::make('Smoke Tests')
                        ->icon('heroicon-o-beaker')
                        ->schema([
                            View::make('livewire.system-health-smoke-embed'),
                        ]),
                ]),
        ]);
    }
}
