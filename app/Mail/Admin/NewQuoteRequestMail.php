<?php

namespace App\Mail\Admin;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewQuoteRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Quotation $quotation, public ?string $url = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New quote request · ' . $this->quotation->quotation_number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin.new-quote-request');
    }
}
