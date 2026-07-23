<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Deal;
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
            'dealGroups' => $this->cart->dealGroups(),
            'subtotal' => $this->cart->subtotal(),
            'dealDiscount' => $this->cart->dealDiscount(),
        ]);
    }

    /** Add every item of a live deal as one linked, discounted group. */
    public function addDeal(Deal $deal): RedirectResponse
    {
        $deal = Deal::live()->whereKey($deal->id)->with('items.variant.product:id,name,is_active,is_sellable,is_stock_tracked')->first();

        if (! $deal) {
            return back()->with('error', 'That deal is no longer available.');
        }
        if ($this->cart->hasDeal($deal->id)) {
            return back()->with('status', 'That deal is already in your cart.');
        }

        // Every item must fit in stock (counting what the cart already holds).
        $allowOversell = (bool) setting('inventory', 'allow_negative_stock', false);
        foreach ($deal->items as $item) {
            $variant = $item->variant;
            if (! $variant || ! $variant->product?->is_active || ! $variant->product->is_sellable) {
                return back()->with('error', 'A product in this deal is not available right now.');
            }
            if ((bool) $variant->product->is_stock_tracked && ! $allowOversell) {
                $stock = max(0, (int) floor((float) $variant->stock_quantity));
                if ($this->cart->heldQuantityOf($variant->id) + (float) $item->quantity > $stock) {
                    return back()->with('error', "“{$variant->product->name}” doesn't have enough stock for this deal.");
                }
            }
        }

        $this->cart->addDeal($deal);

        return back()->with('status', 'Deal added to your cart.');
    }

    public function removeDeal(Deal $deal): RedirectResponse
    {
        $this->cart->removeDeal($deal->id);

        return redirect()->route('cart')->with('status', 'Deal removed.');
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', Rule::exists('product_variants', 'id')->where('is_active', true)],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        // Block adding a non-sellable / unlisted product.
        $variant = ProductVariant::with('product:id,is_active,is_sellable,is_stock_tracked')
            ->find((int) $data['variant_id']);

        if (! $variant || ! $variant->product?->is_active || ! $variant->product->is_sellable) {
            return back()->with('error', 'That product is not available right now.');
        }

        $qty = max(1, (int) ($data['quantity'] ?? 1));

        // Cap at available stock (counting what the cart already holds), unless
        // the product is dropshipped or overselling is explicitly allowed.
        $tracked = (bool) $variant->product->is_stock_tracked;
        $allowOversell = (bool) setting('inventory', 'allow_negative_stock', false);

        if ($tracked && ! $allowOversell) {
            $stock = max(0, (int) floor((float) $variant->stock_quantity));
            $room = $stock - $this->cart->quantityOf($variant->id);

            if ($room <= 0) {
                return back()->with('error', $stock > 0
                    ? "Only {$stock} in stock — your cart already has all of them."
                    : 'That item is out of stock.');
            }

            if ($qty > $room) {
                $this->cart->add($variant->id, $room);

                return back()->with('error', "Only {$stock} in stock — we added the remaining {$room} to your cart.");
            }
        }

        $this->cart->add($variant->id, $qty);

        return back()->with('status', 'Added to your cart.');
    }

    public function update(Request $request, ProductVariant $variant): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:999']]);
        $qty = (int) $data['quantity'];

        // Dropship products (not stock-tracked) are sourced per order — no stock cap.
        $tracked = (bool) $variant->product()->value('is_stock_tracked');

        // Never let the cart hold more than what's in stock (unless overselling is allowed).
        $allowOversell = (bool) setting('inventory', 'allow_negative_stock', false);
        $stock = max(0, (int) floor((float) $variant->stock_quantity));

        if ($tracked && $qty > 0 && ! $allowOversell && $qty > $stock) {
            $this->cart->update($variant->id, $stock);

            return redirect()->route('cart')->with('error', $stock > 0
                ? "Only {$stock} in stock — we set the quantity to {$stock}."
                : 'That item is out of stock and has been removed.');
        }

        $this->cart->update($variant->id, $qty);

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

    /**
     * Rebuild the session cart from a saved abandoned-cart snapshot (reached via a
     * recovery-email link) and drop the shopper back at checkout. Stale/sold-out
     * variants are silently skipped by CartService on the next read.
     */
    public function recover(string $token): RedirectResponse
    {
        $saved = AbandonedCart::open()->where('token', $token)->first();

        if (! $saved) {
            return redirect()->route('cart')->with('error', 'That cart link has expired.');
        }

        $this->cart->clear();

        foreach ($saved->items as $item) {
            $variantId = (int) ($item['variant_id'] ?? 0);
            $qty = (int) round((float) ($item['qty'] ?? 1));
            if ($variantId > 0 && $qty > 0) {
                $this->cart->add($variantId, $qty);
            }
        }

        if ($this->cart->isEmpty()) {
            return redirect()->route('shop')->with('error', 'Those items are no longer available.');
        }

        return redirect()->route('checkout')->with('status', 'Welcome back — your cart is ready.');
    }
}
