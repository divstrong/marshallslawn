<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChemicalLogResource\Pages;
use App\Models\ChemicalLog;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class ChemicalLogResource extends Resource
{
    protected static ?string $model = ChemicalLog::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-beaker';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('employee_id')
                ->relationship('employee', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('job_id')
                ->relationship('job', 'title')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('property_id')
                ->relationship('property', 'address')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\DatePicker::make('application_date')
                ->required(),
            Forms\Components\TextInput::make('chemical_name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('epa_registration_number')
                ->maxLength(255),
            Forms\Components\TextInput::make('target_pest')
                ->maxLength(255),
            Forms\Components\TextInput::make('application_rate')
                ->numeric(),
            Forms\Components\TextInput::make('application_unit')
                ->maxLength(255),
            Forms\Components\TextInput::make('area_treated')
                ->numeric(),
            Forms\Components\TextInput::make('wind_speed')
                ->numeric(),
            Forms\Components\TextInput::make('temperature')
                ->numeric(),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable(),
                Tables\Columns\TextColumn::make('property.address')
                    ->label('Property')
                    ->searchable(),
                Tables\Columns\TextColumn::make('application_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('chemical_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('area_treated'),
            ])
            ->filters([])
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
            'index' => Pages\ListChemicalLogs::route('/'),
            'create' => Pages\CreateChemicalLog::route('/create'),
            'edit' => Pages\EditChemicalLog::route('/{record}/edit'),
        ];
    }
}
