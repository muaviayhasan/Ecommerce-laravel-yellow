<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Quotations (§10). A quote is a draft sale — it never touches inventory.
 * Converting an accepted quote opens the real sale through SalesService, which
 * is where stock moves and revenue/COGS post.
 */
class QuotationService
{
    public function __construct(private SalesService $sales) {}

    /**
     * Turn an accepted quotation into a credit order: copy the quoted lines and
     * their prices, move stock out, post revenue + COGS, and link both records.
     */
    public function convert(Quotation $quotation): Order
    {
        if ($quotation->status === 'converted' || $quotation->converted_order_id) {
            throw new RuntimeException('This quotation has already been converted.');
        }

        return DB::transaction(function () use ($quotation) {
            $quotation->load('items.variant.product', 'customer');

            $lines = $quotation->items
                ->filter(fn ($item) => $item->variant !== null)
                ->map(fn ($item) => [
                    'variant' => $item->variant,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                ])->values()->all();

            if ($lines === []) {
                throw new RuntimeException('This quotation has no sellable items to convert.');
            }

            $channel = $quotation->price_tier === 'wholesale' ? Order::CHANNEL_VENDOR : Order::CHANNEL_WEB;

            $order = $this->sales->place($channel, $quotation->customer, $lines, [
                'payment_method' => 'credit',
                'paid' => 0,
                'tax_total' => (float) $quotation->tax_total,
                'discount_type' => $quotation->discount_type,
                'discount_value' => (float) $quotation->discount_value,
                'quotation_id' => $quotation->id,
            ]);

            $quotation->update(['status' => 'converted', 'converted_order_id' => $order->id]);

            return $order;
        });
    }
}
