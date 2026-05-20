<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Imports\EmployeeImporter;
use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()
                ->importer(EmployeeImporter::class)
                ->label('Import CSV')
                ->icon('heroicon-o-circle-stack'),
            Actions\CreateAction::make(),
        ];
    }
}
