<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * One-off email for the "Send test email" button on Settings → Mail. Deliberately
 * NOT queued so transport errors surface immediately to the admin.
 */
class TestMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Test email · ' . setting('general', 'app_name', config('app.name')));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.test');
    }
}
