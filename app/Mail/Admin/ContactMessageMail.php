<?php

namespace App\Mail\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Staff alert for a storefront "Contact us" submission. Reply-to is set to the
 * sender so the team can answer straight from their inbox.
 *
 * @property array{name:string,email:string,phone?:?string,subject:string,message:string} $data
 */
class ContactMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New contact message · ' . ($this->data['subject'] ?? 'Website'),
            replyTo: [new Address((string) $this->data['email'], (string) ($this->data['name'] ?? ''))],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin.contact-message');
    }
}
