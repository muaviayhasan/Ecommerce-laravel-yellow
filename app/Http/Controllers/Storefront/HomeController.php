<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\ProvidesSampleProducts;
use Illuminate\View\View;

class HomeController extends Controller
{
    use ProvidesSampleProducts;

    /**
     * Storefront home page.
     *
     * NOTE: product data here is PLACEHOLDER sample data so the theme renders
     * fully before the catalog module exists. Swap each collection for real
     * queries (e.g. Product::active()->published()->featured()->with('defaultVariant')…)
     * when Products & Variants are built (PROJECT_DOCUMENTATION build order #4).
     */
    public function index(): View
    {
        $pool = $this->sampleProducts();

        return view('storefront.home', [
            'featured' => $pool->take(6)->values(),
            'onSale' => $pool->whereNotNull('compare')->take(6)->values(),
            'topRated' => $pool->sortByDesc('price')->take(6)->values(),
            'laptops' => $pool->values(), // showcased in a slider, chunked into slides in the view
            'trending' => $pool->reverse()->values(), // slider, chunked into slides of 4 in the view
            'bestsellers' => $pool->take(8)->values(), // 4x2 grid of small cards
            'bestsellerFeature' => $pool->firstWhere('name', 'Game Console Controller + USB 3.0') ?? $pool->last(),
            'tvProducts' => $pool->values(), // Television Entertainment slider (chunked into slides in the view)
            'recentlyViewed' => $pool->slice(3, 6)->values(), // placeholder; real list comes from session/cookie later
        ]);
    }

    /**
     * Generic "coming soon" placeholder for storefront pages not yet built.
     */
    public function placeholder(string $title): View
    {
        return view('storefront.placeholder', ['pageTitle' => $title]);
    }
}
