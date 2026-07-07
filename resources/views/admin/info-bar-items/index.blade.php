@extends('layouts.admin')

@section('title', 'Info Bar')

@section('content')
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Info Bar</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Info Bar</h2>
            <p class="text-sm text-on-surface-variant mt-1">The icon + text strip on the home page (Free Delivery, Secure Payment, …).</p>
        </div>
        <a href="{{ route('admin.info-bar-items.create') }}"
            class="bg-primary text-on-primary px-5 py-2.5 rounded-lg font-semibold text-sm flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-sm shadow-primary/20">
            <span class="material-symbols-outlined">add</span>
            Add item
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <x-admin.stat-card title="Total items" tone="primary" icon="view_week" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Active" tone="secondary" icon="check_circle" :value="number_format($stats['active'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="p-4 border-b border-outline-variant/60 flex items-center">
            <x-admin.per-page :per-page="$perPage" />
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3"><x-admin.sort-header column="title" label="Item" /></th>
                        <th class="px-6 py-3"><x-admin.sort-header column="icon" label="Icon name" /></th>
                        <th class="px-6 py-3 text-center"><x-admin.sort-header column="sort" label="Sort" /></th>
                        <th class="px-6 py-3"><x-admin.sort-header column="status" label="Status" /></th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($items as $item)
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-primary-container text-[28px] shrink-0">{{ $item->icon }}</span>
                                    <div class="min-w-0">
                                        <div class="font-bold text-on-surface truncate">{{ $item->title }}</div>
                                        <div class="text-[11px] text-outline truncate">{{ $item->subtitle ?: '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant font-mono text-xs">{{ $item->icon }}</td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ $item->sort_order }}</td>
                            <td class="px-6 py-3">
                                @if ($item->is_active)
                                    <span class="px-2 py-0.5 bg-secondary-container text-on-secondary-container text-[10px] font-bold rounded-full">Active</span>
                                @else
                                    <span class="px-2 py-0.5 bg-surface-container-high text-on-surface-variant text-[10px] font-bold rounded-full">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.info-bar-items.edit', $item) }}" title="Edit"
                                        class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.info-bar-items.destroy', $item) }}"
                                        onsubmit="return confirm('Delete this item? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete"
                                            class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-error-container/50 hover:text-error transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">view_week</span>
                                <p class="mt-3 font-semibold text-on-surface">No info bar items yet</p>
                                <p class="text-sm text-on-surface-variant mt-1">
                                    <a href="{{ route('admin.info-bar-items.create') }}" class="text-primary font-semibold hover:underline">Add your first item</a>
                                    to fill the home page info strip.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/60">
                <x-admin.pagination :paginator="$items" />
            </div>
        @endif
    </x-admin.panel>
@endsection
