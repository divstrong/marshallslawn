<?php

namespace App\Filament\Resources\RecurringJobTemplateResource\Pages;

use App\Filament\Resources\RecurringJobTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecurringJobTemplate extends EditRecord
{
    protected static string $resource = RecurringJobTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
