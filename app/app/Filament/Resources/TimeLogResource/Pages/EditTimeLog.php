<?php

namespace App\Filament\Resources\TimeLogResource\Pages;

use App\Filament\Resources\TimeLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTimeLog extends EditRecord
{
    protected static string $resource = TimeLogResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
