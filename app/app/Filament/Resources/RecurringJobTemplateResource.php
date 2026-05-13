<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAccess;
use App\Filament\Resources\RecurringJobTemplateResource\Pages;
use App\Models\Property;
use App\Models\RecurringJobTemplate;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RecurringJobTemplateResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = RecurringJobTemplate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Recurring Jobs';

    protected static ?string $modelLabel = 'Recurring Job';

    protected static ?string $pluralModelLabel = 'Recurring Jobs';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
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

            Forms\Components\Select::make('interval_days')
                ->label('Frequency')
                ->options([
                    7 => 'Weekly (7 days)',
                    14 => 'Biweekly (14 days)',
                    21 => 'Every 3 weeks',
                    28 => 'Every 4 weeks',
                    30 => 'Monthly (~30 days)',
                ])
                ->default(7)
                ->required()
                ->helperText('Use a custom value below for other intervals.'),

            Forms\Components\TextInput::make('interval_days')
                ->label('Custom interval (days)')
                ->numeric()
                ->minValue(1)
                ->maxValue(365)
                ->visible(fn (Get $get) => ! in_array((int) $get('interval_days'), [7, 14, 21, 28, 30], true)),

            Forms\Components\Select::make('preferred_day_of_week')
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
                ->placeholder('Any')
                ->helperText('If set, jobs always land on this day.'),

            Forms\Components\DatePicker::make('start_date')
                ->required()
                ->default(now()),

            Forms\Components\DatePicker::make('end_date')
                ->helperText('Leave blank for indefinite.'),

            Forms\Components\Select::make('season_start_month')
                ->label('Season start (month)')
                ->options(self::monthOptions())
                ->placeholder('All year'),

            Forms\Components\Select::make('season_end_month')
                ->label('Season end (month)')
                ->options(self::monthOptions())
                ->placeholder('All year')
                ->helperText('e.g. Leaf Removal: Oct–Dec.'),

            Forms\Components\Toggle::make('active')
                ->default(true)
                ->required(),

            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
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
