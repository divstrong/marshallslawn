<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\Page;

/**
 * Creating an invoice runs through the InvoiceBuilder Livewire component rather
 * than the resource form: the total has to be built from priced service lines
 * with a live preview, which the standard form components can't express.
 */
class CreateInvoice extends Page
{
    protected static string $resource = InvoiceResource::class;

    protected string $view = 'filament.resources.invoice.create';

    public function getTitle(): string
    {
        return 'New Invoice';
    }
}
