<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use App\Models\Role;
use Filament\Forms;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\ChecksResourceAccess;
use Filament\Actions;

class EmployeeResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = Employee::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 4;

    /** The 50 US states, keyed by USPS abbreviation. */
    private const US_STATES = [
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
        'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
        'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
        'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
        'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
        'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
        'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
        'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
        'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
        'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
        'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
        'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
        'WI' => 'Wisconsin', 'WY' => 'Wyoming',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Employee')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('General')
                        ->icon('heroicon-o-information-circle')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('first_name')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('last_name')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->maxLength(255)
                                // The field app is passwordless — this address is both the
                                // login identifier and where the sign-in code is sent.
                                ->helperText('Used to sign in to the field app. Sign-in codes are emailed here.'),
                            Forms\Components\TextInput::make('phone')
                                ->tel()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('mobile_phone')
                                ->label('Mobile Phone')
                                ->tel()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('alt_phone')
                                ->label('Alt. Phone')
                                ->tel()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('address')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('city')
                                ->maxLength(255),
                            Forms\Components\Select::make('state')
                                ->options(self::US_STATES)
                                ->searchable()
                                ->native(false),
                            Forms\Components\TextInput::make('zip')
                                ->maxLength(255),
                            Forms\Components\DatePicker::make('hire_date'),
                            Forms\Components\DatePicker::make('date_of_birth')
                                ->label('Date of Birth'),
                            Forms\Components\Select::make('role')
                                ->options(fn () => Role::orderBy('label')->pluck('label', 'name')->all())
                                ->required(),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                    'terminated' => 'Terminated',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('hourly_rate')
                                ->numeric()
                                ->prefix('$'),
                            Forms\Components\TextInput::make('emergency_contact_name')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('emergency_contact_phone')
                                ->tel()
                                ->maxLength(255),
                            Forms\Components\Placeholder::make('legacy_id')
                                ->label('Legacy ID')
                                ->content(fn (?Employee $record): string => $record?->legacy_id ?: '—')
                                // Import-only data — only shown for records that have one.
                                ->visible(fn (?Employee $record): bool => filled($record?->legacy_id)),
                        ]),
                    Tab::make('Notes')
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->rows(10)
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Display Name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('mobile_phone')
                    ->label('Mobile')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => $state
                        ? (Role::where('name', $state)->value('label') ?? $state)
                        : null),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('hire_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('legacy_id')
                    ->label('Legacy ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
