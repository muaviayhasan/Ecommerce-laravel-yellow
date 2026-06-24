@extends('layouts.storefront')

@section('title', config('app.name') . ' — Electronics Marketplace')
@section('meta_description', 'Shop the latest electronics — phones, laptops, cameras, audio and more at ' . config('app.name') . '.')

@section('content')
    {{-- Hero slider --}}
    @php
        // Hero uses transparent PNG product cut-outs (object-contain), so the product
        // shows in full on both desktop and mobile.
        $heroSlides = [
            [
                'kicker' => 'Power meets portability',
                'line1' => 'NEXT-GEN LAPTOPS',
                'line2' => 'BUILT FOR SPEED',
                'tail' => 'SAVE UP TO',
                'highlight' => '30% OFF',
                'cta' => 'Shop Laptops',
                'image' => '/assets/images/banner-laptops.png',
                'alt' => 'Next-gen laptop',
            ],
            [
                'kicker' => 'Capture every moment',
                'line1' => 'PRO-GRADE',
                'line2' => 'CAMERAS',
                'tail' => 'UP TO',
                'highlight' => '40% OFF',
                'cta' => 'Shop Cameras',
                'image' => '/images/promos/promo-1.png',
                'alt' => '4K camera',
            ],
            [
                'kicker' => 'Hear every detail',
                'line1' => 'IMMERSIVE AUDIO',
                'line2' => 'WIRELESS FREEDOM',
                'tail' => 'STARTING AT',
                'highlight' => 'Rs 4,999',
                'cta' => 'Shop Audio',
                'image' => '/assets/images/banner-smartg3.png',
                'alt' => 'Wireless audio device',
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
                        {{-- Fixed height (not min-h) so every slide is the same height
                             regardless of text length; content is vertically centered. --}}
                        <div class="app-container relative flex items-center h-[480px] lg:h-[500px] overflow-hidden">
                            <div class="w-3/5 md:w-1/2 z-10 py-4">
                                <p class="text-primary font-bold uppercase tracking-widest text-label-sm mb-3 md:mb-4">{{ $slide['kicker'] }}</p>
                                <h1 class="text-3xl sm:text-4xl lg:text-display-hero font-light leading-tight tracking-tight mb-6 md:mb-8">
                                    {{ $slide['line1'] }}<br>
                                    <span class="font-bold">{{ $slide['line2'] }}</span><br>
                                    {{ $slide['tail'] }} <span class="font-bold text-primary">{{ $slide['highlight'] }}</span>
                                </h1>
                                <a href="{{ route('shop') }}"
                                    class="inline-block bg-primary-container px-10 py-4 rounded-full font-bold text-on-surface hover:opacity-90 transition-all shadow-lg">
                                    {{ $slide['cta'] }}
                                </a>
                                {{-- Dots --}}
                                <div class="flex gap-2 mt-8 md:mt-12">
                                    @foreach ($heroSlides as $di => $dslide)
                                        <button type="button" @click="go({{ $di }})" aria-label="Go to slide {{ $di + 1 }}"
                                            class="h-1.5 rounded-full transition-all"
                                            :class="current === {{ $di }} ? 'w-12 bg-primary-container' : 'w-4 bg-outline-variant hover:bg-primary'"></button>
                                    @endforeach
                                </div>
                            </div>
                            {{-- PNG product cut-out, contained. Floated on the right on all
                                 screens (the text may overlap it on small screens — that's fine). --}}
                            <div class="absolute right-0 top-0 bottom-0 w-2/5 md:w-1/2 flex items-center justify-center p-3 md:p-8">
                                <img class="max-w-full max-h-full w-auto object-contain"
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
                <div class="flex gap-6 sm:gap-12 text-base sm:text-headline-md font-bold text-on-surface-variant">
                    @foreach (['featured' => 'Featured', 'sale' => 'On Sale', 'top' => 'Top Rated'] as $key => $label)
                        <button type="button" @click="tab = '{{ $key }}'"
                            class="pb-4 border-b-2 transition-all whitespace-nowrap"
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
            <x-storefront.carousel title="Television Entertainment" :count="$tvSlides->count()">
                @foreach ($tvSlides as $slide)
                    <div class="w-full shrink-0">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($slide as $product)
                                <x-storefront.product-card-wide :product="$product" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </x-storefront.carousel>
        </div>
    </section>

    {{-- Laptops & Computers --}}
    @php $laptopSlides = $laptops->chunk(6)->values(); @endphp
    <section class="py-12 bg-white">
        <div class="app-container">
            <x-storefront.carousel title="Laptops & Computers" :count="$laptopSlides->count()">
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
            </x-storefront.carousel>
        </div>
    </section>

    {{-- Trending Products (single row of 4) --}}
    @php $trendingSlides = $trending->chunk(4)->values(); @endphp
    <section class="py-12 bg-white">
        <div class="app-container">
            <x-storefront.carousel title="Trending Products" :count="$trendingSlides->count()">
                @foreach ($trendingSlides as $slide)
                    <div class="w-full shrink-0">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($slide as $product)
                                <x-storefront.product-card-wide :product="$product"
                                    class="border-r border-gray-200 last:border-r-0 hover:border-transparent" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </x-storefront.carousel>
        </div>
    </section>

    {{-- Bestsellers — 4x2 grid of small cards + one large featured card --}}
    @php
        $bsThumbs = [
            'https://picsum.photos/seed/bs-thumb-1/80/80',
            'https://picsum.photos/seed/bs-thumb-2/80/80',
            'https://picsum.photos/seed/bs-thumb-3/80/80',
        ];
    @endphp
    <section class="py-12 bg-surface-container-low">
        <div class="app-container">
            <x-storefront.section-title title="Bestsellers" />

            <div class="grid grid-cols-2 lg:grid-cols-6 border-t border-l border-gray-200">
                @foreach ($bestsellers as $product)
                    <x-storefront.product-card :product="$product"
                        class="border-b border-gray-200 hover:border-transparent" />
                @endforeach

                <x-storefront.product-card-feature :product="$bestsellerFeature" :thumbnails="$bsThumbs"
                    class="col-span-2 lg:col-start-5 lg:row-start-1 lg:row-span-2 border-r border-b border-gray-200 hover:border-transparent" />
            </div>
        </div>
    </section>

    {{-- Top categories — 4x2 grid of horizontal category cards --}}
    @php
        $topCategories = [
            ['name' => 'Accessories', 'image' => 'https://picsum.photos/seed/cat-acc/200/200', 'subs' => ['Cases', 'Chargers', 'Headphone Accessories', 'Headphone Cases', 'Headphones', 'Pendrives']],
            ['name' => 'Laptops & Computers', 'image' => 'https://picsum.photos/seed/cat-lap/200/200', 'subs' => ['Laptops', 'Desktops', 'Monitors', 'Keyboards']],
            ['name' => 'TV & Audio', 'image' => 'https://picsum.photos/seed/cat-tv/200/200', 'subs' => []],
            ['name' => 'All in One', 'image' => 'https://picsum.photos/seed/cat-aio/200/200', 'subs' => []],
            ['name' => 'Audio Speakers', 'image' => 'https://picsum.photos/seed/cat-spk/200/200', 'subs' => []],
            ['name' => 'Bluetooth Speakers', 'image' => 'https://picsum.photos/seed/cat-bt/200/200', 'subs' => []],
            ['name' => 'Cameras', 'image' => 'https://picsum.photos/seed/cat-cam/200/200', 'subs' => []],
            ['name' => 'Cameras & Photography', 'image' => 'https://picsum.photos/seed/cat-photo/200/200', 'subs' => ['Cameras', 'Photo Cameras', 'Video Cameras']],
        ];
    @endphp
    <section class="py-12 bg-white">
        <div class="app-container">
            <x-storefront.section-title title="Top Categories this Month" />

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 lg:[&>*]:border-gray-200 lg:[&>*:not(:nth-child(4n+1))]:border-l">
                @foreach ($topCategories as $cat)
                    <div class="flex items-start gap-4 px-6 py-4">
                        <a href="{{ route('shop') }}" class="w-20 h-20 shrink-0">
                            <img src="{{ $cat['image'] }}" alt="{{ $cat['name'] }}" loading="lazy"
                                class="w-full h-full object-contain">
                        </a>
                        <div class="flex-1 flex flex-col min-h-[170px]">
                            <h4 class="text-base font-semibold text-on-surface mb-3">
                                <a href="{{ route('shop') }}" class="hover:text-primary transition-colors">{{ $cat['name'] }}</a>
                            </h4>
                            @if (count($cat['subs']))
                                <ul class="space-y-1.5 mb-3">
                                    @foreach ($cat['subs'] as $sub)
                                        <li>
                                            <a href="{{ route('shop') }}"
                                                class="text-label-sm text-on-surface-variant hover:text-primary transition-colors">{{ $sub }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            <a href="{{ route('shop') }}"
                                class="mt-auto self-end text-label-sm font-bold text-on-surface-variant hover:text-primary transition-colors">See all</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Promo banners --}}
    <section class="py-12 bg-white">
        <div class="app-container grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- G9 Laptops --}}
            <a href="{{ route('shop') }}"
                class="bg-surface-container rounded p-8 flex items-center justify-between gap-4 overflow-hidden group">
                <div class="max-w-[55%]">
                    <h3 class="text-headline-md font-bold mb-2 text-on-surface">G9 Laptops with Ultra 4K</h3>
                    <p class="text-body-base text-on-surface-variant">and the fastest Intel Core i7 processor ever</p>
                </div>
                <img src="/assets/images/banner-laptops.png" alt="G9 Laptops"
                    class="w-40 h-32 object-contain shrink-0 group-hover:scale-105 transition-transform">
            </a>

            {{-- smartG3 --}}
            <a href="{{ route('shop') }}"
                class="bg-surface-container rounded p-8 flex items-center justify-between gap-4 overflow-hidden group">
                <div class="flex items-center gap-5">
                    <div>
                        <p class="text-2xl leading-none">
                            <span class="font-bold text-on-surface">smart</span><span class="font-bold text-[#29b6f6]">G3</span>
                        </p>
                        <p class="text-label-sm text-on-surface-variant mt-1">Now with 4G</p>
                    </div>
                    <div class="border-l border-outline-variant pl-5">
                        <p class="text-label-sm text-on-surface-variant">from</p>
                        <p class="text-2xl font-bold text-on-surface leading-none">
                            <span class="text-base align-top">$</span>129<sup class="text-[0.6em] align-super">99</sup>
                        </p>
                    </div>
                </div>
                <img src="/assets/images/banner-smartg3.png" alt="smartG3"
                    class="w-36 h-32 object-contain shrink-0 group-hover:scale-105 transition-transform">
            </a>
        </div>
    </section>

    {{-- Your Recently Viewed Products --}}
    @if ($recentlyViewed->isNotEmpty())
        <section class="py-12 bg-white">
            <div class="app-container">
                <x-storefront.section-title title="Your Recently Viewed Products" />
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 border border-gray-200">
                    @foreach ($recentlyViewed as $product)
                        <x-storefront.product-card :product="$product" class="hover:border-transparent" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-storefront.brand-strip />

    <x-storefront.product-columns :columns="[
        ['title' => 'Featured Products', 'items' => $featured->take(3), 'rating' => null],
        ['title' => 'Top Selling Products', 'items' => $bestsellers->take(3), 'rating' => null],
        ['title' => 'On-sale Products', 'items' => $onSale->take(3), 'rating' => 5],
    ]" />
@endsection
