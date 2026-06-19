<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobResource\Pages;
use App\Models\Job;
use Filament\Forms;
use Filament\Schemas\Components\Fieldset;
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
                                ->options(fn (): array => \App\Models\JobStatus::options())
                                ->default('pending')
                                ->required(),
                            Forms\Components\Select::make('priority')
                                ->options([
                                    'low' => 'Low',
                                    'normal' => 'Normal',
                                    'high' => 'High',
                                    'urgent' => 'Urgent',
                                ])
                                ->required(),

                            // --- Type & recurrence (create only) — issue #13 ---
                            Forms\Components\Radio::make('job_type')
                                ->label('Type')
                                ->options([
                                    'one_time' => 'One Time',
                                    'recurring' => 'Recurring',
                                ])
                                ->default('one_time')
                                ->inline()
                                ->inlineLabel(false)
                                ->live()
                                ->visibleOn('create')
                                ->columnSpanFull(),
                            Forms\Components\Select::make('services')
                                ->label('Services')
                                ->multiple()
                                ->options(fn () => \App\Models\Service::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->visibleOn('create')
                                ->required(fn (Get $get): bool => $get('job_type') === 'recurring')
                                ->helperText('Applied to the job(s). Required for a recurring series.')
                                ->columnSpanFull(),
                            Fieldset::make('Recurrence')
                                // Only on the create form, and only when Recurring is chosen.
                                ->visible(fn (Get $get, string $operation): bool => $operation === 'create'
                                    && $get('job_type') === 'recurring')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Select::make('recur_frequency')
                                        ->label('Frequency')
                                        ->options([
                                            'weekly' => 'Weekly',
                                            'monthly' => 'Monthly',
                                        ])
                                        ->default('weekly')
                                        ->live()
                                        ->required(fn (Get $get): bool => $get('job_type') === 'recurring'),
                                    Forms\Components\Select::make('recur_day_of_week')
                                        ->label('Preferred day of week')
                                        ->options([
                                            0 => 'Sunday',
                                            1 => 'Monday',
                                            2 => 'Tuesday',
                                            3 => 'Wednesday',
                                            4 => 'Thursday',
                                            5 => 'Friday',
                                            6 => 'Saturday',
                                        ])
                                        ->visible(fn (Get $get): bool => $get('recur_frequency') === 'weekly')
                                        ->placeholder('Any day'),
                                    Forms\Components\Toggle::make('recur_indefinite')
                                        ->label('Indefinite (no fixed count)')
                                        ->live()
                                        ->default(false),
                                    Forms\Components\TextInput::make('recur_occurrences')
                                        ->label('Number of occurrences')
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(260)
                                        ->visible(fn (Get $get): bool => ! $get('recur_indefinite'))
                                        ->required(fn (Get $get): bool => $get('job_type') === 'recurring' && ! $get('recur_indefinite')),
                                    Forms\Components\DatePicker::make('recur_start')
                                        ->label('Start date')
                                        ->default(now())
                                        ->required(fn (Get $get): bool => $get('job_type') === 'recurring'),
                                    Forms\Components\DatePicker::make('recur_end')
                                        ->label('Stop date (optional)')
                                        ->helperText('Caps the series even if the count/indefinite setting would continue.'),
                                ]),

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
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'recurring' ? 'Recurring' : 'One Time')
                    ->color(fn (?string $state) => $state === 'recurring' ? 'info' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => \App\Models\JobStatus::options()[$state] ?? $state)
                    ->color(fn (?string $state): string => \App\Models\JobStatus::colorMap()[$state] ?? 'gray'),
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
                    ->options(fn (): array => \App\Models\JobStatus::options()),
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
