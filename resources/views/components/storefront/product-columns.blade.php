@props([
    'columns' => null, // ['title' => string, 'items' => iterable, 'rating' => int|null][]; defaults site-wide
    'solarImage' => null, // representative solar image; falls back to the static banner
])

@php
    // Self-contained (like <x-storefront.brand-strip>) so every page renders an
    // identical section from one source of truth. Callers may still pass :columns
    // to override (e.g. the 404 page). Guarded so the section can never turn a
    // page — least of all an error page — into a 500.
    if ($columns === null || $solarImage === null) {
        try {
            $columns ??= \App\Support\Storefront::promoColumns();
            $solarImage ??= \App\Support\Storefront::categoryImage('solar-plates');
        } catch (\Throwable $e) {
            $columns ??= [];
        }
    }
@endphp

{{-- Featured / Top Selling / On-sale product lists + a SolarMax promo banner. --}}
<section class="py-12 bg-white border-t border-gray-200">
    <div class="app-container grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        @foreach ($columns as $column)
            <div>
                <x-storefront.section-title :title="$column['title']" size="sm" />
                <div class="space-y-6">
                    @foreach ($column['items'] as $product)
                        <x-storefront.product-list-item :product="$product" :rating="$column['rating'] ?? null" />
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- SolarMax vertical banner --}}
        <div class="bg-surface-container rounded p-6 flex flex-col">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-2xl leading-none">
                        <span class="font-bold text-on-surface">Solar</span><span class="font-bold text-primary-container">Max</span>
                    </p>
                    <p class="text-label-sm text-on-surface-variant mt-1">550W Mono PERC</p>
                </div>
                <div class="text-right">
                    <p class="text-label-sm font-bold uppercase text-on-surface-variant">Starting at</p>
                    <p class="text-2xl font-bold text-on-surface leading-none">
                        <span class="text-base align-top">Rs</span> 21,999
                    </p>
                </div>
            </div>
            <a href="{{ route('shop', ['category' => 'solar-plates']) }}" class="flex-1 flex items-center justify-center min-h-[200px]">
                <img src="{{ $solarImage ?? '/assets/images/banner-smartg3.png' }}" alt="SolarMax solar panels" class="max-h-[260px] w-auto object-contain">
            </a>
        </div>
    </div>
</section>
