<?php

namespace App\Filament\Resources\EstimateResource\Pages;

use App\Filament\Resources\EstimateResource;
use App\Models\Estimate;
use Filament\Resources\Pages\Page;

class EditEstimate extends Page
{
    protected static string $resource = EstimateResource::class;

    protected string $view = 'filament.resources.estimate.edit';

    public Estimate $record;

    public function getTitle(): string
    {
        return 'Edit Estimate — ' . $this->record->estimate_number;
    }
}
