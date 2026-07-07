@extends('layouts.admin')

@section('title', 'Production')

@php
    $statusTone = ['draft' => 'bg-surface-container-high text-on-surface-variant', 'completed' => 'bg-secondary-container text-on-secondary-container', 'cancelled' => 'bg-error-container text-on-error-container'];
@endphp

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Production</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Production</h2>
            <p class="text-sm text-on-surface-variant mt-1">Assembly runs — completing one consumes components and produces finished stock.</p>
        </div>
        @can('production.create')
            <a href="{{ route('admin.production.create') }}" class="px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">add</span> New run
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <x-admin.stat-card title="Total runs" tone="primary" icon="precision_manufacturing" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Draft" tone="tertiary" icon="edit_note" :value="number_format($stats['draft'])" />
        <x-admin.stat-card title="Completed" tone="secondary" icon="task_alt" :value="number_format($stats['completed'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search number or product…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary outline-none">
            </div>
            <select name="status" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                @foreach (['draft', 'completed', 'cancelled'] as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.production.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>@endif
            <x-admin.per-page :per-page="$perPage" />
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3"><x-admin.sort-header column="run" label="Run" /></th>
                        <th class="px-6 py-3">Finished product</th>
                        <th class="px-6 py-3 text-right"><x-admin.sort-header column="quantity" label="Quantity" /></th>
                        <th class="px-6 py-3 text-right"><x-admin.sort-header column="cost" label="Unit cost" /></th>
                        <th class="px-6 py-3"><x-admin.sort-header column="status" label="Status" /></th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.production.show', $order) }}" class="font-bold text-on-surface hover:text-primary transition-colors">{{ $order->production_number }}</a>
                                <div class="text-[11px] text-outline">{{ $order->produced_at ? format_date($order->produced_at) : format_date($order->created_at) }}</div>
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant">{{ $order->variant?->product?->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-right text-on-surface-variant">{{ rtrim(rtrim(number_format((float) $order->quantity, 3), '0'), '.') }}</td>
                            <td class="px-6 py-3 text-right text-on-surface-variant">{{ $order->status === 'completed' ? format_money($order->unit_cost) : '—' }}</td>
                            <td class="px-6 py-3"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold capitalize {{ $statusTone[$order->status] ?? '' }}">{{ $order->status }}</span></td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end">
                                    <a href="{{ route('admin.production.show', $order) }}" title="View" class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors"><span class="material-symbols-outlined text-[20px]">visibility</span></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">precision_manufacturing</span>
                                <p class="mt-3 font-semibold text-on-surface">No production runs</p>
                                <p class="text-sm text-on-surface-variant mt-1">@if (array_filter($filters)) Try clearing the filters. @else Start one from a BOM. @endif</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())<div class="px-6 py-4 border-t border-outline-variant/60"><x-admin.pagination :paginator="$orders" /></div>@endif
    </x-admin.panel>
@endsection
