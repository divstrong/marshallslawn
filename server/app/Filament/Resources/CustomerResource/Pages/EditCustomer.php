<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\EstimateResource;
use App\Filament\Resources\JobResource;
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
                ->slideOver()
                ->modalWidth('lg')
                ->modalHeading(fn (): string => 'Chat with ' . (trim(($this->record->first_name ?? '') . ' ' . ($this->record->last_name ?? '')) ?: ($this->record->company_name ?? 'customer')))
                ->modalContent(fn () => view('filament.customer-chat-modal', ['customerId' => $this->record->id]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
            // ?customer_id= opens each create screen already pointed at this customer.
            Actions\Action::make('newEstimate')
                ->label('New Estimate')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn (): string => EstimateResource::getUrl('create', ['customer_id' => $this->record->id])),
            Actions\Action::make('newJob')
                ->label('New Job')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->url(fn (): string => JobResource::getUrl('create', ['customer_id' => $this->record->id])),
            Actions\DeleteAction::make(),
        ];
    }
}
