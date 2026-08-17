<?php

namespace App\Filament\Resources\JobResource\Pages;

use App\Filament\Resources\JobResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Retired: the waiting list is now the Jobs list filtered to the Waiting List
 * status, which retitles itself to match. This page is kept only so existing
 * links and bookmarks land somewhere sensible instead of 404ing.
 */
class ListWaitingListJobs extends ListRecords
{
    protected static string $resource = JobResource::class;

    public function mount(): void
    {
        $this->redirect(JobResource::getUrl('index', [
            'tableFilters' => ['status' => ['value' => 'waiting_list']],
        ]));
    }
}
