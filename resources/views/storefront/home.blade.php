@extends('layouts.storefront')

@section('title', config('app.name') . ' — Electronics Marketplace')
@section('meta_description', 'Shop the latest electronics — phones, laptops, cameras, audio and more at ' . config('app.name') . '.')

@section('content')
    {{-- Hero slider — slides are managed in Admin → Ecommerce → Hero Slides.
         Images use transparent PNG product cut-outs (object-contain), so the
         product shows in full on both desktop and mobile. --}}
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
                                @if ($slide->kicker)
                                    <p class="text-primary font-bold uppercase tracking-widest text-label-sm mb-3 md:mb-4">{{ $slide->kicker }}</p>
                                @endif
                                <h1 class="text-3xl sm:text-4xl lg:text-display-hero font-light leading-tight tracking-tight mb-6 md:mb-8">
                                    {{ $slide->line1 }}
                                    @if ($slide->line2)<br><span class="font-bold">{{ $slide->line2 }}</span>@endif
                                    @if ($slide->tail || $slide->highlight)<br>{{ $slide->tail }} <span class="font-bold text-primary">{{ $slide->highlight }}</span>@endif
                                </h1>
                                @if ($slide->cta_label)
                                    <a href="{{ $slide->cta_link }}"
                                        class="inline-block bg-primary-container px-10 py-4 rounded-full font-bold text-on-surface hover:opacity-90 transition-all shadow-lg">
                                        {{ $slide->cta_label }}
                                    </a>
                                @endif
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
                            @if ($slide->image_url)
                                <div class="absolute right-0 top-0 bottom-0 w-2/5 md:w-1/2 flex items-center justify-center p-3 md:p-8">
                                    <img class="max-w-full max-h-full w-auto object-contain"
                                        src="{{ $slide->image_url }}" alt="{{ $slide->image_alt }}"
                                        @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                                </div>
                            @endif
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
    {{-- Promo grid — cards managed in Admin → Ecommerce → Promo Cards. --}}
    @if ($promoCards->isNotEmpty())
    <section class="py-12 bg-white">
        <div class="app-container grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($promoCards as $promo)
                <a href="{{ $promo->link }}"
                    class="bg-surface-container-low p-6 flex items-center gap-4 group transition-shadow hover:shadow-md">
                    {{-- Image first (left), text second (right) --}}
                    @if ($promo->image_url)
                        <img src="{{ $promo->image_url }}" alt="{{ $promo->image_alt ?: $promo->title }}" loading="lazy"
                            class="w-24 h-24 object-contain shrink-0">
                    @endif
                    <div class="flex-1 min-w-0">
                        @if ($promo->kicker)
                            <p class="text-label-sm font-bold uppercase text-secondary">{{ $promo->kicker }}</p>
                        @endif
                        <h3 class="font-bold text-headline-md leading-tight uppercase text-on-surface">{{ $promo->title }}</h3>
                        @if ($promo->subtitle)
                            <p class="text-label-sm font-bold uppercase text-secondary">{{ $promo->subtitle }}</p>
                        @endif

                        <div class="flex items-center gap-2 mt-2">
                            @if ($promo->display_type === \App\Models\PromoCard::TYPE_SHOP)
                                <span class="font-bold text-on-surface">{{ $promo->prefix ?: 'Shop now' }}</span>
                            @elseif ($promo->display_type === \App\Models\PromoCard::TYPE_PRICE)
                                <span class="text-label-sm uppercase text-secondary">{{ $promo->prefix }}</span>
                                <span class="text-headline-md font-bold text-on-surface leading-none">{{ $promo->currency }}{{ $promo->amount }}<sup class="text-[0.55em] align-super">{{ $promo->cents }}</sup></span>
                            @else
                                <span class="text-label-sm uppercase text-secondary leading-tight">{{ $promo->prefix }}</span>
                                <span class="text-headline-md font-bold text-on-surface leading-none">{{ $promo->amount }}<sup class="text-[0.55em] align-super">%</sup></span>
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
    @endif

    {{-- Info bar — items managed in Admin → Ecommerce → Info Bar. --}}
    @if ($infoBarItems->isNotEmpty())
    <section class="pb-12 bg-white">
        <div class="app-container">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 rounded-lg border border-outline-variant divide-y lg:divide-y-0 lg:divide-x divide-outline-variant">
                @foreach ($infoBarItems as $info)
                    <div class="flex items-center justify-center gap-4 px-6 py-6">
                        <span class="material-symbols-outlined text-primary-container text-4xl">{{ $info->icon }}</span>
                        <div>
                            <p class="text-body-base font-bold">{{ $info->title }}</p>
                            <p class="text-label-sm text-on-surface-variant">{{ $info->subtitle }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

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
    @if ($spotlights->isNotEmpty())
        @php
            $spotPrimary = $spotlights->first();
            $spotCategory = $spotPrimary['category'];
            $tvSlides = collect($spotPrimary['products'])->chunk(4)->values();
        @endphp
        <section class="bg-cover bg-center bg-no-repeat"
            style="background-image: url('/assets/images/television-entertainment-bg.webp')">
            <div class="app-container py-12 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                {{-- Left: category visual (uses the category's admin image; falls back to a placeholder) --}}
                <div class="flex justify-center">
                    <img src="{{ $spotCategory->image?->url ?: '/assets/images/television-entertainment-tv.png' }}"
                        alt="{{ $spotCategory->name }}" class="w-full max-w-xl object-contain">
                </div>

                {{-- Right: slider --}}
                <x-storefront.carousel :title="$spotCategory->name" :count="$tvSlides->count()">
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
    @endif

    {{-- Second category spotlight (falls back to the latest products) --}}
    @php
        $spotSecondary = $spotlights->get(1);
        $laptopTitle = $spotSecondary['category']->name ?? 'Popular Products';
        $laptopSlides = collect($spotSecondary['products'] ?? $latestFallback)->chunk(6)->values();
    @endphp
    @if ($laptopSlides->isNotEmpty())
    <section class="py-12 bg-white">
        <div class="app-container">
            <x-storefront.carousel :title="$laptopTitle" :count="$laptopSlides->count()">
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
    @endif

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

    {{-- Top categories — real category departments (managed in Admin → Categories) --}}
    @if ($topCategories->isNotEmpty())
    <section class="py-12 bg-white">
        <div class="app-container">
            <x-storefront.section-title title="Top Categories this Month" />

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 lg:[&>*]:border-gray-200 lg:[&>*:not(:nth-child(4n+1))]:border-l">
                @foreach ($topCategories->take(8) as $cat)
                    <div class="flex items-start gap-4 px-6 py-4">
                        <a href="{{ route('shop', ['category' => $cat->slug]) }}" class="w-20 h-20 shrink-0">
                            @if ($cat->image?->url)
                                <img src="{{ $cat->image->url }}" alt="{{ $cat->name }}" loading="lazy"
                                    class="w-full h-full object-contain">
                            @else
                                <span class="w-full h-full rounded-lg bg-surface-container-low border border-outline-variant/40 grid place-items-center">
                                    <span class="material-symbols-outlined text-outline">category</span>
                                </span>
                            @endif
                        </a>
                        <div class="flex-1 flex flex-col min-h-[170px]">
                            <h4 class="text-base font-semibold text-on-surface mb-3">
                                <a href="{{ route('shop', ['category' => $cat->slug]) }}" class="hover:text-primary transition-colors">{{ $cat->name }}</a>
                            </h4>
                            @if ($cat->children->isNotEmpty())
                                <ul class="space-y-1.5 mb-3">
                                    @foreach ($cat->children as $sub)
                                        <li>
                                            <a href="{{ route('shop', ['category' => $sub->slug]) }}"
                                                class="text-label-sm text-on-surface-variant hover:text-primary transition-colors">{{ $sub->name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            <a href="{{ route('shop', ['category' => $cat->slug]) }}"
                                class="mt-auto self-end text-label-sm font-bold text-on-surface-variant hover:text-primary transition-colors">See all</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Promo banners --}}
    <section class="py-12 bg-white">
        <div class="app-container grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Inverter coolers --}}
            <a href="{{ route('shop', ['category' => 'coolers']) }}"
                class="bg-surface-container rounded p-8 flex items-center justify-between gap-4 overflow-hidden group">
                <div class="max-w-[55%]">
                    <h3 class="text-headline-md font-bold mb-2 text-on-surface">Inverter Air Coolers</h3>
                    <p class="text-body-base text-on-surface-variant">powerful cooling that saves on your electricity bill</p>
                </div>
                <img src="{{ $promoBanners['coolers'] ?? '/assets/images/banner-laptops.png' }}" alt="Inverter air coolers"
                    class="w-40 h-32 object-contain shrink-0 group-hover:scale-105 transition-transform">
            </a>

            {{-- SolarMax --}}
            <a href="{{ route('shop', ['category' => 'solar-plates']) }}"
                class="bg-surface-container rounded p-8 flex items-center justify-between gap-4 overflow-hidden group">
                <div class="flex items-center gap-5">
                    <div>
                        <p class="text-2xl leading-none">
                            <span class="font-bold text-on-surface">Solar</span><span class="font-bold text-primary-container">Max</span>
                        </p>
                        <p class="text-label-sm text-on-surface-variant mt-1">550W Mono PERC</p>
                    </div>
                    <div class="border-l border-outline-variant pl-5">
                        <p class="text-label-sm text-on-surface-variant">from</p>
                        <p class="text-2xl font-bold text-on-surface leading-none">
                            <span class="text-base align-top">Rs</span> 21,999
                        </p>
                    </div>
                </div>
                <img src="{{ $promoBanners['solar'] ?? '/assets/images/banner-smartg3.png' }}" alt="SolarMax solar panels"
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

    <x-storefront.product-columns />
@endsection
