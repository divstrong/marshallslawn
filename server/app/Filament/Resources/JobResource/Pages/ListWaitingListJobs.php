<?php

namespace App\Filament\Resources\JobResource\Pages;

use App\Filament\Resources\JobResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The waiting list as its own view: every job sitting on the Waiting List
 * status, without anyone having to reach for a filter on the Jobs index.
 */
class ListWaitingListJobs extends ListRecords
{
    protected static string $resource = JobResource::class;

    protected static ?string $title = 'Waiting List';

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'waiting_list'));
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
