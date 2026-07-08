{{-- Storefront mobile bottom navigation (hidden on md+). --}}
@php
    $cartCount = app(\App\Services\CartService::class)->count();
    $wishlistCount = app(\App\Services\WishlistService::class)->count();
    $cartOn = request()->routeIs('cart');

    // null marks the centre slot, filled by the raised Cart button below.
    $items = [
        ['url' => route('home'), 'match' => 'home', 'icon' => 'home', 'label' => 'Home'],
        ['url' => route('shop'), 'match' => 'shop', 'icon' => 'storefront', 'label' => 'Shop'],
        null,
        ['url' => route('wishlist'), 'match' => 'wishlist', 'icon' => 'favorite', 'label' => 'Wishlist', 'badge' => $wishlistCount],
        ['url' => auth()->check() ? route('account') : route('login'), 'match' => 'account*', 'icon' => 'person', 'label' => 'Account', 'avatar' => auth()->check() ? auth()->user()->avatar_url : null],
    ];
@endphp

@once
    <style>
        @keyframes navPop { 0% { transform: translateY(0) scale(.5); opacity: 0 } 100% { transform: translateY(-4px) scale(1); opacity: 1 } }
        @keyframes navBadge { from { transform: scale(0) } to { transform: scale(1) } }
        .nav-pill-on { animation: navPop .34s cubic-bezier(.34, 1.56, .64, 1) both }
        .nav-badge { animation: navBadge .3s cubic-bezier(.34, 1.56, .64, 1) both }
    </style>
@endonce

{{-- Opaque background (no backdrop-blur): a translucent backdrop-filter on a
     position:fixed bar makes iOS Safari detach it and float it to mid-screen while
     scrolling. Solid bg keeps it pinned to the bottom. --}}
<nav class="md:hidden fixed inset-x-0 bottom-0 z-40 bg-surface-container-lowest border-t border-outline-variant/40 shadow-[0_-4px_24px_rgba(0,0,0,0.1)] pb-[env(safe-area-inset-bottom)] print:hidden"
    aria-label="Primary mobile">
    <div class="grid grid-cols-5 h-16">
        @foreach ($items as $item)
            @if ($item === null)
                {{-- Cart — raised primary action --}}
                <div class="relative">
                    <a href="{{ route('cart') }}" aria-label="Cart" @if ($cartOn) aria-current="page" @endif
                        class="absolute left-1/2 -translate-x-1/2 -top-5 w-14 h-14 rounded-full bg-primary-container text-on-primary-container grid place-items-center ring-4 ring-surface-container-lowest active:scale-90 transition-all duration-300 {{ $cartOn ? 'scale-105 shadow-xl shadow-primary/45' : 'shadow-lg shadow-primary/25' }}">
                        <span class="material-symbols-outlined text-[26px]" style="{{ $cartOn ? "font-variation-settings:'FILL' 1" : '' }}">shopping_cart</span>
                        @if ($cartCount)
                            <span class="nav-badge absolute -top-0.5 -right-0.5 min-w-5 h-5 px-1 rounded-full bg-error text-white text-[10px] font-bold grid place-items-center ring-2 ring-surface-container-lowest">{{ $cartCount }}</span>
                        @endif
                    </a>
                    <span class="absolute inset-x-0 bottom-1.5 text-center text-[10px] leading-none pointer-events-none transition-colors {{ $cartOn ? 'text-primary font-bold' : 'text-on-surface-variant font-medium' }}">Cart</span>
                </div>
            @else
                @php $on = request()->routeIs($item['match']); @endphp
                <a href="{{ $item['url'] }}" @if ($on) aria-current="page" @endif
                    class="relative flex flex-col items-center justify-center gap-1 select-none">
                    <span @class([
                        'relative grid place-items-center h-8 w-16 rounded-full transition-all duration-300 ease-out active:scale-90',
                        'active:bg-primary-container/20' => ! $on,
                    ])>
                        @if (! empty($item['avatar']))
                            <img src="{{ $item['avatar'] }}" alt="" @class(['w-6 h-6 rounded-full object-cover transition-all', 'ring-2 ring-primary' => $on, 'ring-1 ring-outline-variant' => ! $on])>
                        @else
                            <span class="material-symbols-outlined transition-all {{ $on ? 'text-primary text-[24px]' : 'text-on-surface-variant text-[22px]' }}" style="{{ $on ? "font-variation-settings:'FILL' 1" : '' }}">{{ $item['icon'] }}</span>
                        @endif
                        @if (! empty($item['badge']))
                            <span class="nav-badge absolute top-0 right-2 min-w-4 h-4 px-1 rounded-full bg-error text-white text-[9px] font-bold grid place-items-center ring-2 ring-surface-container-lowest">{{ $item['badge'] }}</span>
                        @endif
                    </span>
                    <span class="text-[10px] leading-none transition-colors {{ $on ? 'text-primary font-bold' : 'text-on-surface-variant font-medium' }}">{{ $item['label'] }}</span>
                    @if ($on)
                        {{-- Active dot, pinned to the tab's bottom edge so it never shifts the icon/label. --}}
                        <span class="nav-badge absolute bottom-1 left-1/2 -translate-x-1/2 h-1.5 w-1.5 rounded-full bg-primary"></span>
                    @endif
                </a>
            @endif
        @endforeach
    </div>
</nav>
