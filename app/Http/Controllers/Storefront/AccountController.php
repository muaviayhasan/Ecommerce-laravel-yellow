<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Services\WishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Signed-in customer "My Account" area — dashboard, orders, addresses, profile. */
class AccountController extends Controller
{
    /** Pakistani mobile number in 0300-0000000 form (dash optional). */
    private const PHONE_RULE = 'regex:/^03\d{2}-?\d{7}$/';
    private const PHONE_MESSAGE = 'Enter a valid mobile number like 0300-0000000.';

    public function dashboard(Request $request): View
    {
        $user = $request->user();

        return view('storefront.account.dashboard', [
            'user' => $user,
            'orders' => $user->orders()->latest('id')->take(4)->get(),
            'orderCount' => $user->orders()->count(),
            'wishlistCount' => app(WishlistService::class)->count(),
            'address' => $user->addresses()->orderByDesc('is_default_shipping')->first(),
        ]);
    }

    public function orders(Request $request): View
    {
        return view('storefront.account.orders', [
            'orders' => $request->user()->orders()->withCount('items')->latest('id')->paginate(10),
        ]);
    }

    public function showOrder(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load(['items', 'shippingAddress', 'billingAddress']);

        return view('storefront.account.order', ['order' => $order]);
    }

    public function addresses(Request $request): View
    {
        return view('storefront.account.addresses', [
            'addresses' => $request->user()->addresses()
                ->orderByDesc('is_default_shipping')->orderByDesc('is_default_billing')->orderBy('id')->get(),
            ...$this->mapConfig(),
        ]);
    }

    /** Shared Google Maps config for address forms — all keys degrade gracefully when unset. */
    public static function mapConfig(): array
    {
        $key = setting('maps', 'google_maps_key');
        $enabled = (bool) setting('maps', 'enabled', false) && filled($key);
        [$lat, $lng] = array_pad(array_map('trim', explode(',', (string) setting('maps', 'map_center', '30.3753,69.3451'))), 2, null);
        $code = strtoupper((string) setting('maps', 'country_code', 'PK'));

        return [
            'mapsEnabled' => $enabled,
            'mapsKey' => $enabled ? $key : null,
            'mapCenter' => ['lat' => (float) ($lat ?: 30.3753), 'lng' => (float) ($lng ?: 69.3451)],
            'countryCode' => $code !== '' ? $code : null,
        ];
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $data = $this->validateAddress($request);
        $isFirst = $request->user()->addresses()->count() === 0;

        $address = $request->user()->addresses()->create($data);

        // A customer's very first address becomes their default automatically.
        $this->syncDefaults($request, $address,
            billing: $isFirst || $request->boolean('is_default_billing'),
            shipping: $isFirst || $request->boolean('is_default_shipping'),
        );

        return redirect()->route('account.addresses')->with('status', 'Address added.');
    }

    public function updateAddress(Request $request, CustomerAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        $address->update($this->validateAddress($request));
        $this->syncDefaults($request, $address,
            billing: $request->boolean('is_default_billing'),
            shipping: $request->boolean('is_default_shipping'),
        );

        return redirect()->route('account.addresses')->with('status', 'Address updated.');
    }

    public function destroyAddress(Request $request, CustomerAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        $address->delete();

        return redirect()->route('account.addresses')->with('status', 'Address removed.');
    }

    /** @return array<string, mixed> */
    protected function validateAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['nullable', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', self::PHONE_RULE],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'zip' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], ['phone.regex' => self::PHONE_MESSAGE]);
    }

    /** When an address is flagged default, clear the flag on the customer's other addresses. */
    protected function syncDefaults(Request $request, CustomerAddress $address, bool $billing, bool $shipping): void
    {
        if ($billing) {
            $request->user()->addresses()->whereKeyNot($address->id)->update(['is_default_billing' => false]);
        }
        if ($shipping) {
            $request->user()->addresses()->whereKeyNot($address->id)->update(['is_default_shipping' => false]);
        }

        $address->update(['is_default_billing' => $billing, 'is_default_shipping' => $shipping]);
    }

    public function profile(Request $request): View
    {
        return view('storefront.account.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', self::PHONE_RULE],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], ['phone.regex' => self::PHONE_MESSAGE]);

        if ($request->hasFile('avatar')) {
            // Replace a previously uploaded file, but never delete a social provider URL.
            if ($user->avatar && ! Str::startsWith($user->avatar, ['http://', 'https://'])) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->fill(['name' => $data['name'], 'phone' => $data['phone'] ?? null])->save();
        $user->customer?->update(['name' => $data['name'], 'phone' => $data['phone'] ?? $user->customer->phone]);

        return back()->with('status', 'Your details have been updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        // Social-login-only accounts have no password yet — let them set one without the current.
        $rules = ['password' => ['required', 'string', 'min:8', 'confirmed']];
        if ($request->user()->password) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $request->validate($rules);

        $request->user()->update(['password' => $request->input('password')]);

        // Security notice: confirm the change by email.
        \App\Support\Mail\Notifier::send('password_changed', $request->user()->email, new \App\Mail\PasswordChangedMail($request->user()));

        return back()->with('status', 'Your password has been changed.');
    }
}
