<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index(): View
    {
        return view('storefront.cart', [
            'items' => $this->cart->items(),
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', Rule::exists('product_variants', 'id')->where('is_active', true)],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        // Block adding a non-sellable / unlisted product.
        $sellable = ProductVariant::where('id', $data['variant_id'])
            ->whereHas('product', fn ($q) => $q->where('is_active', true)->where('is_sellable', true))
            ->exists();

        if (! $sellable) {
            return back()->with('error', 'That product is not available right now.');
        }

        $this->cart->add((int) $data['variant_id'], (int) ($data['quantity'] ?? 1));

        return back()->with('status', 'Added to your cart.');
    }

    public function update(Request $request, ProductVariant $variant): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:999']]);

        $this->cart->update($variant->id, (int) $data['quantity']);

        return redirect()->route('cart')->with('status', 'Cart updated.');
    }

    public function remove(ProductVariant $variant): RedirectResponse
    {
        $this->cart->remove($variant->id);

        return redirect()->route('cart')->with('status', 'Item removed.');
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return redirect()->route('cart')->with('status', 'Cart cleared.');
    }
}
