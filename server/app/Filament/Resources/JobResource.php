<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobResource\Pages;
use App\Models\Job;
use Filament\Forms;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\ChecksResourceAccess;
use Filament\Actions;

class JobResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = Job::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Job')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('General')
                        ->icon('heroicon-o-information-circle')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('customer_id')
                                ->relationship('customer', 'last_name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Forms\Components\Select::make('property_id')
                                ->relationship('property', 'address')
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('estimate_id')
                                ->relationship('estimate', 'estimate_number')
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('crew_id')
                                ->relationship('crew', 'name')
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'scheduled' => 'Scheduled',
                                    'in_progress' => 'In Progress',
                                    'completed' => 'Completed',
                                    'skipped' => 'Skipped',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->required(),
                            Forms\Components\Select::make('priority')
                                ->options([
                                    'low' => 'Low',
                                    'normal' => 'Normal',
                                    'high' => 'High',
                                    'urgent' => 'Urgent',
                                ])
                                ->required(),
                            Forms\Components\Radio::make('is_scheduled')
                                ->label('Scheduled')
                                ->options([
                                    'no' => 'No (TBD)',
                                    'yes' => 'Yes',
                                ])
                                ->default('no')
                                ->inline()
                                ->inlineLabel(false)
                                ->live()
                                ->dehydrated(false)
                                ->afterStateHydrated(function ($component, ?Job $record) {
                                    $component->state($record?->scheduled_date ? 'yes' : 'no');
                                })
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state !== 'yes') {
                                        $set('scheduled_date', null);
                                    }
                                }),
                            Forms\Components\DatePicker::make('scheduled_date')
                                ->label('Scheduled date')
                                ->visible(fn (Get $get): bool => $get('is_scheduled') === 'yes')
                                ->required(fn (Get $get): bool => $get('is_scheduled') === 'yes'),
                            Forms\Components\Textarea::make('notes')
                                ->columnSpanFull(),
                        ]),
                    Tab::make('Services')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->badge(fn (?Job $record): ?string => $record?->jobServices()->count() ?: null)
                        ->hidden(fn (?Job $record): bool => ! $record?->exists)
                        ->schema([
                            View::make('filament.resources.job.services-tab'),
                        ]),
                    Tab::make('Attachments')
                        ->icon('heroicon-o-paper-clip')
                        ->badge(fn (?Job $record): ?string => $record?->media()->count() ?: null)
                        ->hidden(fn (?Job $record): bool => ! $record?->exists)
                        ->schema([
                            View::make('filament.resources.job.attachments-tab'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Job #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.last_name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('crew.name')
                    ->label('Crew'),
                Tables\Columns\TextColumn::make('jobServices.service.name')
                    ->label('Services')
                    ->badge()
                    ->separator(',')
                    ->limitList(3)
                    ->expandableLimitedList(),
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
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'skipped' => 'Skipped',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
            ])
            ->defaultPaginationPageOption(50)
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobs::route('/'),
            'create' => Pages\CreateJob::route('/create'),
            'edit' => Pages\EditJob::route('/{record}/edit'),
        ];
    }
}
