<?php

namespace App\Filament\Resources\RecurringJobTemplateResource\Pages;

use App\Filament\Resources\RecurringJobTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecurringJobTemplates extends ListRecords
{
    protected static string $resource = RecurringJobTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
