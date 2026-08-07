<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAccess;
use App\Filament\Resources\RecurringJobTemplateResource\Pages;
use App\Models\Property;
use App\Models\RecurringJobTemplate;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RecurringJobTemplateResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = RecurringJobTemplate::class;

    // Recurring series are created from the Jobs form now (Type = Recurring),
    // so this resource is no longer surfaced in the navigation.
    protected static bool $shouldRegisterNavigation = false;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Recurring Jobs';

    protected static ?string $modelLabel = 'Recurring Job';

    protected static ?string $pluralModelLabel = 'Recurring Jobs';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        // Three named sections — what the work is, when it repeats, and when it
        // runs — rather than one flat list of fifteen fields.
        return $schema->schema([
            Section::make('Work')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Weekly Mow — Smith Property')
                        ->columnSpanFull(),

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
                        ->required()
                        ->placeholder('Select a customer first'),

                    Forms\Components\Select::make('service_id')
                        ->relationship('service', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('crew_id')
                        ->label('Default crew')
                        ->relationship('crew', 'name')
                        ->searchable()
                        ->preload(),
                ]),

            Section::make('Schedule')
                ->columns(3)
                ->schema([
                    // Preset intervals cover almost every series; anything else is
                    // typed into the custom box, which shares the same column.
                    Forms\Components\Select::make('interval_preset')
                        ->label('Repeats')
                        ->options([
                            7 => 'Weekly',
                            14 => 'Biweekly',
                            21 => 'Every 3 weeks',
                            28 => 'Every 4 weeks',
                            30 => 'Monthly',
                            0 => 'Custom…',
                        ])
                        ->native(false)
                        ->required()
                        ->dehydrated(false)
                        ->live()
                        ->afterStateHydrated(function ($component, ?RecurringJobTemplate $record): void {
                            $days = (int) ($record?->interval_days ?? 7);
                            $component->state(in_array($days, [7, 14, 21, 28, 30], true) ? $days : 0);
                        })
                        ->afterStateUpdated(function ($state, Set $set): void {
                            if ((int) $state !== 0) {
                                $set('interval_days', (int) $state);
                            }
                        }),

                    Forms\Components\TextInput::make('interval_days')
                        ->label('Every (days)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(365)
                        ->default(7)
                        ->required(fn (Get $get): bool => (int) $get('interval_preset') === 0)
                        ->visible(fn (Get $get): bool => (int) $get('interval_preset') === 0)
                        // Picking a preset writes the interval here while this box
                        // is hidden — it still has to reach the model.
                        ->dehydratedWhenHidden(),

                    Forms\Components\Select::make('preferred_day_of_week')
                        ->label('On day')
                        ->options([
                            0 => 'Sunday',
                            1 => 'Monday',
                            2 => 'Tuesday',
                            3 => 'Wednesday',
                            4 => 'Thursday',
                            5 => 'Friday',
                            6 => 'Saturday',
                        ])
                        ->native(false)
                        ->placeholder('Any')
                        ->helperText('If set, jobs always land on this day.'),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('First visit')
                        ->required()
                        ->default(now()),
                ]),

            Section::make('Season & status')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('season_start_month')
                        ->label('Season start')
                        ->options(self::monthOptions())
                        ->native(false)
                        ->placeholder('All year'),

                    Forms\Components\Select::make('season_end_month')
                        ->label('Season end')
                        ->options(self::monthOptions())
                        ->native(false)
                        ->placeholder('All year')
                        ->helperText('e.g. Leaf Removal: Oct–Dec.'),

                    Forms\Components\Toggle::make('active')
                        ->default(true)
                        ->required()
                        ->inline(false),

                    Forms\Components\Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
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
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service'),
                Tables\Columns\TextColumn::make('crew.name')
                    ->label('Crew'),
                Tables\Columns\TextColumn::make('interval_days')
                    ->label('Every')
                    ->formatStateUsing(fn ($state) => match ((int) $state) {
                        7 => 'Weekly',
                        14 => 'Biweekly',
                        21 => '3 weeks',
                        28 => '4 weeks',
                        30 => 'Monthly',
                        default => $state . ' days',
                    }),
                Tables\Columns\TextColumn::make('preferred_day_of_week')
                    ->label('Day')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$state]),
                Tables\Columns\TextColumn::make('next_generation_date')
                    ->label('Next gen')
                    ->date(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
            ])
            ->defaultSort('title')
            ->defaultPaginationPageOption(50)
            ->filters([
                Tables\Filters\SelectFilter::make('service_id')
                    ->relationship('service', 'name')
                    ->label('Service'),
                Tables\Filters\SelectFilter::make('crew_id')
                    ->relationship('crew', 'name')
                    ->label('Crew'),
                Tables\Filters\TernaryFilter::make('active'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringJobTemplates::route('/'),
            'create' => Pages\CreateRecurringJobTemplate::route('/create'),
            'edit' => Pages\EditRecurringJobTemplate::route('/{record}/edit'),
        ];
    }

    private static function monthOptions(): array
    {
        return [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
    }
}
