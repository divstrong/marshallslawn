<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\Property;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class JobsRelationManager extends RelationManager
{
    protected static string $relationship = 'jobs';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-clipboard-document-list';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('property_id')
                ->label('Property')
                ->options(fn (): array => Property::query()
                    ->where('customer_id', $this->getOwnerRecord()->getKey())
                    ->orderByDesc('is_primary')
                    ->orderBy('address')
                    ->pluck('address', 'id')
                    ->all())
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->placeholder('Mowing, Mulch install, Spring cleanup…'),
            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'scheduled' => 'Scheduled',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'skipped' => 'Skipped',
                    'cancelled' => 'Cancelled',
                ])
                ->default('pending')
                ->required(),
            Forms\Components\Select::make('priority')
                ->options([
                    'low' => 'Low',
                    'normal' => 'Normal',
                    'high' => 'High',
                    'urgent' => 'Urgent',
                ])
                ->default('normal')
                ->required(),
            Forms\Components\DatePicker::make('scheduled_date')
                ->label('Scheduled date'),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
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
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['type'] = $data['type'] ?? 'one_time';
                        return $data;
                    }),
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
