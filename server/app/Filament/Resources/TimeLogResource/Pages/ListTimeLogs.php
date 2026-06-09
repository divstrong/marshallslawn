<?php

namespace App\Filament\Resources\TimeLogResource\Pages;

use App\Filament\Resources\TimeLogResource;
use Filament\Resources\Pages\ListRecords;

class ListTimeLogs extends ListRecords
{
    protected static string $resource = TimeLogResource::class;

    // Job time logs originate in the field app — no manual "create" here.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
