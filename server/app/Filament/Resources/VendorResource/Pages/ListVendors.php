<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Imports\VendorImporter;
use App\Filament\Resources\VendorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()
                ->importer(VendorImporter::class)
                ->label('Import CSV')
                ->icon('heroicon-o-circle-stack'),
            Actions\CreateAction::make(),
        ];
    }
}
