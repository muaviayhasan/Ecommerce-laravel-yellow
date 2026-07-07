@extends('layouts.admin')

@section('title', 'Products')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Products</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Products</h2>
        </div>
        <a href="{{ route('admin.products.create') }}"
            class="px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">add</span> Add product
        </a>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card title="Total products" tone="primary" icon="inventory_2" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Active" tone="secondary" icon="check_circle" :value="number_format($stats['active'])" />
        <x-admin.stat-card title="Live on store" tone="tertiary" icon="public" :value="number_format($stats['web_listed'])" />
        <x-admin.stat-card title="Featured" tone="primary" icon="star" :value="number_format($stats['featured'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or SKU…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="category" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">All categories</option>
                @foreach ($categories as $id => $label)
                    <option value="{{ $id }}" @selected((string) ($filters['category'] ?? '') === (string) $id)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="brand" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">All brands</option>
                @foreach ($brands as $id => $label)
                    <option value="{{ $id }}" @selected((string) ($filters['brand'] ?? '') === (string) $id)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                <option value="web" @selected(($filters['status'] ?? '') === 'web')>Live on store</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.products.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>
            @endif

            {{-- Preserve the active sort when filtering; per-page selector auto-submits. --}}
            <input type="hidden" name="sort" value="{{ $filters['sort'] ?? '' }}">
            <input type="hidden" name="dir" value="{{ $filters['dir'] ?? '' }}">
            @php $ppOptions = collect([15, 25, 50, 100])->push($perPage)->unique()->sort()->values(); @endphp
            <select name="per_page" onchange="this.form.submit()" title="Rows per page"
                class="ml-auto bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                @foreach ($ppOptions as $n)
                    <option value="{{ $n }}" @selected($n === $perPage)>{{ $n }} / page</option>
                @endforeach
            </select>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3"><x-admin.sort-header column="name" label="Product" /></th>
                        <th class="px-6 py-3"><x-admin.sort-header column="sku" label="SKU" /></th>
                        <th class="px-6 py-3 text-right"><x-admin.sort-header column="price" label="Price" /></th>
                        <th class="px-6 py-3 text-center"><x-admin.sort-header column="stock" label="Stock" /></th>
                        <th class="px-6 py-3"><x-admin.sort-header column="status" label="Status" /></th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($products as $product)
                        @php
                            $img = $product->media->first()?->url ?? $product->defaultVariant?->image?->url;
                            $variant = $product->defaultVariant;
                            $stock = $variant ? (float) $variant->stock_quantity : null;
                            $low = $variant ? (float) $variant->low_stock_threshold : 0;
                        @endphp
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-lg bg-surface-container-low border border-outline-variant/40 overflow-hidden grid place-items-center shrink-0">
                                        @if ($img)
                                            <img src="{{ $img }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="material-symbols-outlined text-outline text-[20px]">image</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.products.show', $product) }}" class="font-semibold text-on-surface hover:text-primary transition-colors line-clamp-1">{{ $product->name }}</a>
                                        <div class="text-[11px] text-outline">{{ $product->category?->name ?? '—' }}{{ $product->brand ? ' · ' . $product->brand->name : '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant font-mono text-[12px]">{{ $product->sku }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ format_money($variant?->retail_price ?? $product->base_price ?? 0) }}</td>
                            <td class="px-6 py-3 text-center">
                                @if ($stock === null)
                                    <span class="text-outline text-xs">—</span>
                                @elseif ($stock <= 0)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-error-container text-on-error-container">Out</span>
                                @elseif ($stock <= $low)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-tertiary-fixed text-tertiary">Low · {{ (int) $stock }}</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">{{ (int) $stock }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if (! $product->is_active)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-error-container text-on-error-container">Inactive</span>
                                    @elseif ($product->is_web_listed && $product->published_at)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">Live</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-surface-container-high text-on-surface-variant">Draft</span>
                                    @endif
                                    @if ($product->is_featured)
                                        <span title="Featured" class="material-symbols-outlined text-[16px] text-primary" style="font-variation-settings:'FILL' 1;">star</span>
                                    @endif
                                    @if ($product->is_trending)
                                        <span title="Trending" class="material-symbols-outlined text-[16px] text-tertiary">trending_up</span>
                                    @endif
                                    @if ($product->is_bestseller)
                                        <span title="Bestseller" class="material-symbols-outlined text-[16px] text-secondary">workspace_premium</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.products.show', $product) }}" title="View" class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </a>
                                    @can('products.edit')
                                        <a href="{{ route('admin.products.edit', $product) }}" title="Edit" class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </a>
                                    @endcan
                                    @can('products.delete')
                                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete “{{ $product->name }}”? It will be archived (soft-deleted).');">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Delete" class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-error-container hover:text-error transition-colors">
                                                <span class="material-symbols-outlined text-[20px]">delete</span>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">inventory_2</span>
                                <p class="mt-3 font-semibold text-on-surface">No products found</p>
                                <p class="text-sm text-on-surface-variant mt-1">
                                    @if (array_filter($filters)) Try clearing the filters. @else <a href="{{ route('admin.products.create') }}" class="text-primary font-semibold hover:underline">Add your first product</a>. @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/60">
                <x-admin.pagination :paginator="$products" />
            </div>
        @endif
    </x-admin.panel>
@endsection
