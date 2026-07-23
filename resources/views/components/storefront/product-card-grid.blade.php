@props(['product'])

@php
    $name = data_get($product, 'name');
    $category = data_get($product, 'category');
    $price = data_get($product, 'price');
    $compare = data_get($product, 'compare');
    $image = data_get($product, 'image');
    $url = data_get($product, 'url', '#');
    $variantId = data_get($product, 'variant_id');
    $inStock = data_get($product, 'in_stock', true);
    $onSale = $compare !== null && (float) $compare > (float) $price;
@endphp

{{-- Bordered, hover-lift product card used in the related/accessories grids. --}}
<div {{ $attributes->class('bg-white border border-outline-variant p-4 group hover:shadow-lg transition-all') }}>
    <a href="{{ $url }}" class="aspect-square bg-surface mb-4 rounded-lg overflow-hidden flex items-center justify-center relative">
        <img src="{{ $image }}" alt="{{ $name }}" loading="lazy"
            class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-300">
        <span class="absolute top-2 right-2 p-2 bg-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity shadow-sm hover:text-primary">
            <span class="material-symbols-outlined text-[18px] align-middle">favorite</span>
        </span>
    </a>
    <div class="text-[11px] text-primary font-bold uppercase mb-1 truncate">{{ $category }}</div>
    <h3 class="text-product-title min-h-9 line-clamp-2 group-hover:text-primary transition-colors">
        <a href="{{ $url }}">{{ $name }}</a>
    </h3>
    <div class="flex justify-between items-end mt-4">
        <div class="leading-tight">
            @if ($onSale)
                <div class="text-price-lg font-bold text-error">Rs {{ number_format($price) }}</div>
                <div class="text-label-sm text-on-surface-variant line-through">Rs {{ number_format($compare) }}</div>
            @else
                <div class="text-price-lg font-bold">Rs {{ number_format($price) }}</div>
            @endif
        </div>
        <x-storefront.add-to-cart :variant-id="$variantId" :in-stock="$inStock" :name="$name" :url="$url" icon="add_shopping_cart" icon-class=""
            class="bg-surface p-2 rounded-full hover:bg-primary-container transition-colors shrink-0" />
    </div>
</div>
