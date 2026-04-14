<?php

namespace App\Filament\Resources\PackageResource\Pages;

use App\Filament\Resources\PackageResource;
use Filament\Resources\Pages\Page;

class CreatePackage extends Page
{
    protected static string $resource = PackageResource::class;

    protected string $view = 'filament.resources.package.create';

    public function getTitle(): string
    {
        return 'New Package';
    }
}
