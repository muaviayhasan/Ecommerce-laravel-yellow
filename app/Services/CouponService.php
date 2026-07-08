<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;

/**
 * Validates and prices discount coupons for the storefront checkout. Central so
 * the same rules (active window + global cap via {@see Coupon::isUsable()}, plus
 * minimum-spend and per-customer limits) apply wherever a code is redeemed.
 */
class CouponService
{
    /**
     * Find and fully validate a coupon for the given subtotal and shopper.
     *
     * @return array{0: ?Coupon, 1: ?string}  [coupon, null] when usable, else [null, reason]
     */
    public function evaluate(string $code, float $subtotal, ?int $userId = null, ?string $email = null): array
    {
        $code = trim($code);
        if ($code === '') {
            return [null, 'Enter a coupon code.'];
        }

        $coupon = Coupon::with('customers:id,email,user_id')
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->first();

        if (! $coupon || ! $coupon->isUsable()) {
            return [null, 'This coupon code is invalid or has expired.'];
        }

        if ($coupon->min_subtotal !== null && $subtotal < (float) $coupon->min_subtotal) {
            $short = (float) $coupon->min_subtotal - $subtotal;

            return [null, 'Spend ' . format_money($short) . ' more to use this coupon (minimum order ' . format_money((float) $coupon->min_subtotal) . ').'];
        }

        if ($coupon->usage_limit_per_customer !== null) {
            $used = $this->timesUsedBy($coupon, $userId, $email);
            if ($used !== null && $used >= (int) $coupon->usage_limit_per_customer) {
                return [null, 'You have already used this coupon the maximum number of times.'];
            }
        }

        // Private coupon — restricted to specific customers (matched by email or account).
        if ($coupon->customers->isNotEmpty()) {
            if (! $userId && ! $email) {
                return [null, 'Enter your email above, then apply this coupon.'];
            }
            $eligible = $coupon->customers->contains(fn ($c) => ($email && mb_strtolower((string) $c->email) === mb_strtolower($email))
                || ($userId && (int) $c->user_id === (int) $userId));
            if (! $eligible) {
                return [null, 'This coupon is not available for your account.'];
            }
        }

        // "Welcome" coupon — valid only on the shopper's very first order.
        if ($coupon->first_order_only) {
            if (! $userId && ! $email) {
                return [null, 'Enter your email above, then apply this coupon.'];
            }
            if ($this->hasPriorOrders($userId, $email)) {
                return [null, 'This coupon is valid on your first order only.'];
            }
        }

        return [$coupon, null];
    }

    /** Whether this shopper (by account or by email) already has any orders. */
    private function hasPriorOrders(?int $userId, ?string $email): bool
    {
        $customerIds = $email ? Customer::where('email', $email)->pluck('id') : collect();

        if (! $userId && $customerIds->isEmpty()) {
            return false;
        }

        return Order::where(function ($q) use ($userId, $customerIds) {
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
            if ($customerIds->isNotEmpty()) {
                $q->orWhereIn('customer_id', $customerIds);
            }
        })->exists();
    }

    /** The money value of a coupon against a subtotal (never more than the subtotal). */
    public function discountFor(Coupon $coupon, float $subtotal): float
    {
        $amount = $coupon->type === Coupon::TYPE_PERCENT
            ? $subtotal * min((float) $coupon->value, 100) / 100
            : min((float) $coupon->value, $subtotal);

        return round($amount, 2);
    }

    /**
     * How many past orders this shopper (by account or by email) placed with this
     * coupon. Null when there is no identity to attribute usage to yet (a guest
     * who hasn't entered an email) — the check is deferred to order placement.
     */
    private function timesUsedBy(Coupon $coupon, ?int $userId, ?string $email): ?int
    {
        $customerIds = $email ? Customer::where('email', $email)->pluck('id') : collect();

        if (! $userId && $customerIds->isEmpty()) {
            return null;
        }

        return Order::where('coupon_id', $coupon->id)
            ->where(function ($q) use ($userId, $customerIds) {
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
                if ($customerIds->isNotEmpty()) {
                    $q->orWhereIn('customer_id', $customerIds);
                }
            })
            ->count();
    }
}
