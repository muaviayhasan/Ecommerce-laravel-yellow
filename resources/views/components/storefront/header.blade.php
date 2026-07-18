@php
    // Live taxonomy from the catalog (managed in Admin → Categories). Roots with
    // their active children are eager-loaded; the storefront nav is data-driven so
    // it always mirrors the real category tree.
    $rootCategories = \App\Models\Category::query()
        ->where('is_active', true)
        ->whereNull('parent_id')
        ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();

    // Departments for the inline yellow bar: a root's children when it has any,
    // otherwise the root itself — so a single "Electronics" root still surfaces
    // Coolers / Geysers / Fans / … as top-level links. Adapts to any tree shape.
    $navDepartments = $rootCategories
        ->flatMap(fn ($root) => $root->children->isNotEmpty() ? $root->children : collect([$root]))
        ->take(8);

    // Material Symbol per category slug for the Browse dropdown / mobile menu.
    $categoryIcons = [
        'electronics' => 'devices', 'coolers' => 'ac_unit', 'air-cooler' => 'air',
        'water-cooler' => 'water_drop', 'geysers' => 'water_heater', 'instant-geysers' => 'bolt',
        'electric-geysers' => 'electric_bolt', 'gas-geysers' => 'local_fire_department',
        'fans' => 'mode_fan', 'ac-fans' => 'mode_fan', 'dc-fans' => 'mode_fan',
        'home-appliances' => 'kitchen', 'washing-machine' => 'local_laundry_service',
        'water-dispenser' => 'water_drop', 'stoves' => 'stove', 'solar-plates' => 'solar_power',
    ];
    $iconFor = fn ($slug) => $categoryIcons[$slug] ?? 'category';

    $cart = app(\App\Services\CartService::class);
    $cartCount = $cart->count();
    $cartTotal = 'Rs ' . number_format($cart->subtotal());
    $wishlistCount = app(\App\Services\WishlistService::class)->count();
@endphp

