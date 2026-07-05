{{-- Storefront mobile bottom navigation (hidden on md+). --}}
@php
    $cartCount = app(\App\Services\CartService::class)->count();
    $wishlistCount = app(\App\Services\WishlistService::class)->count();

    // null marks the centre slot, filled by the raised Cart button below.
    $items = [
        ['url' => route('home'), 'match' => 'home', 'icon' => 'home', 'label' => 'Home'],
        ['url' => route('shop'), 'match' => 'shop', 'icon' => 'storefront', 'label' => 'Shop'],
        null,
        ['url' => route('wishlist'), 'match' => 'wishlist', 'icon' => 'favorite', 'label' => 'Wishlist', 'badge' => $wishlistCount],
        ['url' => auth()->check() ? route('account') : route('login'), 'match' => 'account*', 'icon' => 'person', 'label' => 'Account', 'avatar' => auth()->check() ? auth()->user()->avatar_url : null],
    ];
@endphp

<nav class="md:hidden fixed inset-x-0 bottom-0 z-40 bg-surface-container-lowest border-t border-outline-variant/50 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] pb-[env(safe-area-inset-bottom)] print:hidden"
    aria-label="Primary mobile">
    <div class="grid grid-cols-5 h-16">
        @foreach ($items as $item)
            @if ($item === null)
                {{-- Cart — raised primary action --}}
                <div class="relative">
                    <a href="{{ route('cart') }}" aria-label="Cart"
                        class="absolute left-1/2 -translate-x-1/2 -top-5 w-14 h-14 rounded-full bg-primary-container text-on-primary-container grid place-items-center shadow-lg shadow-primary/25 ring-4 ring-surface-container-lowest hover:brightness-105 active:scale-95 transition">
                        <span class="material-symbols-outlined text-[26px]">shopping_cart</span>
                        @if ($cartCount)
                            <span class="absolute -top-0.5 -right-0.5 min-w-5 h-5 px-1 rounded-full bg-error text-white text-[10px] font-bold grid place-items-center ring-2 ring-surface-container-lowest">{{ $cartCount }}</span>
                        @endif
                    </a>
                    <span class="absolute inset-x-0 bottom-1.5 text-center text-[10px] font-medium text-on-surface-variant pointer-events-none">Cart</span>
                </div>
            @else
                @php $on = request()->routeIs($item['match']); @endphp
                <a href="{{ $item['url'] }}" @class(['flex flex-col items-center justify-center gap-1 transition-colors', 'text-primary' => $on, 'text-on-surface-variant' => ! $on])>
                    <span @class(['relative grid place-items-center h-7 w-14 rounded-full transition-colors', 'bg-primary-container/30' => $on])>
                        @if (! empty($item['avatar']))
                            <img src="{{ $item['avatar'] }}" alt="" @class(['w-6 h-6 rounded-full object-cover', 'ring-2 ring-primary' => $on])>
                        @else
                            <span class="material-symbols-outlined text-[22px]" style="{{ $on ? "font-variation-settings:'FILL' 1" : '' }}">{{ $item['icon'] }}</span>
                        @endif
                        @if (! empty($item['badge']))
                            <span class="absolute top-0 right-2.5 min-w-4 h-4 px-1 rounded-full bg-error text-white text-[9px] font-bold grid place-items-center ring-2 ring-surface-container-lowest">{{ $item['badge'] }}</span>
                        @endif
                    </span>
                    <span class="text-[10px] font-medium leading-none">{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </div>
</nav>
