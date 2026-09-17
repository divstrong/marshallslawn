<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Deliberately sends no SMS. Adding a customer here used to fire the double
 * opt-in request, but that texted a number the office typed in, before the
 * customer had consented to anything — contrary to the A2P campaign, which
 * registers the public /sms-opt-in form as the only way in. Customers opt in
 * there themselves; see docs/a2p-campaign-registration.md.
 */
class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
