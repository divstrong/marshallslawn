<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class Settings extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string | \UnitEnum | null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Settings';

    protected static ?string $slug = 'settings';

    protected string $view = 'filament.pages.settings';

    public function schema(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Settings')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('General')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            View::make('livewire.settings-general-embed'),
                        ]),
                    Tab::make('Roles')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            View::make('livewire.role-manager-embed'),
                        ]),
                    Tab::make('Permissions')
                        ->icon('heroicon-o-lock-closed')
                        ->schema([
                            View::make('livewire.permission-manager-embed'),
                        ]),
                ]),
        ]);
    }
}
