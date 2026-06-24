@extends('layouts.storefront')

@section('title', 'Shop — ' . config('app.name'))
@section('meta_description', 'Browse all products at ' . config('app.name') . '.')

@section('content')
    <div class="bg-white py-8">
        <div class="app-container" x-data="{ filtersOpen: false }">
            {{-- Breadcrumbs --}}
            <nav class="text-label-sm text-on-surface-variant mb-8 flex items-center gap-2" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span>&rsaquo;</span>
                <span class="text-on-surface">Shop</span>
            </nav>

            {{-- Mobile: toggle to show/hide categories + filters (always shown on lg) --}}
            <button type="button" @click="filtersOpen = !filtersOpen" :aria-expanded="filtersOpen.toString()"
                class="lg:hidden mb-6 w-full flex items-center justify-center gap-2 border border-gray-300 rounded-full py-2.5 font-bold hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[20px]">tune</span>
                <span x-text="filtersOpen ? 'Hide Categories & Filters' : 'Show Categories & Filters'"></span>
            </button>

            <div class="flex flex-col lg:flex-row gap-8">
                {{-- ============================ Sidebar ============================ --}}
                <aside class="w-full lg:w-64 shrink-0 hidden lg:block" :class="{ '!block': filtersOpen }">
                    {{-- Categories --}}
                    <div class="mb-10">
                        <h3 class="font-bold border-b border-gray-200 pb-3 mb-4 text-lg">All Categories</h3>
                        <ul class="space-y-3 text-body-base text-on-surface-variant">
                            <li class="font-bold text-on-surface">Smart Phones &amp; Tablets <span class="font-normal text-gray-400">(25)</span></li>
                            <li class="pl-4"><a href="{{ route('shop') }}" class="hover:text-primary transition-colors">Smartphones <span class="text-gray-400">(21)</span></a></li>
                            <li class="pl-4"><a href="{{ route('shop') }}" class="hover:text-primary transition-colors">Tablets <span class="text-gray-400">(4)</span></a></li>
                        </ul>
                    </div>

                    {{-- Filters --}}
                    <h3 class="font-bold text-lg mb-6">Filters</h3>

                    {{-- Brands --}}
                    <div class="mb-8">
                        <h4 class="font-bold text-body-base mb-4">Brands</h4>
                        <div class="space-y-2 text-body-base text-on-surface-variant">
                            @foreach (['Apple' => 4, 'Gionee' => 2, 'HTC' => 2, 'LG' => 2, 'Micromax' => 1] as $brand => $n)
                                <label class="flex items-center cursor-pointer hover:text-primary">
                                    <input type="checkbox" class="rounded border-gray-300 accent-primary-container mr-2"> {{ $brand }}
                                    <span class="ml-1 text-gray-400">({{ $n }})</span>
                                </label>
                            @endforeach
                            <button type="button" class="text-secondary text-label-sm font-medium hover:text-primary">+ Show more</button>
                        </div>
                    </div>

                    {{-- Color --}}
                    <div class="mb-8">
                        <h4 class="font-bold text-body-base mb-4">Color</h4>
                        <div class="space-y-2 text-body-base text-on-surface-variant">
                            @foreach (['Black' => 3, 'Black Leather' => 2, 'Gold' => 4, 'Spacegrey' => 3, 'Turquoise' => 2] as $color => $n)
                                <label class="flex items-center cursor-pointer hover:text-primary">
                                    <input type="checkbox" class="rounded border-gray-300 accent-primary-container mr-2"> {{ $color }}
                                    <span class="ml-1 text-gray-400">({{ $n }})</span>
                                </label>
                            @endforeach
                            <button type="button" class="text-secondary text-label-sm font-medium hover:text-primary">+ Show more</button>
                        </div>
                    </div>

                    {{-- Price --}}
                    <div class="mb-10">
                        <h4 class="font-bold text-body-base mb-4">Price</h4>
                        <input type="range" min="60" max="3490" value="1500"
                            class="w-full h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary-container">
                        <div class="flex justify-between text-label-sm text-on-surface-variant mt-4">
                            <span>Price: Rs 6,000 — Rs 349,000</span>
                        </div>
                        <button type="button" class="mt-4 bg-surface-container px-6 py-2 rounded-full text-label-sm font-bold hover:bg-primary-container transition-colors">Filter</button>
                    </div>

                    {{-- Ad banner --}}
                    <a href="{{ route('shop') }}" class="block relative rounded-lg overflow-hidden bg-surface-container group mb-10">
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
                        <h1 class="text-3xl sm:text-4xl font-light">All Products</h1>
                        <span class="text-body-base text-on-surface-variant">Showing all {{ $products->count() }} results</span>
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
                        <div class="flex items-center gap-4">
                            <select data-no-select2 class="border-none bg-transparent text-body-base outline-none cursor-pointer">
                                <option>Default sorting</option>
                                <option>Popularity</option>
                                <option>Newness</option>
                                <option>Price: low to high</option>
                                <option>Price: high to low</option>
                            </select>
                            <select data-no-select2 class="border-none bg-transparent text-body-base outline-none cursor-pointer">
                                <option>Show 20</option>
                                <option>Show 40</option>
                                <option>Show all</option>
                            </select>
                        </div>
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

                    {{-- Pagination --}}
                    <nav class="flex justify-center items-center gap-2 mt-10" aria-label="Pagination">
                        <span class="w-9 h-9 flex items-center justify-center rounded-full bg-primary-container font-bold">1</span>
                        @foreach ([2, 3] as $page)
                            <a href="{{ route('shop') }}" class="w-9 h-9 flex items-center justify-center rounded-full bg-surface-container hover:bg-primary-container font-bold transition-colors">{{ $page }}</a>
                        @endforeach
                        <a href="{{ route('shop') }}" aria-label="Next page" class="w-9 h-9 flex items-center justify-center rounded-full bg-surface-container hover:bg-primary-container transition-colors">
                            <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                        </a>
                    </nav>
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
