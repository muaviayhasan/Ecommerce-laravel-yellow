@extends('layouts.storefront')

@section('title', $product['name'] . ' — ' . config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($product['features'][0] ?? $product['name']), 155))

@php
    $isOnSale = $product['compare'] !== null && (float) $product['compare'] > (float) $product['price'];
@endphp

@section('content')
    <div class="bg-white py-8">
        <div class="app-container">
            {{-- Breadcrumbs --}}
            <nav class="text-label-sm text-on-surface-variant mb-8 flex flex-wrap items-center gap-2" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span>&rsaquo;</span>
                <a href="{{ route('shop') }}" class="hover:text-primary transition-colors">Shop</a>
                <span>&rsaquo;</span>
                <span class="text-on-surface line-clamp-1">{{ $product['name'] }}</span>
            </nav>

            <div class="flex flex-col lg:flex-row gap-8">
                {{-- ===================== Sidebar (below product on mobile) ===================== --}}
                <aside class="w-full lg:w-1/4 shrink-0 order-2 lg:order-1 space-y-8">
                    {{-- Categories --}}
                    <div class="border border-gray-200 rounded p-6">
                        <h3 class="font-bold border-b border-gray-200 pb-3 mb-4">All Categories</h3>
                        <ul class="space-y-3 text-body-base text-on-surface-variant">
                            <li class="font-bold text-on-surface">Smart Phones &amp; Tablets <span class="font-normal text-gray-400">(25)</span></li>
                            <li class="pl-4"><a href="{{ route('shop') }}" class="hover:text-primary transition-colors">Smartphones <span class="text-gray-400">(21)</span></a></li>
                            <li class="pl-4"><a href="{{ route('shop') }}" class="hover:text-primary transition-colors">Tablets <span class="text-gray-400">(4)</span></a></li>
                        </ul>
                    </div>

                    {{-- Ad banner --}}
                    <a href="{{ route('shop') }}" class="block relative rounded-lg overflow-hidden bg-surface-container group">
                        <img src="/images/promos/promo-1.png" alt="Cameras promo"
                            class="w-full h-64 object-contain p-6 group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-6 left-6">
                            <p class="text-[10px] uppercase font-bold tracking-widest text-on-surface-variant mb-1">All-new</p>
                            <h4 class="text-3xl font-black text-on-surface leading-none">4K</h4>
                            <p class="text-body-base font-bold text-on-surface">CAMERAS</p>
                            <p class="text-[10px] text-on-surface-variant mt-3">STARTING AT</p>
                            <p class="text-2xl font-bold text-primary">$79.99</p>
                        </div>
                    </a>

                    {{-- Latest products --}}
                    <div>
                        <h3 class="font-bold border-b border-gray-200 pb-3 mb-6">Latest Products</h3>
                        <div class="space-y-6">
                            @foreach ($latest as $item)
                                <x-storefront.product-list-item :product="$item" />
                            @endforeach
                        </div>
                    </div>
                </aside>

                {{-- ===================== Main product section ===================== --}}
                <div class="w-full lg:w-3/4 order-1 lg:order-2">
                    <div class="flex flex-col md:flex-row gap-8 lg:gap-10 mb-12">
                        {{-- Gallery --}}
                        <div class="w-full md:w-1/2 flex gap-3"
                            x-data="{ active: 0, images: @js($product['gallery']) }">
                            <div class="flex flex-col gap-2 w-16 sm:w-20 shrink-0">
                                @foreach ($product['gallery'] as $i => $img)
                                    <button type="button" @click="active = {{ $i }}" aria-label="View image {{ $i + 1 }}"
                                        class="border-2 rounded p-1 transition-colors"
                                        :class="active === {{ $i }} ? 'border-primary-container' : 'border-gray-200 hover:border-primary-container'">
                                        <img src="{{ $img }}" alt="" loading="lazy" class="w-full aspect-square object-contain">
                                    </button>
                                @endforeach
                            </div>
                            <div class="flex-grow relative border border-gray-200 rounded-lg p-6 sm:p-8 aspect-square flex items-center justify-center overflow-hidden group cursor-zoom-in">
                                <span class="material-symbols-outlined absolute top-3 right-3 text-on-surface-variant text-[22px]">zoom_in</span>
                                <img :src="images[active]" alt="{{ $product['name'] }}"
                                    class="max-w-full max-h-full object-contain transition-transform duration-500 group-hover:scale-110">
                            </div>
                        </div>

                        {{-- Info --}}
                        <div class="w-full md:w-1/2">
                            <p class="text-label-sm text-on-surface-variant mb-2 line-clamp-2">{{ $product['categories'] }}</p>
                            <h1 class="text-3xl font-normal mb-3">{{ $product['name'] }}</h1>
                            <p class="text-body-base text-on-surface-variant mb-5">
                                Availability: <span class="text-green-600 font-bold">{{ $product['availability'] }}</span>
                            </p>

                            <div class="flex items-center gap-6 text-label-sm text-primary mb-6">
                                <button type="button" class="flex items-center gap-1 hover:underline">
                                    <span class="material-symbols-outlined text-[18px]">favorite</span> Wishlist
                                </button>
                                <button type="button" class="flex items-center gap-1 hover:underline">
                                    <span class="material-symbols-outlined text-[18px]">sync</span> Compare
                                </button>
                            </div>

                            <ul class="list-disc list-inside text-body-base text-on-surface-variant space-y-2 mb-8">
                                @foreach ($product['features'] as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>

                            {{-- Price --}}
                            <div class="flex items-end gap-3 mb-6">
                                <span class="text-4xl font-medium {{ $isOnSale ? 'text-error' : 'text-on-surface' }}">Rs {{ number_format($product['price']) }}</span>
                                @if ($isOnSale)
                                    <span class="text-xl text-on-surface-variant line-through pb-1">Rs {{ number_format($product['compare']) }}</span>
                                @endif
                            </div>

                            {{-- Qty + add to cart --}}
                            <div class="flex flex-wrap items-center gap-4 mb-4" x-data="{ qty: 1 }">
                                <div class="flex items-center border border-gray-300 rounded-full overflow-hidden">
                                    <button type="button" @click="qty = Math.max(1, qty - 1)" aria-label="Decrease quantity" class="px-4 py-2 hover:bg-surface-container transition-colors">&minus;</button>
                                    <input type="number" min="1" x-model.number="qty" aria-label="Quantity"
                                        class="w-12 border-none text-center py-2 outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                                    <button type="button" @click="qty++" aria-label="Increase quantity" class="px-4 py-2 hover:bg-surface-container transition-colors">+</button>
                                </div>
                                <button type="button" class="bg-primary-container text-on-surface font-bold px-8 py-3 rounded-full hover:bg-primary-fixed-dim transition-colors flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[20px]">shopping_cart</span> Add to cart
                                </button>
                            </div>
                            <button type="button" class="w-full sm:w-auto bg-inverse-surface text-inverse-on-surface font-bold px-10 py-3 rounded-full hover:opacity-90 transition-opacity">
                                Buy Now
                            </button>
                        </div>
                    </div>

                    {{-- ===================== Tabs ===================== --}}
                    <div class="mb-16" x-data="{ tab: 'accessories' }">
                        <div class="flex justify-start lg:justify-center border-b border-gray-200 mb-10 gap-6 sm:gap-10 lg:gap-12 overflow-x-auto no-scrollbar">
                            @foreach (['accessories' => 'Accessories', 'description' => 'Description', 'specification' => 'Specification', 'reviews' => 'Reviews', 'more' => 'More Products'] as $key => $label)
                                <button type="button" @click="tab = '{{ $key }}'"
                                    class="shrink-0 pb-3 text-lg font-bold border-b-4 transition-colors whitespace-nowrap"
                                    :class="tab === '{{ $key }}' ? 'border-primary-container text-on-surface' : 'border-transparent text-on-surface-variant hover:text-on-surface'">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Accessories --}}
                        <div x-show="tab === 'accessories'">
                            <p class="text-on-surface-variant mb-8 max-w-3xl">Recommended add-ons and accessories that pair perfectly with the {{ $product['name'] }}.</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 border-t border-l border-gray-200">
                                @foreach ($accessories as $item)
                                    <x-storefront.product-card :product="$item" class="border-b border-gray-200 hover:border-transparent" />
                                @endforeach
                            </div>
                        </div>

                        {{-- Description --}}
                        <div x-show="tab === 'description'" x-cloak class="max-w-4xl">
                            <p class="text-lg text-on-surface-variant leading-relaxed mb-6">{{ $product['description_intro'] }}</p>
                            @foreach ($product['description_body'] as $paragraph)
                                <p class="text-on-surface-variant leading-relaxed mb-4">{{ $paragraph }}</p>
                            @endforeach
                            <h4 class="font-bold text-xl mt-8 mb-4">Key Features</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
                                @foreach ($product['highlights'] as $highlight)
                                    <div class="flex items-start gap-2 text-on-surface-variant">
                                        <span class="material-symbols-outlined text-primary text-[20px] shrink-0">check_circle</span>
                                        <span>{{ $highlight }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Specification --}}
                        <div x-show="tab === 'specification'" x-cloak class="max-w-4xl">
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                @foreach ($product['specifications'] as $group => $rows)
                                    <div class="bg-surface-container px-6 py-3 font-bold border-t border-gray-200 first:border-t-0">{{ $group }}</div>
                                    @foreach ($rows as $label => $value)
                                        <div class="flex border-t border-gray-200 text-body-base">
                                            <div class="w-2/5 sm:w-1/3 px-6 py-3 font-medium bg-surface-container-low">{{ $label }}</div>
                                            <div class="w-3/5 sm:w-2/3 px-6 py-3 text-on-surface-variant">{{ $value }}</div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>

                        {{-- Reviews --}}
                        <div x-show="tab === 'reviews'" x-cloak>
                            <div class="flex flex-col lg:flex-row gap-12">
                                {{-- Ratings summary --}}
                                <div class="lg:w-1/3">
                                    <h4 class="font-bold mb-4">Based on 0 reviews</h4>
                                    <div class="flex items-start gap-4 mb-6">
                                        <div class="text-6xl font-bold leading-none">0.0</div>
                                        <div class="text-body-base text-on-surface-variant pt-2">overall</div>
                                    </div>
                                    <div class="space-y-2">
                                        @foreach (['★★★★★', '★★★★☆', '★★★☆☆', '★★☆☆☆', '★☆☆☆☆'] as $stars)
                                            <div class="flex items-center text-label-sm gap-3">
                                                <div class="text-primary-container">{{ $stars }}</div>
                                                <div class="flex-grow h-2 bg-surface-container rounded"></div>
                                                <div class="text-gray-400">0</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Review form --}}
                                <div class="lg:w-2/3">
                                    <h4 class="font-bold mb-6">Be the first to review “{{ $product['name'] }}”</h4>
                                    <form class="space-y-6" onsubmit="return false">
                                        <div>
                                            <label class="block text-label-sm font-bold mb-2">Your Rating</label>
                                            <div class="text-primary-container text-xl tracking-widest cursor-pointer">☆☆☆☆☆</div>
                                        </div>
                                        <div>
                                            <label for="review-body" class="block text-label-sm font-bold mb-2">Your Review</label>
                                            <textarea id="review-body" rows="6"
                                                class="w-full border-gray-300 rounded-2xl focus:ring-primary-container focus:border-primary-container"></textarea>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label for="review-name" class="block text-label-sm font-bold mb-2">Name *</label>
                                                <input id="review-name" type="text"
                                                    class="w-full border-gray-300 rounded-full py-3 focus:ring-primary-container focus:border-primary-container">
                                            </div>
                                            <div>
                                                <label for="review-email" class="block text-label-sm font-bold mb-2">Email *</label>
                                                <input id="review-email" type="email"
                                                    class="w-full border-gray-300 rounded-full py-3 focus:ring-primary-container focus:border-primary-container">
                                            </div>
                                        </div>
                                        <label class="flex items-start gap-2 text-body-base text-on-surface-variant">
                                            <input type="checkbox" class="mt-1 rounded border-gray-300 accent-primary-container">
                                            Save my name and email in this browser for the next time I comment.
                                        </label>
                                        <button type="submit" class="bg-primary-container font-bold px-8 py-3 rounded-full hover:bg-primary-fixed-dim transition-colors">
                                            Add Review
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="mt-12 p-4 bg-primary-container/30 text-center text-on-surface-variant font-medium rounded">
                                There are no reviews yet.
                            </div>
                        </div>

                        {{-- More Products --}}
                        <div x-show="tab === 'more'" x-cloak>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 border-t border-l border-gray-200">
                                @foreach ($moreProducts as $item)
                                    <x-storefront.product-card :product="$item" class="border-b border-gray-200 hover:border-transparent" />
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- ===================== Related products ===================== --}}
                    <section>
                        <x-storefront.section-title title="Related products" />
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 border-t border-l border-gray-200">
                            @foreach ($related as $item)
                                <x-storefront.product-card :product="$item" class="border-b border-gray-200 hover:border-transparent" />
                            @endforeach
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <x-storefront.brand-strip />

    <x-storefront.product-columns :columns="[
        ['title' => 'Featured Products', 'items' => $featured, 'rating' => null],
        ['title' => 'Top Selling Products', 'items' => $topSelling, 'rating' => null],
        ['title' => 'On-sale Products', 'items' => $onSale, 'rating' => 5],
    ]" />
@endsection
