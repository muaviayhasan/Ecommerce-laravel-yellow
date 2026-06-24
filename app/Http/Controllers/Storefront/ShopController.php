<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\ProvidesSampleProducts;
use Illuminate\View\View;

class ShopController extends Controller
{
    use ProvidesSampleProducts;

    /**
     * Shop / catalog listing page.
     *
     * Design-only for now: PLACEHOLDER product data + static filter lists.
     * Wire to real queries (Product::active()->filter()->paginate()) and real
     * Category/Brand/Attribute facets when the catalog module is built.
     */
    public function index(): View
    {
        $pool = $this->sampleProducts();

        return view('storefront.shop', [
            'products' => $pool->values(),
            'recommended' => $pool->values(), // top carousel (chunked into slides in the view)
            'latest' => $pool->slice(1, 3)->values(),
            'featured' => $pool->take(2)->values(),
            'topSelling' => $pool->slice(10, 2)->values(),
            'onSale' => $pool->whereNotNull('compare')->take(1)->values(),
        ]);
    }
}
