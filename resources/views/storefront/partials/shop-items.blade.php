{{-- One page of shop products, for the mobile infinite-scroll fetch. The client
     appends the grid cards to #shop-grid and the list cards to #shop-list. --}}
<div data-shop-grid-items>
    @foreach ($products as $product)
        <x-storefront.product-card :product="$product" class="border-b border-gray-200 hover:border-transparent" />
    @endforeach
</div>
<div data-shop-list-items>
    @foreach ($products as $product)
        <x-storefront.product-card-wide :product="$product" class="border-b border-gray-200 last:border-b-0 hover:border-transparent" />
    @endforeach
</div>
