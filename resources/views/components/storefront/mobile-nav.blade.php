{{-- Storefront mobile bottom navigation (hidden on md+). --}}
@php
    $cartCount = app(\App\Services\CartService::class)->count();
    $wishlistCount = app(\App\Services\WishlistService::class)->count();
    $accountUrl = auth()->check() ? route('account') : route('login');
@endphp

<nav class="md:hidden fixed inset-x-0 bottom-0 z-40 h-16 bg-surface-container-lowest border-t border-outline-variant/60 shadow-[0_-2px_12px_rgba(0,0,0,0.08)] print:hidden"
    aria-label="Primary mobile">
    <div class="grid grid-cols-5 h-full text-on-surface-variant">

        {{-- Home --}}
        <a href="{{ route('home') }}" @class(['flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium transition-colors', 'text-primary' => request()->routeIs('home')])>
            <span class="material-symbols-outlined text-[24px]">home</span>
            Home
        </a>

        {{-- Shop --}}
        <a href="{{ route('shop') }}" @class(['flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium transition-colors', 'text-primary' => request()->routeIs('shop')])>
            <span class="material-symbols-outlined text-[24px]">storefront</span>
            Shop
        </a>

        {{-- Cart — raised primary action --}}
        <div class="relative">
            <a href="{{ route('cart') }}" aria-label="Cart"
                class="absolute left-1/2 -translate-x-1/2 -top-5 w-14 h-14 rounded-full bg-primary-container text-on-primary-container grid place-items-center shadow-lg ring-4 ring-surface-container-lowest hover:brightness-105 active:scale-95 transition">
                <span class="material-symbols-outlined text-[26px]">shopping_cart</span>
                @if ($cartCount)
                    <span class="absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-error text-white text-[10px] font-bold grid place-items-center ring-2 ring-surface-container-lowest">{{ $cartCount }}</span>
                @endif
            </a>
        </div>

        {{-- Wishlist --}}
        <a href="{{ route('wishlist') }}" @class(['flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium transition-colors', 'text-primary' => request()->routeIs('wishlist')])>
            <span class="relative">
                <span class="material-symbols-outlined text-[24px]">favorite</span>
                @if ($wishlistCount)
                    <span class="absolute -top-1.5 -right-2 min-w-4 h-4 px-1 rounded-full bg-error text-white text-[9px] font-bold grid place-items-center">{{ $wishlistCount }}</span>
                @endif
            </span>
            Wishlist
        </a>

        {{-- Account --}}
        <a href="{{ $accountUrl }}" @class(['flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium transition-colors', 'text-primary' => request()->routeIs('account')])>
            <span class="material-symbols-outlined text-[24px]">person</span>
            Account
        </a>
    </div>
</nav>
