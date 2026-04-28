<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAccess;
use App\Filament\Resources\RouteResource\Pages;
use App\Filament\Resources\RouteResource\RelationManagers\StopsRelationManager;
use App\Models\Route;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RouteResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = Route::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->placeholder('Mon AM — North Side'),
            Forms\Components\DatePicker::make('route_date')
                ->required()
                ->default(now()),
            Forms\Components\Select::make('crew_id')
                ->label('Crew (Foreman)')
                ->relationship('crew', 'name')
                ->getOptionLabelFromRecordUsing(function ($record) {
                    $foreman = $record->foreman;
                    $foremanName = $foreman
                        ? trim(($foreman->first_name ?? '') . ' ' . ($foreman->last_name ?? '')) ?: ($foreman->name ?? '')
                        : null;

                    return $foremanName
                        ? "{$record->name} — {$foremanName}"
                        : $record->name;
                })
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('status')
                ->options([
                    'planning' => 'Planning',
                    'active' => 'Active',
                    'completed' => 'Completed',
                ])
                ->default('planning')
                ->required(),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('route_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('crew.name')
                    ->label('Crew')
                    ->searchable(),
                Tables\Columns\TextColumn::make('foreman.last_name')
                    ->label('Foreman')
                    ->formatStateUsing(function ($record) {
                        $f = $record->foreman;
                        if (! $f) {
                            return null;
                        }

                        return trim(($f->first_name ?? '') . ' ' . ($f->last_name ?? '')) ?: $f->name;
                    }),
                Tables\Columns\TextColumn::make('stops_count')
                    ->label('Stops')
                    ->counts('stops'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'planning' => 'gray',
                        'active' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('route_date', 'desc')
            ->defaultPaginationPageOption(50)
            ->filters([
                Tables\Filters\SelectFilter::make('crew_id')
                    ->relationship('crew', 'name')
                    ->label('Crew'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'planning' => 'Planning',
                        'active' => 'Active',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StopsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoutes::route('/'),
            'create' => Pages\CreateRoute::route('/create'),
            'edit' => Pages\EditRoute::route('/{record}/edit'),
        ];
    }
}
