@extends('layouts.storefront')

@section('title', 'My Account — ' . config('app.name'))
@section('hideNewsletter', '1')

@section('content')
    <x-storefront.account-shell active="dashboard">
        {{-- Welcome --}}
        <div class="bg-white rounded-lg border border-outline-variant p-6">
            <h1 class="text-2xl font-bold mb-1">Hello, {{ $user->name }} 👋</h1>
            <p class="text-on-surface-variant">Welcome back — here's a quick look at your account.</p>
        </div>

        {{-- Quick stats --}}
        <div class="grid grid-cols-3 gap-3 sm:gap-4">
            <a href="{{ route('account.orders') }}" class="bg-white rounded-lg border border-outline-variant p-4 sm:p-5 hover:border-primary hover:shadow-sm transition-all">
                <span class="material-symbols-outlined text-primary text-[26px]">receipt_long</span>
                <p class="text-2xl font-bold mt-1">{{ $orderCount }}</p>
                <p class="text-label-sm text-on-surface-variant">Orders</p>
            </a>
            <a href="{{ route('wishlist') }}" class="bg-white rounded-lg border border-outline-variant p-4 sm:p-5 hover:border-primary hover:shadow-sm transition-all">
                <span class="material-symbols-outlined text-primary text-[26px]">favorite</span>
                <p class="text-2xl font-bold mt-1">{{ $wishlistCount }}</p>
                <p class="text-label-sm text-on-surface-variant">Wishlist</p>
            </a>
            <a href="{{ route('account.addresses') }}" class="bg-white rounded-lg border border-outline-variant p-4 sm:p-5 hover:border-primary hover:shadow-sm transition-all">
                <span class="material-symbols-outlined text-primary text-[26px]">location_on</span>
                <p class="text-2xl font-bold mt-1">{{ auth()->user()->addresses()->count() }}</p>
                <p class="text-label-sm text-on-surface-variant">Addresses</p>
            </a>
        </div>

        {{-- Recent orders --}}
        <div class="bg-white rounded-lg border border-outline-variant overflow-hidden">
            <div class="p-5 border-b border-outline-variant flex justify-between items-center">
                <h2 class="font-bold text-lg">Recent Orders</h2>
                @if ($orders->isNotEmpty())
                    <a href="{{ route('account.orders') }}" class="text-primary text-label-sm font-bold hover:underline">View all</a>
                @endif
            </div>
            @forelse ($orders as $order)
                <a href="{{ route('account.orders.show', $order) }}" class="flex items-center justify-between gap-3 p-4 border-b border-outline-variant/60 last:border-b-0 hover:bg-surface-container-low/50 transition-colors">
                    <div class="min-w-0">
                        <p class="font-bold">#{{ $order->order_number }}</p>
                        <p class="text-label-sm text-on-surface-variant">{{ ($order->placed_at ?? $order->created_at)->format('d M Y') }}</p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <x-storefront.order-status :status="$order->status" />
                        <span class="font-bold whitespace-nowrap">{{ format_money($order->grand_total) }}</span>
                        <span class="material-symbols-outlined text-outline text-[20px]">chevron_right</span>
                    </div>
                </a>
            @empty
                <div class="p-10 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-gray-300" style="font-size:56px;">receipt_long</span>
                    <p class="mt-2">You haven't placed any orders yet.</p>
                    <a href="{{ route('shop') }}" class="inline-block mt-4 bg-primary-container text-on-primary-container px-6 py-2.5 font-bold rounded-full hover:brightness-105 transition">Start shopping</a>
                </div>
            @endforelse
        </div>

        {{-- Account + default address --}}
        <div class="grid sm:grid-cols-2 gap-4 sm:gap-6">
            <div class="bg-white rounded-lg border border-outline-variant p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold">Account Details</h3>
                    <a href="{{ route('account.profile') }}" class="text-primary text-label-sm font-bold hover:underline">Edit</a>
                </div>
                <p class="font-medium">{{ $user->name }}</p>
                <p class="text-body-base text-on-surface-variant">{{ $user->email }}</p>
                @if ($user->phone)<p class="text-body-base text-on-surface-variant">{{ $user->phone }}</p>@endif
            </div>
            <div class="bg-white rounded-lg border border-outline-variant p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold">Default Address</h3>
                    <a href="{{ route('account.addresses') }}" class="text-primary text-label-sm font-bold hover:underline">{{ $address ? 'Manage' : 'Add' }}</a>
                </div>
                @if ($address)
                    <p class="font-medium">{{ $address->name }}</p>
                    <p class="text-body-base text-on-surface-variant">{{ collect([$address->line1, $address->line2, $address->city, $address->state, $address->zip, $address->country])->filter()->join(', ') }}</p>
                @else
                    <p class="text-body-base text-on-surface-variant">No address saved yet.</p>
                @endif
            </div>
        </div>
    </x-storefront.account-shell>
@endsection
