<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use App\Filament\Concerns\ChecksResourceAccess;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class CustomerResource extends Resource
{
    use ChecksResourceAccess;
    protected static ?string $model = Customer::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | \UnitEnum | null $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Customer')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('General')
                        ->icon('heroicon-o-information-circle')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('company_name')
                                ->columnSpanFull()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('first_name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('last_name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('phone')
                                ->tel()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('address')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('city')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('state')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('zip')
                                ->maxLength(255),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                    'lead' => 'Lead',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('customer_type')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('account_number')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('division')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('source')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('legacy_id')
                                ->label('Legacy ID')
                                ->disabled()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('notes')
                                ->columnSpanFull(),
                        ]),
                    Tab::make('Properties')
                        ->icon('heroicon-o-home-modern')
                        ->badge(fn (?Customer $record): ?string => $record?->properties()->count() ?: null)
                        ->hidden(fn (?Customer $record): bool => ! $record?->exists)
                        ->schema([
                            View::make('filament.resources.customer.properties-tab'),
                        ]),
                    Tab::make('Invoices')
                        ->icon('heroicon-o-document-text')
                        ->badge(fn (?Customer $record): ?string => $record?->invoices()->count() ?: null)
                        ->hidden(fn (?Customer $record): bool => ! $record?->exists)
                        ->schema([
                            View::make('filament.resources.customer.invoices-tab'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Customer')
                    ->formatStateUsing(fn ($state, $record) => $state ?: trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')))
                    ->description(fn ($record) => $record->company_name ? trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) : null)
                    ->searchable(['company_name', 'first_name', 'last_name'])
                    ->sortable(['last_name']),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('customer_type')
                    ->label('Type')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('account_number')
                    ->label('Account #')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
