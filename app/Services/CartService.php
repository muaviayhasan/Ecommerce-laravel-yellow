<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Storefront;
use Illuminate\Support\Collection;

/**
 * Session-backed shopping cart for the storefront. Stores a simple
 * {variant_id => qty} map; line items are resolved against the live catalog on
 * read so prices/availability are always current and stale variants drop out.
 */
class CartService
{
    private const KEY = 'cart';

    public function add(int $variantId, int $qty = 1): void
    {
        $cart = $this->raw();
        $cart[$variantId] = ($cart[$variantId] ?? 0) + max(1, $qty);
        $this->save($cart);
    }

    /** Current quantity of one variant in the cart (0 when absent). */
    public function quantityOf(int $variantId): int
    {
        return (int) ($this->raw()[$variantId] ?? 0);
    }

    public function update(int $variantId, int $qty): void
    {
        $cart = $this->raw();
        if ($qty <= 0) {
            unset($cart[$variantId]);
        } else {
            $cart[$variantId] = $qty;
        }
        $this->save($cart);
    }

    public function remove(int $variantId): void
    {
        $cart = $this->raw();
        unset($cart[$variantId]);
        $this->save($cart);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    public function count(): int
    {
        return (int) array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    /**
     * Enriched, in-stock-or-not line items for the current cart (sellable only).
     *
     * @return Collection<int, object>
     */
    public function items(): Collection
    {
        $cart = $this->raw();
        if ($cart === []) {
            return collect();
        }

        $variants = ProductVariant::query()
            ->whereIn('id', array_keys($cart))
            ->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_active', true)->where('is_sellable', true))
            ->with(['product.media', 'image'])
            ->get()
            ->keyBy('id');

        return collect($cart)
            ->map(function (int $qty, int $id) use ($variants) {
                $variant = $variants->get($id);
                if (! $variant) {
                    return null;
                }
                $price = (float) $variant->retail_price;

                return (object) [
                    'variant_id' => $variant->id,
                    'name' => $variant->product?->name ?? 'Item',
                    'slug' => $variant->product?->slug,
                    'sku' => $variant->sku,
                    'image' => $variant->image?->thumbUrl(200) ?? $variant->product?->media->first()?->thumbUrl(200) ?? Storefront::placeholder(),
                    'price' => $price,
                    'qty' => $qty,
                    'line_total' => round($price * $qty, 2),
                    'url' => $variant->product ? route('product.show', $variant->product->slug) : route('shop'),
                    'stock' => (float) $variant->stock_quantity,
                ];
            })
            ->filter()
            ->values();
    }

    public function subtotal(): float
    {
        return round((float) $this->items()->sum('line_total'), 2);
    }

    /**
     * Re-add a past order's items to the cart (a "reorder"). Only items whose
     * variant is still active and sellable are added; returns how many were.
     */
    public function addFromOrder(Order $order): int
    {
        $order->loadMissing('items');

        $sellable = ProductVariant::query()
            ->whereIn('id', $order->items->pluck('product_variant_id')->filter())
            ->where('is_active', true)
            ->whereHas('product', fn ($p) => $p->where('is_active', true)->where('is_sellable', true))
            ->pluck('id')->flip();

        $added = 0;
        foreach ($order->items as $item) {
            if ($item->product_variant_id && $sellable->has($item->product_variant_id)) {
                $this->add((int) $item->product_variant_id, max(1, (int) round((float) $item->quantity)));
                $added++;
            }
        }

        return $added;
    }

    /**
     * @return array<int, int>
     */
    private function raw(): array
    {
        return (array) session(self::KEY, []);
    }

    /**
     * @param  array<int, int>  $cart
     */
    private function save(array $cart): void
    {
        session()->put(self::KEY, $cart);
    }
}
