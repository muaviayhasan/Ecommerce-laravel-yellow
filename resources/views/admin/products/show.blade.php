@extends('layouts.admin')

@section('title', $product->name)

@php
    $variant = $product->defaultVariant;
    $images = $product->media;
    $onSale = $variant && $variant->compare_at_price && (float) $variant->compare_at_price > (float) $variant->retail_price;
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.products.index') }}" class="text-primary font-semibold hover:underline">Products</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold line-clamp-1">{{ $product->name }}</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-2xl font-bold text-on-surface">{{ $product->name }}</h2>
                @if (! $product->is_active)
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-error-container text-on-error-container">Inactive</span>
                @elseif ($product->is_web_listed && $product->published_at)
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">Live</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-surface-container-high text-on-surface-variant">Draft</span>
                @endif
            </div>
            <p class="text-sm text-on-surface-variant mt-1 font-mono">{{ $product->sku }}</p>
        </div>
        @can('products.edit')
            <a href="{{ route('admin.products.edit', $product) }}" class="px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 transition-all flex items-center gap-2 shrink-0">
                <span class="material-symbols-outlined text-[20px]">edit</span> Edit
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-12 gap-6 items-start">
        {{-- Left --}}
        <div class="col-span-12 lg:col-span-8 space-y-6">
            {{-- Gallery --}}
            <x-admin.panel title="Images">
                @if ($images->isNotEmpty())
                    <div x-data="{ active: @js($images->first()->url) }">
                        <div class="aspect-[16/10] rounded-xl bg-surface-container-low border border-outline-variant/40 overflow-hidden grid place-items-center mb-3">
                            <img :src="active" alt="{{ $product->name }}" class="w-full h-full object-contain">
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($images as $m)
                                <button type="button" @click="active = @js($m->url)"
                                    :class="active === @js($m->url) ? 'ring-2 ring-primary' : 'border border-outline-variant/40'"
                                    class="w-16 h-16 rounded-lg overflow-hidden bg-surface-container-low shrink-0">
                                    <img src="{{ $m->url }}" alt="" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="aspect-[16/10] rounded-xl bg-surface-container-low border border-dashed border-outline-variant grid place-items-center text-on-surface-variant">
                        <div class="text-center">
                            <span class="material-symbols-outlined" style="font-size:40px;">image</span>
                            <p class="text-sm mt-1">No images yet</p>
                        </div>
                    </div>
                @endif
            </x-admin.panel>

            {{-- Description --}}
            @if ($product->short_description || $product->description)
                <x-admin.panel title="Description">
                    @if ($product->short_description)
                        <p class="text-sm font-medium text-on-surface mb-3">{{ $product->short_description }}</p>
                    @endif
                    @if ($product->description)
                        <div class="text-sm text-on-surface-variant whitespace-pre-line leading-relaxed">{{ $product->description }}</div>
                    @endif
                </x-admin.panel>
            @endif

            {{-- Key features --}}
            @if (! empty($product->highlights))
                <x-admin.panel title="Key features">
                    <ul class="space-y-1.5">
                        @foreach ($product->highlights as $h)
                            <li class="flex items-start gap-2 text-sm text-on-surface-variant">
                                <span class="material-symbols-outlined text-secondary text-[18px] shrink-0">check_circle</span>{{ $h }}
                            </li>
                        @endforeach
                    </ul>
                </x-admin.panel>
            @endif

            {{-- Specifications --}}
            @if (! empty($product->specifications))
                <x-admin.panel title="Specifications">
                    <div class="space-y-4">
                        @foreach ($product->specifications as $group => $rows)
                            <div>
                                @if (! is_numeric($group))<p class="text-xs font-bold text-outline uppercase tracking-wide mb-2">{{ $group }}</p>@endif
                                <dl class="divide-y divide-outline-variant/40">
                                    @foreach ((array) $rows as $k => $v)
                                        <div class="flex justify-between gap-4 py-2 text-sm">
                                            <dt class="text-on-surface-variant">{{ $k }}</dt>
                                            <dd class="text-on-surface font-medium text-right">{{ is_array($v) ? implode(', ', $v) : $v }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endforeach
                    </div>
                </x-admin.panel>
            @endif

            {{-- Variants --}}
            <x-admin.panel class="!p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-outline-variant/60 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-on-surface">Variants</h3>
                    <span class="text-xs text-outline capitalize">{{ $product->variant_mode }} · {{ $product->variants->count() }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                            <tr>
                                <th class="px-6 py-3">SKU</th>
                                <th class="px-6 py-3">Variant</th>
                                <th class="px-6 py-3 text-right">Price</th>
                                <th class="px-6 py-3 text-right">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @foreach ($product->variants as $v)
                                <tr>
                                    <td class="px-6 py-3 font-mono text-[12px] text-on-surface-variant">{{ $v->sku }}</td>
                                    <td class="px-6 py-3">
                                        @if ($v->attributeValues->isNotEmpty())
                                            {{ $v->attributeValues->map(fn ($av) => $av->label ?: $av->value)->implode(' / ') }}
                                        @else
                                            <span class="text-outline">Default</span>
                                        @endif
                                        @if ($v->is_default)<span class="ml-1 px-1.5 py-0.5 rounded bg-primary/10 text-primary text-[9px] font-bold uppercase">Default</span>@endif
                                    </td>
                                    <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ format_money($v->retail_price) }}</td>
                                    <td class="px-6 py-3 text-right text-on-surface-variant">{{ rtrim(rtrim(number_format((float) $v->stock_quantity, 3), '0'), '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.panel>
        </div>

        {{-- Right --}}
        <div class="col-span-12 lg:col-span-4 space-y-6">
            <x-admin.panel title="Overview">
                <div class="flex items-baseline gap-2 mb-4">
                    <span class="text-2xl font-bold text-on-surface">{{ format_money($variant?->retail_price ?? $product->base_price ?? 0) }}</span>
                    @if ($onSale)<span class="text-sm text-outline line-through">{{ format_money($variant->compare_at_price) }}</span>@endif
                </div>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Category</dt><dd class="text-on-surface font-medium text-right">{{ $product->category?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Brand</dt><dd class="text-on-surface font-medium text-right">{{ $product->brand?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Type</dt><dd class="text-on-surface font-medium text-right capitalize">{{ $product->type }}</dd></div>
                    @if ($variant)
                        <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Cost</dt><dd class="text-on-surface font-medium text-right">{{ format_money($variant->cost) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">In stock</dt><dd class="text-on-surface font-medium text-right">{{ rtrim(rtrim(number_format((float) $variant->stock_quantity, 3), '0'), '.') }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Reviews</dt><dd class="text-on-surface font-medium text-right">{{ $product->reviews_count }}</dd></div>
                </dl>
            </x-admin.panel>

            @if ($product->warranty || $product->return_policy || $product->video_url)
                <x-admin.panel title="Details">
                    <dl class="space-y-2.5 text-sm">
                        @if ($product->warranty)<div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Warranty</dt><dd class="text-on-surface font-medium text-right">{{ $product->warranty }}</dd></div>@endif
                        @if ($product->video_url)<div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Video</dt><dd class="text-right"><a href="{{ $product->video_url }}" target="_blank" rel="noopener" class="text-primary font-medium hover:underline">Watch</a></dd></div>@endif
                        @if ($product->return_policy)<div><dt class="text-on-surface-variant mb-1">Return policy</dt><dd class="text-on-surface-variant whitespace-pre-line">{{ $product->return_policy }}</dd></div>@endif
                    </dl>
                </x-admin.panel>
            @endif

            <x-admin.panel title="Storefront placement">
                @php
                    $placements = [
                        ['on' => $product->is_active, 'label' => 'Active', 'icon' => 'check_circle'],
                        ['on' => $product->is_web_listed, 'label' => 'Listed on storefront', 'icon' => 'public'],
                        ['on' => (bool) $product->published_at, 'label' => 'Published', 'icon' => 'visibility'],
                        ['on' => $product->is_featured, 'label' => 'Featured section', 'icon' => 'star'],
                        ['on' => $product->is_trending, 'label' => 'Trending section', 'icon' => 'trending_up'],
                        ['on' => $product->is_bestseller, 'label' => 'Bestsellers section', 'icon' => 'workspace_premium'],
                    ];
                @endphp
                <ul class="space-y-2.5 text-sm">
                    @foreach ($placements as $p)
                        <li class="flex items-center justify-between gap-3">
                            <span class="flex items-center gap-2 text-on-surface-variant">
                                <span class="material-symbols-outlined text-[18px]">{{ $p['icon'] }}</span>{{ $p['label'] }}
                            </span>
                            @if ($p['on'])
                                <span class="material-symbols-outlined text-[20px] text-secondary" style="font-variation-settings:'FILL' 1;">check_circle</span>
                            @else
                                <span class="material-symbols-outlined text-[20px] text-outline">cancel</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-admin.panel>

            @if ($product->meta_title || $product->meta_description)
                <x-admin.panel title="SEO">
                    <dl class="space-y-2.5 text-sm">
                        @if ($product->meta_title)<div><dt class="text-xs text-outline uppercase tracking-wide">Title</dt><dd class="text-on-surface">{{ $product->meta_title }}</dd></div>@endif
                        @if ($product->meta_description)<div><dt class="text-xs text-outline uppercase tracking-wide">Description</dt><dd class="text-on-surface-variant">{{ $product->meta_description }}</dd></div>@endif
                        <div class="flex justify-between gap-4 pt-1"><dt class="text-on-surface-variant">Indexable</dt><dd class="text-on-surface font-medium">{{ $product->no_index ? 'No (noindex)' : 'Yes' }}</dd></div>
                    </dl>
                </x-admin.panel>
            @endif
        </div>
    </div>
@endsection
