@extends('layouts.storefront')

{{-- Category-filtered views get category SEO: its own meta fields when set in
     the admin, sensible generated copy otherwise, and a self-canonical so
     Google indexes each category page instead of folding them into /shop. --}}
@php
    $seoCat = $activeCategory ?? null;
    // The brand suffix applies to category pages only. The bare /shop title is set
    // verbatim and is already 53 characters; appending the store name would push it
    // past the ~60 Google displays.
    $shopTitle = $seoCat
        ? ($seoCat->meta_title ?: $seoCat->name . ' — Best Prices in Pakistan') . ' — ' . config('app.name')
        : 'Shop Home Appliances & Electronics Online in Pakistan';
    $shopDesc = $seoCat
        ? ($seoCat->meta_description ?: 'Shop ' . $seoCat->name . ' at ' . config('app.name') . ' — genuine brands, updated prices and delivery across Lahore & all Pakistan.')
        // Kept under ~155 characters — Google truncates the snippet past roughly there.
        : 'Air coolers, geysers, fans, washing machines, kitchen and gas appliances, solar and batteries — genuine brands, fair prices, delivery across Pakistan.';

    /*
     | Searched, sorted, price-filtered and "load more" views of the shop show the
     | same products the plain category pages already cover, so they are kept out of
     | the index — Google's own guidance is that internal search results shouldn't be
     | indexed. `follow` so the crawler still walks through to the products. The
     | canonical below points every one of them back at the real page.
     */
    $shopIsFiltered = collect(['q', 'sort', 'min', 'max', 'brand', 'loaded', 'page'])
        ->contains(fn ($p) => request()->filled($p));
@endphp
@section('title', $shopTitle)
@section('meta_description', $shopDesc)
@if ($shopIsFiltered)
    @section('robots', 'noindex, follow')
