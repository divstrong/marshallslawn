<?php

namespace App\Mail;

use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShareEstimateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Estimate $estimate,
        public string $personalMessage = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Estimate from Marshall's Lawn & Landscape — {$this->estimate->estimate_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.share-estimate',
            with: [
                'estimate' => $this->estimate,
                'personalMessage' => $this->personalMessage,
                'publicUrl' => $this->estimate->getPublicUrl(),
            ],
        );
    }
}
