<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Storefront;
use Illuminate\Support\Collection;

/**
 * Session-backed shopping cart for the storefront. Stores a simple
 * {variant_id => qty} map for standalone items, plus a parallel deals map for
 * linked deal groups (locked quantities + their discount). Line items are
 * resolved against the live catalog on read so prices/availability stay current.
 */
class CartService
{
    private const KEY = 'cart';

    private const DEALS_KEY = 'cart_deals';

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
        session()->forget([self::KEY, self::DEALS_KEY]);
    }

    public function count(): int
    {
        $dealQty = collect($this->rawDeals())->sum(fn ($d) => array_sum($d['items'] ?? []));

        return (int) (array_sum($this->raw()) + $dealQty);
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [] && $this->rawDeals() === [];
    }

    // --- Deals (linked groups with locked quantities + their discount) ----------

    /** How many of a variant the whole cart already holds (standalone + all deals). */
    public function heldQuantityOf(int $variantId): int
    {
        $inDeals = collect($this->rawDeals())->sum(fn ($d) => (float) ($d['items'][$variantId] ?? 0));

        return (int) round($this->quantityOf($variantId) + $inDeals);
    }

    public function hasDeal(int $dealId): bool
    {
        return isset($this->rawDeals()[$dealId]);
    }

    /** Snapshot a live deal's items + discount into the cart (quantities locked). */
    public function addDeal(Deal $deal): void
    {
        $deal->loadMissing('items.variant');

        $items = [];
        foreach ($deal->items as $item) {
            if ($item->variant && $item->variant->is_active) {
                $items[$item->product_variant_id] = (float) $item->quantity;
            }
        }
        if ($items === []) {
            return;
        }

        $deals = $this->rawDeals();
        $deals[$deal->id] = [
            'name' => $deal->name,
            'slug' => $deal->slug,
            'discount_type' => $deal->discount_type,
            'discount_value' => (float) $deal->discount_value,
            'items' => $items,
        ];
        $this->saveDeals($deals);
    }

    public function removeDeal(int $dealId): void
    {
        $deals = $this->rawDeals();
        unset($deals[$dealId]);
        $this->saveDeals($deals);
    }

    /**
     * Resolved deal groups: each with its live line items, subtotal, discount and
     * total. A deal whose items have all gone unsellable drops out entirely.
     *
     * @return Collection<int, object>
     */
    public function dealGroups(): Collection
    {
        $deals = $this->rawDeals();
        if ($deals === []) {
            return collect();
        }

        $ids = collect($deals)->flatMap(fn ($d) => array_keys($d['items'] ?? []))->unique()->all();
        $variants = ProductVariant::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_active', true)->where('is_sellable', true))
            ->with(['product.media', 'image'])
            ->get()->keyBy('id');

        return collect($deals)->map(function (array $d, $dealId) use ($variants) {
            $lines = [];
            $subtotal = 0.0;
            foreach ($d['items'] as $variantId => $qty) {
                $variant = $variants->get((int) $variantId);
                if (! $variant) {
                    continue;
                }
                $price = (float) $variant->retail_price;
                $lineTotal = round($price * (float) $qty, 2);
                $subtotal += $lineTotal;
                $lines[] = (object) [
                    'variant_id' => $variant->id,
                    'name' => $variant->product?->name ?? 'Item',
                    'sku' => $variant->sku,
                    'image' => $variant->image?->thumbUrl(200) ?? $variant->product?->media->first()?->thumbUrl(200) ?? Storefront::placeholder(),
                    'price' => $price,
                    'qty' => (float) $qty,
                    'line_total' => $lineTotal,
                    'url' => $variant->product ? route('product.show', $variant->product->slug) : route('shop'),
                    'stock' => (float) $variant->stock_quantity,
                ];
            }
            if ($lines === []) {
                return null;
            }

            $subtotal = round($subtotal, 2);
            $discount = $d['discount_type'] === 'percent'
                ? round($subtotal * min((float) $d['discount_value'], 100) / 100, 2)
                : round(min((float) $d['discount_value'], $subtotal), 2);

            return (object) [
                'deal_id' => (int) $dealId,
                'name' => $d['name'],
                'slug' => $d['slug'],
                'url' => route('deal.show', $d['slug']),
                'items' => collect($lines),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => round($subtotal - $discount, 2),
                'discount_label' => (float) $d['discount_value'] <= 0 ? null
                    : ($d['discount_type'] === 'percent'
                        ? 'Save ' . rtrim(rtrim(number_format((float) $d['discount_value'], 2), '0'), '.') . '%'
                        : 'Save ' . format_money($discount)),
            ];
        })->filter()->values();
    }

    /** Combined deal discount across all deal groups (recomputed on live prices). */
    public function dealDiscount(): float
    {
        return round((float) $this->dealGroups()->sum('discount'), 2);
    }

    /**
     * Flat order-line specs for checkout: standalone items (deal_id null) plus
     * every deal's items (carrying their deal_id).
     *
     * @return list<array{variant_id:int, qty:float, deal_id:?int}>
     */
    public function lineSpecs(): array
    {
        $specs = [];
        foreach ($this->raw() as $variantId => $qty) {
            $specs[] = ['variant_id' => (int) $variantId, 'qty' => (float) $qty, 'deal_id' => null];
        }
        foreach ($this->rawDeals() as $dealId => $deal) {
            foreach (($deal['items'] ?? []) as $variantId => $qty) {
                $specs[] = ['variant_id' => (int) $variantId, 'qty' => (float) $qty, 'deal_id' => (int) $dealId];
            }
        }

        return $specs;
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

    /** Standalone items plus all deal items (before the deal discount). */
    public function subtotal(): float
    {
        return round((float) $this->items()->sum('line_total') + (float) $this->dealGroups()->sum('subtotal'), 2);
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

    /**
     * @return array<int, array{name:string, slug:string, discount_type:string, discount_value:float, items:array<int, float>}>
     */
    private function rawDeals(): array
    {
        return (array) session(self::DEALS_KEY, []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $deals
     */
    private function saveDeals(array $deals): void
    {
        session()->put(self::DEALS_KEY, $deals);
    }
}
