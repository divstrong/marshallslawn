<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Mail\ShareInvoiceMail;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\ChecksResourceAccess;
use Filament\Actions;
use Illuminate\Support\Facades\Mail;

class InvoiceResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = Invoice::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static string | \UnitEnum | null $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 3;

    /**
     * One scrolling form rather than tabs. Three of the four tabs only existed once
     * the record did, so creating an invoice meant looking at a tab bar where
     * everything but the first entry was missing.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->schema([
            Section::make('Invoice')
                ->icon('heroicon-o-information-circle')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->relationship('customer', 'last_name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}" . ($record->company_name ? " ({$record->company_name})" : ''))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        // Switching customer invalidates an estimate belonging to the old one.
                        ->afterStateUpdated(fn (Set $set) => $set('estimate_id', null)),
                    // Only the chosen customer's estimates, so an invoice can't be tied
                    // to someone else's quote.
                    Forms\Components\Select::make('estimate_id')
                        ->label('From estimate')
                        ->relationship(
                            name: 'estimate',
                            titleAttribute: 'estimate_number',
                            modifyQueryUsing: fn ($query, Get $get) => $query
                                ->when($get('customer_id'), fn ($q, $id) => $q->where('customer_id', $id)),
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record): string => trim(
                            ($record->estimate_number ?: "Estimate #{$record->id}")
                            . ' — $' . number_format((float) $record->total, 2)
                            . ' (' . ucfirst((string) $record->status) . ')'
                        ))
                        ->searchable()
                        ->preload()
                        ->disabled(fn (Get $get): bool => blank($get('customer_id')))
                        ->placeholder(fn (Get $get): string => blank($get('customer_id'))
                            ? 'Select a customer first'
                            : 'Not from an estimate')
                        ->helperText('Optional — links this invoice back to the quote it came from.'),
                    Forms\Components\TextInput::make('invoice_number')
                        ->label('Invoice #')
                        ->disabled()
                        // Numbers are assigned by Invoice::booted() on create, so there's
                        // nothing to submit — showing that beats an empty locked box.
                        ->dehydrated(fn (?Invoice $record): bool => (bool) $record?->exists)
                        ->placeholder('Assigned automatically on save')
                        ->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'sent' => 'Sent',
                            'paid' => 'Paid',
                            'overdue' => 'Overdue',
                            'payment_plan' => 'Payment Plan',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('draft')
                        ->native(false)
                        ->required(),
                    Forms\Components\DatePicker::make('issued_at')
                        ->label('Issued Date'),
                    Forms\Components\DatePicker::make('due_at')
                        ->label('Due Date'),
                ]),

            Section::make('Amounts')
                ->icon('heroicon-o-calculator')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('subtotal')
                        ->numeric()
                        ->prefix('$')
                        ->default(0)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcTotal($get, $set)),
                    Forms\Components\TextInput::make('tax')
                        ->numeric()
                        ->prefix('$')
                        ->default(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcTotal($get, $set)),
                    Forms\Components\TextInput::make('credits_total')
                        ->label('Credits Applied')
                        ->numeric()
                        ->prefix('$')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('total')
                        ->numeric()
                        ->prefix('$')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            Section::make('Payment options')
                ->icon('heroicon-o-credit-card')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Toggle::make('allows_payment_plan')
                        ->label('Allow a payment plan on this invoice')
                        ->helperText('When off, the customer only sees the pay-in-full option on the public invoice.')
                        ->default(true)
                        ->columnSpanFull(),
                    // Read-only status of a plan that's already running.
                    Forms\Components\Placeholder::make('plan_monthly')
                        ->label('Monthly Payment')
                        ->content(fn (?Invoice $record) => $record?->payment_plan_amount ? '$' . number_format($record->payment_plan_amount, 2) . '/mo' : '-')
                        ->visible(fn (?Invoice $record) => (bool) $record?->is_payment_plan),
                    Forms\Components\Placeholder::make('plan_progress')
                        ->label('Payments Made')
                        ->content(fn (?Invoice $record) => $record ? "{$record->payment_plan_payments_made} of {$record->payment_plan_installments}" : '-')
                        ->visible(fn (?Invoice $record) => (bool) $record?->is_payment_plan),
                    Forms\Components\Placeholder::make('plan_started')
                        ->label('Started')
                        ->content(fn (?Invoice $record) => $record?->payment_plan_started_at?->format('M d, Y') ?? '-')
                        ->visible(fn (?Invoice $record) => (bool) $record?->is_payment_plan),
                    Forms\Components\Placeholder::make('plan_next')
                        ->label('Next Payment')
                        ->content(fn (?Invoice $record) => $record?->payment_plan_started_at
                            ? $record->payment_plan_started_at->addDays(30 * $record->payment_plan_payments_made)->format('M d, Y')
                            : '-')
                        ->visible(fn (?Invoice $record) => (bool) $record?->is_payment_plan),
                ])
                ->columns(4),

            Section::make('Notes')
                ->icon('heroicon-o-pencil-square')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->hiddenLabel()
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            // The record-bound managers. Collapsed by default on an existing invoice so
            // the form still opens as one readable page.
            Section::make('Line Items')
                ->icon('heroicon-o-list-bullet')
                ->columnSpanFull()
                ->collapsible()
                ->description(fn (?Invoice $record): ?string => $record?->exists
                    ? $record->lineItems()->count() . ' line item(s)'
                    : null)
                ->hidden(fn (?Invoice $record): bool => ! $record?->exists)
                ->schema([
                    View::make('filament.resources.invoice.line-items-tab'),
                ]),

            Section::make('Credits')
                ->icon('heroicon-o-receipt-percent')
                ->columnSpanFull()
                ->collapsible()
                ->collapsed()
                ->description(fn (?Invoice $record): ?string => $record?->exists
                    ? $record->credits()->count() . ' credit(s) applied'
                    : null)
                ->hidden(fn (?Invoice $record): bool => ! $record?->exists)
                ->schema([
                    View::make('filament.resources.invoice.credits-tab'),
                ]),

            Section::make('Payments')
                ->icon('heroicon-o-banknotes')
                ->columnSpanFull()
                ->collapsible()
                ->collapsed()
                ->description(fn (?Invoice $record): ?string => $record?->exists
                    ? $record->payments()->count() . ' payment(s) received'
                    : null)
                ->hidden(fn (?Invoice $record): bool => ! $record?->exists)
                ->schema([
                    View::make('filament.resources.invoice.payments-tab'),
                ]),
        ]);
    }

    /** Live-update the Total as subtotal/tax are entered: subtotal + tax - credits. */
    protected static function recalcTotal(Get $get, Set $set): void
    {
        $total = (float) ($get('subtotal') ?? 0)
            + (float) ($get('tax') ?? 0)
            - (float) ($get('credits_total') ?? 0);

        $set('total', number_format($total, 2, '.', ''));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('issued_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.last_name')
                    ->label('Customer')
                    ->formatStateUsing(fn ($record) => "{$record->customer->first_name} {$record->customer->last_name}")
                    ->description(fn ($record) => $record->customer->company_name ?: null)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
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
            ->defaultPaginationPageOption(50)
            ->actions([
                Actions\Action::make('view_public')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Invoice $record) => $record->share_token ? $record->getPublicUrl() : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Invoice $record) => (bool) $record->share_token),
                Actions\Action::make('send')
                    ->label('Send')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->label('Recipient Email')
                            ->email()
                            ->required()
                            ->default(fn (Invoice $record) => $record->customer?->emailFor('billing')),
                        Forms\Components\Textarea::make('message')
                            ->label('Notes (optional)')
                            ->rows(3),
                    ])
                    ->action(function (Invoice $record, array $data) {
                        Mail::to($data['email'])->send(
                            new ShareInvoiceMail($record, $data['message'] ?? ''),
                        );

                        if (in_array($record->status, ['draft'])) {
                            $record->update([
                                'status' => 'sent',
                                'sent_at' => now(),
                                'issued_at' => $record->issued_at ?? now(),
                            ]);
                        }

                        Notification::make()
                            ->title('Invoice sent')
                            ->body("Sent to {$data['email']}")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Invoice $record) => in_array($record->status, ['draft', 'sent', 'overdue'])),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
