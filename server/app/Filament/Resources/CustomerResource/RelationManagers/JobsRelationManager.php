<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Filament\Resources\JobResource;
use App\Models\Job;
use App\Services\JobFromFormCreator;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class JobsRelationManager extends RelationManager
{
    protected static string $relationship = 'jobs';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-clipboard-document-list';

    /**
     * Reuse the main Jobs resource form so the New Job modal here carries exactly
     * the same fields and tabs — Services (with TBD pricing), recurrence, and the
     * rest — as creating a job from the Jobs resource (issue #54). The customer is
     * already known (the owner of this tab), so it is forced on save rather than
     * picked.
     */
    public function form(Schema $schema): Schema
    {
        return JobResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Job #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'urgent', 'high' => 'danger',
                        'normal' => 'warning',
                        'low' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->date()
                    ->placeholder('TBD')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'skipped' => 'Skipped',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('New Job')
                    ->icon('heroicon-o-plus')
                    ->modalWidth(\Filament\Support\Enums\Width::FiveExtraLarge)
                    // The customer is already known — pre-select it so the shared
                    // picker is valid and shows the right account. fillForm()
                    // replaces the schema's own defaults rather than merging with
                    // them, so the ones the form relies on are restored here.
                    ->fillForm(fn (): array => [
                        'customer_id' => $this->getOwnerRecord()->getKey(),
                        'kind' => Job::KIND_SERVICE,
                        'status' => 'pending',
                        'priority' => 'normal',
                        'job_type' => 'one_time',
                    ])
                    // Pin the customer to this tab's owner, then run the same
                    // creation path (services + recurrence) as the Jobs resource.
                    ->using(fn (array $data): \Illuminate\Database\Eloquent\Model => app(JobFromFormCreator::class)
                        ->create($data, ['customer_id' => $this->getOwnerRecord()->getKey()])['job']),
            ])
            ->actions([
                Actions\Action::make('open')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.jobs.edit', $record)),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
