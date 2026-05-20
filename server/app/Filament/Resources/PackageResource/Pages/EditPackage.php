<?php

namespace App\Filament\Resources\PackageResource\Pages;

use App\Filament\Resources\PackageResource;
use App\Models\Package;
use Filament\Resources\Pages\Page;

class EditPackage extends Page
{
    protected static string $resource = PackageResource::class;

    protected string $view = 'filament.resources.package.edit';

    public Package $record;

    public function getTitle(): string
    {
        return 'Edit Package — ' . $this->record->name;
    }
}
