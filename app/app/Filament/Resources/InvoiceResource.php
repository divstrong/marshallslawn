<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Mail\ShareInvoiceMail;
use App\Models\Invoice;
use App\Models\InvoiceCredit;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

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
                        ->schema([
                            Forms\Components\Select::make('customer_id')
                                ->relationship('customer', 'last_name')
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}" . ($record->company_name ? " ({$record->company_name})" : ''))
                                ->searchable()
                                ->preload()
                                ->required(),
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
                                ->required(),
                            Forms\Components\TextInput::make('tax')
                                ->numeric()
                                ->prefix('$')
                                ->default(0),
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
                            Forms\Components\DatePicker::make('paid_at')
                                ->label('Paid Date'),
                            Forms\Components\Textarea::make('notes')
                                ->columnSpanFull(),
                            Section::make('Payment Plan')
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
                    Tab::make('Credits')
                        ->icon('heroicon-o-receipt-percent')
                        ->badge(fn (?Invoice $record): ?string => $record?->credits()->count() ?: null)
                        ->hidden(fn (?Invoice $record): bool => ! $record?->exists)
                        ->schema([
                            Forms\Components\Repeater::make('credits')
                                ->relationship()
                                ->schema([
                                    Forms\Components\TextInput::make('code')
                                        ->label('Credit Code')
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('description')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('amount')
                                        ->numeric()
                                        ->prefix('$')
                                        ->required(),
                                    Forms\Components\Placeholder::make('applied_by_name')
                                        ->label('Applied By')
                                        ->content(fn (?InvoiceCredit $record): string => $record?->appliedBy?->name ?? '-'),
                                    Forms\Components\Placeholder::make('created_at_display')
                                        ->label('Applied At')
                                        ->content(fn (?InvoiceCredit $record): string => $record?->created_at?->format('M d, Y g:i A') ?? '-'),
                                ])
                                ->columns(3)
                                ->defaultItems(0)
                                ->addActionLabel('Add Credit')
                                ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                    $data['applied_by'] = auth()->id();
                                    return $data;
                                })
                                ->visible(fn () => auth()->user()?->isAdmin()),
                            Forms\Components\Placeholder::make('credits_readonly')
                                ->label('')
                                ->content('Only administrators can add or manage credits.')
                                ->visible(fn () => ! auth()->user()?->isAdmin()),
                        ]),
                ]),
        ]);
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
                            ->default(fn (Invoice $record) => $record->customer?->email),
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
