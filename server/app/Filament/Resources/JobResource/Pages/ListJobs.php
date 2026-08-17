<?php

namespace App\Filament\Resources\JobResource\Pages;

use App\Filament\Resources\JobResource;
use App\Models\JobStatus;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;

class ListJobs extends ListRecords
{
    protected static string $resource = JobResource::class;

    /**
     * Reflect an active status filter in the page title, so arriving from a
     * pre-filtered link (e.g. Dispatch → Waiting List) reads as "Jobs — Waiting
     * List" rather than a plain "Jobs" list that looks suspiciously short.
     */
    public function getTitle(): string
    {
        $status = $this->tableFilters['status']['value'] ?? null;

        if (blank($status)) {
            return 'Jobs';
        }

        $label = JobStatus::options()[$status] ?? Str::headline((string) $status);

        return 'Jobs — ' . $label;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
