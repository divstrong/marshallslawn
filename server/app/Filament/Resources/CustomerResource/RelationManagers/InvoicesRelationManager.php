<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Mail\ShareInvoiceMail;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Illuminate\Support\Facades\Mail;

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
                // Same send-and-remind pair as the Invoices index, so an unpaid
                // balance can be chased without leaving the customer record. The
                // email carries the "View Invoice" CTA linking to the public page.
                Actions\Action::make('send')
                    ->label('Send')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->schema(self::emailForm('Notes (optional)'))
                    ->action(fn (Invoice $record, array $data) => self::sendInvoiceEmail($record, $data))
                    ->visible(fn (Invoice $record): bool => in_array($record->status, ['draft', 'sent', 'overdue'], true)),
                Actions\Action::make('remind')
                    ->label('Remind')
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->schema(self::emailForm('Reminder message', draftReminder: true))
                    ->action(fn (Invoice $record, array $data) => self::sendInvoiceEmail($record, $data, 'Reminder sent'))
                    // Only worth chasing once it's out the door and still unpaid.
                    ->visible(fn (Invoice $record): bool => in_array($record->status, ['sent', 'overdue', 'payment_plan'], true)),
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

    /**
     * Recipient + message inputs shared by the Send and Remind actions. The
     * recipient defaults to the customer's billing address, falling back to
     * their primary email.
     *
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    private static function emailForm(string $messageLabel, bool $draftReminder = false): array
    {
        return [
            Forms\Components\TextInput::make('email')
                ->label('Recipient Email')
                ->email()
                ->required()
                ->default(fn (Invoice $record) => $record->customer?->emailFor('billing')),
            Forms\Components\Textarea::make('message')
                ->label($messageLabel)
                ->rows(3)
                ->default(fn (Invoice $record): ?string => $draftReminder
                    ? self::defaultReminderMessage($record)
                    : null),
        ];
    }

    /** @param array<string, mixed> $data */
    private static function sendInvoiceEmail(Invoice $record, array $data, string $title = 'Invoice sent'): void
    {
        Mail::to($data['email'])->send(
            new ShareInvoiceMail($record, $data['message'] ?? ''),
        );

        // Sending a draft is what puts it in play — mirror the Invoices index.
        if ($record->status === 'draft') {
            $record->update([
                'status' => 'sent',
                'sent_at' => now(),
                'issued_at' => $record->issued_at ?? now(),
            ]);
        }

        Notification::make()
            ->title($title)
            ->body("Sent to {$data['email']}")
            ->success()
            ->send();
    }

    private static function defaultReminderMessage(Invoice $record): string
    {
        $name = trim($record->customer?->first_name ?? '') ?: 'there';
        $due = $record->due_at ? ' It was due on ' . $record->due_at->format('M j, Y') . '.' : '';

        return "Hi {$name},\n\nThis is a friendly reminder that invoice "
            . ($record->invoice_number ?? '#' . $record->id)
            . ' for $' . number_format((float) $record->total, 2) . " is still outstanding.{$due}"
            . "\n\nYou can view and pay it using the button below. Thank you!";
    }
}
