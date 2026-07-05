{{-- Shop filter controls, shared by the desktop sidebar and the mobile modal.
     Expects: $categories, $brands, $filters, $mergeQuery (from the parent scope). --}}

{{-- Categories --}}
<x-storefront.filter-section title="All Categories">
    <ul class="space-y-3 text-body-base text-on-surface-variant">
        <li><a href="{{ route('shop') }}" class="hover:text-primary transition-colors {{ empty($filters['category']) ? 'font-bold text-on-surface' : '' }}">All products</a></li>
        @foreach ($categories as $cat)
            <li>
                <a href="{{ $mergeQuery(['category' => $cat->slug]) }}" class="hover:text-primary transition-colors {{ ($filters['category'] ?? '') === $cat->slug ? 'font-bold text-primary' : '' }}">
                    {{ $cat->name }} <span class="font-normal text-gray-400">({{ $cat->products_count }})</span>
                </a>
                @if ($cat->children->isNotEmpty())
                    <ul class="pl-4 mt-2 space-y-2">
                        @foreach ($cat->children as $child)
                            <li><a href="{{ $mergeQuery(['category' => $child->slug]) }}" class="hover:text-primary transition-colors {{ ($filters['category'] ?? '') === $child->slug ? 'font-bold text-primary' : '' }}">{{ $child->name }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</x-storefront.filter-section>

{{-- Brands --}}
@if ($brands->isNotEmpty())
    <x-storefront.filter-section title="Brands">
        <div class="space-y-2 text-body-base text-on-surface-variant">
            @foreach ($brands as $brand)
                <a href="{{ $mergeQuery(['brand' => $brand->slug]) }}" class="flex items-center justify-between hover:text-primary transition-colors {{ ($filters['brand'] ?? '') === $brand->slug ? 'font-bold text-primary' : '' }}">
                    <span>{{ $brand->name }}</span><span class="text-gray-400">({{ $brand->products_count }})</span>
                </a>
            @endforeach
            @if (! empty($filters['brand']))
                <a href="{{ $mergeQuery(['brand' => null]) }}" class="inline-block pt-1 text-secondary text-label-sm font-medium hover:text-primary">&times; Clear brand</a>
            @endif
        </div>
    </x-storefront.filter-section>
@endif

{{-- Price --}}
<x-storefront.filter-section title="Price">
    <form method="GET" action="{{ route('shop') }}" class="space-y-3">
        @foreach (['q', 'category', 'brand', 'sort'] as $k)
            @if (! empty($filters[$k]))<input type="hidden" name="{{ $k }}" value="{{ $filters[$k] }}">@endif
        @endforeach
        <div class="flex items-center gap-2">
            <input type="number" name="min" min="0" value="{{ $filters['min'] ?? '' }}" placeholder="Min" class="w-full border border-gray-300 rounded px-2 py-1.5 text-label-sm outline-none focus:border-primary">
            <span class="text-gray-400">&mdash;</span>
            <input type="number" name="max" min="0" value="{{ $filters['max'] ?? '' }}" placeholder="Max" class="w-full border border-gray-300 rounded px-2 py-1.5 text-label-sm outline-none focus:border-primary">
        </div>
        <button type="submit" class="bg-surface-container px-6 py-2 rounded-full text-label-sm font-bold hover:bg-primary-container transition-colors">Filter</button>
    </form>
</x-storefront.filter-section>
