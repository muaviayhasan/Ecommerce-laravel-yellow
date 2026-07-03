@props(['product', 'thumbnails' => []])

@php
    $name = data_get($product, 'name');
    $category = data_get($product, 'category');
    $price = (float) data_get($product, 'price', 0);
    $compare = data_get($product, 'compare');
    $image = data_get($product, 'image');
    $url = data_get($product, 'url', '#');
    $slug = data_get($product, 'slug');
    $onSale = $compare !== null && (float) $compare > $price;
@endphp

<div {{ $attributes->class('product-card group relative flex flex-col bg-white px-8 py-6 transition-all duration-200 hover:shadow-[0_0_6px_0_rgba(1,1,1,0.3)] hover:z-20') }}>
    <p class="text-label-sm text-secondary mb-1 line-clamp-1">{{ $category }}</p>
    <h4 class="text-base font-semibold text-primary mb-4 line-clamp-2">
        <a href="{{ $url }}" class="hover:underline">{{ $name }}</a>
    </h4>

    <a href="{{ $url }}" class="flex-1 flex items-center justify-center py-6 min-h-[220px]">
        <img src="{{ $image }}" alt="{{ $name }}" loading="lazy"
            class="max-h-[300px] w-auto max-w-full object-contain transition-transform group-hover:scale-105">
    </a>

    @if (count($thumbnails))
        <div class="flex gap-3 mb-6">
            @foreach ($thumbnails as $thumb)
                <button type="button" aria-label="View image"
                    class="w-16 h-16 border border-gray-200 p-1 hover:border-primary-container transition-colors">
                    <img src="{{ $thumb }}" alt="" loading="lazy" class="w-full h-full object-contain">
                </button>
            @endforeach
        </div>
    @endif

    <div class="mt-auto flex items-center justify-between">
        <div>
            @if ($onSale)
                <p class="text-price-lg text-error">
                    Rs {{ number_format($price) }}
                    <span class="text-label-sm text-on-surface-variant line-through">Rs {{ number_format($compare) }}</span>
                </p>
            @else
                <p class="text-price-lg text-on-surface">Rs {{ number_format($price) }}</p>
            @endif
        </div>
        <button type="button" aria-label="Add {{ $name }} to cart"
            class="w-9 h-9 rounded-full bg-surface-container text-secondary flex items-center justify-center transition-colors group-hover:bg-primary-container group-hover:text-white hover:!bg-primary-fixed-dim">
            <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
        </button>
    </div>

    {{-- Hover actions: Wishlist / Compare --}}
    <div class="absolute left-0 right-0 top-full -mt-px bg-white px-8 pb-6 z-10 opacity-0 invisible translate-y-1 transition-all duration-200 group-hover:opacity-100 group-hover:visible group-hover:translate-y-0 shadow-[0_3px_6px_0_rgba(1,1,1,0.3)]">
        <div class="flex items-center justify-center gap-6 border-t border-gray-200 pt-4 text-label-sm font-medium text-on-surface-variant">
            @if ($slug)
                <form method="POST" action="{{ route('wishlist.toggle', $slug) }}">@csrf
                    <button type="submit" class="flex items-center gap-1.5 hover:text-primary transition-colors" aria-label="Add to wishlist">
                        <span class="material-symbols-outlined text-[18px]">favorite</span> Wishlist
                    </button>
                </form>
                <form method="POST" action="{{ route('compare.toggle', $slug) }}">@csrf
                    <button type="submit" class="flex items-center gap-1.5 hover:text-primary transition-colors" aria-label="Add to compare">
                        <span class="material-symbols-outlined text-[18px]">sync</span> Compare
                    </button>
                </form>
            @else
                <a href="{{ route('wishlist') }}" class="flex items-center gap-1.5 hover:text-primary transition-colors"><span class="material-symbols-outlined text-[18px]">favorite</span> Wishlist</a>
                <a href="{{ route('compare') }}" class="flex items-center gap-1.5 hover:text-primary transition-colors"><span class="material-symbols-outlined text-[18px]">sync</span> Compare</a>
            @endif
        </div>
    </div>
</div>
