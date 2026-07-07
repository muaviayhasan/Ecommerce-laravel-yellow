<?php

namespace App\Jobs;

use App\Mail\AbandonedCartMail;
use App\Models\AbandonedCart;
use App\Models\Coupon;
use App\Models\NewsletterSubscriber;
use App\Support\Mail\Notifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sweeps open abandoned carts and emails the ones whose quiet period has elapsed,
 * up to a configurable number of reminders. Idempotent and self-throttling: each
 * cart's reminders_sent / last_reminded_at gate whether it's due, so re-running
 * the sweep never double-sends. Scheduled from routes/console.php.
 *
 * All timing/limits come from the "emails" settings group so the shop owner tunes
 * the cadence without a deploy; the "emails.abandoned_cart" toggle is the master
 * switch (off by default — recovery mail is opt-in).
 */
class SendAbandonedCartRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 0;

    /** Never nag a cart older than this — avoids blasting a backlog when first enabled. */
    private const MAX_AGE_DAYS = 30;

    public function __construct()
    {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        if (! Notifier::enabled('abandoned_cart') || ! (bool) setting('emails', 'abandoned_cart', false)) {
            return;
        }

        $max = max(1, (int) setting('emails', 'abandoned_cart_max_reminders', 2));
        $firstDelay = max(0, (int) setting('emails', 'abandoned_cart_first_delay_hours', 1));
        $followupDelay = max(1, (int) setting('emails', 'abandoned_cart_followup_delay_hours', 20));

        $coupon = ($couponId = (int) setting('emails', 'abandoned_cart_coupon_id', 0))
            ? Coupon::where('is_active', true)->find($couponId)
            : null;

        $now = now();

        AbandonedCart::query()
            ->open()
            ->where('reminders_sent', '<', $max)
            ->where('updated_at', '>=', $now->copy()->subDays(self::MAX_AGE_DAYS))
            ->where(function ($q) use ($now, $firstDelay, $followupDelay) {
                // First reminder: cart has sat idle for the initial delay.
                $q->where(function ($q) use ($now, $firstDelay) {
                    $q->where('reminders_sent', 0)
                        ->where('updated_at', '<=', $now->copy()->subHours($firstDelay));
                })
                // Follow-ups: spaced from the previous reminder.
                    ->orWhere(function ($q) use ($now, $followupDelay) {
                        $q->where('reminders_sent', '>=', 1)
                            ->whereNotNull('last_reminded_at')
                            ->where('last_reminded_at', '<=', $now->copy()->subHours($followupDelay));
                    });
            })
            ->orderBy('id')
            ->chunkById(100, function ($carts) use ($coupon, $max) {
                foreach ($carts as $cart) {
                    $this->remind($cart, $coupon, $max);
                }
            });
    }

    private function remind(AbandonedCart $cart, ?Coupon $coupon, int $max): void
    {
        // One opt-out store shared with campaigns: guarantees an unsubscribe link
        // and lets a shopper who unsubscribed suppress future reminders.
        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => $cart->email],
            ['name' => $cart->name, 'source' => 'abandoned_cart'],
        );

        if ($subscriber->unsubscribed_at) {
            // Take it out of the sweep for good without pretending it converted.
            $cart->update(['reminders_sent' => $max]);

            return;
        }

        $sent = Notifier::send('abandoned_cart', $cart->email, new AbandonedCartMail(
            $cart,
            route('cart.recover', $cart->token),
            route('newsletter.unsubscribe', $subscriber->token),
            $coupon,
        ));

        if ($sent) {
            $cart->forceFill([
                'reminders_sent' => $cart->reminders_sent + 1,
                'last_reminded_at' => now(),
            ])->save();
        }
    }
}
