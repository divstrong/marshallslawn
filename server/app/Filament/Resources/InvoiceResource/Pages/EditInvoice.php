<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Mail\ShareInvoiceMail;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_public')
                ->label('View Public')
                ->icon('heroicon-o-eye')
                ->url(fn () => $this->record->share_token ? $this->record->getPublicUrl() : null)
                ->openUrlInNewTab()
                ->visible(fn () => (bool) $this->record->share_token),
            Actions\Action::make('send')
                ->label('Send Invoice')
                ->icon('heroicon-o-envelope')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('email')
                        ->label('Recipient Email')
                        ->email()
                        ->required()
                        ->default(fn () => $this->record->customer?->email),
                    Forms\Components\Textarea::make('message')
                        ->label('Notes (optional)')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    Mail::to($data['email'])->send(
                        new ShareInvoiceMail($this->record, $data['message'] ?? ''),
                    );

                    if ($this->record->status === 'draft') {
                        $this->record->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                            'issued_at' => $this->record->issued_at ?? now(),
                        ]);
                    }

                    Notification::make()
                        ->title('Invoice sent')
                        ->body("Sent to {$data['email']}")
                        ->success()
                        ->send();
                })
                ->visible(fn () => in_array($this->record->status, ['draft', 'sent', 'overdue'])),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['total'] = ($data['subtotal'] ?? 0) + ($data['tax'] ?? 0) - ($this->record->credits_total ?? 0);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->recalculateTotal();
    }
}
