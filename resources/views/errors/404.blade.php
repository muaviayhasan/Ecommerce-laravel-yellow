@extends('layouts.storefront')

@section('title', 'Page Not Found — ' . config('app.name'))

@php
    // Error views render outside the controller flow, so pull the placeholder
    // catalog directly from the service (no view composer to depend on).
    $catalog = app(\App\Support\SampleCatalog::class);
    $products = $catalog->products();
    $categories = $catalog->categories();
@endphp

@section('content')
    <div class="bg-white py-12">
        <div class="app-container">
            {{-- ===================== 404 hero ===================== --}}
            <section class="text-center mb-16">
                <h1 class="text-7xl sm:text-8xl lg:text-[120px] font-extrabold leading-none text-on-surface mb-4">404!</h1>
                <p class="text-headline-md text-on-surface-variant mb-8 max-w-2xl mx-auto">
                    Nothing was found at this location. Try searching, or check out the links below.
                </p>
                <form action="{{ route('shop') }}" method="GET"
                    class="max-w-xl mx-auto flex items-center bg-white rounded-full border border-outline focus-within:border-primary shadow-sm overflow-hidden p-1">
                    <input name="q" type="text" placeholder="Search products..."
                        class="flex-grow border-none focus:ring-0 px-6 py-2 text-body-base bg-transparent">
                    <button type="submit"
                        class="bg-primary-container text-on-surface px-8 py-2 rounded-full font-bold hover:bg-primary-fixed-dim transition-colors shrink-0">
                        Search
                    </button>
                </form>
            </section>

            {{-- ===================== Helpful content + sidebar ===================== --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-9 space-y-12">
                    {{-- Featured Products --}}
                    <section>
                        <x-storefront.section-title title="Featured Products" />
                        <div class="grid grid-cols-2 md:grid-cols-3 border-t border-l border-gray-200">
                            @foreach ($products->take(3) as $item)
                                <x-storefront.product-card :product="$item" class="border-b border-gray-200 hover:border-transparent" />
                            @endforeach
                        </div>
                    </section>

                    {{-- Popular Products --}}
                    <section>
                        <x-storefront.section-title title="Popular Products" />
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 border-t border-l border-gray-200">
                            @foreach ($products->slice(3, 4) as $item)
                                <x-storefront.product-card :product="$item" class="border-b border-gray-200 hover:border-transparent" />
                            @endforeach
                        </div>
                    </section>

                    <x-storefront.brand-strip />
                </div>

                {{-- Sidebar --}}
                <aside class="lg:col-span-3 space-y-8">
                    {{-- Product categories --}}
                    <div class="border border-gray-200 rounded overflow-hidden">
                        <div class="bg-surface-container px-4 py-3 border-b border-gray-200">
                            <h3 class="font-bold">Product categories</h3>
                        </div>
                        <ul>
                            @foreach ($categories as $name => $count)
                                <li class="border-b border-gray-200 last:border-0">
                                    <a href="{{ route('shop') }}"
                                        class="flex justify-between items-center px-4 py-3 text-body-base hover:text-primary hover:bg-surface-container-low transition-colors group">
                                        <span class="flex items-center gap-2 min-w-0">
                                            <span class="material-symbols-outlined text-[18px] text-outline group-hover:text-primary shrink-0">chevron_right</span>
                                            <span class="truncate">{{ $name }}</span>
                                        </span>
                                        <span class="text-label-sm text-on-surface-variant shrink-0">({{ $count }})</span>
                                    </a>
                                </li>
                            @endforeach
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
                </aside>
            </div>
        </div>
    </div>

    <x-storefront.product-columns :columns="[
        ['title' => 'Featured Products', 'items' => $products->take(3), 'rating' => null],
        ['title' => 'Top Selling Products', 'items' => $products->slice(6, 3), 'rating' => null],
        ['title' => 'On-sale Products', 'items' => $products->whereNotNull('compare')->take(3), 'rating' => 5],
    ]" />
@endsection
