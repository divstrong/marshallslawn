<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Mail\ShareInvoiceMail;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Invoice')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('General')
                        ->icon('heroicon-o-information-circle')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('customer_id')
                                ->relationship('customer', 'last_name')
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}" . ($record->company_name ? " ({$record->company_name})" : ''))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('invoice_number')
                                ->label('Invoice #')
                                ->disabled()
                                ->dehydrated()
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
                                ->required(),
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
                            Forms\Components\DatePicker::make('issued_at')
                                ->label('Issued Date'),
                            Forms\Components\DatePicker::make('due_at')
                                ->label('Due Date'),
                            Forms\Components\Textarea::make('notes')
                                ->columnSpanFull(),
                            Section::make('Payment Plan')
                                ->columnSpanFull()
                                ->schema([
                                    Forms\Components\Placeholder::make('plan_monthly')
                                        ->label('Monthly Payment')
                                        ->content(fn (?Invoice $record) => $record?->payment_plan_amount ? '$' . number_format($record->payment_plan_amount, 2) . '/mo' : '-'),
                                    Forms\Components\Placeholder::make('plan_progress')
                                        ->label('Payments Made')
                                        ->content(fn (?Invoice $record) => $record ? "{$record->payment_plan_payments_made} of {$record->payment_plan_installments}" : '-'),
                                    Forms\Components\Placeholder::make('plan_started')
                                        ->label('Started')
                                        ->content(fn (?Invoice $record) => $record?->payment_plan_started_at?->format('M d, Y') ?? '-'),
                                    Forms\Components\Placeholder::make('plan_next')
                                        ->label('Next Payment')
                                        ->content(fn (?Invoice $record) => $record?->payment_plan_started_at
                                            ? $record->payment_plan_started_at->addDays(30 * $record->payment_plan_payments_made)->format('M d, Y')
                                            : '-'),
                                ])
                                ->columns(4)
                                ->visible(fn (?Invoice $record) => $record?->is_payment_plan),
                        ]),
                    Tab::make('Line Items')
                        ->icon('heroicon-o-list-bullet')
                        ->badge(fn (?Invoice $record): ?string => $record?->lineItems()->count() ?: null)
                        ->hidden(fn (?Invoice $record): bool => ! $record?->exists)
                        ->schema([
                            View::make('filament.resources.invoice.line-items-tab'),
                        ]),
                    Tab::make('Credits')
                        ->icon('heroicon-o-receipt-percent')
                        ->badge(fn (?Invoice $record): ?string => $record?->credits()->count() ?: null)
                        ->hidden(fn (?Invoice $record): bool => ! $record?->exists)
                        ->schema([
                            View::make('filament.resources.invoice.credits-tab'),
                        ]),
                    Tab::make('Payments')
                        ->icon('heroicon-o-banknotes')
                        ->badge(fn (?Invoice $record): ?string => $record?->payments()->count() ?: null)
                        ->hidden(fn (?Invoice $record): bool => ! $record?->exists)
                        ->schema([
                            View::make('filament.resources.invoice.payments-tab'),
                        ]),
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
