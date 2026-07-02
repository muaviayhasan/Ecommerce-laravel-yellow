<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Sales (§12). The single entry point for placing a sale on any channel
 * (web / pos / vendor). One transaction: resolve prices, create the order +
 * items (with cost snapshots), move stock out, record payment, and post the
 * revenue + COGS entries to the ledger.
 */
class SalesService
{
    public function __construct(
        private StockService $stock,
        private LedgerService $ledger,
    ) {}

    /**
     * @param  array<int, array{variant: ProductVariant, quantity: float, unit_price?: float}>  $lines
     * @param  array{payment_method?: string, paid?: float, pay_full?: bool, tax_total?: float, tax_rate?: float, shipping_total?: float, discount_total?: float, discount_type?: string, discount_value?: float, shipping_method?: ?string, courier?: ?string, tracking_number?: ?string, quotation_id?: int}  $opts
     */
    public function place(string $channel, ?Customer $customer, array $lines, array $opts = []): Order
    {
        return DB::transaction(function () use ($channel, $customer, $lines, $opts) {
            $tier = ($channel === Order::CHANNEL_VENDOR || $customer?->price_tier === 'wholesale') ? 'wholesale' : 'retail';

            // 1) Build line items (priced + cost-snapshotted).
            $rows = [];
            $subtotal = 0.0;
            $cogs = 0.0;
            foreach ($lines as $line) {
                $variant = $line['variant'];
                $qty = (float) $line['quantity'];
                // Honour an explicit price (e.g. a converted quotation) over the tier price.
                $unitPrice = isset($line['unit_price']) ? (float) $line['unit_price'] : $this->price($variant, $tier);
                $lineTotal = round($unitPrice * $qty, 2);
                $cost = (float) $variant->cost;

                $subtotal += $lineTotal;
                $cogs += round($cost * $qty, 2);
                $rows[] = [
                    'variant' => $variant,
                    'name_snapshot' => $variant->product?->name ?? 'Item',
                    'sku_snapshot' => $variant->sku,
                    'attributes_snapshot' => $variant->relationLoaded('attributeValues')
                        ? $variant->attributeValues->map(fn ($a) => $a->label ?: $a->value)->values()->all()
                        : [],
                    'unit_price' => $unitPrice,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                    'cost_snapshot' => $cost,
                ];
            }

            $subtotal = round($subtotal, 2);
            $cogs = round($cogs, 2);
            [$discountType, $discountValue, $discount] = $this->resolveDiscount($subtotal, $opts);
            // Tax: an explicit amount, or computed from a rate on the net subtotal.
            $tax = isset($opts['tax_total'])
                ? round((float) $opts['tax_total'], 2)
                : round(($subtotal - $discount) * (float) ($opts['tax_rate'] ?? 0) / 100, 2);
            $shipping = round((float) ($opts['shipping_total'] ?? 0), 2);
            $grand = round($subtotal - $discount + $tax + $shipping, 2);
            // `pay_full` (POS/immediate) settles the whole total; otherwise use the given amount.
            $paid = ! empty($opts['pay_full']) ? $grand : round((float) ($opts['paid'] ?? 0), 2);

            // 2) The order.
            $order = Order::create([
                'order_number' => $this->nextNumber(),
                'channel' => $channel,
                'customer_id' => $customer?->id,
                'quotation_id' => $opts['quotation_id'] ?? null,
                'price_tier' => $tier,
                'status' => $paid >= $grand ? 'paid' : 'pending',
                'payment_method' => $opts['payment_method'] ?? 'cash',
                'payment_status' => $paid <= 0 ? 'unpaid' : ($paid >= $grand ? 'paid' : 'partial'),
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_total' => $discount,
                'tax_total' => $tax,
                'shipping_total' => $shipping,
                'shipping_method' => $opts['shipping_method'] ?? null,
                'courier' => $opts['courier'] ?? null,
                'tracking_number' => $opts['tracking_number'] ?? null,
                'grand_total' => $grand,
                'paid_total' => $paid,
                'currency' => setting('general', 'currency', 'PKR'),
                'placed_at' => now(),
                'created_by' => auth()->id(),
            ]);

            // 3) Items + stock out (cost snapshot at sale time).
            foreach ($rows as $row) {
                $variant = $row['variant'];
                unset($row['variant']);
                $order->items()->create($row);
                $this->stock->move($variant, StockMovement::TYPE_SALE_OUT, -(float) $row['quantity'], (float) $row['cost_snapshot'], $order, "Sale {$order->order_number}");
            }

            // 4) Payment (skip pure credit/unpaid).
            if ($paid > 0) {
                $order->payments()->create([
                    'gateway' => $this->gateway($order->payment_method),
                    'amount' => $paid,
                    'status' => 'succeeded',
                    'received_by' => auth()->id(),
                ]);
            }

            // 5) Ledger — revenue + tax + shipping, and COGS vs inventory (one balanced post).
            $lines = [];
            if ($paid > 0) {
                $lines[] = ['account' => 'cash', 'debit' => $paid];
            }
            if ($grand - $paid > 0) {
                $lines[] = ['account' => 'accounts_receivable', 'debit' => round($grand - $paid, 2)];
            }
            $lines[] = ['account' => 'sales_revenue', 'credit' => round($subtotal - $discount, 2)];
            if ($tax > 0) {
                $lines[] = ['account' => 'tax_payable', 'credit' => $tax];
            }
            if ($shipping > 0) {
                $lines[] = ['account' => 'shipping_income', 'credit' => $shipping];
            }
            if ($cogs > 0) {
                $lines[] = ['account' => 'cogs', 'debit' => $cogs];
                $lines[] = ['account' => 'inventory', 'credit' => $cogs];
            }
            $this->ledger->post($lines, $order, "Sale {$order->order_number}");

            return $order;
        });
    }

    /**
     * Resolve a manual discount into [type, value, amount]. Accepts either an explicit
     * `discount_type` + `discount_value` (fixed Rs or percent of subtotal) or a plain
     * `discount_total` (legacy / coupon path). Always capped: percent ≤ 100, amount ≤ subtotal.
     *
     * @param  array<string, mixed>  $opts
     * @return array{0: string, 1: float, 2: float}
     */
    private function resolveDiscount(float $subtotal, array $opts): array
    {
        if (isset($opts['discount_type']) && in_array($opts['discount_type'], ['fixed', 'percent'], true)) {
            $type = $opts['discount_type'];
            $value = round((float) ($opts['discount_value'] ?? 0), 2);
            $amount = $type === 'percent'
                ? round($subtotal * min($value, 100) / 100, 2)
                : min($value, $subtotal);

            return [$type, $value, round($amount, 2)];
        }

        $value = round((float) ($opts['discount_total'] ?? 0), 2);

        return ['fixed', $value, min($value, $subtotal)];
    }

    private function price(ProductVariant $variant, string $tier): float
    {
        if ($tier === 'wholesale' && $variant->wholesale_price !== null) {
            return (float) $variant->wholesale_price;
        }

        return (float) $variant->retail_price;
    }

    private function gateway(string $method): string
    {
        return match ($method) {
            'qr' => 'manual_qr',
            'credit' => 'manual_qr',
            default => $method, // cod | cash | card | bank
        };
    }

    private function nextNumber(): string
    {
        $prefix = (string) setting('numbering', 'order_prefix', 'ORD-');

        return $prefix . str_pad((string) ((Order::max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT);
    }
}
