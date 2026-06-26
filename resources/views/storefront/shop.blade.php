@extends('layouts.storefront')

@section('title', 'Shop — ' . config('app.name'))
@section('meta_description', 'Browse all products at ' . config('app.name') . '.')

@section('content')
    @php
        // Build a shop URL that keeps the current filters and changes/clears just a few.
        $mergeQuery = fn (array $params) => route('shop', array_filter(array_merge(request()->query(), $params), fn ($v) => $v !== null && $v !== ''));
    @endphp
    <div class="bg-white py-8">
        <div class="app-container" x-data="{ filtersOpen: false }">
            {{-- Breadcrumbs --}}
            <nav class="text-label-sm text-on-surface-variant mb-8 flex items-center gap-2" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span>&rsaquo;</span>
                <span class="text-on-surface">Shop</span>
            </nav>

            {{-- Mobile: animated toggle for the categories + filters panel (always shown on lg) --}}
            <button type="button" @click="filtersOpen = !filtersOpen" :aria-expanded="filtersOpen.toString()"
                class="lg:hidden mb-6 w-full flex items-center justify-center gap-2 bg-surface-container-low border border-gray-300 rounded-full py-3 font-bold hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[20px]">tune</span>
                <span x-text="filtersOpen ? 'Hide Filters' : 'Categories & Filters'"></span>
                <span class="material-symbols-outlined text-[20px] transition-transform duration-300"
                    :class="{ 'rotate-180': filtersOpen }">expand_more</span>
            </button>

            <div class="flex flex-col lg:flex-row gap-8">
                {{-- ============================ Sidebar ============================ --}}
                <aside class="w-full lg:w-64 shrink-0">
                    {{-- Animated collapse: hidden on mobile until toggled; always open on lg.
                         The grid-rows 0fr→1fr trick gives a smooth height transition. --}}
                    <div class="grid lg:!grid-rows-[1fr] transition-[grid-template-rows] duration-300 ease-in-out"
                        :class="filtersOpen ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                        <div class="overflow-hidden lg:overflow-visible">
                            {{-- Categories (accordion) --}}
                            <x-storefront.filter-section title="All Categories">
                                <ul class="space-y-3 text-body-base text-on-surface-variant">
                                    <li><a href="{{ route('shop') }}" class="hover:text-primary transition-colors {{ empty($filters['category']) ? 'font-bold text-on-surface' : '' }}">All products</a></li>
                                    @foreach ($categories as $cat)
                                        <li>
                                            <a href="{{ $mergeQuery(['category' => $cat->slug]) }}" class="hover:text-primary transition-colors {{ ($filters['category'] ?? '') === $cat->slug ? 'font-bold text-primary' : '' }}">
                                                {{ $cat->name }} <span class="font-normal text-gray-400">({{ $cat->products_count }})</span>
                                            </a>
                                            @if ($cat->children->isNotEmpty())
                                                <ul class="pl-4 mt-2 space-y-2">
                                                    @foreach ($cat->children as $child)
                                                        <li><a href="{{ $mergeQuery(['category' => $child->slug]) }}" class="hover:text-primary transition-colors {{ ($filters['category'] ?? '') === $child->slug ? 'font-bold text-primary' : '' }}">{{ $child->name }}</a></li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </x-storefront.filter-section>

                            {{-- Brands (accordion) --}}
                            @if ($brands->isNotEmpty())
                                <x-storefront.filter-section title="Brands">
                                    <div class="space-y-2 text-body-base text-on-surface-variant">
                                        @foreach ($brands as $brand)
                                            <a href="{{ $mergeQuery(['brand' => $brand->slug]) }}" class="flex items-center justify-between hover:text-primary transition-colors {{ ($filters['brand'] ?? '') === $brand->slug ? 'font-bold text-primary' : '' }}">
                                                <span>{{ $brand->name }}</span><span class="text-gray-400">({{ $brand->products_count }})</span>
                                            </a>
                                        @endforeach
                                        @if (! empty($filters['brand']))
                                            <a href="{{ $mergeQuery(['brand' => null]) }}" class="inline-block pt-1 text-secondary text-label-sm font-medium hover:text-primary">&times; Clear brand</a>
                                        @endif
                                    </div>
                                </x-storefront.filter-section>
                            @endif

                            {{-- Price (accordion) --}}
                            <x-storefront.filter-section title="Price">
                                <form method="GET" action="{{ route('shop') }}" class="space-y-3">
                                    @foreach (['q', 'category', 'brand', 'sort'] as $k)
                                        @if (! empty($filters[$k]))<input type="hidden" name="{{ $k }}" value="{{ $filters[$k] }}">@endif
                                    @endforeach
                                    <div class="flex items-center gap-2">
                                        <input type="number" name="min" min="0" value="{{ $filters['min'] ?? '' }}" placeholder="Min" class="w-full border border-gray-300 rounded px-2 py-1.5 text-label-sm outline-none focus:border-primary">
                                        <span class="text-gray-400">&mdash;</span>
                                        <input type="number" name="max" min="0" value="{{ $filters['max'] ?? '' }}" placeholder="Max" class="w-full border border-gray-300 rounded px-2 py-1.5 text-label-sm outline-none focus:border-primary">
                                    </div>
                                    <button type="submit" class="bg-surface-container px-6 py-2 rounded-full text-label-sm font-bold hover:bg-primary-container transition-colors">Filter</button>
                                </form>
                            </x-storefront.filter-section>

                            {{-- Ad banner --}}
                            <a href="{{ route('shop') }}" class="block relative rounded-lg overflow-hidden bg-surface-container group mt-8 mb-10">
                                <img src="/images/promos/promo-1.png" alt="Cameras promo"
                                    class="w-full h-56 object-contain p-6 group-hover:scale-105 transition-transform duration-500">
                                <div class="absolute top-6 left-6">
                                    <p class="text-[10px] uppercase font-bold tracking-widest text-on-surface-variant mb-1">All-new</p>
                                    <h4 class="text-3xl font-black text-on-surface leading-none">4K</h4>
                                    <p class="text-body-base font-bold text-on-surface">CAMERAS</p>
                                    <p class="text-[10px] text-on-surface-variant mt-3">STARTING AT</p>
                                    <p class="text-2xl font-bold text-primary">$79.99</p>
                                </div>
                            </a>

                            {{-- Latest Products --}}
                            <div>
                                <h3 class="font-bold border-b border-gray-200 pb-3 mb-6 text-lg">Latest Products</h3>
                                <div class="space-y-6">
                                    @foreach ($latest as $product)
                                        <x-storefront.product-list-item :product="$product" />
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                {{-- ============================ Main ============================ --}}
                <section class="flex-1 min-w-0" x-data="{ view: 'grid' }">
                    {{-- Recommended Products carousel (reuses the same component + heading as the home sliders) --}}
                    @php $recommendedSlides = $recommended->chunk(4)->values(); @endphp
                    <div class="mb-12">
                        <x-storefront.carousel title="Recommended Products" :count="$recommendedSlides->count()">
                            @foreach ($recommendedSlides as $slide)
                                <div class="w-full shrink-0">
                                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 border-t border-l border-gray-200">
                                        @foreach ($slide as $product)
                                            <x-storefront.product-card :product="$product"
                                                class="border-b border-gray-200 hover:border-transparent" />
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </x-storefront.carousel>
                    </div>

                    <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
                        <h1 class="text-3xl sm:text-4xl font-light">{{ $filters['q'] ?? '' ? 'Results for “' . $filters['q'] . '”' : 'All Products' }}</h1>
                        <span class="text-body-base text-on-surface-variant">
                            @if ($products->total())Showing {{ $products->firstItem() }}&ndash;{{ $products->lastItem() }} of {{ $products->total() }} results @else No products found @endif
                        </span>
                    </div>

                    {{-- Control bar --}}
                    <div class="bg-surface-container-low p-2 flex flex-wrap gap-3 justify-between items-center mb-8 rounded">
                        <div class="flex items-center gap-1 ml-2">
                            <button type="button" @click="view = 'grid'" aria-label="Grid view"
                                class="p-1.5 rounded hover:text-on-surface transition-colors"
                                :class="view === 'grid' ? 'text-on-surface' : 'text-on-surface-variant'">
                                <span class="material-symbols-outlined text-[20px]">grid_view</span>
                            </button>
                            <button type="button" @click="view = 'list'" aria-label="List view"
                                class="p-1.5 rounded hover:text-on-surface transition-colors"
                                :class="view === 'list' ? 'text-on-surface' : 'text-on-surface-variant'">
                                <span class="material-symbols-outlined text-[20px]">view_list</span>
                            </button>
                        </div>
                        <form method="GET" action="{{ route('shop') }}" class="flex items-center gap-4">
                            @foreach (['q', 'category', 'brand', 'min', 'max'] as $k)
                                @if (! empty($filters[$k]))<input type="hidden" name="{{ $k }}" value="{{ $filters[$k] }}">@endif
                            @endforeach
                            <select name="sort" data-no-select2 onchange="this.form.submit()" class="border-none bg-transparent text-body-base outline-none cursor-pointer">
                                @foreach ($sorts as $val => $label)
                                    <option value="{{ $val }}" @selected(($filters['sort'] ?? 'newness') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    {{-- Grid view --}}
                    <div x-show="view === 'grid'" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 border-t border-l border-gray-200">
                        @foreach ($products as $product)
                            <x-storefront.product-card :product="$product"
                                class="border-b border-gray-200 hover:border-transparent" />
                        @endforeach
                    </div>

                    {{-- List view --}}
                    <div x-show="view === 'list'" x-cloak class="border border-gray-200">
                        @foreach ($products as $product)
                            <x-storefront.product-card-wide :product="$product"
                                class="border-b border-gray-200 last:border-b-0 hover:border-transparent" />
                        @endforeach
                    </div>

                    {{-- Empty state --}}
                    @if ($products->isEmpty())
                        <div class="text-center py-20 border border-gray-200 border-t-0">
                            <span class="material-symbols-outlined text-gray-300" style="font-size:64px;">search_off</span>
                            <p class="mt-4 text-xl font-light text-on-surface-variant">No products match your filters.</p>
                            <a href="{{ route('shop') }}" class="inline-block mt-4 text-primary font-bold hover:underline">Clear all filters</a>
                        </div>
                    @endif

                    {{-- Pagination --}}
                    @if ($products->hasPages())
                        <nav class="flex justify-center items-center gap-2 mt-10" aria-label="Pagination">
                            @if (! $products->onFirstPage())
                                <a href="{{ $products->previousPageUrl() }}" aria-label="Previous page" class="w-9 h-9 flex items-center justify-center rounded-full bg-surface-container hover:bg-primary-container transition-colors"><span class="material-symbols-outlined text-[20px]">chevron_left</span></a>
                            @endif
                            @foreach ($products->getUrlRange(max(1, $products->currentPage() - 2), min($products->lastPage(), $products->currentPage() + 2)) as $page => $url)
                                @if ($page == $products->currentPage())
                                    <span class="w-9 h-9 flex items-center justify-center rounded-full bg-primary-container font-bold">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="w-9 h-9 flex items-center justify-center rounded-full bg-surface-container hover:bg-primary-container font-bold transition-colors">{{ $page }}</a>
                                @endif
                            @endforeach
                            @if ($products->hasMorePages())
                                <a href="{{ $products->nextPageUrl() }}" aria-label="Next page" class="w-9 h-9 flex items-center justify-center rounded-full bg-surface-container hover:bg-primary-container transition-colors"><span class="material-symbols-outlined text-[20px]">chevron_right</span></a>
                            @endif
                        </nav>
                    @endif
                </section>
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
