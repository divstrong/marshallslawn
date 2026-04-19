<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Models\Property;
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

class PropertyResource extends Resource
{
    use ChecksResourceAccess;
    protected static ?string $model = Property::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static string | \UnitEnum | null $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Property')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('General')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Select::make('customer_id')
                                ->relationship('customer', 'last_name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Forms\Components\TextInput::make('address')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('city')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('state')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('zip')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('lot_size')
                                ->numeric(),
                            Forms\Components\TextInput::make('lawn_size')
                                ->numeric(),
                            Forms\Components\TextInput::make('square_footage')
                                ->label('Square Footage')
                                ->numeric()
                                ->suffix('sq ft'),
                            Forms\Components\Toggle::make('is_primary')
                                ->default(false),
                            Forms\Components\Textarea::make('notes')
                                ->columnSpanFull(),
                        ]),
                    Tab::make('Media')
                        ->icon('heroicon-o-photo')
                        ->badge(fn (?Property $record): ?string => $record?->media()->count() ?: null)
                        ->hidden(fn (?Property $record): bool => ! $record?->exists)
                        ->schema([
                            View::make('filament.resources.property.media-tab'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('state'),
                Tables\Columns\TextColumn::make('customer.last_name')
                    ->label('Customer'),
                Tables\Columns\TextColumn::make('lot_size'),
                Tables\Columns\TextColumn::make('square_footage')
                    ->label('Sq Ft')
                    ->suffix(' sq ft')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_primary')
                    ->boolean(),
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
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}
