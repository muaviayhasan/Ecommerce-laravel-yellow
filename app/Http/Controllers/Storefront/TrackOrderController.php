<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Guest-friendly order tracking. A shopper looks up an order with its number and
 * the email it was placed under (matched against the customer or the order's
 * addresses); we then show the status and its history — no login required. The
 * same email check guards "reorder", so only the buyer can refill their cart.
 */
class TrackOrderController extends Controller
{
    public function index(): View
    {
        return view('storefront.track-order', ['order' => null, 'searched' => false, 'filters' => []]);
    }

    public function lookup(Request $request): View
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $order = $this->match($data['order_number'], $data['email'], withDetail: true);

        return view('storefront.track-order', [
            'order' => $order,
            'searched' => true,
            'filters' => $data,
        ]);
    }

    /** Re-add the items from a (re-verified) past order to the session cart. */
    public function reorder(Request $request, CartService $cart): RedirectResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $order = $this->match($data['order_number'], $data['email'], withDetail: false);

        if (! $order) {
            return redirect()->route('track.order')->with('error', 'We couldn’t match that order — please look it up again.');
        }

        if ($cart->addFromOrder($order) === 0) {
            return redirect()->route('shop')->with('error', 'None of those items are available to reorder right now.');
        }

        return redirect()->route('cart')->with('status', "We’ve added the available items from order {$order->order_number} to your cart.");
    }

    /**
     * Find an order by its number, confirming the email matches the customer or
     * one of the order's addresses. $withDetail eager-loads the tracking view's
     * relations; reorder only needs the items.
     */
    private function match(string $orderNumber, string $email, bool $withDetail): ?Order
    {
        $email = trim($email);

        return Order::query()
            ->where('order_number', trim($orderNumber))
            ->where(function ($q) use ($email) {
                $q->whereHas('customer', fn ($c) => $c->where('email', $email))
                    ->orWhereHas('addresses', fn ($a) => $a->where('email', $email));
            })
            ->when(
                $withDetail,
                fn ($q) => $q->with(['items', 'addresses', 'statusHistory' => fn ($h) => $h->orderByDesc('id')]),
                fn ($q) => $q->with('items'),
            )
            ->first();
    }
}
