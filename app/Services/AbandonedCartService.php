<?php

namespace App\Services;

use App\Models\AbandonedCart;
use Illuminate\Support\Str;

/**
 * Persists high-intent carts (a shopper who reached checkout with a known email)
 * so the recovery scheduler can nudge them back. The storefront cart is
 * session-only, so this snapshot is the only durable record to remind against.
 *
 * Capture is gated by the "emails.abandoned_cart" toggle at the call sites, so
 * nothing is stored while the feature is off.
 */
class AbandonedCartService
{
    /**
     * Snapshot (or refresh) the open cart for an email. Re-capturing an active
     * cart preserves its reminder progress; re-capturing after a purchase starts
     * a fresh cycle. Empty carts are ignored.
     *
     * @param  array<int, array<string, mixed>>  $items  [{variant_id, name, sku, qty, price, image, url}]
     */
    public function capture(string $email, array $items, float $subtotal, ?string $name = null, ?int $userId = null): ?AbandonedCart
    {
        $email = $this->normalize($email);

        if ($email === '' || $items === []) {
            return null;
        }

        $cart = AbandonedCart::firstOrNew(['email' => $email]);
        $startNewCycle = ! $cart->exists || $cart->recovered_at !== null;

        $cart->fill([
            'user_id' => $userId ?: $cart->user_id,
            'name' => $name ?: $cart->name,
            'items' => array_values($items),
            'subtotal' => round($subtotal, 2),
            'item_count' => (int) round(array_sum(array_column($items, 'qty'))),
            'recovered_at' => null,
        ]);

        $cart->token ??= Str::random(48);

        if ($startNewCycle) {
            $cart->reminders_sent = 0;
            $cart->last_reminded_at = null;
        }

        $cart->save();

        return $cart;
    }

    /**
     * Close any open carts for this email (and optionally user) — called when an
     * order is placed so the shopper stops receiving reminders.
     */
    public function markRecovered(string $email, ?int $userId = null): void
    {
        $email = $this->normalize($email);

        AbandonedCart::query()
            ->open()
            ->where(function ($q) use ($email, $userId) {
                $q->where('email', $email);
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            })
            ->update(['recovered_at' => now()]);
    }

    private function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
