{{-- Compact add-to-cart control for product cards. Posts to cart.add when the
     product has a sellable variant; otherwise links to the product page. --}}
@props(['variantId' => null, 'name' => '', 'url' => '#', 'icon' => 'shopping_cart', 'iconClass' => 'text-[16px]'])

@if ($variantId)
    <form method="POST" action="{{ route('cart.add') }}" class="shrink-0">
        @csrf
        <input type="hidden" name="variant_id" value="{{ $variantId }}">
        <button type="submit" aria-label="Add {{ $name }} to cart" {{ $attributes }}>
            <span class="material-symbols-outlined align-middle {{ $iconClass }}">{{ $icon }}</span>
        </button>
    </form>
@else
    <a href="{{ $url }}" aria-label="View {{ $name }}" {{ $attributes }}>
        <span class="material-symbols-outlined align-middle {{ $iconClass }}">{{ $icon }}</span>
    </a>
@endif
