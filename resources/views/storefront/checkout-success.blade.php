@extends('layouts.storefront')
@section('robots', 'noindex, follow')

@section('title', 'Order Confirmed — ' . config('app.name'))

@php $ship = $order->addresses->firstWhere('type', 'shipping'); @endphp

@section('content')
    <div class="bg-background py-12">
        <div class="app-container max-w-3xl">
            <div class="bg-white rounded-lg border border-outline-variant p-8 lg:p-12 text-center mb-8">
                <span class="material-symbols-outlined text-secondary" style="font-size:72px; font-variation-settings: 'FILL' 1;">check_circle</span>
                <h1 class="text-headline-lg font-medium mt-4">Thank you for your order!</h1>
                <p class="text-on-surface-variant mt-2">Your order <span class="font-bold text-on-surface">{{ $order->order_number }}</span> has been placed.</p>
                <p class="text-on-surface-variant">We'll send updates as it’s processed.</p>
            </div>

            <div class="bg-white rounded-lg border border-outline-variant overflow-hidden">
                <div class="p-6 border-b border-outline-variant flex flex-wrap justify-between gap-4">
                    <div>
                        <p class="text-label-sm text-on-surface-variant uppercase tracking-wide">Order number</p>
                        <p class="font-bold">{{ $order->order_number }}</p>
                    </div>
                    <div>
                        <p class="text-label-sm text-on-surface-variant uppercase tracking-wide">Date</p>
                        <p class="font-bold">{{ $order->created_at?->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-label-sm text-on-surface-variant uppercase tracking-wide">Payment</p>
                        <p class="font-bold capitalize">{{ $order->payment_method }} · {{ $order->payment_status }}</p>
                    </div>
                </div>

                <div class="p-6 divide-y divide-outline-variant">
                    @foreach ($order->items as $item)
                        <div class="flex justify-between gap-4 py-3">
                            <span>{{ $item->name_snapshot }} <span class="text-on-surface-variant">&times; {{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</span></span>
                            <span class="font-medium whitespace-nowrap">{{ format_money($item->line_total) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="p-6 border-t border-outline-variant space-y-2 text-body-base">
                    <div class="flex justify-between"><span class="text-on-surface-variant">Subtotal</span><span>{{ format_money($order->subtotal) }}</span></div>
                    @if ((float) $order->discount_total > 0)<div class="flex justify-between"><span class="text-on-surface-variant">Discount</span><span>− {{ format_money($order->discount_total) }}</span></div>@endif
                    @if ((float) $order->tax_total > 0)<div class="flex justify-between"><span class="text-on-surface-variant">Tax</span><span>{{ format_money($order->tax_total) }}</span></div>@endif
                    <div class="flex justify-between"><span class="text-on-surface-variant">Shipping</span><span>{{ (float) $order->shipping_total > 0 ? format_money($order->shipping_total) : 'Free' }}</span></div>
                    <div class="flex justify-between text-headline-md font-bold pt-2 border-t border-outline-variant"><span>Total</span><span class="text-primary">{{ format_money($order->grand_total) }}</span></div>
                </div>

                @if ($ship)
                    <div class="p-6 border-t border-outline-variant">
                        <p class="text-label-sm text-on-surface-variant uppercase tracking-wide mb-1">Shipping to</p>
                        <p class="font-medium">{{ $ship->name }}</p>
                        <p class="text-on-surface-variant text-body-base">{{ collect([$ship->line1, $ship->line2, $ship->city, $ship->state, $ship->country])->filter()->implode(', ') }}</p>
                        <p class="text-on-surface-variant text-body-base">{{ $ship->phone }}</p>
                    </div>
                @endif
            </div>

            <div class="text-center mt-8">
                <a href="{{ route('shop') }}" class="inline-block bg-primary-container text-on-primary-container px-8 py-3 font-bold rounded hover:brightness-95 transition-all">Continue shopping</a>
            </div>
        </div>
    </div>
@endsection
