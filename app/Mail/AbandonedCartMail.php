<?php

namespace App\Mail;

use App\Models\AbandonedCart;
use App\Models\Coupon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A "you left something behind" reminder for an abandoned cart. Carries a link
 * that rehydrates the cart and drops the shopper back at checkout, an optional
 * incentive coupon, and an unsubscribe link. Rides the low-priority queue so a
 * batch of reminders never delays transactional mail.
 */
class AbandonedCartMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(
        public AbandonedCart $cart,
        public string $recoverUrl,
        public string $unsubscribeUrl,
        public ?Coupon $coupon = null,
    ) {
        $this->onQueue('low');
    }

    public function envelope(): Envelope
    {
        $store = (string) setting('general', 'app_name', config('app.name', 'our store'));

        return new Envelope(subject: 'You left something in your cart · ' . $store);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.abandoned-cart');
    }
}
