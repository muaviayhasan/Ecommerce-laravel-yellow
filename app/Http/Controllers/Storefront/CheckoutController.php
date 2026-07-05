<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use App\Services\SalesService;
use App\Services\SupportBot;
use App\Support\Storefront;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class CheckoutController extends Controller
{
    /** Pakistani mobile number in 0300-0000000 form (dash optional). */
    private const PHONE_RULE = 'regex:/^03\d{2}-?\d{7}$/';
    private const PHONE_MESSAGE = 'Enter a valid mobile number like 0300-0000000.';

    public function __construct(private CartService $cart) {}

    public function index(): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Your cart is empty.');
        }

        $subtotal = $this->cart->subtotal();
        $shipping = $this->shipping($subtotal);

        // Remember they reached checkout so the support bot can nudge if they don't finish.
        if (auth()->check()) {
            session(['co_pending_at' => now()->timestamp, 'co_nudged' => false]);
        }

        return view('storefront.checkout', [
            'items' => $this->cart->items(),
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
            'user' => auth()->user(),
            'addresses' => auth()->check()
                ? auth()->user()->addresses()->orderByDesc('is_default_shipping')->orderByDesc('is_default_billing')->orderBy('id')->get()
                : collect(),
            'featured' => Storefront::cards(Storefront::query()->featured()->take(2)->get()),
            'topSelling' => Storefront::cards(Storefront::query()->bestseller()->take(2)->get()),
            'onSale' => Storefront::cards(Storefront::onSaleQuery()->take(2)->get()),
        ]);
    }

    public function store(Request $request, SalesService $sales): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Your cart is empty.');
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', self::PHONE_RULE],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'zip' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', 'in:cod,bank'],
            'terms' => ['accepted'],
            'save_address' => ['nullable', 'boolean'],
            // Separate shipping address (only when "ship to a different address" is on)
            'ship_to_different' => ['nullable', 'boolean'],
            'ship_name' => ['nullable', 'required_if:ship_to_different,1', 'string', 'max:255'],
            'ship_phone' => ['nullable', self::PHONE_RULE],
            'ship_line1' => ['nullable', 'required_if:ship_to_different,1', 'string', 'max:255'],
            'ship_line2' => ['nullable', 'string', 'max:255'],
            'ship_city' => ['nullable', 'required_if:ship_to_different,1', 'string', 'max:120'],
            'ship_state' => ['nullable', 'string', 'max:120'],
            'ship_zip' => ['nullable', 'string', 'max:20'],
            'ship_country' => ['nullable', 'string', 'max:120'],
        ], [
            'phone.regex' => self::PHONE_MESSAGE,
            'ship_phone.regex' => self::PHONE_MESSAGE,
            'ship_name.required_if' => 'Enter the shipping name.',
            'ship_line1.required_if' => 'Enter the shipping street address.',
            'ship_city.required_if' => 'Enter the shipping city.',
        ]);

        $items = $this->cart->items();
        $variants = ProductVariant::with('product:id,name')
            ->whereIn('id', $items->pluck('variant_id'))->get()->keyBy('id');

        $lines = [];
        foreach ($items as $item) {
            $variant = $variants->get($item->variant_id);
            if ($variant) {
                $lines[] = ['variant' => $variant, 'quantity' => (float) $item->qty];
            }
        }
        if ($lines === []) {
            return redirect()->route('cart')->with('error', 'Your cart is empty.');
        }

        $name = trim($data['first_name'] . ' ' . $data['last_name']);
        $customer = Customer::firstOrCreate(
            ['email' => $data['email']],
            ['name' => $name, 'phone' => $data['phone'], 'type' => 'retail', 'price_tier' => 'retail', 'is_active' => true, 'user_id' => auth()->id()],
        );

        $shipping = $this->shipping($this->cart->subtotal());

        try {
            $order = $sales->place('web', $customer, $lines, [
                'payment_method' => $data['payment_method'],
                'shipping_total' => $shipping,
                'paid' => 0, // COD / bank transfer — settled later
                'user_id' => auth()->id(), // so it shows in the customer's account
            ]);
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $address = [
            'name' => $name, 'company' => $data['company'] ?? null, 'phone' => $data['phone'], 'email' => $data['email'],
            'line1' => $data['line1'], 'line2' => $data['line2'] ?? null, 'city' => $data['city'],
            'state' => $data['state'] ?? null, 'zip' => $data['zip'] ?? null, 'country' => $data['country'],
        ];
        $order->addresses()->create(['type' => 'billing'] + $address);

        // Ship to the same place unless the customer gave a separate shipping address.
        $shippingAddress = $request->boolean('ship_to_different')
            ? [
                'name' => $data['ship_name'], 'company' => null, 'phone' => $data['ship_phone'] ?: $data['phone'], 'email' => $data['email'],
                'line1' => $data['ship_line1'], 'line2' => $data['ship_line2'] ?? null, 'city' => $data['ship_city'],
                'state' => $data['ship_state'] ?? null, 'zip' => $data['ship_zip'] ?? null, 'country' => $data['ship_country'] ?: $data['country'],
            ]
            : $address;
        $order->addresses()->create(['type' => 'shipping'] + $shippingAddress);

        if (! empty($data['notes'])) {
            $order->update(['customer_notes' => $data['notes']]);
        }

        // Optionally remember this address on the customer's account for next time.
        if (auth()->check() && $request->boolean('save_address')) {
            $this->rememberAddress($request->user(), $name, $data);
        }

        // Confirm the order in the customer's support chat + cancel any abandonment nudge.
        if (auth()->check()) {
            app(SupportBot::class)->notifyUser($request->user(),
                "🎉 Thank you! Your order {$order->order_number} has been placed.\n"
                . 'Total ' . format_money($order->grand_total) . ' · ' . ucfirst(str_replace('_', ' ', $order->payment_status)) . ".\n"
                . 'Track it here: ' . route('account.orders.show', $order));
            session()->forget(['co_pending_at', 'co_nudged']);
        }

        $this->cart->clear();
        session(['last_order_id' => $order->id]);

        return redirect()->route('checkout.success');
    }

    public function success(): View|RedirectResponse
    {
        $orderId = session('last_order_id');
        if (! $orderId) {
            return redirect()->route('home');
        }

        $order = Order::with('items', 'addresses')->find($orderId);
        if (! $order) {
            return redirect()->route('home');
        }

        return view('storefront.checkout-success', ['order' => $order]);
    }

    /** Save the checkout address to the customer's address book, skipping exact duplicates. */
    private function rememberAddress(User $user, string $name, array $data): void
    {
        $exists = $user->addresses()
            ->where('line1', $data['line1'])
            ->where('city', $data['city'])
            ->where('zip', $data['zip'] ?? null)
            ->exists();

        if ($exists) {
            return;
        }

        $isFirst = $user->addresses()->count() === 0;

        $user->addresses()->create([
            'name' => $name,
            'company' => $data['company'] ?? null,
            'phone' => $data['phone'],
            'line1' => $data['line1'],
            'line2' => $data['line2'] ?? null,
            'city' => $data['city'],
            'state' => $data['state'] ?? null,
            'zip' => $data['zip'] ?? null,
            'country' => $data['country'],
            'is_default_billing' => $isFirst,
            'is_default_shipping' => $isFirst,
        ]);
    }

    private function shipping(float $subtotal): float
    {
        $freeOver = (float) setting('shipping', 'free_over', 0);
        if ($freeOver > 0 && $subtotal >= $freeOver) {
            return 0.0;
        }

        return (float) setting('shipping', 'flat_rate', 0);
    }
}
