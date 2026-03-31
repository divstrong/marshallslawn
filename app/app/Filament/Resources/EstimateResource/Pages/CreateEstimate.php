<?php

namespace App\Filament\Resources\EstimateResource\Pages;

use App\Filament\Resources\EstimateResource;
use Filament\Resources\Pages\Page;

class CreateEstimate extends Page
{
    protected static string $resource = EstimateResource::class;

    protected string $view = 'filament.resources.estimate.create';

    public function getTitle(): string
    {
        return 'New Estimate';
    }
}
