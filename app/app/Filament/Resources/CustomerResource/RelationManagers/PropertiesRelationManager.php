<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-home-modern';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
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
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('address')
            ->columns([
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city'),
                Tables\Columns\TextColumn::make('state'),
                Tables\Columns\TextColumn::make('zip'),
                Tables\Columns\TextColumn::make('lot_size'),
                Tables\Columns\TextColumn::make('square_footage')
                    ->label('Sq Ft')
                    ->suffix(' sq ft'),
                Tables\Columns\IconColumn::make('is_primary')
                    ->boolean(),
            ])
            ->filters([])
            ->defaultPaginationPageOption(50)
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
