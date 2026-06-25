@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Categories</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Categories</h2>
        </div>
        <a href="{{ route('admin.categories.create') }}"
            class="bg-primary text-on-primary px-5 py-2.5 rounded-lg font-semibold text-sm flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-sm shadow-primary/20">
            <span class="material-symbols-outlined">add</span>
            Add category
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <x-admin.stat-card title="Total categories" tone="primary" icon="category" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Active" tone="secondary" icon="check_circle" :value="number_format($stats['active'])" />
        <x-admin.stat-card title="Top-level" tone="tertiary" icon="account_tree" :value="number_format($stats['roots'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        {{-- Filter bar --}}
        <form method="GET" class="p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or slug…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>

            <select name="parent" data-no-select2
                class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">All parents</option>
                @foreach ($parents as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['parent'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>

            <select name="status" data-no-select2
                class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.categories.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3">Category</th>
                        <th class="px-6 py-3">Parent</th>
                        <th class="px-6 py-3 text-center">Products</th>
                        <th class="px-6 py-3 text-center">Sort</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-surface-container-low border border-outline-variant/40 overflow-hidden flex items-center justify-center shrink-0">
                                        @if ($category->image)
                                            <img src="{{ $category->image->url }}" alt="{{ $category->name }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="material-symbols-outlined text-outline text-[20px]">category</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-on-surface truncate">{{ $category->name }}</div>
                                        <div class="text-[11px] text-outline truncate">/{{ $category->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant">{{ $category->parent?->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ number_format($category->products_count) }}</td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ $category->sort_order }}</td>
                            <td class="px-6 py-3">
                                @if ($category->is_active)
                                    <span class="px-2 py-0.5 bg-secondary-container text-on-secondary-container text-[10px] font-bold rounded-full">Active</span>
                                @else
                                    <span class="px-2 py-0.5 bg-surface-container-high text-on-surface-variant text-[10px] font-bold rounded-full">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.categories.edit', $category) }}" title="Edit"
                                        class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                        onsubmit="return confirm('Delete “{{ $category->name }}”? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete"
                                            class="p-2 rounded-lg text-on-surface-variant hover:bg-error-container/50 hover:text-error transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">category</span>
                                <p class="mt-3 font-semibold text-on-surface">No categories found</p>
                                <p class="text-sm text-on-surface-variant mt-1">
                                    @if (array_filter($filters))
                                        Try clearing the filters, or
                                    @endif
                                    <a href="{{ route('admin.categories.create') }}" class="text-primary font-semibold hover:underline">add your first category</a>.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/60">
                <x-admin.pagination :paginator="$categories" />
            </div>
        @endif
    </x-admin.panel>
@endsection
