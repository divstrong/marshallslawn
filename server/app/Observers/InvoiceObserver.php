<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\CustomerSmsNotifier;

class InvoiceObserver
{
    public function __construct(private readonly CustomerSmsNotifier $sms)
    {
    }

    public function created(Invoice $invoice): void
    {
        // An invoice created directly in a "sent" state is issued on creation.
        if ($invoice->status === 'sent') {
            $this->sms->invoiceIssued($invoice);
        }
    }

    public function updated(Invoice $invoice): void
    {
        // Text the customer the moment an invoice is issued (draft → sent).
        if ($invoice->wasChanged('status') && $invoice->status === 'sent') {
            $this->sms->invoiceIssued($invoice);
        }
    }
}
