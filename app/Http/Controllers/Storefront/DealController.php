<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Support\Storefront;
use Illuminate\View\View;

/** Storefront deal page — a live deal's products with its combined price. */
class DealController extends Controller
{
    public function show(string $slug): View
    {
        $deal = Deal::live()->where('slug', $slug)
            ->with(['image', 'items.variant.product.category', 'items.variant.product.media', 'items.variant.image', 'items.variant.attributeValues'])
            ->firstOrFail();

        $variants = $deal->items
            ->map(fn ($item) => $item->variant)
            ->filter(fn ($v) => $v !== null && $v->is_active);

        return view('storefront.deal', [
            'deal' => $deal,
            'card' => Storefront::dealCard($deal),
            'items' => Storefront::variantCards($variants),
        ]);
    }
}
