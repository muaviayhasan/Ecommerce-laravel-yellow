@extends('layouts.storefront')
@section('robots', 'noindex, follow')

@section('title', 'Checkout — ' . config('app.name'))
@section('hideNewsletter', '1')

@php
    $field = 'w-full px-3.5 py-2.5 border border-outline-variant rounded-lg bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors text-body-base';
    $label = 'block mb-1 text-label-sm font-medium text-on-surface';
    $provinces = ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Islamabad Capital Territory', 'Gilgit-Baltistan', 'Azad Kashmir'];
    $adData = fn ($a) => ['id' => $a->id, 'name' => $a->name ?? '', 'phone' => $a->phone ?? '', 'line1' => $a->line1 ?? '', 'line2' => $a->line2 ?? '', 'city' => $a->city ?? '', 'state' => $a->state ?? '', 'zip' => $a->zip ?? '', 'country' => $a->country ?? 'Pakistan'];
    $default = $addresses->firstWhere('is_default_shipping', true) ?? $addresses->first();
    $err = fn ($f) => $errors->has($f) ? '<p class="text-error text-label-sm mt-1">' . e($errors->first($f)) . '</p>' : '';
@endphp

@section('content')
    <div class="bg-background py-8">
        <div class="app-container">
            <nav class="flex items-center gap-2 text-label-sm text-on-surface-variant mb-4" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                <span class="font-bold text-on-surface">Checkout</span>
            </nav>

            <h1 class="text-2xl font-bold mb-6">Checkout</h1>

            @guest
                <div class="bg-primary-container/50 text-on-surface p-3.5 rounded-lg flex flex-wrap items-center gap-2 text-body-base mb-5">
                    <span class="material-symbols-outlined text-[20px]">person</span>
                    Returning customer? <a href="{{ route('login') }}" class="underline hover:no-underline font-bold">Log in</a> for a faster checkout.
                </div>
            @endguest

            @if (session('error'))
                <div class="mb-5 p-4 rounded-lg bg-error-container/40 text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-error">error</span> {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('checkout.store') }}" x-data="checkout()" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                @csrf
                <input type="hidden" name="country" :value="country">
                <input type="hidden" name="ship_to_different" :value="shipDifferent ? 1 : 0">
                <input type="hidden" name="address_mode" :value="mode">
                <input type="hidden" name="selected_address" :value="selected">

                {{-- ============ Left: details ============ --}}
                <div class="lg:col-span-7 space-y-5">

                    {{-- Contact --}}
                    <section class="bg-white border border-outline-variant rounded-xl p-5">
                        <h2 class="font-bold mb-4">Contact</h2>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="{{ $label }}" for="email">Email <span class="text-error">*</span></label>
                                <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" placeholder="email@example.com" class="{{ $field }}">
                                {!! $err('email') !!}
                            </div>
                            <div>
                                <label class="{{ $label }}">Phone <span class="text-error">*</span></label>
                                <div class="flex rounded-lg border border-outline-variant overflow-hidden focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-colors @error('phone') border-error @enderror">
                                    <span class="grid place-items-center px-3 bg-surface-container-low text-on-surface-variant font-semibold border-r border-outline-variant select-none">03</span>
                                    <input type="tel" inputmode="numeric" x-model="phoneRest" @input="phoneRest = fmtPhone(phoneRest)" maxlength="10" placeholder="00-0000000" autocomplete="tel-national" class="flex-1 min-w-0 px-3 py-2.5 outline-none bg-transparent">
                                </div>
                                <input type="hidden" name="phone" :value="phoneFull">
                                {!! $err('phone') !!}
                            </div>
                        </div>
                    </section>

                    {{-- Delivery address --}}
                    <section class="bg-white border border-outline-variant rounded-xl p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="font-bold">Delivery address</h2>
                            @auth
                                @if ($addresses->isNotEmpty())
                                    <a href="{{ route('account.addresses') }}" class="text-label-sm text-primary font-bold hover:underline">Manage</a>
                                @endif
                            @endauth
                        </div>

                        @auth
                            @if ($addresses->isNotEmpty())
                                {{-- Saved address cards --}}
                                <div x-show="mode === 'saved'" class="space-y-2.5">
                                    @foreach ($addresses as $a)
                                        <button type="button" @click='pick(@json($adData($a)))'
                                            class="w-full text-left p-3.5 border rounded-lg flex items-start gap-3 transition-colors"
                                            :class="selected === {{ $a->id }} ? 'border-primary bg-primary-container/10 ring-1 ring-primary' : 'border-outline-variant hover:border-primary'">
                                            <span class="mt-0.5 w-4 h-4 rounded-full border-2 shrink-0 grid place-items-center" :class="selected === {{ $a->id }} ? 'border-primary' : 'border-outline'">
                                                <span class="w-2 h-2 rounded-full bg-primary" x-show="selected === {{ $a->id }}"></span>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="flex items-center gap-2">
                                                    @if ($a->label)<span class="font-bold text-body-base">{{ $a->label }}</span>@endif
                                                    @if ($a->is_default_shipping)<span class="text-[10px] font-bold uppercase tracking-wide bg-primary-container/40 text-on-primary-container px-2 py-0.5 rounded-full">Default</span>@endif
                                                </span>
                                                <span class="block font-medium">{{ $a->name }}</span>
                                                <span class="block text-label-sm text-on-surface-variant">{{ \Illuminate\Support\Str::limit(collect([$a->line1, $a->city, $a->state])->filter()->join(', '), 64) }}</span>
                                            </span>
                                        </button>
                                    @endforeach
                                    <button type="button" @click="useNew()" class="w-full p-3 border border-dashed border-outline-variant rounded-lg text-primary font-bold text-label-sm hover:border-primary hover:bg-primary-container/10 transition flex items-center justify-center gap-1.5">
                                        <span class="material-symbols-outlined text-[18px]">add</span> Deliver to a new address
                                    </button>
                                </div>
                            @endif
                        @endauth

                        {{-- New / guest address form --}}
                        <div x-show="mode === 'new'" x-transition class="space-y-4">
                            @auth
                                @if ($addresses->isNotEmpty())
                                    <button type="button" @click="mode = 'saved'" class="text-label-sm text-primary font-bold hover:underline flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">arrow_back</span> Use a saved address
                                    </button>
                                @endif
                            @endauth
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="{{ $label }}">First name <span class="text-error">*</span></label>
                                    <input x-ref="fname" name="first_name" type="text" value="{{ old('first_name') }}" autocomplete="given-name" class="{{ $field }}">
                                    {!! $err('first_name') !!}
                                </div>
                                <div>
                                    <label class="{{ $label }}">Last name <span class="text-error">*</span></label>
                                    <input x-ref="lname" name="last_name" type="text" value="{{ old('last_name') }}" autocomplete="family-name" class="{{ $field }}">
                                    {!! $err('last_name') !!}
                                </div>
                            </div>
                            <div>
                                <label class="{{ $label }}">Street address <span class="text-error">*</span></label>
                                <input x-ref="line1" name="line1" type="text" value="{{ old('line1') }}" placeholder="House no., street, area" autocomplete="address-line1" class="{{ $field }}">
                                {!! $err('line1') !!}
                                <input x-ref="line2" name="line2" type="text" value="{{ old('line2') }}" placeholder="Apartment, suite, landmark (optional)" autocomplete="address-line2" class="{{ $field }} mt-2">
                            </div>
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="{{ $label }}">Town / City <span class="text-error">*</span></label>
                                    <input x-ref="city" name="city" type="text" value="{{ old('city') }}" autocomplete="address-level2" class="{{ $field }}">
                                    {!! $err('city') !!}
                                </div>
                                <div>
                                    <label class="{{ $label }}">Province</label>
                                    <select x-ref="state" name="state" data-no-select2 class="{{ $field }}">
                                        <option value="">Select province</option>
                                        @foreach ($provinces as $s)
                                            <option @selected(old('state') === $s)>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="sm:w-1/2 sm:pr-2">
                                <label class="{{ $label }}">ZIP / Postal code</label>
                                <input x-ref="zip" name="zip" type="text" value="{{ old('zip') }}" inputmode="numeric" autocomplete="postal-code" class="{{ $field }}">
                            </div>
                            @auth
                                <label class="flex items-center gap-2.5 cursor-pointer pt-1">
                                    <input type="checkbox" name="save_address" value="1" @checked(old('save_address')) class="w-4 h-4 rounded border-outline-variant accent-primary-container">
                                    <span class="text-body-base">Save this address to my account for next time</span>
                                </label>
                            @endauth
                        </div>
                    </section>

                    {{-- Ship to a different address --}}
                    <section class="bg-white border border-outline-variant rounded-xl p-5">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" x-model="shipDifferent" class="w-4 h-4 rounded border-outline-variant accent-primary-container">
                            <span class="font-medium">Ship to a different address?</span>
                        </label>
                        <div x-show="shipDifferent" x-transition class="mt-4 space-y-4 pt-4 border-t border-outline-variant">
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="{{ $label }}">Full name <span class="text-error">*</span></label>
                                    <input name="ship_name" type="text" value="{{ old('ship_name') }}" class="{{ $field }}">
                                    {!! $err('ship_name') !!}
                                </div>
                                <div>
                                    <label class="{{ $label }}">Phone</label>
                                    <div class="flex rounded-lg border border-outline-variant overflow-hidden focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-colors @error('ship_phone') border-error @enderror">
                                        <span class="grid place-items-center px-3 bg-surface-container-low text-on-surface-variant font-semibold border-r border-outline-variant select-none">03</span>
                                        <input type="tel" inputmode="numeric" x-model="shipPhoneRest" @input="shipPhoneRest = fmtPhone(shipPhoneRest)" maxlength="10" placeholder="00-0000000" class="flex-1 min-w-0 px-3 py-2.5 outline-none bg-transparent">
                                    </div>
                                    <input type="hidden" name="ship_phone" :value="shipPhoneFull">
                                    {!! $err('ship_phone') !!}
                                </div>
                            </div>
                            <div>
                                <label class="{{ $label }}">Street address <span class="text-error">*</span></label>
                                <input name="ship_line1" type="text" value="{{ old('ship_line1') }}" placeholder="House no., street, area" class="{{ $field }}">
                                {!! $err('ship_line1') !!}
                                <input name="ship_line2" type="text" value="{{ old('ship_line2') }}" placeholder="Apartment, suite, landmark (optional)" class="{{ $field }} mt-2">
                            </div>
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="{{ $label }}">Town / City <span class="text-error">*</span></label>
                                    <input name="ship_city" type="text" value="{{ old('ship_city') }}" class="{{ $field }}">
                                    {!! $err('ship_city') !!}
                                </div>
                                <div>
                                    <label class="{{ $label }}">Province</label>
                                    <select name="ship_state" data-no-select2 class="{{ $field }}">
                                        <option value="">Select province</option>
                                        @foreach ($provinces as $s)
                                            <option @selected(old('ship_state') === $s)>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="sm:w-1/2 sm:pr-2">
                                <label class="{{ $label }}">ZIP / Postal code</label>
                                <input name="ship_zip" type="text" value="{{ old('ship_zip') }}" inputmode="numeric" class="{{ $field }}">
                            </div>
                            <input type="hidden" name="ship_country" value="Pakistan">
                        </div>
                    </section>

                    {{-- Notes --}}
                    <section class="bg-white border border-outline-variant rounded-xl p-5">
                        <label class="{{ $label }}" for="notes">Order notes (optional)</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Notes about your order, e.g. delivery instructions." class="{{ $field }}">{{ old('notes') }}</textarea>
                    </section>
                </div>

                {{-- ============ Right: order summary ============ --}}
                <div class="lg:col-span-5 lg:sticky lg:top-24">
                    <div class="bg-white border border-outline-variant rounded-xl p-5 sm:p-6 shadow-sm">
                        <h2 class="font-bold text-lg mb-4">Your order</h2>

                        <div class="border-b border-outline-variant pb-4 mb-4 space-y-3">
                            @foreach ($items as $item)
                                <div class="flex justify-between items-start gap-4">
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-body-base font-medium truncate">{{ $item->name }} <span class="text-on-surface-variant">&times; {{ $item->qty }}</span></span>
                                        <span class="text-label-sm text-on-surface-variant">{{ $item->sku }}</span>
                                    </div>
                                    <span class="font-medium whitespace-nowrap">{{ format_money($item->line_total) }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="space-y-2.5 border-b border-outline-variant pb-4 mb-4 text-body-base">
                            <div class="flex justify-between"><span class="text-on-surface-variant">Subtotal</span><span class="font-medium">{{ format_money($subtotal) }}</span></div>
                            <div class="flex justify-between"><span class="text-on-surface-variant">Shipping</span><span class="font-medium">{{ $shipping > 0 ? format_money($shipping) : 'Free' }}</span></div>
                            <div class="flex justify-between pt-1"><span class="text-lg font-bold">Total</span><span class="text-lg font-bold text-primary">{{ format_money($total) }}</span></div>
                        </div>

                        {{-- Payment --}}
                        <div class="space-y-2.5 mb-4">
                            <label class="flex items-center gap-3 p-3.5 border rounded-lg cursor-pointer transition-colors" :class="payment === 'cod' ? 'border-primary bg-primary-container/10' : 'border-outline-variant hover:bg-surface-container-low'">
                                <input type="radio" name="payment_method" value="cod" x-model="payment" class="accent-primary-container">
                                <span class="font-bold">Cash on Delivery</span>
                            </label>
                            <label class="flex items-center gap-3 p-3.5 border rounded-lg cursor-pointer transition-colors" :class="payment === 'bank' ? 'border-primary bg-primary-container/10' : 'border-outline-variant hover:bg-surface-container-low'">
                                <input type="radio" name="payment_method" value="bank" x-model="payment" class="accent-primary-container">
                                <span class="font-bold">Direct bank transfer</span>
                            </label>
                            <p x-show="payment === 'bank'" x-cloak class="text-label-sm text-on-surface-variant px-1">Pay into our bank account and use your order number as the reference.</p>
                            @error('payment_method')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                        </div>

                        <label class="flex items-start gap-2.5 mb-4 text-label-sm leading-tight text-on-surface-variant cursor-pointer">
                            <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 w-4 h-4 rounded border-outline-variant accent-primary-container shrink-0">
                            <span>I have read and agree to the website <a href="#" class="text-primary hover:underline font-bold">terms and conditions *</a></span>
                        </label>
                        @error('terms')<p class="text-error text-label-sm -mt-3 mb-3">{{ $message }}</p>@enderror

                        <button type="submit" class="w-full bg-primary-container text-on-primary-container py-3.5 rounded-full font-bold shadow-lg hover:brightness-95 active:scale-[0.98] transition-all">
                            Place order · {{ format_money($total) }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function checkout() {
                return {
                    addresses: @js((auth()->check() ? $addresses : collect())->keyBy('id')->map($adData)),
                    mode: '{{ (auth()->check() && $addresses->isNotEmpty()) ? 'saved' : 'new' }}',
                    selected: null,
                    shipDifferent: {{ old('ship_to_different') ? 'true' : 'false' }},
                    payment: @js(old('payment_method', 'cod')),
                    country: @js(old('country', 'Pakistan')),
                    phoneRest: '',
                    shipPhoneRest: '',
                    init() {
                        @auth
                            @if ($addresses->isNotEmpty())
                                this.mode = @js(old('address_mode', 'saved'));
                                if (this.mode === 'saved') {
                                    const id = {{ (int) old('selected_address') }} || {{ (int) (optional($default)->id ?? 0) }};
                                    if (this.addresses[id]) this.pick(this.addresses[id]);
                                }
                            @endif
                        @endauth
                        @if (old('phone')) this.seedPhone(@js(old('phone'))); @endif
                        @if (old('ship_phone')) this.shipPhoneRest = this.fmtPhone(@js(old('ship_phone'))); @endif
                    },
                    pick(a) { this.selected = a.id; this.mode = 'saved'; this.fill(a); },
                    useNew() { this.mode = 'new'; this.selected = null; this.clear(); },
                    fill(a) {
                        const p = (a.name || '').trim().split(/\s+/);
                        this.$refs.fname.value = p.shift() || '';
                        this.$refs.lname.value = p.join(' ');
                        this.seedPhone(a.phone || '');
                        this.$refs.line1.value = a.line1 || '';
                        this.$refs.line2.value = a.line2 || '';
                        this.$refs.city.value = a.city || '';
                        this.$refs.zip.value = a.zip || '';
                        this.setSelect(this.$refs.state, a.state);
                        this.country = a.country || 'Pakistan';
                    },
                    clear() {
                        ['fname', 'lname', 'line1', 'line2', 'city', 'zip'].forEach((r) => { if (this.$refs[r]) this.$refs[r].value = ''; });
                        if (this.$refs.state) this.$refs.state.value = '';
                        this.phoneRest = ''; this.country = 'Pakistan';
                    },
                    setSelect(sel, val) { if (!sel || !val) return; const o = [...sel.options].find((o) => o.value === val || o.text === val); if (o) sel.value = o.value; },
                    fmtPhone(v) { const d = (v || '').replace(/\D/g, '').slice(0, 9); return d.length > 2 ? d.slice(0, 2) + '-' + d.slice(2) : d; },
                    fullPhone(rest) { const d = (rest || '').replace(/\D/g, ''); return d ? (d.length > 2 ? '03' + d.slice(0, 2) + '-' + d.slice(2) : '03' + d) : ''; },
                    seedPhone(full) { const d = (full || '').replace(/\D/g, ''); const r = d.startsWith('03') ? d.slice(2) : d; this.phoneRest = this.fmtPhone(r); },
                    get phoneFull() { return this.fullPhone(this.phoneRest); },
                    get shipPhoneFull() { return this.fullPhone(this.shipPhoneRest); },
                };
            }
        </script>
    @endpush

    <x-storefront.brand-strip />

    <x-storefront.product-columns :columns="[
        ['title' => 'Featured Products', 'items' => $featured, 'rating' => null],
        ['title' => 'Top Selling Products', 'items' => $topSelling, 'rating' => null],
        ['title' => 'On-sale Products', 'items' => $onSale, 'rating' => 5],
    ]" />
@endsection
