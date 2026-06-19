<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\Payment;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-banknotes';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->defaultSort('paid_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->url(fn (Payment $record) => $record->invoice
                        ? route('filament.admin.resources.invoices.edit', $record->invoice)
                        : null)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Payment::METHODS[$state] ?? $state),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->options(Payment::METHODS),
                Tables\Filters\Filter::make('paid_at')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Paid from'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Paid until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('paid_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('paid_at', '<=', $d));
                    }),
            ])
            ->actions([
                Actions\Action::make('invoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Payment $record) => $record->invoice
                        ? route('filament.admin.resources.invoices.edit', $record->invoice)
                        : null)
                    ->visible(fn (Payment $record) => (bool) $record->invoice),
            ]);
    }
}