@endif
@if ($seoCat)
    @section('canonical', route('shop', ['category' => $seoCat->slug]))
    {{-- Share a category link and you got the site logo, because nothing on this page
         set a social image. The category's own artwork is a far better preview. --}}
    @if ($seoCat->image?->url)
        @section('og_image', $seoCat->image->thumbUrl(1200))
    @endif
@endif

@php
    // Trail used by both the visible breadcrumb and the schema below. It follows the
    // active category up through its parent, so a three-level taxonomy
    // (Electronics › Coolers › Air Cooler) is legible to readers and crawlers alike.
    $crumbs = [['name' => 'Home', 'url' => route('home')], ['name' => 'Shop', 'url' => route('shop')]];
    if ($seoCat) {
        if ($parentCat = $seoCat->parent) {
            $crumbs[] = ['name' => $parentCat->name, 'url' => route('shop', ['category' => $parentCat->slug])];
        }
        $crumbs[] = ['name' => $seoCat->name, 'url' => route('shop', ['category' => $seoCat->slug])];
    }

    // Built here rather than inline in @json(...): Blade scans markup for directives
    // before PHP runs, so a literal '@context' / '@type' key written out there is
    // mistaken for one and the view fails to compile.
    $crumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($crumbs)->values()->map(fn ($c, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $c['name'],
            'item' => $c['url'],
        ])->all(),
    ];
@endphp

{{-- Pushed from the top level, not from inside @section('content'): the layout emits
     <head> before the content section runs, so a push from in there never lands. --}}
@push('schema')
    <script type="application/ld+json">@json($crumbSchema)</script>
@endpush

@section('content')
    <div class="bg-white py-8">
        <div class="app-container" x-data="{ filtersOpen: false }">
            {{-- Breadcrumbs. The trail stopped at "Shop" regardless of what you were
                 browsing, so a three-level taxonomy (Electronics › Coolers › Air Cooler)
                 was invisible to both readers and crawlers. Now it follows the active
                 category up through its parent, and emits BreadcrumbList to match. --}}
            <nav class="text-label-sm text-on-surface-variant mb-4 flex flex-wrap items-center gap-2" aria-label="Breadcrumb">
                @foreach ($crumbs as $i => $crumb)
                    @if ($i > 0)<span aria-hidden="true">&rsaquo;</span>@endif
                    @if ($loop->last)
                        <span class="text-on-surface" aria-current="page">{{ $crumb['name'] }}</span>
                    @else
                        <a href="{{ $crumb['url'] }}" class="hover:text-primary transition-colors">{{ $crumb['name'] }}</a>
                    @endif
                @endforeach
            </nav>


            <div class="flex flex-col lg:flex-row lg:gap-8">
                {{-- ===================== Sidebar (desktop only) ===================== --}}
                <aside class="hidden lg:block w-64 shrink-0">
                    <form method="GET" action="{{ route('shop') }}">
                        @include('storefront.partials.shop-filters')
                        <button type="submit" class="mt-4 w-full bg-primary-container text-on-primary-container font-bold py-2.5 rounded-full hover:brightness-105 active:scale-95 transition">Apply Filters</button>
                    </form>

                    {{-- Latest Products --}}
                    <div class="mt-10">
                        <h3 class="font-bold border-b border-outline-variant pb-3 mb-6 text-lg">Latest Products</h3>
                        <div class="space-y-6">
                            @foreach ($latest as $product)
                                <x-storefront.product-list-item :product="$product" />
                            @endforeach
                        </div>
                    </div>
                </aside>

                {{-- ============================ Main ============================ --}}
                <section class="flex-1 min-w-0" x-data="{ view: 'grid' }">
                    {{-- The page says what it is before offering tools to change it:
                         title + count, then one tools row, then products. --}}
                    <div class="flex flex-wrap justify-between items-baseline gap-x-4 gap-y-1 mb-4">
                        <h1 class="text-3xl sm:text-4xl font-light">{{ $filters['q'] ?? '' ? 'Results for “' . $filters['q'] . '”' : 'All Products' }}</h1>
                        <span class="text-body-base text-on-surface-variant">
                            @if ($products->total()){{ number_format($products->total()) }} {{ Str::plural('product', $products->total()) }} @else No products found @endif
                        </span>
                    </div>

                    {{-- Control bar — every tool for this list in one row; on mobile the
                         filters live here too instead of a separate full-width band. --}}
                    <div class="bg-surface-container-low p-2 flex flex-wrap gap-3 justify-between items-center mb-6 rounded">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="filtersOpen = true"
                                class="lg:hidden flex items-center gap-1.5 bg-white border border-outline rounded-full pl-3 pr-4 py-1.5 text-sm font-bold active:scale-95 transition-all">
                                <span aria-hidden="true" class="material-symbols-outlined text-[18px]">tune</span>
                                Filters
                            </button>
                            <div class="flex items-center gap-1 ml-1">
                                <button type="button" @click="view = 'grid'" aria-label="Grid view"
                                    class="p-1.5 rounded hover:text-on-surface transition-colors"
                                    :class="view === 'grid' ? 'text-on-surface' : 'text-on-surface-variant'">
                                    <span aria-hidden="true" class="material-symbols-outlined text-[20px]">grid_view</span>
                                </button>
                                <button type="button" @click="view = 'list'" aria-label="List view"
                                    class="p-1.5 rounded hover:text-on-surface transition-colors"
                                    :class="view === 'list' ? 'text-on-surface' : 'text-on-surface-variant'">
                                    <span aria-hidden="true" class="material-symbols-outlined text-[20px]">view_list</span>
                                </button>
                            </div>
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
                        {{-- In PER_PAGE units, which diverge from the paginator's own once
                             ?loaded= has widened the first "page" — see ShopController. --}}
                        data-current-page="{{ $shownPages }}" data-last-page="{{ $totalPages }}"
                        class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 border-t border-l border-outline-variant">
                        @foreach ($products as $product)
                            <x-storefront.product-card :product="$product" class="border-b border-outline-variant hover:border-transparent" />
                        @endforeach
                    </div>

                    {{-- List view --}}
                    <div id="shop-list" x-show="view === 'list'" x-cloak class="border border-outline-variant">
                        @foreach ($products as $product)
                            <x-storefront.product-card-wide :product="$product" class="border-b border-outline-variant last:border-b-0 hover:border-transparent" />
                        @endforeach
                    </div>

                    {{-- Empty state --}}
                    @if ($products->isEmpty())
                        <div class="text-center py-20 border border-outline-variant border-t-0">
                            <span aria-hidden="true" class="material-symbols-outlined text-outline-variant" style="font-size:64px;">search_off</span>
                            <p class="mt-4 text-xl font-light text-on-surface-variant">No products match your filters.</p>
                            <a href="{{ route('shop') }}" class="inline-block mt-4 text-primary font-bold hover:underline">Clear all filters</a>
                        </div>
                    @endif

                    {{-- Mobile: "Load more" button — loads the next page until every product is shown --}}
                    @if ($products->hasMorePages())
                        <div id="shop-loadmore" class="lg:hidden flex flex-col items-center gap-3 py-8">
                            <button type="button" data-loadmore-btn
                                class="inline-flex items-center gap-2 bg-primary-container text-on-primary-container font-bold px-8 py-3 rounded-full shadow-sm hover:brightness-95 active:scale-95 transition-all disabled:opacity-60 disabled:active:scale-100">
                                <span aria-hidden="true" class="material-symbols-outlined text-[20px]" data-loadmore-icon>expand_more</span>
                                <span data-loadmore-label>Load more products</span>
                            </button>
                            <p class="text-label-sm text-outline">
                                Showing <span data-loadmore-shown>{{ $products->count() }}</span> of {{ $products->total() }} products
                            </p>
                        </div>
                    @endif

                    {{-- Pagination (desktop only) --}}
                    @if ($products->hasPages())
                        <nav class="hidden lg:flex justify-center items-center gap-2 mt-10" aria-label="Pagination">
                            @if (! $products->onFirstPage())
                                <a href="{{ $products->previousPageUrl() }}" aria-label="Previous page" class="w-9 h-9 flex items-center justify-center rounded-full bg-surface-container hover:bg-primary-container transition-colors"><span aria-hidden="true" class="material-symbols-outlined text-[20px]">chevron_left</span></a>
                            @endif
                            @foreach ($products->getUrlRange(max(1, $products->currentPage() - 2), min($products->lastPage(), $products->currentPage() + 2)) as $page => $url)
                                @if ($page == $products->currentPage())
                                    <span class="w-9 h-9 flex items-center justify-center rounded-full bg-primary-container font-bold">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="w-9 h-9 flex items-center justify-center rounded-full bg-surface-container hover:bg-primary-container font-bold transition-colors">{{ $page }}</a>
                                @endif
                            @endforeach
                            @if ($products->hasMorePages())
                                <a href="{{ $products->nextPageUrl() }}" aria-label="Next page" class="w-9 h-9 flex items-center justify-center rounded-full bg-surface-container hover:bg-primary-container transition-colors"><span aria-hidden="true" class="material-symbols-outlined text-[20px]">chevron_right</span></a>
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
                                        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 border-t border-l border-outline-variant">
                                            @foreach ($slide as $product)
                                                <x-storefront.product-card :product="$product"
                                                    class="border-b border-outline-variant hover:border-transparent" />
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
                    <div class="flex items-center justify-between px-4 py-4 border-b border-outline-variant shrink-0">
                        <h2 class="font-bold text-lg flex items-center gap-2"><span aria-hidden="true" class="material-symbols-outlined">tune</span> Categories &amp; Filters</h2>
                        <button type="button" @click="filtersOpen = false" aria-label="Close" class="w-9 h-9 grid place-items-center rounded-full hover:bg-surface-container"><span aria-hidden="true" class="material-symbols-outlined">close</span></button>
                    </div>
                    <form method="GET" action="{{ route('shop') }}" class="flex-1 flex flex-col min-h-0">
                        <div class="flex-1 overflow-y-auto px-4 py-2">
                            @include('storefront.partials.shop-filters')
                        </div>
                        <div class="px-4 py-3 border-t border-outline-variant shrink-0">
                            <button type="submit" class="w-full bg-primary-container text-on-primary-container font-bold py-3 rounded-full hover:brightness-105 transition">View results</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-storefront.brand-strip />

    <x-storefront.product-columns />

    @push('scripts')
        <script>
            // Mobile "Load more": append the next page of products on each click, until the last page.
            (function () {
                // How deep the customer has loaded lives in the URL (`?loaded=3`), not in
                // memory. A plain page-1 render was previously all a reload could produce, so
                // every "Load more" was undone by a refresh, by the browser restoring this tab
                // hours later, by the back button, or by add-to-cart (which POSTs and
                // redirects back here). Keeping it in the URL fixes all four at once, and the
                // generic scroll restore in layouts/storefront.blade.php then lands correctly
                // because the products it saved a position against are actually on the page.
                const start = () => {
                    const grid = document.getElementById('shop-grid');
                    const box = document.getElementById('shop-loadmore');
                    if (!grid || !box) return; // nothing more to load

                    const list = document.getElementById('shop-list');
                    const btn = box.querySelector('[data-loadmore-btn]');
                    const icon = box.querySelector('[data-loadmore-icon]');
                    const label = box.querySelector('[data-loadmore-label]');
                    const shown = box.querySelector('[data-loadmore-shown]');
                    let page = parseInt(grid.dataset.currentPage || '1', 10);
                    const lastPage = parseInt(grid.dataset.lastPage || '1', 10);
                    let loading = false;

                    const setLoading = (on) => {
                        loading = on;
                        btn.disabled = on;
                        if (icon) { icon.textContent = on ? 'progress_activity' : 'expand_more'; icon.classList.toggle('animate-spin', on); }
                        if (label) label.textContent = on ? 'Loading…' : 'Load more products';
                    };

                    const loadMore = async () => {
                        if (loading || page >= lastPage) return;
                        setLoading(true);
                        try {
                            const url = new URL(window.location.href);
                            url.searchParams.delete('loaded'); // the partial endpoint always serves one page
                            url.searchParams.set('page', page + 1);
                            url.searchParams.set('partial', '1');
                            const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                            if (!r.ok) throw new Error('status ' + r.status);
                            const tmp = document.createElement('div');
                            tmp.innerHTML = await r.text();
                            tmp.querySelectorAll('[data-shop-grid-items] > *').forEach((el) => grid.appendChild(el));
                            if (list) tmp.querySelectorAll('[data-shop-list-items] > *').forEach((el) => list.appendChild(el));
                            page++;
                            if (shown) shown.textContent = grid.children.length;
                            // replaceState, not pushState: the back button should leave the
                            // listing, not step back through each batch the customer loaded.
                            try {
                                const here = new URL(window.location.href);
                                here.searchParams.delete('page'); // `loaded` supersedes it — never carry both
                                here.searchParams.set('loaded', page);
                                history.replaceState(history.state, '', here);
                            } catch (err) {}
                        } catch (e) {
                            setLoading(false); // keep the button so it can be retried
                            return;
                        }
                        setLoading(false);
                        if (page >= lastPage) box.remove(); // every product is now showing
                    };

                    btn.addEventListener('click', loadMore);
                };

                // Run now if the DOM is already parsed, else wait for it.
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', start);
                } else {
                    start();
                }
            })();
        </script>
    @endpush
@endsection
