<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShareInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $personalMessage = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice {$this->invoice->invoice_number} — Marshall's Lawn & Landscape",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.share-invoice',
            with: [
                'invoice' => $this->invoice,
                'personalMessage' => $this->personalMessage,
                'publicUrl' => $this->invoice->getPublicUrl(),
            ],
        );
    }
}
