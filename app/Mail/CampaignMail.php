<?php

namespace App\Mail;

use App\Models\Coupon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A single marketing-campaign email. The body is admin-authored HTML with merge
 * tags already substituted for this recipient. Always carries an unsubscribe link.
 */
class CampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $bodyHtml;

    public string $unsubscribeUrl;

    public ?Coupon $coupon;

    public ?string $preheader;

    public function __construct(string $subject, string $bodyHtml, string $unsubscribeUrl, ?Coupon $coupon = null, ?string $preheader = null)
    {
        $this->subject = $subject; // Mailable's own subject property → drives the envelope
        $this->bodyHtml = $bodyHtml;
        $this->unsubscribeUrl = $unsubscribeUrl;
        $this->coupon = $coupon;
        $this->preheader = $preheader;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.campaign', with: ['subject' => $this->subject]);
    }
}
