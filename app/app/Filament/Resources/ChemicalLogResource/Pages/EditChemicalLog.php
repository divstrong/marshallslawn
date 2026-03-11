<?php

namespace App\Filament\Resources\ChemicalLogResource\Pages;

use App\Filament\Resources\ChemicalLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChemicalLog extends EditRecord
{
    protected static string $resource = ChemicalLogResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
