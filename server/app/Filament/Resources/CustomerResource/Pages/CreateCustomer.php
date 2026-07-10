<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    /**
     * Fire the one-time SMS double opt-in request for a newly added customer.
     * This is a no-op unless double opt-in is enabled and the customer has a
     * phone, so it's safe when the channel is off. Deliberately hooked here (an
     * office action) rather than a model observer, to keep the bulk importer
     * from texting thousands of records.
     */
    protected function afterCreate(): void
    {
        $this->record->sendSmsOptInRequest();
    }
}
