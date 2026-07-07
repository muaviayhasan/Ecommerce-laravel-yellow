@extends('layouts.storefront')

@section('robots', 'noindex, follow')
@section('title', 'Track Your Order — ' . config('app.name'))
@section('meta_description', 'Check the status of your order with your order number and email.')

@section('content')
    <section class="bg-surface-container-low border-b border-outline-variant/40">
        <div class="app-container py-12 lg:py-16 text-center max-w-2xl">
            <span class="material-symbols-outlined text-5xl text-primary mb-2">local_shipping</span>
            <h1 class="text-headline-lg font-bold mb-2">Track your order</h1>
            <p class="text-body-base text-on-surface-variant">Enter your order number and the email you used at checkout to see the latest status.</p>
        </div>
    </section>

    <div class="app-container py-10 lg:py-14 max-w-3xl">
        @if (session('error'))
            <div class="mb-6 flex items-start gap-2 bg-error-container text-on-error-container px-4 py-3 rounded-lg text-label-sm">
                <span class="material-symbols-outlined text-[18px] shrink-0">error</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Lookup form --}}
        <form method="POST" action="{{ route('track.order.lookup') }}"
            class="bg-surface-container-lowest p-6 lg:p-7 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.06)] border border-outline-variant/40">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="order_number" class="block text-product-title font-semibold text-on-surface-variant">Order number <span class="text-error">*</span></label>
                    <input id="order_number" name="order_number" type="text" required maxlength="50" value="{{ old('order_number', $filters['order_number'] ?? '') }}"
                        placeholder="e.g. ORD-00042"
                        class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('order_number') border-error @else border-outline-variant @enderror">
                    @error('order_number')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                </div>
                <div class="space-y-1.5">
                    <label for="email" class="block text-product-title font-semibold text-on-surface-variant">Email <span class="text-error">*</span></label>
                    <input id="email" name="email" type="email" required maxlength="255" value="{{ old('email', $filters['email'] ?? auth()->user()->email ?? '') }}"
                        placeholder="you@example.com"
                        class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('email') border-error @else border-outline-variant @enderror">
                    @error('email')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit"
                class="mt-5 w-full sm:w-auto px-8 h-12 bg-primary-container text-on-surface font-bold rounded shadow-sm hover:brightness-105 active:scale-[0.98] transition-all inline-flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[20px]">search</span> Track order
            </button>
        </form>

        {{-- Results --}}
        @if ($searched && ! $order)
            <div class="mt-8 text-center bg-surface-container-lowest border border-outline-variant/40 rounded-xl p-10">
                <span class="material-symbols-outlined text-5xl text-outline mb-2">search_off</span>
                <h2 class="text-xl font-bold mb-1">We couldn’t find that order</h2>
                <p class="text-body-base text-on-surface-variant max-w-md mx-auto">
                    Please double-check the order number and the email you used. Still stuck?
                    <a href="{{ route('contact') }}" class="text-primary font-semibold hover:underline">Contact us</a> and we’ll help.
                </p>
            </div>
        @elseif ($order)
            <div class="mt-8 space-y-5">
                {{-- Status header --}}
                <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-xl p-5 lg:p-6 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-bold">Order #{{ $order->order_number }}</h2>
                        <p class="text-label-sm text-on-surface-variant">Placed {{ format_datetime($order->placed_at ?? $order->created_at) }}</p>
                    </div>
                    <x-storefront.order-status :status="$order->status" class="text-body-base !px-3 !py-1" />
                </div>

                {{-- Tracking --}}
                @if ($order->tracking_number)
                    <div class="bg-primary-container/20 border border-primary-container/40 rounded-xl p-5 flex flex-wrap items-center gap-x-6 gap-y-2">
                        <div>
                            <div class="text-label-sm text-on-surface-variant">Courier</div>
                            <div class="font-semibold">{{ $order->courier ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="text-label-sm text-on-surface-variant">Tracking number</div>
                            <div class="font-semibold">{{ $order->tracking_number }}</div>
                        </div>
                    </div>
                @endif

                {{-- Timeline --}}
                <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-xl p-5 lg:p-6">
                    <h3 class="font-bold mb-4">Progress</h3>
                    <ol class="relative border-l-2 border-outline-variant/50 ml-2 space-y-6">
                        @forelse ($order->statusHistory as $event)
                            <li class="ml-5">
                                <span class="absolute -left-[9px] w-4 h-4 rounded-full {{ $loop->first ? 'bg-primary' : 'bg-outline-variant' }} border-2 border-surface-container-lowest"></span>
                                <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                                    <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $event->to_status)) }}</span>
                                    <span class="text-label-sm text-on-surface-variant">{{ format_datetime($event->created_at) }}</span>
                                </div>
                                @if ($event->note)<p class="text-label-sm text-on-surface-variant mt-0.5">{{ $event->note }}</p>@endif
                            </li>
                        @empty
                            <li class="ml-5">
                                <span class="absolute -left-[9px] w-4 h-4 rounded-full bg-primary border-2 border-surface-container-lowest"></span>
                                <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                                    <span class="font-semibold">Order placed</span>
                                    <span class="text-label-sm text-on-surface-variant">{{ format_datetime($order->placed_at ?? $order->created_at) }}</span>
                                </div>
                            </li>
                        @endforelse
                    </ol>
                </div>

                {{-- Items + total --}}
                <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-xl overflow-hidden">
                    <div class="p-5 border-b border-outline-variant/40"><h3 class="font-bold">Items</h3></div>
                    <div class="divide-y divide-outline-variant/40">
                        @foreach ($order->items as $item)
                            <div class="p-4 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-medium line-clamp-2">{{ $item->name_snapshot }}</p>
                                    <p class="text-label-sm text-on-surface-variant">{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }} × {{ format_money($item->unit_price) }}</p>
                                </div>
                                <span class="font-bold whitespace-nowrap">{{ format_money($item->line_total) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="p-4 flex items-center justify-between border-t border-outline-variant/40 bg-surface-container-low/40">
                        <span class="font-bold">Total</span>
                        <span class="font-black text-lg">{{ format_money($order->grand_total) }}</span>
                    </div>
                </div>

                {{-- Reorder --}}
                <form method="POST" action="{{ route('track.order.reorder') }}" class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    @csrf
                    <input type="hidden" name="order_number" value="{{ $filters['order_number'] ?? $order->order_number }}">
                    <input type="hidden" name="email" value="{{ $filters['email'] ?? '' }}">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 bg-primary-container text-on-surface font-bold px-7 py-3 rounded-full hover:brightness-105 active:scale-[0.98] transition-all">
                        <span class="material-symbols-outlined text-[20px]">shopping_cart_checkout</span> Reorder these items
                    </button>
                    <span class="text-label-sm text-on-surface-variant">Adds the still-available items to your cart at current prices.</span>
                </form>

                @auth
                    <a href="{{ route('account.orders.show', $order) }}" class="inline-flex items-center gap-1 text-label-sm font-semibold text-primary hover:underline">
                        View full order details <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </a>
                @endauth
            </div>
        @endif
    </div>
@endsection
