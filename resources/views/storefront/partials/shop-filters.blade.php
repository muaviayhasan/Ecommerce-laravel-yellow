{{-- Shop filter fields (price + checkboxes), shared by the desktop sidebar and the
     mobile modal. The PARENT wraps these in a <form method="GET"> and supplies the
     submit button. Expects: $categories, $brands, $filters (category/brand are arrays). --}}

{{-- Keep the current search + sort when filters are applied. --}}
@if (! empty($filters['q']))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
@if (! empty($filters['sort']))<input type="hidden" name="sort" value="{{ $filters['sort'] }}">@endif

{{-- Price --}}
<x-storefront.filter-section title="Price">
    <div class="flex items-center gap-2">
        <input type="number" name="min" min="0" value="{{ $filters['min'] ?? '' }}" placeholder="Min" class="w-full border border-gray-300 rounded px-2 py-1.5 text-label-sm outline-none focus:border-primary">
        <span class="text-gray-400">&mdash;</span>
        <input type="number" name="max" min="0" value="{{ $filters['max'] ?? '' }}" placeholder="Max" class="w-full border border-gray-300 rounded px-2 py-1.5 text-label-sm outline-none focus:border-primary">
    </div>
</x-storefront.filter-section>

{{-- Categories --}}
<x-storefront.filter-section title="All Categories">
    <ul class="space-y-2.5 text-body-base text-on-surface-variant">
        @foreach ($categories as $cat)
            <li>
                <label class="flex items-center gap-2.5 cursor-pointer hover:text-on-surface transition-colors">
                    <input type="checkbox" name="category[]" value="{{ $cat->slug }}" @checked(in_array($cat->slug, $filters['category'] ?? [], true))
                        class="w-4 h-4 rounded border-gray-300 text-primary-container focus:ring-primary-container">
                    <span class="flex-1 {{ in_array($cat->slug, $filters['category'] ?? [], true) ? 'font-semibold text-on-surface' : '' }}">{{ $cat->name }} <span class="font-normal text-gray-400">({{ $cat->products_count }})</span></span>
                </label>
                @if ($cat->children->isNotEmpty())
                    <ul class="pl-6 mt-2 space-y-2">
                        @foreach ($cat->children as $child)
                            <li>
                                <label class="flex items-center gap-2.5 cursor-pointer hover:text-on-surface transition-colors">
                                    <input type="checkbox" name="category[]" value="{{ $child->slug }}" @checked(in_array($child->slug, $filters['category'] ?? [], true))
                                        class="w-4 h-4 rounded border-gray-300 text-primary-container focus:ring-primary-container">
                                    <span class="{{ in_array($child->slug, $filters['category'] ?? [], true) ? 'font-semibold text-on-surface' : '' }}">{{ $child->name }}</span>
                                </label>
                            </li>
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
        <div class="space-y-2.5 text-body-base text-on-surface-variant">
            @foreach ($brands as $brand)
                <label class="flex items-center gap-2.5 cursor-pointer hover:text-on-surface transition-colors">
                    <input type="checkbox" name="brand[]" value="{{ $brand->slug }}" @checked(in_array($brand->slug, $filters['brand'] ?? [], true))
                        class="w-4 h-4 rounded border-gray-300 text-primary-container focus:ring-primary-container">
                    <span class="flex-1 {{ in_array($brand->slug, $filters['brand'] ?? [], true) ? 'font-semibold text-on-surface' : '' }}">{{ $brand->name }}</span>
                    <span class="text-gray-400">({{ $brand->products_count }})</span>
                </label>
            @endforeach
        </div>
    </x-storefront.filter-section>
@endif

@if (! empty($filters['category']) || ! empty($filters['brand']) || ! empty($filters['min']) || ! empty($filters['max']))
    <a href="{{ route('shop', array_filter(['q' => $filters['q'] ?? null, 'sort' => $filters['sort'] ?? null])) }}"
        class="inline-flex items-center gap-1 mt-1 text-secondary text-label-sm font-medium hover:text-primary">
        <span class="material-symbols-outlined text-[16px]">close</span> Clear all filters
    </a>
@endif
