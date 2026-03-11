<?php

namespace App\Filament\Resources\ChemicalLogResource\Pages;

use App\Filament\Resources\ChemicalLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChemicalLogs extends ListRecords
{
    protected static string $resource = ChemicalLogResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
