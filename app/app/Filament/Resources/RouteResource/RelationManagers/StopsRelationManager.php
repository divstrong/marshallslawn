<?php

namespace App\Filament\Resources\RouteResource\RelationManagers;

use App\Models\Property;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class StopsRelationManager extends RelationManager
{
    protected static string $relationship = 'stops';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-list-bullet';

    protected static ?string $title = 'Stops';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'last_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => trim(
                    ($record->company_name ? $record->company_name . ' — ' : '')
                    . trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? ''))
                ))
                ->searchable(['first_name', 'last_name', 'company_name', 'email'])
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('property_id', null)),
            Forms\Components\Select::make('property_id')
                ->label('Property')
                ->options(fn (Get $get) => $get('customer_id')
                    ? Property::query()
                        ->where('customer_id', $get('customer_id'))
                        ->orderByDesc('is_primary')
                        ->orderBy('address')
                        ->pluck('address', 'id')
                        ->all()
                    : [])
                ->searchable()
                ->placeholder('Select a customer first'),
            Forms\Components\Select::make('service_id')
                ->relationship('service', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(fn ($livewire) => ($livewire->getOwnerRecord()->stops()->max('sort_order') ?? 0) + 1)
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'skipped' => 'Skipped',
                ])
                ->default('pending')
                ->required(),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.last_name')
                    ->label('Customer')
                    ->formatStateUsing(function ($record) {
                        $c = $record->customer;
                        if (! $c) {
                            return null;
                        }
                        $name = trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
                        return $c->company_name ? "{$c->company_name} — {$name}" : $name;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('property.address')
                    ->label('Property'),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'skipped' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
