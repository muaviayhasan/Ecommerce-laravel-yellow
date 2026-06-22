@props([
    'product',
    'boxed' => false, // render inside a bordered white box (bestsellers grid)
])

@php
    $name = data_get($product, 'name');
    $category = data_get($product, 'category');
    $price = (float) data_get($product, 'price', 0);
    $compare = data_get($product, 'compare');
    $image = data_get($product, 'image');
    $url = data_get($product, 'url', '#');
    $onSale = $compare !== null && (float) $compare > $price;

    $wrapper = $boxed
        ? 'bg-white p-4 rounded border border-outline-variant hover:border-primary-container'
        : 'border-b border-surface-container pb-4';
@endphp

<a href="{{ $url }}" {{ $attributes->class("flex gap-4 items-center group transition-all $wrapper") }}>
    <div class="w-20 h-20 shrink-0 bg-surface rounded p-2">
        <img src="{{ $image }}" alt="{{ $name }}" loading="lazy" class="w-full h-full object-contain">
    </div>
    <div class="flex-1 min-w-0">
        @if ($category)
            <p class="text-label-sm text-secondary line-clamp-1">{{ $category }}</p>
        @endif
        <h4 class="text-product-title text-primary group-hover:underline line-clamp-1">{{ $name }}</h4>
        @if ($onSale)
            <p class="text-price-lg font-bold text-error">
                Rs {{ number_format($price) }}
                <span class="text-label-sm text-on-surface-variant line-through font-normal">Rs {{ number_format($compare) }}</span>
            </p>
        @else
            <p class="text-price-lg font-bold text-on-surface">Rs {{ number_format($price) }}</p>
        @endif
    </div>
</a>
