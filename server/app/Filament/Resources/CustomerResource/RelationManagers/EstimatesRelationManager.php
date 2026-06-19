<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class EstimatesRelationManager extends RelationManager
{
    protected static string $relationship = 'estimates';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-calculator';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('estimate_number')
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('estimate_number')
                    ->label('Estimate #')
                    ->searchable()
                    ->sortable()
                    // Link the ID straight to the estimate detail (edit) view.
                    ->url(fn ($record) => route('filament.admin.resources.estimates.edit', $record))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('total')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'declined' => 'Declined',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Actions\Action::make('details')
                    ->label('Details')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn ($record) => route('filament.admin.resources.estimates.edit', $record)),
                // The same public page the client sees.
                Actions\Action::make('view_public')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => $record->share_token ? $record->getPublicUrl() : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => (bool) $record->share_token),
            ]);
    }
}
