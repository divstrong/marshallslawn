<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Imports\ServiceImporter;
use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()
                ->importer(ServiceImporter::class)
                ->label('Import CSV')
                ->icon('heroicon-o-circle-stack'),
            Actions\CreateAction::make(),
        ];
    }
}
