@extends('layouts.storefront')

@section('title', 'My Orders — ' . config('app.name'))
@section('hideNewsletter', '1')

@section('content')
    <x-storefront.account-shell active="orders">
        <div class="bg-white rounded-lg border border-outline-variant overflow-hidden">
            <div class="p-5 border-b border-outline-variant">
                <h1 class="text-xl font-bold">My Orders</h1>
            </div>

            @forelse ($orders as $order)
                <a href="{{ route('account.orders.show', $order) }}"
                    class="flex flex-wrap items-center justify-between gap-3 p-4 sm:p-5 border-b border-outline-variant/60 last:border-b-0 hover:bg-surface-container-low/50 transition-colors">
                    <div class="min-w-0">
                        <p class="font-bold">#{{ $order->order_number }}</p>
                        <p class="text-label-sm text-on-surface-variant">
                            {{ ($order->placed_at ?? $order->created_at)->format('d M Y') }}
                            · {{ $order->items_count ?? $order->items()->count() }} item{{ ($order->items_count ?? $order->items()->count()) === 1 ? '' : 's' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                        <x-storefront.order-status :status="$order->status" />
                        <span class="font-bold whitespace-nowrap">{{ format_money($order->grand_total) }}</span>
                        <span class="material-symbols-outlined text-outline text-[20px]">chevron_right</span>
                    </div>
                </a>
            @empty
                <div class="p-16 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-gray-300" style="font-size:64px;">receipt_long</span>
                    <p class="mt-3 text-lg font-light">You haven't placed any orders yet.</p>
                    <a href="{{ route('shop') }}" class="inline-block mt-5 bg-primary-container text-on-primary-container px-8 py-3 font-bold rounded-full hover:brightness-105 transition">Start shopping</a>
                </div>
            @endforelse
        </div>

        @if ($orders->hasPages())
            <div>{{ $orders->onEachSide(1)->links() }}</div>
        @endif
    </x-storefront.account-shell>
@endsection
