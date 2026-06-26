@php
    // Placeholder taxonomy — replace with Category::active()->roots() when the
    // catalog module is wired (PROJECT_DOCUMENTATION build order). `icon` is a
    // Material Symbol name used in the Browse Categories dropdown.
    $allCategories = [
        ['name' => 'Laptops & Computers', 'icon' => 'laptop_mac'],
        ['name' => 'Smartphones & Tablets', 'icon' => 'smartphone'],
        ['name' => 'TV & Audio', 'icon' => 'tv'],
        ['name' => 'Cameras & Photography', 'icon' => 'photo_camera'],
        ['name' => 'Gaming & Consoles', 'icon' => 'sports_esports'],
        ['name' => 'Audio & Headphones', 'icon' => 'headphones'],
        ['name' => 'Wearables & Smartwatches', 'icon' => 'watch'],
        ['name' => 'Accessories', 'icon' => 'cable'],
        ['name' => 'Home Appliances', 'icon' => 'kitchen'],
    ];

    // Shorter set shown inline in the yellow bar.
    $navCategories = ['Laptops & Computers', 'Smartphones & Tablets', 'TV & Audio', 'Cameras', 'Gaming', 'Accessories'];

    $cart = app(\App\Services\CartService::class);
    $cartCount = $cart->count();
    $cartTotal = 'Rs ' . number_format($cart->subtotal());
    $wishlistCount = 0;
@endphp

<div x-data="{ mobileMenu: false }">
    {{-- Top bar --}}
    <div class="bg-surface border-b border-outline-variant">
        <div class="app-container flex items-center justify-between py-2 text-label-sm text-on-surface-variant">
            <span class="hidden sm:block">Welcome to {{ config('app.name') }} — Worldwide Electronics Store</span>
            <nav class="flex items-center gap-6" aria-label="Utility">
                <a class="flex items-center gap-1 hover:text-primary transition-colors" href="#">
                    <span class="material-symbols-outlined text-[16px]">location_on</span>
                    <span class="hidden md:inline">Store Locator</span>
                </a>
                <a class="flex items-center gap-1 hover:text-primary transition-colors" href="{{ route('track.order') }}">
                    <span class="material-symbols-outlined text-[16px]">local_shipping</span>
                    <span class="hidden md:inline">Track Your Order</span>
                </a>
                <a class="flex items-center gap-1 hover:text-primary transition-colors" href="{{ route('shop') }}">
                    <span class="material-symbols-outlined text-[16px]">shopping_bag</span>
                    <span class="hidden md:inline">Shop</span>
                </a>
                @auth
                    <a class="flex items-center gap-1 hover:text-primary transition-colors" href="{{ route('account') }}">
                        <span class="material-symbols-outlined text-[16px]">person</span>
                        <span class="hidden md:inline">My Account</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="contents">
                        @csrf
                        <button type="submit" class="flex items-center gap-1 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-[16px]">logout</span>
                            <span class="hidden md:inline">Logout</span>
                        </button>
                    </form>
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
                    <span class="truncate text-xl sm:text-2xl lg:text-headline-lg">{{ config('app.name') }}</span>
                    <span class="shrink-0 text-xl sm:text-2xl lg:text-headline-lg text-primary-container">.</span>
                </a>
            </div>

            {{-- Search --}}
            <form action="{{ route('shop') }}" method="GET" role="search" class="hidden md:flex flex-1 max-w-2xl">
                <div class="flex w-full border-2 border-primary-container rounded-full overflow-hidden focus-within:shadow-md transition-shadow">
                    <input name="q" value="{{ request('q') }}" type="search" aria-label="Search for products"
                        placeholder="Search for Products"
                        class="flex-1 px-6 py-2 outline-none border-none text-body-base bg-surface-bright">
                    <div class="flex items-center px-4 border-l border-outline-variant bg-surface-bright">
                        <select name="category" aria-label="Category"
                            class="bg-transparent border-none outline-none text-label-sm pr-4">
                            <option value="">All Categories</option>
                            @foreach ($allCategories as $category)
                                <option value="{{ \Illuminate\Support\Str::slug($category['name']) }}">{{ $category['name'] }}</option>
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
            <div class="flex items-center gap-6 shrink-0">
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
                <a class="flex items-center gap-3 group" href="{{ route('cart') }}" aria-label="Cart">
                    <div class="relative">
                        <span class="material-symbols-outlined text-3xl">shopping_cart</span>
                        <span class="absolute -top-2 -right-2 bg-primary-container text-[10px] w-4 h-4 flex items-center justify-center rounded-full font-bold">{{ $cartCount }}</span>
                    </div>
                    <div class="hidden sm:block">
                        <div class="text-[10px] text-on-surface-variant font-medium">Cart</div>
                        <div class="text-body-base font-bold">{{ $cartTotal }}</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Category nav (desktop) --}}
        <nav class="hidden md:block bg-primary-container" aria-label="Categories">
            <div class="app-container">
                <ul class="flex items-center gap-6 py-3 text-label-sm font-bold uppercase tracking-tight no-scrollbar overflow-x-auto">
                    {{-- Browse Categories dropdown --}}
                    <li class="relative shrink-0 border-r border-on-primary-container/20 pr-6" x-data="{ open: false }"
                        @click.outside="open = false" @keydown.escape="open = false">
                        <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                            class="flex items-center gap-2 hover:text-on-primary-container transition-colors">
                            <span class="material-symbols-outlined">menu</span> Browse Categories
                        </button>
                        <div x-cloak x-show="open"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="absolute left-0 top-full z-50 mt-3 w-72 rounded-lg bg-surface-container-lowest text-on-surface normal-case shadow-xl border border-outline-variant py-2">
                            @foreach ($allCategories as $category)
                                <a href="{{ route('shop') }}"
                                    class="flex items-center gap-3 px-4 py-2.5 text-body-base font-medium hover:bg-surface-container hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-[20px] text-primary">{{ $category['icon'] }}</span>
                                    {{ $category['name'] }}
                                </a>
                            @endforeach
                        </div>
                    </li>

                    <li class="shrink-0">
                        <a href="{{ route('home') }}"
                            class="pb-1 border-b-2 border-on-primary-container hover:text-on-primary-container transition-colors">Home</a>
                    </li>
                    @foreach ($navCategories as $category)
                        <li class="shrink-0">
                            <a href="{{ route('shop') }}" class="hover:text-on-primary-container transition-colors">{{ $category }}</a>
                        </li>
                    @endforeach
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
                @foreach ($allCategories as $category)
                    <li>
                        <a href="{{ route('shop') }}" class="flex items-center gap-3 px-4 py-3 rounded hover:bg-surface-container">
                            <span class="material-symbols-outlined text-[20px] text-primary">{{ $category['icon'] }}</span>
                            {{ $category['name'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="p-4 border-t border-outline-variant flex flex-col gap-2">
                @auth
                    <a href="{{ route('account') }}" class="flex items-center gap-2 px-4 py-2 hover:text-primary"><span class="material-symbols-outlined">person</span> My Account</a>
                @else
                    <a href="{{ route('login') }}" class="flex items-center gap-2 px-4 py-2 hover:text-primary"><span class="material-symbols-outlined">person</span> Login</a>
                @endauth
                <a href="{{ route('wishlist') }}" class="flex items-center gap-2 px-4 py-2 hover:text-primary"><span class="material-symbols-outlined">favorite</span> Wishlist ({{ $wishlistCount }})</a>
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 px-4 py-2 hover:text-primary w-full text-left"><span class="material-symbols-outlined">logout</span> Logout</button>
                    </form>
                @endauth
            </div>
        </div>
    </div>
</div>
