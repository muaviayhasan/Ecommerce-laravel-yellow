@props(['product', 'rating' => null])

@php
    $name = data_get($product, 'name');
    $price = (float) data_get($product, 'price', 0);
    $compare = data_get($product, 'compare');
    $image = data_get($product, 'image');
    $url = data_get($product, 'url', '#');
    $onSale = $compare !== null && (float) $compare > $price;
@endphp

<a href="{{ $url }}" class="flex gap-4 items-start group">
    <div class="w-16 h-16 shrink-0 bg-surface p-1">
        <img src="{{ $image }}" alt="{{ $name }}" loading="lazy" class="w-full h-full object-contain">
    </div>
    <div class="flex-1 min-w-0">
        <h4 class="text-product-title font-semibold text-primary group-hover:underline line-clamp-2 mb-1">{{ $name }}</h4>
        @if ($rating)
            <div class="text-primary-container text-sm leading-none mb-1" aria-label="{{ $rating }} out of 5 stars">★★★★★</div>
        @endif
        @if ($onSale)
            <p class="text-base font-bold text-error">
                Rs {{ number_format($price) }}
                <span class="text-label-sm text-on-surface-variant line-through font-normal">Rs {{ number_format($compare) }}</span>
            </p>
        @else
            <p class="text-base font-bold text-on-surface">Rs {{ number_format($price) }}</p>
        @endif
    </div>
</a>