<div x-data="{ mobileMenu: false, logoutConfirm: false }">
    {{-- Top bar (hidden on mobile — its links live in the main header / bottom nav / drawer) --}}
    <div class="hidden md:block bg-surface border-b border-outline-variant">
        <div class="app-container flex items-center justify-between py-2 text-label-sm text-on-surface-variant">
            <span class="hidden lg:block">Welcome to {{ config('app.name') }} — Home Appliances &amp; Electronics Store</span>
            <nav class="flex items-center gap-5" aria-label="Utility">
                <a class="hidden md:flex items-center gap-1 hover:text-primary transition-colors" href="{{ route('quote.request') }}">
                    <span class="material-symbols-outlined text-[16px]">request_quote</span>
                    <span>Get Quotation</span>
                </a>
                <a class="hidden md:flex items-center gap-1 hover:text-primary transition-colors" href="{{ route('about') }}">
                    <span class="material-symbols-outlined text-[16px]">info</span>
                    <span>About Us</span>
                </a>
                <a class="hidden md:flex items-center gap-1 hover:text-primary transition-colors" href="{{ route('contact') }}">
                    <span class="material-symbols-outlined text-[16px]">mail</span>
                    <span>Contact Us</span>
                </a>
                <a class="hidden md:flex items-center gap-1 hover:text-primary transition-colors" href="{{ route('blog') }}">
                    <span class="material-symbols-outlined text-[16px]">article</span>
                    <span>Blog</span>
                </a>
                <a class="flex items-center gap-1 hover:text-primary transition-colors" href="{{ route('track.order') }}">
                    <span class="material-symbols-outlined text-[16px]">local_shipping</span>
                    <span class="hidden md:inline">Track Order</span>
                </a>
                @auth
                    @if (auth()->user()->isStaff())
                        <a class="flex items-center gap-1 text-primary font-medium hover:opacity-80 transition-opacity" href="{{ route('admin.dashboard') }}" title="Go to admin panel">
                            <span class="material-symbols-outlined text-[16px]">admin_panel_settings</span>
                            <span class="hidden md:inline">Admin</span>
                        </a>
                    @endif
                    <a class="flex items-center gap-1 hover:text-primary transition-colors" href="{{ route('account') }}">
                        @if (auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-6 h-6 rounded-full object-cover">
                        @else
                            <span class="material-symbols-outlined text-[16px]">person</span>
                        @endif
                        <span class="hidden md:inline">My Account</span>
                    </a>
                    <button type="button" @click="logoutConfirm = true" class="flex items-center gap-1 hover:text-primary transition-colors">
                        <span class="material-symbols-outlined text-[16px]">logout</span>
                        <span class="hidden md:inline">Logout</span>
                    </button>
                @else
                    <a class="flex items-center gap-1 hover:text-primary transition-colors" href="{{ route('login') }}">
                        <span class="material-symbols-outlined text-[16px]">person</span>
                        <span class="hidden md:inline">Login</span>
                    </a>
                @endauth
            </nav>
        </div>
    </div>

    {{-- Main header --}}
    <header class="bg-surface sticky top-0 z-40 shadow-sm">
        <div class="app-container flex items-center justify-between gap-8 py-5">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" class="md:hidden shrink-0" aria-label="Open menu" @click="mobileMenu = true">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <a class="flex items-baseline min-w-0 font-bold text-on-surface" href="{{ route('home') }}">
                    @if ($logo = logo_url())
                        <img src="{{ $logo }}" alt="{{ config('app.name') }}" class="h-9 sm:h-10 lg:h-12 w-auto max-w-full object-contain self-center">
                    @else
                        <span class="truncate text-xl sm:text-2xl lg:text-headline-lg">{{ config('app.name') }}</span>
                        <span class="shrink-0 text-xl sm:text-2xl lg:text-headline-lg text-primary-container">.</span>
                    @endif
                </a>
            </div>

            {{-- Search --}}
            @once
                {{-- Strip Select2's box so the category picker blends into the search pill
                     while staying searchable. Scoped to .cat-select2 to affect only this one. --}}
                <style>
                    .cat-select2 .select2-container { width: auto !important; min-width: 8.5rem; }
                    .cat-select2 .select2-container--default .select2-selection--single {
                        background: transparent;
                        border: 0;
                        border-radius: 0;
                        height: auto;
                        display: flex;
                        align-items: center;
                        outline: none;
                    }
                    .cat-select2 .select2-container--default .select2-selection--single .select2-selection__rendered {
                        color: inherit;
                        padding: 0 1.75rem 0 0;
                        line-height: 1.4;
                        font-size: 0.8125rem;
                    }
                    .cat-select2 .select2-container--default .select2-selection--single .select2-selection__arrow {
                        height: 100%;
                        right: 0.25rem;
                    }
                </style>
            @endonce
            <form action="{{ route('shop') }}" method="GET" role="search" class="hidden md:flex flex-1 max-w-2xl">
                <div class="flex w-full border-2 border-primary-container rounded-full overflow-hidden focus-within:shadow-md transition-shadow">
                    <input name="q" value="{{ request('q') }}" type="search" aria-label="Search for products"
                        placeholder="Search for Products"
                        class="flex-1 px-6 py-2 outline-none border-none text-body-base bg-surface-bright">
                    <div class="cat-select2 flex items-center pl-4 border-l border-outline-variant bg-surface-bright">
                        <select name="category" aria-label="Category"
                            class="bg-transparent border-none outline-none text-label-sm cursor-pointer">
                            <option value="" @selected(! request('category'))>All Categories</option>
                            @foreach ($rootCategories as $root)
                                <option value="{{ $root->slug }}" @selected(request('category') == $root->slug)>{{ $root->name }}</option>
                                @foreach ($root->children as $child)
                                    <option value="{{ $child->slug }}" @selected(request('category') == $child->slug)>&nbsp;&nbsp;— {{ $child->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" aria-label="Search"
                        class="bg-primary-container px-6 flex items-center justify-center hover:opacity-90 transition-all">
                        <span class="material-symbols-outlined">search</span>
                    </button>
                </div>
            </form>

            {{-- Actions --}}
            <div class="flex items-center gap-5 sm:gap-6 shrink-0">
                <div class="hidden lg:flex items-center gap-4">
                    <a class="hover:text-primary transition-colors" href="{{ route('compare') }}" aria-label="Compare">
                        <span class="material-symbols-outlined">sync</span>
                    </a>
                    <a class="relative hover:text-primary transition-colors" href="{{ route('wishlist') }}"
                        aria-label="Wishlist">
                        <span class="material-symbols-outlined">favorite</span>
                        <span class="absolute -top-2 -right-2 bg-primary-container text-[10px] w-4 h-4 flex items-center justify-center rounded-full font-bold">{{ $wishlistCount }}</span>
                    </a>
                </div>
                {{-- Cart: desktop/tablet only — on mobile the bottom nav carries the cart --}}
                <a class="hidden md:flex items-center gap-3 group" href="{{ route('cart') }}" aria-label="Cart">
                    <div class="relative">
                        <span class="material-symbols-outlined text-3xl">shopping_cart</span>
                        <span class="absolute -top-2 -right-2 bg-primary-container text-[10px] w-4 h-4 flex items-center justify-center rounded-full font-bold">{{ $cartCount }}</span>
                    </div>
                    <div class="hidden sm:block">
                        <div class="text-[10px] text-on-surface-variant font-medium">Cart</div>
                        <div class="text-body-base font-bold">{{ $cartTotal }}</div>
                    </div>
                </a>
                {{-- Mobile: login (guest) / logout (signed in) — replaces the cart on small screens --}}
                <div class="md:hidden">
                    @auth
                        <button type="button" @click="logoutConfirm = true" aria-label="Log out"
                            class="flex items-center gap-1.5 text-on-surface hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-[28px]">logout</span>
                            <span class="text-sm font-semibold">Logout</span>
                        </button>
                    @else
                        <a href="{{ route('login') }}" aria-label="Login"
                            class="flex items-center gap-1.5 text-on-surface hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-[28px]">person</span>
                            <span class="text-sm font-semibold">Login</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>

        {{-- Category nav (desktop) --}}
        <nav class="hidden md:block bg-primary-container" aria-label="Categories">
            <div class="app-container">
                <ul class="flex items-center gap-6 py-3 text-label-sm font-bold uppercase tracking-tight no-scrollbar overflow-x-auto">
                    <li class="shrink-0">
                        <a href="{{ route('home') }}"
                            class="pb-1 border-b-2 {{ request()->routeIs('home') ? 'border-on-primary-container' : 'border-transparent' }} hover:text-on-primary-container transition-colors">Home</a>
                    </li>
                    {{-- Root categories (e.g. Electronics) — links to the whole department --}}
                    @foreach ($rootCategories as $root)
                        <li class="shrink-0">
                            <a href="{{ route('shop', ['category' => $root->slug]) }}"
                                class="pb-1 border-b-2 {{ request('category') == $root->slug ? 'border-on-primary-container' : 'border-transparent' }} hover:text-on-primary-container transition-colors">{{ $root->name }}</a>
                        </li>
                    @endforeach
                    @foreach ($navDepartments as $department)
                        <li class="shrink-0">
                            <a href="{{ route('shop', ['category' => $department->slug]) }}"
                                class="pb-1 border-b-2 {{ request('category') == $department->slug ? 'border-on-primary-container' : 'border-transparent' }} hover:text-on-primary-container transition-colors">{{ $department->name }}</a>
                        </li>
                    @endforeach
                    <li class="shrink-0">
                        <a href="{{ route('blog') }}"
                            class="pb-1 border-b-2 {{ request()->routeIs('blog*') ? 'border-on-primary-container' : 'border-transparent' }} hover:text-on-primary-container transition-colors">Blog</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    {{-- Mobile drawer --}}
    <div x-cloak x-show="mobileMenu" class="fixed inset-0 z-50 md:hidden" @keydown.escape.window="mobileMenu = false">
        <div class="absolute inset-0 bg-black/50" @click="mobileMenu = false" x-transition.opacity></div>
        <div class="absolute left-0 top-0 h-full w-80 max-w-[85%] bg-surface-container-lowest shadow-xl overflow-y-auto"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
            <div class="flex items-center justify-between p-4 border-b border-outline-variant">
                <span class="text-headline-md font-bold">Menu</span>
                <button type="button" aria-label="Close menu" @click="mobileMenu = false">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('shop') }}" method="GET" role="search" class="p-4 border-b border-outline-variant">
                <div class="flex border-2 border-primary-container rounded-full overflow-hidden">
                    <input name="q" type="search" placeholder="Search for Products"
                        class="flex-1 px-4 py-2 outline-none text-body-base bg-surface-bright" aria-label="Search">
                    <button type="submit" class="bg-primary-container px-4" aria-label="Search">
                        <span class="material-symbols-outlined">search</span>
                    </button>
                </div>
            </form>
            <ul class="p-2">
                <li><a href="{{ route('home') }}" class="block px-4 py-3 rounded hover:bg-surface-container font-bold">Home</a></li>
                @foreach ($rootCategories as $root)
                    <li>
                        <a href="{{ route('shop', ['category' => $root->slug]) }}" class="flex items-center gap-3 px-4 py-3 rounded hover:bg-surface-container font-bold">
                            <span class="material-symbols-outlined text-[20px] text-primary">{{ $iconFor($root->slug) }}</span>
                            {{ $root->name }}
                        </a>
                    </li>
                    @foreach ($root->children as $child)
                        <li>
                            <a href="{{ route('shop', ['category' => $child->slug]) }}" class="flex items-center gap-3 pl-11 pr-4 py-2.5 rounded hover:bg-surface-container text-on-surface-variant">
                                <span class="material-symbols-outlined text-[18px] text-outline">{{ $iconFor($child->slug) }}</span>
                                {{ $child->name }}
                            </a>
                        </li>
                    @endforeach
                @endforeach
            </ul>
            {{-- Info / action links --}}
            <ul class="p-2 border-t border-outline-variant">
                <li><a href="{{ route('quote.request') }}" class="flex items-center gap-3 px-4 py-2.5 rounded hover:bg-surface-container"><span class="material-symbols-outlined text-[20px] text-primary">request_quote</span> Get Quotation</a></li>
                <li><a href="{{ route('about') }}" class="flex items-center gap-3 px-4 py-2.5 rounded hover:bg-surface-container"><span class="material-symbols-outlined text-[20px] text-primary">info</span> About Us</a></li>
                <li><a href="{{ route('contact') }}" class="flex items-center gap-3 px-4 py-2.5 rounded hover:bg-surface-container"><span class="material-symbols-outlined text-[20px] text-primary">mail</span> Contact Us</a></li>
                <li><a href="{{ route('blog') }}" class="flex items-center gap-3 px-4 py-2.5 rounded hover:bg-surface-container"><span class="material-symbols-outlined text-[20px] text-primary">article</span> Blog</a></li>
                <li><a href="{{ route('track.order') }}" class="flex items-center gap-3 px-4 py-2.5 rounded hover:bg-surface-container"><span class="material-symbols-outlined text-[20px] text-primary">local_shipping</span> Track Order</a></li>
            </ul>
            <div class="p-4 border-t border-outline-variant flex flex-col gap-2">
                @auth
                    @if (auth()->user()->isStaff())
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-4 py-2 text-primary font-medium"><span class="material-symbols-outlined">admin_panel_settings</span> Admin panel</a>
                    @endif
                    <a href="{{ route('account') }}" class="flex items-center gap-2 px-4 py-2 hover:text-primary"><span class="material-symbols-outlined">person</span> My Account</a>
                @else
                    <a href="{{ route('login') }}" class="flex items-center gap-2 px-4 py-2 hover:text-primary"><span class="material-symbols-outlined">person</span> Login</a>
                @endauth
                <a href="{{ route('wishlist') }}" class="flex items-center gap-2 px-4 py-2 hover:text-primary"><span class="material-symbols-outlined">favorite</span> Wishlist ({{ $wishlistCount }})</a>
                @auth
                    <button type="button" @click="mobileMenu = false; logoutConfirm = true" class="flex items-center gap-2 px-4 py-2 hover:text-primary w-full text-left"><span class="material-symbols-outlined">logout</span> Logout</button>
                @endauth
            </div>
        </div>
    </div>

    @auth
        {{-- Logout confirmation. Teleported to <body> so no sticky/overflow/transform
             ancestor can clip or mis-stack it; z above the bottom nav + chat widget. --}}
        <template x-teleport="body">
            <div x-cloak x-show="logoutConfirm" class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                @keydown.escape.window="logoutConfirm = false" role="dialog" aria-modal="true">
                <div class="absolute inset-0 bg-black/50" @click="logoutConfirm = false"
                    x-show="logoutConfirm" x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></div>
                <div class="relative w-full max-w-sm bg-surface-container-lowest rounded-2xl shadow-2xl p-6 text-center"
                    x-show="logoutConfirm"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                    <div class="w-14 h-14 mx-auto rounded-full bg-error-container grid place-items-center mb-4">
                        <span class="material-symbols-outlined text-error text-[28px]">logout</span>
                    </div>
                    <h3 class="text-lg font-bold text-on-surface mb-1">Log out?</h3>
                    <p class="text-body-base text-on-surface-variant mb-6">Do you want to log out of your account?</p>
                    <div class="flex gap-3">
                        <button type="button" @click="logoutConfirm = false"
                            class="flex-1 py-2.5 border border-outline text-on-surface font-semibold rounded-full hover:bg-surface-container transition-colors">Cancel</button>
                        <button type="submit" form="header-logout-form"
                            class="flex-1 py-2.5 bg-error text-white font-bold rounded-full hover:brightness-110 active:scale-95 transition-all">Log out</button>
                    </div>
                </div>
            </div>
        </template>
        <form id="header-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
    @endauth
</div>
