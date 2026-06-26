@extends('layouts.admin')

@section('title', 'Bills of materials')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">BOMs</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Bills of materials</h2>
            <p class="text-sm text-on-surface-variant mt-1">Recipes that assemble components into finished products.</p>
        </div>
        @can('boms.create')
            <a href="{{ route('admin.boms.create') }}" class="px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">add</span> New BOM
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <x-admin.stat-card title="Total BOMs" tone="primary" icon="account_tree" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Active" tone="secondary" icon="check_circle" :value="number_format($stats['active'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search product or BOM name…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary outline-none">
            </div>
            <select name="status" data-no-select2 class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.boms.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>@endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3">Product</th>
                        <th class="px-6 py-3 text-right">Output qty</th>
                        <th class="px-6 py-3 text-center">Components</th>
                        <th class="px-6 py-3 text-right">Unit cost</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($boms as $bom)
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.boms.show', $bom) }}" class="font-semibold text-on-surface hover:text-primary transition-colors">{{ $bom->product?->name ?? '—' }}</a>
                                @if ($bom->name)<div class="text-[11px] text-outline">{{ $bom->name }}</div>@endif
                            </td>
                            <td class="px-6 py-3 text-right text-on-surface-variant">{{ rtrim(rtrim(number_format((float) $bom->output_quantity, 3), '0'), '.') }}</td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ number_format($bom->items_count) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ format_money($bom->computed_cost) }}</td>
                            <td class="px-6 py-3">
                                @if ($bom->is_active)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">Active</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-surface-container-high text-on-surface-variant">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.boms.show', $bom) }}" title="View" class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors"><span class="material-symbols-outlined text-[20px]">visibility</span></a>
                                    @can('boms.edit')
                                        <a href="{{ route('admin.boms.edit', $bom) }}" title="Edit" class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">account_tree</span>
                                <p class="mt-3 font-semibold text-on-surface">No BOMs yet</p>
                                <p class="text-sm text-on-surface-variant mt-1">@if (array_filter($filters)) Try clearing the filters. @else <a href="{{ route('admin.boms.create') }}" class="text-primary font-semibold hover:underline">Create your first BOM</a>. @endif</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($boms->hasPages())<div class="px-6 py-4 border-t border-outline-variant/60"><x-admin.pagination :paginator="$boms" /></div>@endif
    </x-admin.panel>
@endsection
