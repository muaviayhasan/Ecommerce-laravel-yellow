@extends('layouts.storefront')

@section('title', config('app.name') . ' — Electronics Marketplace')
@section('meta_description', 'Shop the latest electronics — phones, laptops, cameras, audio and more at ' . config('app.name') . '.')

@section('content')
    {{-- Hero slider --}}
    @php
        $heroSlides = [
            [
                'kicker' => 'Shop to get what you love',
                'line1' => 'TIMEPIECES THAT',
                'line2' => 'MAKE A STATEMENT',
                'tail' => 'UP TO',
                'highlight' => '40% OFF',
                'cta' => 'Start Buying',
                'image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=1000&q=80',
                'alt' => 'Premium laptop',
            ],
            [
                'kicker' => 'Power meets portability',
                'line1' => 'NEXT-GEN LAPTOPS',
                'line2' => 'BUILT FOR SPEED',
                'tail' => 'SAVE UP TO',
                'highlight' => '30% OFF',
                'cta' => 'Shop Laptops',
                'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=1000&q=80',
                'alt' => 'Modern laptop on a desk',
            ],
            [
                'kicker' => 'Hear every detail',
                'line1' => 'IMMERSIVE AUDIO',
                'line2' => 'WIRELESS FREEDOM',
                'tail' => 'STARTING AT',
                'highlight' => 'Rs 4,999',
                'cta' => 'Shop Audio',
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1000&q=80',
                'alt' => 'Wireless headphones',
            ],
        ];
    @endphp

    <section class="group relative bg-surface overflow-hidden"
        x-data="{
            current: 0,
            count: {{ count($heroSlides) }},
            timer: null,
            start() { this.stop(); this.timer = setInterval(() => this.next(), 6000); },
            stop() { if (this.timer) clearInterval(this.timer); },
            next() { this.current = (this.current + 1) % this.count; },
            prev() { this.current = (this.current - 1 + this.count) % this.count; },
            go(i) { this.current = i; },
        }"
        x-init="start()" @mouseenter="stop()" @mouseleave="start()" role="region" aria-label="Promotions" aria-roledescription="carousel">

        {{-- Track --}}
        <div class="overflow-hidden">
            <div class="flex transition-transform duration-700 ease-in-out"
                :style="`transform: translateX(-${current * 100}%)`">
                @foreach ($heroSlides as $i => $slide)
                    <div class="w-full shrink-0" role="group" aria-roledescription="slide"
                        aria-label="{{ $i + 1 }} of {{ count($heroSlides) }}">
                        <div class="app-container relative flex items-center min-h-[460px]">
                            <div class="w-full md:w-1/2 z-10 py-12">
                                <p class="text-primary font-bold uppercase tracking-widest text-label-sm mb-4">{{ $slide['kicker'] }}</p>
                                <h1 class="text-display-hero leading-tight mb-8">
                                    {{ $slide['line1'] }}<br>
                                    <span class="font-bold">{{ $slide['line2'] }}</span><br>
                                    {{ $slide['tail'] }} <span class="font-bold text-primary">{{ $slide['highlight'] }}</span>
                                </h1>
                                <a href="{{ route('shop') }}"
                                    class="inline-block bg-primary-container px-10 py-4 rounded-full font-bold text-on-surface hover:opacity-90 transition-all shadow-lg">
                                    {{ $slide['cta'] }}
                                </a>
                            </div>
                            <div class="hidden md:block absolute right-0 top-0 bottom-0 w-3/5 overflow-hidden">
                                <img class="w-full h-full object-cover object-center hero-zoom"
                                    src="{{ $slide['image'] }}" alt="{{ $slide['alt'] }}"
                                    @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Prev / Next (appear on hover, desktop) --}}
        <button type="button" @click="prev()" aria-label="Previous slide"
            class="hidden md:flex absolute left-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 items-center justify-center rounded-full bg-white/70 backdrop-blur shadow opacity-0 group-hover:opacity-100 hover:bg-primary-container transition-all">
            <span class="material-symbols-outlined">chevron_left</span>
        </button>
        <button type="button" @click="next()" aria-label="Next slide"
            class="hidden md:flex absolute right-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 items-center justify-center rounded-full bg-white/70 backdrop-blur shadow opacity-0 group-hover:opacity-100 hover:bg-primary-container transition-all">
            <span class="material-symbols-outlined">chevron_right</span>
        </button>

        {{-- Dots --}}
        <div class="absolute bottom-8 left-0 right-0 z-20">
            <div class="app-container flex gap-2">
                @foreach ($heroSlides as $i => $slide)
                    <button type="button" @click="go({{ $i }})" aria-label="Go to slide {{ $i + 1 }}"
                        class="h-1.5 rounded-full transition-all"
                        :class="current === {{ $i }} ? 'w-12 bg-primary-container' : 'w-4 bg-outline-variant hover:bg-primary'"></button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Promo grid --}}
    <section class="py-12 bg-white">
        <div class="app-container grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @php
                // Product images pulled from the electro design (public/images/promos).
                $promos = [
                    ['kicker' => 'Catch the hottest', 'title' => 'Deals', 'subtitle' => 'In Cameras', 'type' => 'shop', 'image' => '/images/promos/promo-1.png'],
                    ['kicker' => 'The New', 'title' => '360 Cameras', 'type' => 'price', 'prefix' => 'From', 'currency' => '$', 'amount' => '749', 'cents' => '99', 'image' => '/images/promos/promo-2.png'],
                    ['kicker' => 'Tablets, Smartphones', 'title' => 'And More', 'type' => 'percent', 'prefix' => 'Up to', 'amount' => '70', 'image' => '/images/promos/promo-3.png'],
                    ['kicker' => 'The New', 'title' => '360 Cameras', 'type' => 'percent', 'prefix' => 'Up to', 'amount' => '70', 'image' => '/images/promos/promo-4.png'],
                ];
            @endphp

            @foreach ($promos as $promo)
                <a href="{{ route('shop') }}"
                    class="bg-surface-container-low p-6 flex items-center gap-4 group transition-shadow hover:shadow-md">
                    {{-- Image first (left), text second (right) --}}
                    <img src="{{ $promo['image'] }}" alt="{{ $promo['title'] }}" loading="lazy"
                        class="w-24 h-24 object-contain shrink-0">
                    <div class="flex-1 min-w-0">
                        <p class="text-label-sm font-bold uppercase text-secondary">{{ $promo['kicker'] }}</p>
                        <h3 class="font-bold text-headline-md leading-tight uppercase text-on-surface">{{ $promo['title'] }}</h3>
                        @isset($promo['subtitle'])
                            <p class="text-label-sm font-bold uppercase text-secondary">{{ $promo['subtitle'] }}</p>
                        @endisset

                        <div class="flex items-center gap-2 mt-2">
                            @if ($promo['type'] === 'shop')
                                <span class="font-bold text-on-surface">Shop now</span>
                            @elseif ($promo['type'] === 'price')
                                <span class="text-label-sm uppercase text-secondary">{{ $promo['prefix'] }}</span>
                                <span class="text-headline-md font-bold text-on-surface leading-none">{{ $promo['currency'] }}{{ $promo['amount'] }}<sup class="text-[0.55em] align-super">{{ $promo['cents'] }}</sup></span>
                            @else
                                <span class="text-label-sm uppercase text-secondary leading-tight">{{ $promo['prefix'] }}</span>
                                <span class="text-headline-md font-bold text-on-surface leading-none">{{ $promo['amount'] }}<sup class="text-[0.55em] align-super">%</sup></span>
                            @endif
                            <span class="w-7 h-7 rounded-full bg-primary-container flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform">
                                <span class="material-symbols-outlined text-[18px] text-on-surface">chevron_right</span>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Info bar --}}
    <section class="pb-12 bg-white">
        <div class="app-container">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 rounded-lg border border-outline-variant divide-y lg:divide-y-0 lg:divide-x divide-outline-variant">
                @foreach ([
                    ['icon' => 'local_shipping', 'title' => 'Free Delivery', 'sub' => 'from Rs 5,000'],
                    ['icon' => 'thumb_up', 'title' => '99% Positive', 'sub' => 'Feedbacks'],
                    ['icon' => 'cached', 'title' => '365 days', 'sub' => 'for free return'],
                    ['icon' => 'account_balance_wallet', 'title' => 'Payment', 'sub' => 'Secure System'],
                    ['icon' => 'sell', 'title' => 'Only Best', 'sub' => 'Brands'],
                ] as $info)
                    <div class="flex items-center justify-center gap-4 px-6 py-6">
                        <span class="material-symbols-outlined text-primary-container text-4xl">{{ $info['icon'] }}</span>
                        <div>
                            <p class="text-body-base font-bold">{{ $info['title'] }}</p>
                            <p class="text-label-sm text-on-surface-variant">{{ $info['sub'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Featured tabs --}}
    <section class="py-12 bg-white" x-data="{ tab: 'featured' }">
        <div class="app-container">
            <div class="flex justify-center border-b border-outline-variant mb-8">
                <div class="flex gap-12 text-headline-md font-bold text-on-surface-variant">
                    @foreach (['featured' => 'Featured', 'sale' => 'On Sale', 'top' => 'Top Rated'] as $key => $label)
                        <button type="button" @click="tab = '{{ $key }}'"
                            class="pb-4 border-b-2 transition-all"
                            :class="tab === '{{ $key }}' ? 'border-primary-container text-on-surface' : 'border-transparent hover:text-on-surface'">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div x-show="tab === 'featured'" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
                @foreach ($featured as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
            <div x-show="tab === 'sale'" x-cloak class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
                @foreach ($onSale as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
            <div x-show="tab === 'top'" x-cloak class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
                @foreach ($topRated as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- Television Entertainment — full-width textured background + product slider --}}
    @php $tvSlides = $tvProducts->chunk(4)->values(); @endphp
    <section class="bg-cover bg-center bg-no-repeat"
        style="background-image: url('/assets/images/television-entertainment-bg.webp')">
        <div class="app-container py-12 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            {{-- Left: TV visual --}}
            <div class="flex justify-center">
                <img src="/assets/images/television-entertainment-tv.png" alt="Television Entertainment"
                    class="w-full max-w-xl object-contain">
            </div>

            {{-- Right: slider --}}
            <div x-data="{
                current: 0,
                count: {{ $tvSlides->count() }},
                next() { this.current = (this.current + 1) % this.count; },
                prev() { this.current = (this.current - 1 + this.count) % this.count; },
                go(i) { this.current = i; },
            }">
                {{-- Heading + arrows --}}
                <div class="flex items-end justify-between border-b border-gray-300 mb-6">
                    <h2 class="text-headline-md font-bold pb-3 -mb-px border-b-2 border-primary-container">Television Entertainment</h2>
                    <div class="flex gap-2 pb-2 text-on-surface-variant">
                        <button type="button" @click="prev()" aria-label="Previous" class="hover:text-primary transition-colors">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </button>
                        <button type="button" @click="next()" aria-label="Next" class="hover:text-primary transition-colors">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </button>
                    </div>
                </div>

                {{-- Slider track. overflow-x-clip (not hidden) so the bottom-row hover
                     panels can still drop below the grid without being clipped. --}}
                <div class="overflow-x-clip">
                    <div class="flex transition-transform duration-500 ease-in-out"
                        :style="`transform: translateX(-${current * 100}%)`">
                        @foreach ($tvSlides as $slide)
                            <div class="w-full shrink-0">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach ($slide as $product)
                                        <x-storefront.product-card-wide :product="$product" />
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Dots --}}
                <div class="flex justify-center gap-2 mt-6">
                    @foreach ($tvSlides as $i => $slide)
                        <button type="button" @click="go({{ $i }})" aria-label="Go to slide {{ $i + 1 }}"
                            class="h-2.5 rounded-full transition-all"
                            :class="current === {{ $i }} ? 'w-8 bg-primary-container' : 'w-2.5 bg-gray-300 hover:bg-gray-400'"></button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Laptops & Computers — product slider --}}
    @php $laptopSlides = $laptops->chunk(6)->values(); @endphp
    <section class="py-12 bg-white"
        x-data="{
            current: 0,
            count: {{ $laptopSlides->count() }},
            next() { this.current = (this.current + 1) % this.count; },
            prev() { this.current = (this.current - 1 + this.count) % this.count; },
            go(i) { this.current = i; },
        }">
        <div class="app-container">
            {{-- Heading + arrows --}}
            <div class="flex items-end justify-between border-b border-gray-300 mb-8">
                <h2 class="text-headline-md font-bold pb-3 -mb-px border-b-2 border-primary-container">Laptops &amp; Computers</h2>
                <div class="flex gap-2 pb-2 text-on-surface-variant">
                    <button type="button" @click="prev()" aria-label="Previous" class="hover:text-primary transition-colors">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </button>
                    <button type="button" @click="next()" aria-label="Next" class="hover:text-primary transition-colors">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                </div>
            </div>

            {{-- Slider track. overflow-x-clip so bottom-row hover panels aren't clipped. --}}
            <div class="overflow-x-clip">
                <div class="flex transition-transform duration-500 ease-in-out"
                    :style="`transform: translateX(-${current * 100}%)`">
                    @foreach ($laptopSlides as $slide)
                        <div class="w-full shrink-0">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($slide as $product)
                                    <x-storefront.product-card-wide :product="$product"
                                        class="border-l border-gray-200 hover:border-transparent" />
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Dots --}}
            <div class="flex justify-center gap-2 mt-8">
                @foreach ($laptopSlides as $i => $slide)
                    <button type="button" @click="go({{ $i }})" aria-label="Go to slide {{ $i + 1 }}"
                        class="h-2.5 rounded-full transition-all"
                        :class="current === {{ $i }} ? 'w-8 bg-primary-container' : 'w-2.5 bg-gray-300 hover:bg-gray-400'"></button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Trending --}}
    <section class="py-12 bg-white">
        <div class="app-container">
            <x-storefront.section-heading title="Trending Products" :arrows="true" />
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
                @foreach ($trending as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- Bestsellers --}}
    <section class="py-12 bg-surface-container-low">
        <div class="app-container">
            <h2 class="text-headline-md font-bold mb-8">Bestsellers</h2>
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($bestsellers as $product)
                        <x-storefront.product-line :product="$product" :boxed="true" />
                    @endforeach
                </div>
                <div class="bg-white p-6 rounded-lg border border-outline-variant flex flex-col justify-center items-center text-center">
                    <h3 class="font-bold mb-4 uppercase tracking-wider text-label-sm">Featured Product</h3>
                    <img class="w-full aspect-square object-contain mb-4" src="{{ $spotlight['image'] }}"
                        alt="{{ $spotlight['name'] }}">
                    <h4 class="text-product-title text-primary mb-2">{{ $spotlight['name'] }}</h4>
                    <p class="text-price-lg font-bold text-error">Rs {{ number_format($spotlight['price']) }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Top categories --}}
    <section class="py-12 bg-white">
        <div class="app-container">
            <h2 class="text-headline-md font-bold mb-8 border-b border-outline-variant pb-4">Top Categories this Month</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                @foreach ([
                    ['name' => 'Accessories', 'icon' => 'headphones', 'sub' => 'Cases, Chargers'],
                    ['name' => 'Laptops', 'icon' => 'laptop_mac', 'sub' => 'Ultrabooks & Gaming'],
                    ['name' => 'Smartphones', 'icon' => 'smartphone', 'sub' => 'iOS & Android'],
                    ['name' => 'TV & Audio', 'icon' => 'tv', 'sub' => 'OLED, QLED & Hi-Fi'],
                    ['name' => 'Cameras', 'icon' => 'photo_camera', 'sub' => 'DSLR & Mirrorless'],
                    ['name' => 'Gaming', 'icon' => 'sports_esports', 'sub' => 'Consoles & PC'],
                ] as $cat)
                    <a href="{{ route('shop') }}"
                        class="bg-surface p-6 rounded-lg flex flex-col items-center text-center group hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined text-5xl mb-4 text-primary">{{ $cat['icon'] }}</span>
                        <h4 class="font-bold">{{ $cat['name'] }}</h4>
                        <p class="text-label-sm text-on-surface-variant">{{ $cat['sub'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Promo banners --}}
    <section class="py-12 bg-white">
        <div class="app-container grid grid-cols-1 md:grid-cols-2 gap-8">
            @foreach ([
                ['title' => 'G9 Laptops with Ultra 4K', 'sub' => 'Fastest Intel Core i7 processor ever', 'icon' => 'laptop_windows'],
                ['title' => 'smartG3 Now with 4G', 'sub' => 'From Rs 12,999', 'icon' => 'smartphone'],
            ] as $banner)
                <div class="bg-surface-container rounded-xl p-12 flex items-center justify-between overflow-hidden">
                    <div>
                        <h3 class="text-headline-md font-bold mb-2">{{ $banner['title'] }}</h3>
                        <p class="text-body-base">{{ $banner['sub'] }}</p>
                    </div>
                    <span class="material-symbols-outlined text-8xl text-primary-container">{{ $banner['icon'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Brand strip --}}
    <section class="py-12 bg-white border-t border-outline-variant">
        <div class="app-container">
            <div class="flex flex-wrap items-center justify-between gap-12 opacity-50 hover:opacity-100 transition-opacity">
                @foreach (['airnd', 'coinbuild', 'dirrbble', 'Instagrom', 'NEETFLIX'] as $brand)
                    <span class="text-headline-md font-bold tracking-tighter">{{ $brand }}</span>
                @endforeach
            </div>
        </div>
    </section>
@endsection
