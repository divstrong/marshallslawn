<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('chat')
                ->label('Chat')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->modalHeading(fn (): string => 'Chat with ' . (trim(($this->record->first_name ?? '') . ' ' . ($this->record->last_name ?? '')) ?: ($this->record->company_name ?? 'customer')))
                ->modalContent(fn () => view('filament.customer-chat-modal', ['customerId' => $this->record->id]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
            Actions\DeleteAction::make(),
        ];
    }
}
