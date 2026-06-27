@extends('layouts.storefront')

@section('title', 'Checkout — ' . config('app.name'))

@php
    $field = 'w-full px-4 py-3 border border-outline-variant rounded bg-white focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none transition-colors text-body-base';
    $label = 'block mb-2 font-semibold text-body-base text-on-surface';
@endphp

@section('content')
    <div class="bg-background py-12">
        <div class="app-container">
            {{-- Breadcrumbs --}}
            <nav class="flex items-center gap-2 text-label-sm text-on-surface-variant mb-8" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                <span class="font-bold text-on-surface">Checkout</span>
            </nav>

            <h1 class="text-headline-lg font-medium mb-8">Checkout</h1>

            {{-- Notice bars --}}
            <div class="space-y-4 mb-10">
                <div class="bg-primary-container text-on-primary-container p-4 flex flex-wrap items-center gap-2 rounded font-medium">
                    <span class="material-symbols-outlined">info</span>
                    Returning customer? <a href="{{ route('login') }}" class="underline hover:no-underline font-bold">Click here to login</a>
                </div>
                <div class="bg-primary-container text-on-primary-container rounded" x-data="{ couponOpen: false }">
                    <button type="button" @click="couponOpen = !couponOpen" class="w-full p-4 flex flex-wrap items-center gap-2 font-medium text-left">
                        <span class="material-symbols-outlined">confirmation_number</span>
                        Have a coupon? <span class="underline hover:no-underline font-bold">Click here to enter your code</span>
                    </button>
                    <div x-show="couponOpen" x-transition x-cloak class="px-4 pb-4">
                        <div class="bg-white rounded p-4 flex flex-col sm:flex-row gap-3">
                            <input type="text" placeholder="Coupon code" class="{{ $field }} flex-1">
                            <button type="button" class="bg-inverse-surface text-inverse-on-surface px-6 py-3 rounded font-bold hover:opacity-90 transition-opacity whitespace-nowrap">Apply coupon</button>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('error'))
                <div class="mb-6 p-4 rounded bg-error-container/40 text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-error">error</span> {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('checkout.store') }}" class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                @csrf
                {{-- ===================== Billing & shipping ===================== --}}
                <div class="lg:col-span-7">
                    <h2 class="text-headline-md font-medium border-b border-outline-variant pb-4 mb-8">Billing details</h2>
                    @php $err = fn ($f) => $errors->has($f) ? '<p class="text-error text-label-sm mt-1">' . e($errors->first($f)) . '</p>' : ''; @endphp
                    <div class="space-y-6">
                        <div>
                            <label class="{{ $label }}" for="email">Email address *</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" placeholder="email@example.com" class="{{ $field }}">
                            {!! $err('email') !!}
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="{{ $label }}" for="fname">First name *</label>
                                <input id="fname" name="first_name" type="text" value="{{ old('first_name') }}" class="{{ $field }}">
                                {!! $err('first_name') !!}
                            </div>
                            <div>
                                <label class="{{ $label }}" for="lname">Last name *</label>
                                <input id="lname" name="last_name" type="text" value="{{ old('last_name') }}" class="{{ $field }}">
                                {!! $err('last_name') !!}
                            </div>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="company">Company name (optional)</label>
                            <input id="company" name="company" type="text" value="{{ old('company') }}" class="{{ $field }}">
                        </div>
                        <div>
                            <label class="{{ $label }}" for="country">Country / Region *</label>
                            <select id="country" name="country" class="{{ $field }}">
                                @foreach (['Pakistan', 'United States (US)', 'United Kingdom (UK)', 'United Arab Emirates'] as $c)
                                    <option @selected(old('country', 'Pakistan') === $c)>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Street address *</label>
                            <div class="space-y-3">
                                <input type="text" name="line1" value="{{ old('line1') }}" placeholder="House number and street name" class="{{ $field }}">
                                <input type="text" name="line2" value="{{ old('line2') }}" placeholder="Apartment, suite, unit, etc. (optional)" class="{{ $field }}">
                            </div>
                            {!! $err('line1') !!}
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="{{ $label }}" for="city">Town / City *</label>
                                <input id="city" name="city" type="text" value="{{ old('city') }}" class="{{ $field }}">
                                {!! $err('city') !!}
                            </div>
                            <div>
                                <label class="{{ $label }}" for="state">Province / State *</label>
                                <select id="state" name="state" class="{{ $field }}">
                                    @foreach (['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Islamabad Capital Territory'] as $s)
                                        <option @selected(old('state') === $s)>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="{{ $label }}" for="zip">ZIP / Postal code</label>
                                <input id="zip" name="zip" type="text" value="{{ old('zip') }}" class="{{ $field }}">
                            </div>
                            <div>
                                <label class="{{ $label }}" for="phone">Phone *</label>
                                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" class="{{ $field }}">
                                {!! $err('phone') !!}
                            </div>
                        </div>
                    </div>

                    {{-- Shipping --}}
                    <div class="pt-10">
                        <h2 class="text-headline-md font-medium border-b border-outline-variant pb-4 mb-8">Shipping details</h2>
                        <label class="flex items-center gap-3 mb-6 font-medium cursor-pointer">
                            <input type="checkbox" class="w-4 h-4 rounded border-outline-variant accent-primary-container"> Ship to a different address?
                        </label>
                        <div>
                            <label class="{{ $label }}" for="notes">Order notes (optional)</label>
                            <textarea id="notes" name="notes" rows="4" placeholder="Notes about your order, e.g. special notes for delivery." class="{{ $field }}">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- ===================== Order summary ===================== --}}
                <div class="lg:col-span-5">
                    <div class="bg-white border border-outline-variant p-6 lg:p-8 shadow-sm" x-data="{ payment: '{{ old('payment_method', 'cod') }}' }">
                        <h2 class="text-headline-md font-medium mb-6">Your order</h2>

                        {{-- Items --}}
                        <div class="border-b border-outline-variant pb-4 mb-4">
                            <div class="flex justify-between font-bold text-on-surface-variant mb-4">
                                <span>Product</span>
                                <span>Subtotal</span>
                            </div>
                            <div class="space-y-5">
                                @foreach ($items as $item)
                                    <div class="flex justify-between items-start gap-4">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-product-title">{{ $item->name }} <span class="text-on-surface-variant">&times; {{ $item->qty }}</span></span>
                                            <span class="text-label-sm text-on-surface-variant">{{ $item->sku }}</span>
                                        </div>
                                        <span class="font-medium whitespace-nowrap">Rs {{ number_format($item->line_total) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Totals --}}
                        <div class="space-y-4 border-b border-outline-variant pb-6 mb-6">
                            <div class="flex justify-between">
                                <span class="font-medium">Subtotal</span>
                                <span class="font-bold">Rs {{ number_format($subtotal) }}</span>
                            </div>
                            <div class="flex justify-between items-start">
                                <span class="font-medium">Shipping</span>
                                <div class="text-right">
                                    <span class="text-body-base text-on-surface-variant block">Flat rate</span>
                                    <span class="font-bold">Rs {{ number_format($shipping) }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between pt-2">
                                <span class="text-headline-md font-bold">Total</span>
                                <span class="text-headline-md font-bold text-primary">Rs {{ number_format($total) }}</span>
                            </div>
                        </div>

                        {{-- Payment methods --}}
                        <div class="space-y-3 mb-6">
                            <label class="flex items-center gap-3 p-4 border rounded cursor-pointer transition-colors"
                                :class="payment === 'cod' ? 'border-primary bg-surface-container-low' : 'border-outline-variant hover:bg-surface-container-low'">
                                <input type="radio" name="payment_method" value="cod" x-model="payment" class="accent-primary-container">
                                <span class="font-bold">Cash on Delivery</span>
                            </label>
                            <label class="flex items-center gap-3 p-4 border rounded cursor-pointer transition-colors"
                                :class="payment === 'bank' ? 'border-primary bg-surface-container-low' : 'border-outline-variant hover:bg-surface-container-low'">
                                <input type="radio" name="payment_method" value="bank" x-model="payment" class="accent-primary-container">
                                <span class="font-bold">Direct bank transfer</span>
                            </label>
                            <p x-show="payment === 'bank'" x-cloak class="text-label-sm text-on-surface-variant px-1">Make your payment directly into our bank account and use your order number as the reference.</p>
                            @error('payment_method')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                        </div>

                        {{-- Terms --}}
                        <label class="flex items-start gap-3 mb-2 text-label-sm leading-tight text-on-surface-variant cursor-pointer">
                            <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 w-4 h-4 rounded border-outline-variant accent-primary-container shrink-0">
                            <span>I have read and agree to the website <a href="#" class="text-primary hover:underline font-bold">terms and conditions *</a></span>
                        </label>
                        @error('terms')<p class="text-error text-label-sm mb-4">{{ $message }}</p>@enderror

                        <button type="submit" class="w-full bg-primary-container text-on-primary-container py-4 rounded-full font-bold text-headline-md shadow-lg hover:brightness-95 active:scale-[0.98] transition-all">
                            Place order
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <x-storefront.brand-strip />

    <x-storefront.product-columns :columns="[
        ['title' => 'Featured Products', 'items' => $featured, 'rating' => null],
        ['title' => 'Top Selling Products', 'items' => $topSelling, 'rating' => null],
        ['title' => 'On-sale Products', 'items' => $onSale, 'rating' => 5],
    ]" />
@endsection
