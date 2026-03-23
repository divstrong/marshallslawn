<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimeLogResource\Pages;
use App\Models\TimeLog;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class TimeLogResource extends Resource
{
    protected static ?string $model = TimeLog::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static string | \UnitEnum | null $navigationGroup = 'Administration';

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
            Forms\Components\DateTimePicker::make('clock_in')
                ->required(),
            Forms\Components\DateTimePicker::make('clock_out'),
            Forms\Components\TextInput::make('break_minutes')
                ->numeric()
                ->default(0),
            Forms\Components\Select::make('status')
                ->options([
                    'clocked_in' => 'Clocked In',
                    'clocked_out' => 'Clocked Out',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->required(),
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
                Tables\Columns\TextColumn::make('job.title')
                    ->label('Job'),
                Tables\Columns\TextColumn::make('clock_in')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('clock_out')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
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
            'index' => Pages\ListTimeLogs::route('/'),
            'create' => Pages\CreateTimeLog::route('/create'),
            'edit' => Pages\EditTimeLog::route('/{record}/edit'),
        ];
    }
}
