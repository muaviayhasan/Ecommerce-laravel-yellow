@extends('layouts.storefront')

@section('title', 'Shop — ' . config('app.name'))
@section('meta_description', 'Browse all products at ' . config('app.name') . '.')

@section('content')
    <div class="bg-white py-8">
        <div class="app-container" x-data="{ filtersOpen: false }">
            {{-- Breadcrumbs --}}
            <nav class="text-label-sm text-on-surface-variant mb-6 flex items-center gap-2" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span>&rsaquo;</span>
                <span class="text-on-surface">Shop</span>
            </nav>

            {{-- Mobile: open the filters modal --}}
            <button type="button" @click="filtersOpen = true"
                class="lg:hidden mb-4 w-full flex items-center justify-center gap-2 bg-surface-container-low border border-gray-300 rounded-full py-3 font-bold hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[20px]">tune</span>
                Categories &amp; Filters
            </button>

            <div class="flex flex-col lg:flex-row lg:gap-8">
                {{-- ===================== Sidebar (desktop only) ===================== --}}
                <aside class="hidden lg:block w-64 shrink-0">
                    <form method="GET" action="{{ route('shop') }}">
                        @include('storefront.partials.shop-filters')
                        <button type="submit" class="mt-4 w-full bg-primary-container text-on-primary-container font-bold py-2.5 rounded-full hover:brightness-105 active:scale-95 transition">Apply Filters</button>
                    </form>

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
                </aside>

                {{-- ============================ Main ============================ --}}
                <section class="flex-1 min-w-0" x-data="{ view: 'grid' }">
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
                            @foreach (['q', 'min', 'max'] as $k)
                                @if (! empty($filters[$k]))<input type="hidden" name="{{ $k }}" value="{{ $filters[$k] }}">@endif
                            @endforeach
                            @foreach (($filters['category'] ?? []) as $c)<input type="hidden" name="category[]" value="{{ $c }}">@endforeach
                            @foreach (($filters['brand'] ?? []) as $b)<input type="hidden" name="brand[]" value="{{ $b }}">@endforeach
                            <select name="sort" data-no-select2 onchange="this.form.submit()" class="border-none bg-transparent text-body-base outline-none cursor-pointer">
                                @foreach ($sorts as $val => $label)
                                    <option value="{{ $val }}" @selected(($filters['sort'] ?? 'newness') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    {{-- Grid view --}}
                    <div id="shop-grid" x-show="view === 'grid'"
                        data-current-page="{{ $products->currentPage() }}" data-last-page="{{ $products->lastPage() }}"
                        class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 border-t border-l border-gray-200">
                        @foreach ($products as $product)
                            <x-storefront.product-card :product="$product" class="border-b border-gray-200 hover:border-transparent" />
                        @endforeach
                    </div>

                    {{-- List view --}}
                    <div id="shop-list" x-show="view === 'list'" x-cloak class="border border-gray-200">
                        @foreach ($products as $product)
                            <x-storefront.product-card-wide :product="$product" class="border-b border-gray-200 last:border-b-0 hover:border-transparent" />
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

                    {{-- Mobile: infinite-scroll sentinel (loads the next page as you scroll) --}}
                    @if ($products->hasMorePages())
                        <div id="shop-sentinel" class="lg:hidden flex justify-center py-8">
                            <span data-spinner class="material-symbols-outlined animate-spin text-outline text-[28px]">progress_activity</span>
                        </div>
                    @endif

                    {{-- Pagination (desktop only) --}}
                    @if ($products->hasPages())
                        <nav class="hidden lg:flex justify-center items-center gap-2 mt-10" aria-label="Pagination">
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

                    {{-- Recommended Products (below the listing) --}}
                    @php $recommendedSlides = $recommended->chunk(4)->values(); @endphp
                    @if ($recommendedSlides->isNotEmpty())
                        <div class="mt-16">
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
                    @endif
                </section>
            </div>

            {{-- ===================== Mobile filters modal ===================== --}}
            <div x-show="filtersOpen" x-cloak class="lg:hidden fixed inset-0 z-[70]" @keydown.escape.window="filtersOpen = false">
                <div class="absolute inset-0 bg-black/40" @click="filtersOpen = false" x-transition.opacity></div>
                <div class="absolute inset-y-0 left-0 w-full bg-white shadow-2xl flex flex-col"
                    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
                    <div class="flex items-center justify-between px-4 py-4 border-b border-gray-200 shrink-0">
                        <h2 class="font-bold text-lg flex items-center gap-2"><span class="material-symbols-outlined">tune</span> Categories &amp; Filters</h2>
                        <button type="button" @click="filtersOpen = false" aria-label="Close" class="w-9 h-9 grid place-items-center rounded-full hover:bg-surface-container"><span class="material-symbols-outlined">close</span></button>
                    </div>
                    <form method="GET" action="{{ route('shop') }}" class="flex-1 flex flex-col min-h-0">
                        <div class="flex-1 overflow-y-auto px-4 py-2">
                            @include('storefront.partials.shop-filters')
                        </div>
                        <div class="px-4 py-3 border-t border-gray-200 shrink-0">
                            <button type="submit" class="w-full bg-primary-container text-on-primary-container font-bold py-3 rounded-full hover:brightness-105 transition">View results</button>
                        </div>
                    </form>
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

    @push('scripts')
        <script>
            // Mobile infinite scroll: append the next page of products as the sentinel nears view.
            document.addEventListener('DOMContentLoaded', () => {
                const grid = document.getElementById('shop-grid');
                const list = document.getElementById('shop-list');
                const sentinel = document.getElementById('shop-sentinel');
                if (!grid || !sentinel) return;

                let page = parseInt(grid.dataset.currentPage || '1', 10);
                const lastPage = parseInt(grid.dataset.lastPage || '1', 10);
                let loading = false;

                const loadMore = async () => {
                    if (loading || page >= lastPage) return;
                    loading = true;
                    try {
                        const url = new URL(window.location.href);
                        url.searchParams.set('page', page + 1);
                        url.searchParams.set('partial', '1');
                        const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                        if (r.ok) {
                            const tmp = document.createElement('div');
                            tmp.innerHTML = await r.text();
                            tmp.querySelectorAll('[data-shop-grid-items] > *').forEach(el => grid.appendChild(el));
                            if (list) tmp.querySelectorAll('[data-shop-list-items] > *').forEach(el => list.appendChild(el));
                            page++;
                        }
                    } catch (e) {}
                    loading = false;
                    if (page >= lastPage) { io.disconnect(); sentinel.remove(); }
                };

                const io = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting) loadMore();
                }, { rootMargin: '600px' });
                io.observe(sentinel);
            });
        </script>
    @endpush
@endsection
