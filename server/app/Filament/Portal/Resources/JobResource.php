<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\JobResource\Pages;
use App\Models\Job;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JobResource extends Resource
{
    protected static ?string $model = Job::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Jobs';

    protected static ?int $navigationSort = 1;

    /** Never expose anything but the signed-in customer's own jobs. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('customer_id', Filament::auth()->id());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Job')
                    ->searchable(),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Scheduled')
                    ->date()
                    ->placeholder('TBD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('crew.name')
                    ->label('Crew')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->defaultSort('scheduled_date', 'desc')
            ->defaultPaginationPageOption(25);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobs::route('/'),
        ];
    }
}
