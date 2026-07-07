<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $url, public int $expires = 60) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify your email · ' . setting('general', 'app_name', config('app.name')));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verify-email');
    }
}
