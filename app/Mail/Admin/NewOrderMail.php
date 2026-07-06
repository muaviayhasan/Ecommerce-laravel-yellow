<?php

namespace App\Mail\Admin;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public ?string $url = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New order · ' . $this->order->order_number . ' · ' . format_money($this->order->grand_total));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin.new-order');
    }
}
