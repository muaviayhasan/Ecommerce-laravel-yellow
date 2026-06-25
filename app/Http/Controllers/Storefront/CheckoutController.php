<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\ProvidesSampleProducts;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    use ProvidesSampleProducts;

    /**
     * Checkout page. Design-only: PLACEHOLDER cart lines + totals. Replace with the
     * real cart (CartService) and SalesService::place() when the Cart/Sales modules land.
     */
    public function index(): View
    {
        $items = collect([
            ['name' => 'Tablet Red EliteBook Revolve 810 G2', 'qty' => 2, 'price' => 2100, 'vendor' => 'Sara Palson'],
            ['name' => 'White Solo 2 Wireless', 'qty' => 1, 'price' => 249, 'vendor' => 'Sara Palson'],
            ['name' => 'Smartphone 6S 32GB LTE', 'qty' => 1, 'price' => 1100, 'vendor' => 'Sara Palson'],
        ])->map(fn (array $i): array => [...$i, 'line_total' => $i['qty'] * $i['price']]);

        $subtotal = (int) $items->sum('line_total');
        $shipping = 50;

        $pool = $this->sampleProducts();

        return view('storefront.checkout', [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
            'featured' => $pool->take(2)->values(),
            'topSelling' => $pool->slice(10, 2)->values(),
            'onSale' => $pool->whereNotNull('compare')->take(2)->values(),
        ]);
    }
}
