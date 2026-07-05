@extends('layouts.storefront')

@section('title', 'Order #' . $order->order_number . ' — ' . config('app.name'))
@section('hideNewsletter', '1')

@section('content')
    <x-storefront.account-shell active="orders">
        {{-- Header --}}
        <div class="bg-white rounded-lg border border-outline-variant p-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('account.orders') }}" class="text-label-sm text-primary font-bold hover:underline flex items-center gap-1 mb-1"><span class="material-symbols-outlined text-[16px]">arrow_back</span> Back to orders</a>
                <h1 class="text-xl font-bold">Order #{{ $order->order_number }}</h1>
                <p class="text-label-sm text-on-surface-variant">Placed {{ ($order->placed_at ?? $order->created_at)->format('d M Y, h:i A') }}</p>
            </div>
            <x-storefront.order-status :status="$order->status" />
        </div>

        {{-- Items --}}
        <div class="bg-white rounded-lg border border-outline-variant overflow-hidden">
            <div class="p-5 border-b border-outline-variant"><h2 class="font-bold">Items</h2></div>
            <div class="divide-y divide-outline-variant/60">
                @foreach ($order->items as $item)
                    <div class="p-4 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium line-clamp-2">{{ $item->name_snapshot }}</p>
                            <p class="text-label-sm text-on-surface-variant">{{ $item->sku_snapshot }} · {{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }} × {{ format_money($item->unit_price) }}</p>
                        </div>
                        <span class="font-bold whitespace-nowrap">{{ format_money($item->line_total) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Summary + shipping --}}
        <div class="grid sm:grid-cols-2 gap-4 sm:gap-6">
            <div class="bg-white rounded-lg border border-outline-variant p-5 space-y-2 text-body-base">
                <h3 class="font-bold mb-2">Order Summary</h3>
                <div class="flex justify-between"><span class="text-on-surface-variant">Subtotal</span><span>{{ format_money($order->subtotal) }}</span></div>
                @if ($order->discount_total > 0)<div class="flex justify-between text-secondary"><span>Discount</span><span>&minus; {{ format_money($order->discount_total) }}</span></div>@endif
                @if ($order->tax_total > 0)<div class="flex justify-between"><span class="text-on-surface-variant">Tax</span><span>{{ format_money($order->tax_total) }}</span></div>@endif
                <div class="flex justify-between"><span class="text-on-surface-variant">Shipping</span><span>{{ $order->shipping_total > 0 ? format_money($order->shipping_total) : 'Free' }}</span></div>
                <div class="flex justify-between font-black text-lg border-t border-outline-variant pt-2 mt-2"><span>Total</span><span>{{ format_money($order->grand_total) }}</span></div>
                <div class="flex justify-between text-label-sm pt-1"><span class="text-on-surface-variant">Payment</span><span class="font-medium text-right">{{ ucfirst(str_replace('_', ' ', $order->payment_method ?? '—')) }} · {{ ucfirst($order->payment_status) }}</span></div>
            </div>

            <div class="bg-white rounded-lg border border-outline-variant p-5">
                <h3 class="font-bold mb-3">Shipping</h3>
                @if ($order->shippingAddress)
                    @php $a = $order->shippingAddress; @endphp
                    <p class="font-medium">{{ $a->name }}</p>
                    @if ($a->phone)<p class="text-body-base text-on-surface-variant">{{ $a->phone }}</p>@endif
                    <p class="text-body-base text-on-surface-variant">{{ collect([$a->line1, $a->line2, $a->city, $a->state, $a->zip, $a->country])->filter()->join(', ') }}</p>
                @else
                    <p class="text-body-base text-on-surface-variant">No shipping address recorded.</p>
                @endif
                @if ($order->tracking_number)
                    <div class="mt-4 pt-4 border-t border-outline-variant text-label-sm space-y-1">
                        <p class="text-on-surface-variant">Courier: <span class="text-on-surface font-medium">{{ $order->courier ?? '—' }}</span></p>
                        <p class="text-on-surface-variant">Tracking: <span class="text-on-surface font-medium">{{ $order->tracking_number }}</span></p>
                    </div>
                @endif
            </div>
        </div>
    </x-storefront.account-shell>
@endsection
