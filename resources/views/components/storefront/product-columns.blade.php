@props([
    'columns', // array of ['title' => string, 'items' => iterable, 'rating' => int|null]
])

{{-- Featured / Top Selling / On-sale product lists + a smartG3 promo banner. --}}
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

        {{-- smartG3 vertical banner --}}
        <div class="bg-surface-container rounded p-6 flex flex-col">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-2xl leading-none">
                        <span class="font-bold text-on-surface">smart</span><span class="font-bold text-[#29b6f6]">G3</span>
                    </p>
                    <p class="text-label-sm text-on-surface-variant mt-1">Now with 4G</p>
                </div>
                <div class="text-right">
                    <p class="text-label-sm font-bold uppercase text-on-surface-variant">Starting at</p>
                    <p class="text-2xl font-bold text-on-surface leading-none">
                        <span class="text-base align-top">$</span>129<sup class="text-[0.6em] align-super">99</sup>
                    </p>
                </div>
            </div>
            <a href="{{ route('shop') }}" class="flex-1 flex items-center justify-center min-h-[200px]">
                <img src="/assets/images/banner-smartg3.png" alt="smartG3" class="max-h-[260px] w-auto object-contain">
            </a>
        </div>
    </div>
</section>
