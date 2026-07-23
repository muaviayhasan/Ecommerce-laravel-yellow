{{-- Compact add-to-cart control for product cards. Posts to cart.add when the
     product has a sellable, in-stock variant; shows a crossed-out (non-clickable)
     cart when it's out of stock; otherwise links to the product page. --}}
@props(['variantId' => null, 'name' => '', 'url' => '#', 'icon' => 'shopping_cart', 'iconClass' => 'text-[16px]', 'inStock' => true])

@if ($variantId && ! $inStock)
    {{-- Out of stock: a plain <span> (no form, no link) so it can't be clicked.
         The strike is drawn in CSS, so the "crossed cart" never depends on a
         separate font glyph being present in the subset. --}}
    <span aria-label="{{ $name }} — out of stock" title="Out of stock" aria-disabled="true"
        style="position:relative; cursor:not-allowed; opacity:0.55;" {{ $attributes }}>
        <span class="material-symbols-outlined align-middle {{ $iconClass }}">shopping_cart</span>
        <span aria-hidden="true"
            style="position:absolute; left:50%; top:50%; width:90%; height:2px; background:currentColor; border-radius:2px; transform:translate(-50%,-50%) rotate(-45deg);"></span>
    </span>
@elseif ($variantId)
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
