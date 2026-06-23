<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAccess;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = Payment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';

    protected static string | \UnitEnum | null $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('invoice_id')
                ->relationship('invoice', 'invoice_number')
                ->searchable()
                ->preload()
                ->required()
                ->helperText('The customer is taken from the invoice automatically.'),
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'last_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => trim("{$record->first_name} {$record->last_name}") . ($record->company_name ? " ({$record->company_name})" : ''))
                ->searchable()
                ->preload()
                ->helperText('Optional — defaults to the invoice customer.'),
            Forms\Components\TextInput::make('amount')
                ->numeric()
                ->prefix('$')
                ->required(),
            Forms\Components\Select::make('method')
                ->options(Payment::METHODS)
                ->default('card')
                ->required(),
            Forms\Components\DatePicker::make('paid_at')
                ->label('Paid Date')
                ->default(now())
                ->required(),
            Forms\Components\TextInput::make('reference')
                ->placeholder('Check #, transaction id…')
                ->maxLength(255),
            Forms\Components\Textarea::make('notes')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->url(fn (Payment $record) => $record->invoice_id
                        ? InvoiceResource::getUrl('edit', ['record' => $record->invoice_id])
                        : null),
                Tables\Columns\TextColumn::make('customer')
                    ->label('Customer')
                    ->state(fn (Payment $record): string => self::customerName($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'customer',
                        fn (Builder $q) => $q
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%"),
                    )),
                Tables\Columns\TextColumn::make('amount')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Payment::METHODS[$state] ?? $state),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->options(Payment::METHODS),
                Tables\Filters\Filter::make('paid_at')
                    ->schema([
                        Forms\Components\DatePicker::make('from')->label('Paid from'),
                        Forms\Components\DatePicker::make('until')->label('Paid until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('paid_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('paid_at', '<=', $d));
                    }),
            ])
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

    private static function customerName(Payment $payment): string
    {
        $customer = $payment->customer;
        if (! $customer) {
            return '—';
        }

        $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));

        return $name ?: ($customer->company_name ?? '—');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
