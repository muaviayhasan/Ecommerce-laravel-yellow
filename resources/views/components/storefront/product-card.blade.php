@props(['product'])

@php
    $name = data_get($product, 'name');
    $category = data_get($product, 'category');
    $price = (float) data_get($product, 'price', 0);
    $compare = data_get($product, 'compare');
    $image = data_get($product, 'image');
    $url = data_get($product, 'url', '#');
    $slug = data_get($product, 'slug');
    $onSale = $compare !== null && (float) $compare > $price;
    // TODO: swap "Rs " formatting for format_money() once the Settings helpers land.
@endphp

<div {{ $attributes->class('product-card group relative flex flex-col bg-white pt-5 px-6 pb-4 border-r border-gray-200 last:border-r-0 transition-all duration-200 hover:border-transparent hover:shadow-[0_0_6px_0_rgba(1,1,1,0.3)] hover:z-20') }}>
    <p class="text-label-sm text-secondary mb-1 line-clamp-1 min-h-4">{{ $category }}</p>

    <h4 class="text-product-title text-primary mb-4 line-clamp-2 min-h-9">
        <a href="{{ $url }}" class="hover:underline">{{ $name }}</a>
    </h4>

    <a href="{{ $url }}" class="block aspect-square mb-4">
        <img src="{{ $image }}" alt="{{ $name }}" loading="lazy"
            class="w-full h-full object-contain transition-transform group-hover:scale-105">
    </a>

    <div class="mt-auto flex items-center justify-between">
        <div>
            @if ($onSale)
                <p class="text-price-lg text-error">Rs {{ number_format($price) }}</p>
                <p class="text-label-sm text-on-surface-variant line-through">Rs {{ number_format($compare) }}</p>
            @else
                <p class="text-price-lg text-on-surface">Rs {{ number_format($price) }}</p>
            @endif
        </div>
        <button type="button" aria-label="Add {{ $name }} to cart"
            class="w-8 h-8 rounded-full bg-surface-container text-secondary flex items-center justify-center transition-colors group-hover:bg-primary-container group-hover:text-white hover:!bg-primary-fixed-dim">
            <span class="material-symbols-outlined text-[16px]">shopping_cart</span>
        </button>
    </div>

    {{-- Hover actions: Wishlist / Compare (revealed below the card on hover) --}}
    <div class="absolute left-0 right-0 top-full -mt-px bg-white px-6 pb-4 z-10 opacity-0 invisible translate-y-1 transition-all duration-200 group-hover:opacity-100 group-hover:visible group-hover:translate-y-0 shadow-[0_3px_6px_0_rgba(1,1,1,0.3)]">
        <div class="flex items-center justify-center gap-6 border-t border-gray-200 pt-3 text-label-sm font-medium text-on-surface-variant">
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
