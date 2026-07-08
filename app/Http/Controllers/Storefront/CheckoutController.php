<?php

namespace App\Http\Controllers\Storefront;

use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Mail\Admin\NewOrderMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\AbandonedCartService;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\SalesService;
use App\Services\SupportBot;
use App\Support\Mail\Notifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class CheckoutController extends Controller
{
    /** Pakistani mobile number in 0300-0000000 form (dash optional). */
    private const PHONE_RULE = 'regex:/^03\d{2}-?\d{7}$/';
    private const PHONE_MESSAGE = 'Enter a valid mobile number like 0300-0000000.';

    /** Session key holding the applied coupon code (re-validated on every read). */
    private const COUPON_KEY = 'checkout_coupon';

    public function __construct(private CartService $cart, private CouponService $coupons) {}

    public function index(): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Your cart is empty.');
        }

        $subtotal = $this->cart->subtotal();
        $shipping = $this->shipping($subtotal);
        $coupon = $this->activeCoupon($subtotal);
        $discount = $coupon ? $this->coupons->discountFor($coupon, $subtotal) : 0.0;

        // Remember they reached checkout so the support bot can nudge if they don't finish.
        if (auth()->check()) {
            session(['co_pending_at' => now()->timestamp, 'co_nudged' => false]);
            // Snapshot the cart for email recovery — we already know a signed-in shopper's address.
            $this->captureAbandoned(auth()->user()->email, auth()->user()->name);
        }

        return view('storefront.checkout', [
            'items' => $this->cart->items(),
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'coupon' => $coupon,
            'discount' => $discount,
            'total' => max(0, round($subtotal - $discount + $shipping, 2)),
            'user' => auth()->user(),
            'addresses' => auth()->check()
                ? auth()->user()->addresses()->orderByDesc('is_default_shipping')->orderByDesc('is_default_billing')->orderBy('id')->get()
                : collect(),
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
            'last_name' => ['nullable', 'string', 'max:100'],
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

        $name = trim($data['first_name'] . ' ' . ($data['last_name'] ?? ''));
        $customer = Customer::firstOrCreate(
            ['email' => $data['email']],
            ['name' => $name, 'phone' => $data['phone'], 'type' => 'retail', 'price_tier' => 'retail', 'is_active' => true, 'user_id' => auth()->id()],
        );

        $subtotal = $this->cart->subtotal();
        $shipping = $this->shipping($subtotal);

        // Re-validate the applied coupon against the final cart + this shopper — the
        // session value is only a hint; this is the authoritative check. A coupon that
        // lapsed between apply and submit bounces back rather than silently charging full price.
        $couponOpts = [];
        if ($code = session(self::COUPON_KEY)) {
            [$coupon, $couponError] = $this->coupons->evaluate((string) $code, $subtotal, auth()->id(), $data['email']);
            if (! $coupon) {
                session()->forget(self::COUPON_KEY);

                return back()->withInput()->with('coupon_error', $couponError);
            }
            $couponOpts = [
                'discount_type' => $coupon->type,
                'discount_value' => (float) $coupon->value,
                'coupon_id' => $coupon->id,
            ];
        }

        try {
            $order = $sales->place('web', $customer, $lines, [
                'payment_method' => $data['payment_method'],
                'shipping_total' => $shipping,
                'paid' => 0, // COD / bank transfer — settled later
                'user_id' => auth()->id(), // so it shows in the customer's account
            ] + $couponOpts);
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Redeemed — bump the coupon's global usage counter and drop it from the session.
        if (! empty($couponOpts['coupon_id'])) {
            Coupon::whereKey($couponOpts['coupon_id'])->increment('used_count');
        }
        session()->forget([self::COUPON_KEY, 'checkout_email']);

        // Realtime ping to the admin header bell. Best-effort — never fail the order.
        try {
            broadcast(new OrderPlaced($order->loadMissing('customer')));
        } catch (\Throwable $e) {
            report($e);
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

        // Email the customer their confirmation and alert staff of the new order.
        $order->loadMissing('items', 'customer');
        $customerUrl = auth()->check() ? route('account.orders.show', $order) : null;
        Notifier::send('order_confirmation', $customer->email, new OrderConfirmationMail($order, $customerUrl));

        $adminEmail = setting('store', 'support_email') ?: setting('mail', 'from_address') ?: config('mail.from.address');
        Notifier::send('admin_new_order', $adminEmail, new NewOrderMail($order, route('admin.orders.show', $order)));

        // The sale closes any open recovery reminder for this shopper.
        app(AbandonedCartService::class)->markRecovered($customer->email, auth()->id());

        $this->cart->clear();
        session(['last_order_id' => $order->id]);

        return redirect()->route('checkout.success');
    }

    /** Apply a coupon code to the checkout (stored in the session, re-validated on each render). */
    public function applyCoupon(Request $request): RedirectResponse
    {
        $request->validate([
            'coupon_code' => ['required', 'string', 'max:60'],
            'email' => ['nullable', 'email'],
        ]);

        if ($this->cart->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Your cart is empty.');
        }

        // The whole checkout form is posted here (formaction), so a guest's typed email is
        // available for targeted / first-order eligibility — remember it for re-validation.
        $email = auth()->user()?->email ?? ($request->filled('email') ? (string) $request->input('email') : null);
        if ($email && ! auth()->check()) {
            session(['checkout_email' => $email]);
        }

        $subtotal = $this->cart->subtotal();
        [$coupon, $error] = $this->coupons->evaluate(
            (string) $request->input('coupon_code'), $subtotal, auth()->id(), $email,
        );

        if (! $coupon) {
            return redirect()->route('checkout')->withInput()->with('coupon_error', $error);
        }

        session([self::COUPON_KEY => $coupon->code]);

        return redirect()->route('checkout')->withInput()
            ->with('status', "Coupon “{$coupon->code}” applied — you saved " . format_money($this->coupons->discountFor($coupon, $subtotal)) . '.');
    }

    /** Remove the applied coupon. */
    public function removeCoupon(): RedirectResponse
    {
        session()->forget(self::COUPON_KEY);

        return redirect()->route('checkout')->withInput()->with('status', 'Coupon removed.');
    }

    /** The session coupon re-validated against the live cart; forgotten (returns null) if no longer valid. */
    private function activeCoupon(float $subtotal): ?Coupon
    {
        $code = session(self::COUPON_KEY);
        if (! $code) {
            return null;
        }

        $email = auth()->user()?->email ?? session('checkout_email');
        [$coupon] = $this->coupons->evaluate((string) $code, $subtotal, auth()->id(), $email);
        if (! $coupon) {
            session()->forget(self::COUPON_KEY);
        }

        return $coupon;
    }

    /**
     * Progressive capture for guests: the checkout page posts here once a valid
     * email is entered, so an unfinished guest cart can still be recovered.
     */
    public function capture(Request $request): Response
    {
        // Only store anything while the feature is switched on (opt-in).
        if (! Notifier::enabled('abandoned_cart') || ! (bool) setting('emails', 'abandoned_cart', false)) {
            return response()->noContent();
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $this->captureAbandoned($data['email'], $data['name'] ?? null);

        return response()->noContent();
    }

    /**
     * Snapshot the current session cart against an email so the recovery
     * scheduler can remind them. No-ops when the feature is off or the cart is
     * empty.
     */
    private function captureAbandoned(string $email, ?string $name = null): void
    {
        if (! Notifier::enabled('abandoned_cart') || ! (bool) setting('emails', 'abandoned_cart', false)) {
            return;
        }

        $items = $this->cart->items();
        if ($items->isEmpty()) {
            return;
        }

        $snapshot = $items->map(fn ($item) => [
            'variant_id' => $item->variant_id,
            'name' => $item->name,
            'sku' => $item->sku,
            'qty' => $item->qty,
            'price' => $item->price,
            'image' => $item->image,
            'url' => $item->url,
        ])->all();

        app(AbandonedCartService::class)->capture(
            email: $email,
            items: $snapshot,
            subtotal: $this->cart->subtotal(),
            name: $name,
            userId: auth()->id(),
        );
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
