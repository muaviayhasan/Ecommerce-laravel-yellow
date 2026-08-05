{{-- Shop filter fields (price + checkboxes), shared by the desktop sidebar and the
     mobile modal. The PARENT wraps these in a <form method="GET"> and supplies the
     submit button. Expects: $categories, $brands, $filters (category/brand are arrays). --}}

{{-- Keep the current search + sort when filters are applied. --}}
@if (! empty($filters['q']))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
@if (! empty($filters['sort']))<input type="hidden" name="sort" value="{{ $filters['sort'] }}">@endif

{{-- Price --}}
<x-storefront.filter-section title="Price">
    <div class="flex items-center gap-2">
        <input type="number" name="min" min="0" value="{{ $filters['min'] ?? '' }}" placeholder="Min" class="w-full border border-outline rounded px-2 py-1.5 text-label-sm outline-none focus:border-primary">
        <span class="text-outline">&mdash;</span>
        <input type="number" name="max" min="0" value="{{ $filters['max'] ?? '' }}" placeholder="Max" class="w-full border border-outline rounded px-2 py-1.5 text-label-sm outline-none focus:border-primary">
    </div>
</x-storefront.filter-section>

{{-- Categories --}}
<x-storefront.filter-section title="All Categories">
    <ul class="space-y-2.5 text-body-base text-on-surface-variant">
        @foreach ($categories as $cat)
            <li>
                <label class="flex items-center gap-2.5 cursor-pointer hover:text-on-surface transition-colors">
                    <input type="checkbox" name="category[]" value="{{ $cat->slug }}" @checked(in_array($cat->slug, $filters['category'] ?? [], true))
                        class="w-4 h-4 rounded border-outline text-primary-container focus:ring-primary-container">
                    {{-- A parent's products live mostly in its children — count the whole branch, and never show a bare (0). --}}
                    @php $branchCount = $cat->products_count + $cat->children->sum('products_count'); @endphp
                    <span class="flex-1 {{ in_array($cat->slug, $filters['category'] ?? [], true) ? 'font-semibold text-on-surface' : '' }}">{{ $cat->name }}@if ($branchCount > 0) <span class="font-normal text-outline">({{ $branchCount }})</span>@endif</span>
                </label>
                @if ($cat->children->isNotEmpty())
                    <ul class="pl-6 mt-2 space-y-2">
                        @foreach ($cat->children as $child)
                            <li>
                                <label class="flex items-center gap-2.5 cursor-pointer hover:text-on-surface transition-colors">
                                    <input type="checkbox" name="category[]" value="{{ $child->slug }}" @checked(in_array($child->slug, $filters['category'] ?? [], true))
                                        class="w-4 h-4 rounded border-outline text-primary-container focus:ring-primary-container">
                                    <span class="{{ in_array($child->slug, $filters['category'] ?? [], true) ? 'font-semibold text-on-surface' : '' }}">{{ $child->name }}@if ($child->products_count > 0) <span class="font-normal text-outline">({{ $child->products_count }})</span>@endif</span>
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
                        class="w-4 h-4 rounded border-outline text-primary-container focus:ring-primary-container">
                    <span class="flex-1 {{ in_array($brand->slug, $filters['brand'] ?? [], true) ? 'font-semibold text-on-surface' : '' }}">{{ $brand->name }}</span>
                    <span class="text-outline">({{ $brand->products_count }})</span>
                </label>
            @endforeach
        </div>
    </x-storefront.filter-section>
@endif

{{-- "Clear all" lives with the actions, not buried under the last accordion:
     the desktop sidebar shows it above these fields, the mobile modal in its
     sticky footer beside "View results" (see shop.blade.php). --}}
