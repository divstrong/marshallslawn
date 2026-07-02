<?php

namespace App\Filament\Resources\JobResource\Pages;

use App\Filament\Resources\JobResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJob extends EditRecord
{
    protected static string $resource = JobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Chat with this job's customer, right from the job detail.
            Actions\Action::make('chatCustomer')
                ->label('Chat with customer')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->visible(fn (): bool => $this->record->customer_id !== null)
                ->modalHeading(fn (): string => 'Chat with ' . ($this->record->customer
                    ? (trim(($this->record->customer->first_name ?? '') . ' ' . ($this->record->customer->last_name ?? '')) ?: ($this->record->customer->company_name ?? 'customer'))
                    : 'customer'))
                ->modalContent(fn () => view('filament.customer-chat-modal', ['customerId' => $this->record->customer_id]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
            Actions\DeleteAction::make(),
        ];
    }
}
