<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Deal;

/**
 * Shared by the POS and vendor-sale pickers: live deals matching a search term,
 * with channel-priced items — the cart applies the deal discount on these
 * actual line prices. Host controllers provide variantImage().
 */
trait BuildsDealResults
{
    /**
     * @return list<array<string, mixed>>
     */
    private function dealResults(string $term, bool $wholesale): array
    {
        return Deal::live()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%' . $term . '%'))
            ->with(['image', 'items.variant.product:id,name,is_stock_tracked', 'items.variant.product.media', 'items.variant.image'])
            ->orderBy('sort_order')->orderBy('name')->take(5)->get()
            ->map(fn (Deal $d) => [
                'kind' => 'deal',
                'id' => $d->id,
                'name' => $d->name,
                'image' => $d->image?->url,
                'discount_type' => $d->discount_type,
                'discount_value' => (float) $d->discount_value,
                'items' => $d->items
                    ->filter(fn ($it) => $it->variant !== null && $it->variant->is_active)
                    ->map(fn ($it) => [
                        'id' => $it->variant->id,
                        'name' => $it->variant->product?->name ?? 'Item',
                        'sku' => $it->variant->sku,
                        'price' => $wholesale
                            ? (float) ($it->variant->wholesale_price ?? $it->variant->retail_price)
                            : (float) $it->variant->retail_price,
                        'qty' => (float) $it->quantity,
                        'stock' => (float) $it->variant->stock_quantity,
                        'tracked' => (bool) ($it->variant->product?->is_stock_tracked ?? true),
                        'image' => $this->variantImage($it->variant),
                    ])->values()->all(),
            ])
            ->filter(fn ($d) => count($d['items']) > 0)
            ->values()->all();
    }
}
