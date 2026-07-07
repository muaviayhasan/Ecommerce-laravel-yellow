<?php

namespace App\Jobs;

use App\Mail\CampaignMail;
use App\Models\CampaignRecipient;
use App\Models\Customer;
use App\Models\EmailCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Fans a marketing campaign out to its audience: builds the recipient list once
 * (idempotent), then queues one CampaignMail per recipient with merge tags
 * substituted and a working unsubscribe link. Unsubscribed addresses are skipped.
 */
class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 0;

    public function __construct(public int $campaignId)
    {
        $this->onQueue('low'); // the fan-out itself is bulk work
    }

    public function handle(): void
    {
        $campaign = EmailCampaign::with('coupon')->find($this->campaignId);

        if (! $campaign || $campaign->status === 'sent') {
            return;
        }

        $campaign->update(['status' => 'sending']);

        $this->buildRecipients($campaign);
        $campaign->update(['recipients_count' => $campaign->recipients()->count()]);

        $campaign->recipients()->where('status', 'pending')->chunkById(200, function ($rows) use ($campaign) {
            foreach ($rows as $recipient) {
                // One opt-out store: ensure a subscriber row so unsubscribe + suppression work.
                $subscriber = NewsletterSubscriber::firstOrCreate(
                    ['email' => $recipient->email],
                    ['name' => $recipient->name, 'source' => 'campaign'],
                );

                if ($subscriber->unsubscribed_at) {
                    $recipient->update(['status' => 'suppressed']);

                    continue;
                }

                $unsubscribeUrl = route('newsletter.unsubscribe', $subscriber->token);

                Mail::to($recipient->email)->queue(new CampaignMail(
                    $campaign->subject,
                    $this->render($campaign, $recipient->name, $unsubscribeUrl),
                    $unsubscribeUrl,
                    $campaign->coupon,
                    $campaign->preheader,
                ));

                $recipient->update(['status' => 'sent', 'sent_at' => now()]);
            }
        });

        $campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_count' => $campaign->recipients()->where('status', 'sent')->count(),
        ]);
    }

    /** Insert the audience as pending recipient rows (deduped by the unique index). */
    private function buildRecipients(EmailCampaign $campaign): void
    {
        $this->audienceQuery($campaign)->chunk(500, function ($rows) use ($campaign) {
            $now = now();
            $data = [];

            foreach ($rows as $row) {
                if (blank($row->email)) {
                    continue;
                }
                $data[] = [
                    'email_campaign_id' => $campaign->id,
                    'email' => $row->email,
                    'name' => $row->name,
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($data) {
                CampaignRecipient::upsert($data, ['email_campaign_id', 'email'], ['name']);
            }
        });
    }

    /** A query of {email, name} rows for the chosen audience. */
    private function audienceQuery(EmailCampaign $campaign): Builder
    {
        if ($campaign->audience === 'subscribers') {
            return NewsletterSubscriber::query()->active()->whereNotNull('email')->select('email', 'name');
        }

        $query = Customer::query()->where('is_active', true)->whereNotNull('email')->select('email', 'name');

        return match ($campaign->audience) {
            'retail' => $query->where('type', 'retail'),
            'wholesale' => $query->where('type', 'wholesale'),
            default => $query, // all_customers
        };
    }

    /** Substitute merge tags in the admin-authored body (values are escaped). */
    private function render(EmailCampaign $campaign, ?string $name, string $unsubscribeUrl): string
    {
        $coupon = $campaign->coupon?->code ?? '';
        $name = e($name ?: 'there');

        return strtr($campaign->body, [
            '{{ name }}' => $name,
            '{{name}}' => $name,
            '{{ coupon_code }}' => e($coupon),
            '{{coupon_code}}' => e($coupon),
            '{{ unsubscribe_url }}' => e($unsubscribeUrl),
            '{{unsubscribe_url}}' => e($unsubscribeUrl),
        ]);
    }
}
