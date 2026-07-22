<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The one-time code an employee needs to sign in to the native app.
 */
class LoginCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $code,
        public int $minutesValid,
    ) {}

    public function envelope(): Envelope
    {
        // The code leads the subject on purpose: iOS Mail only offers its
        // one-tap verification-code autofill when it can spot the code, and the
        // subject is the first place it looks. It also means a crew member can
        // read the code off the notification without opening the mail.
        return new Envelope(
            subject: "{$this->code} is your Marshall's Lawn sign-in code",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.login-code',
            with: [
                'code' => $this->code,
                'minutesValid' => $this->minutesValid,
            ],
        );
    }
}
