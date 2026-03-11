<?php

namespace App\Filament\Resources\TimeLogResource\Pages;

use App\Filament\Resources\TimeLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTimeLogs extends ListRecords
{
    protected static string $resource = TimeLogResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
