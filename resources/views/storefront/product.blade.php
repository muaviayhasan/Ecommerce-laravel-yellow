@extends('layouts.storefront')

@section('title', $product['name'] . ' — ' . config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($product['features'][0] ?? $product['name']), 155))

@php
    $isOnSale = $product['compare'] !== null && (float) $product['compare'] > (float) $product['price'];
    $rating = (int) ($product['rating'] ?? 0);
    // Flatten the grouped specs into a single key/value list for the 2-column table.
    $specRows = [];
    foreach (($product['specifications'] ?? []) as $group) {
        foreach ($group as $label => $value) {
            $specRows[] = [$label, $value];
        }
    }
    $specHalf = (int) ceil(count($specRows) / 2);
@endphp

@section('content')
    <div class="bg-background py-8">
        <div class="app-container">
            {{-- Breadcrumbs --}}
            <nav class="flex flex-wrap items-center gap-2 text-label-sm text-on-surface-variant mb-8" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a href="{{ route('shop') }}" class="hover:text-primary transition-colors">Shop</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-on-surface line-clamp-1">{{ $product['name'] }}</span>
            </nav>

            {{-- ===================== Product hero card ===================== --}}
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 mb-12 bg-white p-6 lg:p-8 rounded-lg shadow-sm border border-outline-variant"
                x-data="{ active: 0, images: @js($product['gallery']) }">
                {{-- Gallery: main image on top, thumbnails below --}}
                <div class="space-y-6">
                    <div class="aspect-square bg-white rounded-lg overflow-hidden flex items-center justify-center p-6 sm:p-8 group cursor-zoom-in">
                        <img :src="images[active]" alt="{{ $product['name'] }}"
                            class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-500">
                    </div>
                    <div class="flex gap-3 sm:gap-4 overflow-x-auto pb-2 no-scrollbar">
                        @foreach ($product['gallery'] as $i => $img)
                            <button type="button" @click="active = {{ $i }}" aria-label="View image {{ $i + 1 }}"
                                class="w-20 h-20 shrink-0 border-2 rounded-lg p-2 transition-colors"
                                :class="active === {{ $i }} ? 'border-primary' : 'border-outline-variant hover:border-primary'">
                                <img src="{{ $img }}" alt="" loading="lazy" class="w-full h-full object-contain">
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Product info --}}
                <div class="flex flex-col">
                    <a href="{{ route('shop') }}" class="text-primary font-bold text-label-sm uppercase tracking-wider mb-2">{{ $product['categories'] }}</a>
                    <h1 class="text-headline-md font-medium mb-4">{{ $product['name'] }}</h1>

                    {{-- Rating --}}
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex text-primary-container">
                            @for ($s = 1; $s <= 5; $s++)
                                <span class="material-symbols-outlined text-[20px]" @if ($s <= $rating) style="font-variation-settings: 'FILL' 1;" @endif>star</span>
                            @endfor
                        </div>
                        <span class="text-label-sm text-on-surface-variant">({{ $product['reviews_count'] ?? 0 }} Customer Reviews)</span>
                    </div>

                    {{-- Feature box + SKU --}}
                    <div class="bg-surface p-4 rounded-lg mb-6 text-body-base border border-outline-variant">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($product['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-4 text-on-surface-variant italic">SKU: {{ $product['sku'] ?? 'N/A' }}</p>
                    </div>

                    {{-- Availability + price + actions --}}
                    <div class="border-t border-outline-variant pt-6">
                        <div class="text-label-sm text-green-600 font-bold mb-2">Availability: {{ $product['stock'] ?? 0 }} in stock</div>
                        <div class="flex items-end gap-3 mb-6">
                            <span class="text-[40px] leading-none font-black {{ $isOnSale ? 'text-error' : 'text-on-surface' }}">Rs {{ number_format($product['price']) }}</span>
                            @if ($isOnSale)
                                <span class="text-xl text-on-surface-variant line-through pb-1">Rs {{ number_format($product['compare']) }}</span>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-4 items-center" x-data="{ qty: 1 }">
                            <div class="flex border border-outline rounded-lg overflow-hidden h-12">
                                <button type="button" @click="qty = Math.max(1, qty - 1)" aria-label="Decrease quantity" class="px-4 hover:bg-surface transition-colors">&minus;</button>
                                <input type="number" min="1" x-model.number="qty" aria-label="Quantity"
                                    class="w-12 text-center border-none focus:ring-0 outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                                <button type="button" @click="qty++" aria-label="Increase quantity" class="px-4 hover:bg-surface transition-colors">+</button>
                            </div>
                            <button type="button" class="bg-primary-container text-on-primary-container px-10 h-12 font-bold rounded hover:brightness-95 transition-all flex items-center gap-2">
                                <span class="material-symbols-outlined">shopping_cart</span> Add to Cart
                            </button>
                        </div>

                        <button type="button" class="w-full mt-4 bg-[#00d084] text-white py-3 rounded-lg font-bold flex items-center justify-center gap-2 hover:opacity-90 transition-opacity">
                            Pay with <span class="font-black italic">link</span>
                        </button>

                        <div class="flex gap-8 mt-6 text-label-sm font-bold text-on-surface-variant">
                            <button type="button" class="flex items-center gap-1 hover:text-primary transition-colors"><span class="material-symbols-outlined text-[18px]">favorite</span> Wishlist</button>
                            <button type="button" class="flex items-center gap-1 hover:text-primary transition-colors"><span class="material-symbols-outlined text-[18px]">sync</span> Compare</button>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===================== Tabbed content ===================== --}}
            <section class="bg-white rounded-lg border border-outline-variant mb-12 overflow-hidden" x-data="{ tab: 'description' }">
                <div class="border-b border-outline-variant flex justify-start lg:justify-center gap-8 lg:gap-12 px-4 overflow-x-auto no-scrollbar font-bold text-label-sm uppercase tracking-wide">
                    @foreach (['accessories' => 'Accessories', 'description' => 'Description', 'specification' => 'Specification', 'reviews' => 'Reviews', 'more' => 'More Products'] as $key => $label)
                        <button type="button" @click="tab = '{{ $key }}'"
                            class="py-4 px-1 border-b-2 transition-colors whitespace-nowrap"
                            :class="tab === '{{ $key }}' ? 'border-primary text-on-surface' : 'border-transparent text-on-surface-variant hover:text-primary'">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Description --}}
                <div x-show="tab === 'description'" class="p-6 lg:p-12 space-y-16">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-12 items-center">
                        <div class="space-y-4">
                            <h2 class="text-headline-md">{{ $product['description_blocks'][0]['heading'] }}</h2>
                            <p class="text-on-surface-variant leading-relaxed">{{ $product['description_blocks'][0]['body'] }}</p>
                            <h2 class="text-headline-md !mt-8">{{ $product['description_blocks'][1]['heading'] }}</h2>
                            <p class="text-on-surface-variant leading-relaxed">{{ $product['description_blocks'][1]['body'] }}</p>
                        </div>
                        <div class="w-full h-80 bg-surface rounded-xl overflow-hidden flex items-center justify-center">
                            <img src="{{ $product['gallery'][1] ?? $product['gallery'][0] }}" alt="" class="w-full h-full object-contain p-8">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-12 items-center">
                        <div class="order-2 lg:order-1 w-full h-80 bg-surface rounded-xl overflow-hidden flex items-center justify-center">
                            <img src="{{ $product['gallery'][2] ?? $product['gallery'][0] }}" alt="" class="w-full h-full object-contain p-8">
                        </div>
                        <div class="order-1 lg:order-2 space-y-4">
                            <h2 class="text-headline-md">{{ $product['description_blocks'][2]['heading'] }}</h2>
                            <p class="text-on-surface-variant leading-relaxed">{{ $product['description_blocks'][2]['body'] }}</p>
                            <h2 class="text-headline-md !mt-8">{{ $product['description_blocks'][3]['heading'] }}</h2>
                            <p class="text-on-surface-variant leading-relaxed">{{ $product['description_blocks'][3]['body'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- Accessories --}}
                <div x-show="tab === 'accessories'" x-cloak class="p-6 lg:p-12">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        @foreach ($accessories as $item)
                            <x-storefront.product-card-grid :product="$item" />
                        @endforeach
                    </div>
                </div>

                {{-- Specification --}}
                <div x-show="tab === 'specification'" x-cloak class="p-6 lg:p-12">
                    <h2 class="text-headline-md mb-8 text-center">Technical Specifications</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 border-t border-outline-variant max-w-4xl mx-auto">
                        <div class="divide-y divide-outline-variant">
                            @foreach (array_slice($specRows, 0, $specHalf) as [$label, $value])
                                <div class="py-4 flex justify-between gap-4">
                                    <span class="font-bold">{{ $label }}</span>
                                    <span class="text-on-surface-variant text-right">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="divide-y divide-outline-variant md:border-t-0 border-t border-outline-variant">
                            @foreach (array_slice($specRows, $specHalf) as [$label, $value])
                                <div class="py-4 flex justify-between gap-4">
                                    <span class="font-bold">{{ $label }}</span>
                                    <span class="text-on-surface-variant text-right">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Reviews --}}
                <div x-show="tab === 'reviews'" x-cloak class="p-6 lg:p-12">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                        {{-- Summary --}}
                        <div>
                            <h2 class="text-headline-md mb-8">Based on {{ $product['reviews_count'] ?? 0 }} reviews</h2>
                            <div class="flex items-center gap-4 mb-8">
                                <div class="text-[64px] font-black leading-none">0.0</div>
                                <div>
                                    <div class="text-on-surface-variant">overall</div>
                                    <div class="flex text-outline">
                                        @for ($s = 0; $s < 5; $s++)<span class="material-symbols-outlined">star</span>@endfor
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                @foreach (['★★★★★', '★★★★☆', '★★★☆☆', '★★☆☆☆', '★☆☆☆☆'] as $stars)
                                    <div class="flex items-center gap-4">
                                        <div class="text-primary-container text-label-sm w-24 shrink-0">{{ $stars }}</div>
                                        <div class="flex-1 h-2 bg-surface rounded-full overflow-hidden">
                                            <div class="h-full bg-primary-container" style="width: 0"></div>
                                        </div>
                                        <span class="text-label-sm w-4 text-right">0</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-8 p-4 bg-primary-container/10 border border-primary-container rounded-lg text-label-sm">
                                There are no reviews yet.
                            </div>
                        </div>

                        {{-- Form --}}
                        <div>
                            <h2 class="text-headline-md mb-4">Be the first to review</h2>
                            <p class="text-on-surface-variant text-label-sm mb-6">Your email address will not be published. Required fields are marked *</p>
                            <form class="space-y-6" onsubmit="return false">
                                <div>
                                    <label class="block text-label-sm font-bold mb-2">Your Rating</label>
                                    <div class="flex text-outline cursor-pointer hover:text-primary-container text-2xl">
                                        @for ($s = 0; $s < 5; $s++)<span class="material-symbols-outlined">star</span>@endfor
                                    </div>
                                </div>
                                <div>
                                    <label for="review-body" class="block text-label-sm font-bold mb-2">Your Review *</label>
                                    <textarea id="review-body" rows="5" class="w-full border border-outline-variant rounded px-4 py-2 focus:ring-primary focus:border-primary"></textarea>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="review-name" class="block text-label-sm font-bold mb-2">Name *</label>
                                        <input id="review-name" type="text" class="w-full border border-outline-variant rounded px-4 py-2 focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label for="review-email" class="block text-label-sm font-bold mb-2">Email *</label>
                                        <input id="review-email" type="email" class="w-full border border-outline-variant rounded px-4 py-2 focus:ring-primary focus:border-primary">
                                    </div>
                                </div>
                                <label class="flex items-start gap-2 text-label-sm text-on-surface-variant">
                                    <input type="checkbox" class="mt-0.5 rounded border-outline-variant accent-primary-container">
                                    Save my name and email in this browser for the next time I comment.
                                </label>
                                <button type="submit" class="bg-primary-container text-on-primary-container px-8 py-3 font-bold rounded-full hover:brightness-95 transition-all">Add Review</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- More Products --}}
                <div x-show="tab === 'more'" x-cloak class="p-6 lg:p-12">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        @foreach ($moreProducts as $item)
                            <x-storefront.product-card-grid :product="$item" />
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ===================== Related products ===================== --}}
            <section class="mb-12">
                <h2 class="text-headline-md mb-8 pb-3 border-b-2 border-primary-container w-max">Related products</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
                    @foreach ($related as $item)
                        <x-storefront.product-card-grid :product="$item" />
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <x-storefront.brand-strip />

    <x-storefront.product-columns :columns="[
        ['title' => 'Featured Products', 'items' => $featured, 'rating' => null],
        ['title' => 'Top Selling Products', 'items' => $topSelling, 'rating' => null],
        ['title' => 'On-sale Products', 'items' => $onSale, 'rating' => 5],
    ]" />
@endsection
