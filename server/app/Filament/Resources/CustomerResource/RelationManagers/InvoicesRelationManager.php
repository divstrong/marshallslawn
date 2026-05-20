<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-document-text';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('invoice_number')
                ->label('Invoice #')
                ->disabled()
                ->maxLength(255),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'sent' => 'Sent',
                    'paid' => 'Paid',
                    'overdue' => 'Overdue',
                    'cancelled' => 'Cancelled',
                ])
                ->required(),
            Forms\Components\TextInput::make('subtotal')
                ->numeric()
                ->prefix('$')
                ->required(),
            Forms\Components\TextInput::make('tax')
                ->numeric()
                ->prefix('$')
                ->default(0),
            Forms\Components\DatePicker::make('issued_at')
                ->label('Issued Date'),
            Forms\Components\DatePicker::make('due_at')
                ->label('Due Date'),
            Forms\Components\DatePicker::make('paid_at')
                ->label('Paid Date'),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->defaultSort('issued_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('credits_total')
                    ->label('Credits')
                    ->money('usd'),
                Tables\Columns\TextColumn::make('total')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('issued_at')
                    ->label('Issued')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_at')
                    ->label('Due')
                    ->date()
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([
                Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['total'] = ($data['subtotal'] ?? 0) + ($data['tax'] ?? 0);
                        return $data;
                    }),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['total'] = ($data['subtotal'] ?? 0) + ($data['tax'] ?? 0) - ($data['credits_total'] ?? 0);
                        return $data;
                    }),
                Actions\Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.invoices.edit', $record)),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
