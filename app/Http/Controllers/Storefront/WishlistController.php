<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\WishlistService;
use App\Support\Storefront;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function __construct(private WishlistService $wishlist) {}

    public function index(): View
    {
        return view('storefront.wishlist', [
            'products' => Storefront::cards($this->wishlist->products()),
        ]);
    }

    public function toggle(Product $product): RedirectResponse
    {
        $result = $this->wishlist->toggle($product->id);

        return back()->with('status', $result['added'] ? 'Added to your wishlist.' : 'Removed from your wishlist.');
    }

    public function remove(Product $product): RedirectResponse
    {
        $this->wishlist->remove($product->id);

        return redirect()->route('wishlist')->with('status', 'Removed from your wishlist.');
    }

    public function clear(): RedirectResponse
    {
        $this->wishlist->clear();

        return redirect()->route('wishlist')->with('status', 'Wishlist cleared.');
    }
}
