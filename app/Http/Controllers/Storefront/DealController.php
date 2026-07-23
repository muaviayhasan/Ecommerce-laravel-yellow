<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Support\Storefront;
use Illuminate\View\View;

/** Storefront deal pages — the live-deal index and a single deal's products. */
class DealController extends Controller
{
    /** All active deals, two cards per row. */
    public function index(): View
    {
        return view('storefront.deals', [
            'deals' => Storefront::liveDeals(),
        ]);
    }

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
