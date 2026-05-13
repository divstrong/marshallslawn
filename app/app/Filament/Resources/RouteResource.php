<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAccess;
use App\Filament\Resources\RouteResource\Pages;
use App\Models\Crew;
use App\Models\Route;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

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
            Tabs::make('Route')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('General')
                        ->icon('heroicon-o-information-circle')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Mon AM — North Side')
                                ->columnSpanFull(),
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
                        ]),
                    Tab::make('Stops')
                        ->icon('heroicon-o-map-pin')
                        ->badge(fn (?Route $record): ?string => $record?->stops()->count() ?: null)
                        ->hidden(fn (?Route $record): bool => ! $record?->exists)
                        ->schema([
                            View::make('filament.resources.route.stops-tab'),
                        ]),
                ]),
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
                Actions\Action::make('copy')
                    ->label('Copy')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->schema(fn (Route $record) => [
                        Forms\Components\DatePicker::make('new_date')
                            ->label('New date')
                            ->required()
                            ->default(Carbon::parse($record->route_date)->addWeek()),
                        Forms\Components\Select::make('new_crew_id')
                            ->label('Crew')
                            ->relationship('crew', 'name')
                            ->searchable()
                            ->preload()
                            ->default($record->crew_id)
                            ->helperText('Pick a different crew to copy onto, or keep the same one.'),
                        Forms\Components\Toggle::make('reset_status')
                            ->label('Reset all stop statuses to pending')
                            ->default(true),
                    ])
                    ->action(function (array $data, Route $record) {
                        $newDate = $data['new_date'];
                        $newCrewId = $data['new_crew_id'] ?? $record->crew_id;
                        $resetStatus = (bool) ($data['reset_status'] ?? true);

                        $crewName = Crew::whereKey($newCrewId)->value('name') ?? 'Route';

                        $newRoute = DB::transaction(function () use ($record, $newDate, $newCrewId, $crewName, $resetStatus) {
                            $copy = Route::create([
                                'name' => Carbon::parse($newDate)->format('D, M j') . ' — ' . $crewName,
                                'route_date' => $newDate,
                                'crew_id' => $newCrewId,
                                'status' => 'planning',
                                'notes' => $record->notes,
                            ]);

                            foreach ($record->stops()->orderBy('sort_order')->get() as $stop) {
                                $copy->stops()->create([
                                    'job_id' => null, // decouple from the source date's Job instances
                                    'customer_id' => $stop->customer_id,
                                    'property_id' => $stop->property_id,
                                    'service_id' => $stop->service_id,
                                    'sort_order' => $stop->sort_order,
                                    'status' => $resetStatus ? 'pending' : $stop->status,
                                    'completed_at' => $resetStatus ? null : $stop->completed_at,
                                    'notes' => $stop->notes,
                                ]);
                            }

                            return $copy;
                        });

                        Notification::make()
                            ->title('Route copied')
                            ->body("Created \"{$newRoute->name}\" with " . $newRoute->stops()->count() . ' stops.')
                            ->success()
                            ->send();
                    })
                    ->modalHeading(fn (Route $record) => 'Copy route: ' . $record->name)
                    ->modalSubmitActionLabel('Create copy'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        // StopsRelationManager removed — stops are now managed via the drag/drop "Stops" tab
        // on the edit form (powered by App\Livewire\RouteStopsManager).
        return [];
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
