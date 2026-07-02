@extends('layouts.admin')

@section('title', 'Quotations')

@php
    $statusStyles = [
        'draft' => 'bg-surface-container-high text-on-surface-variant',
        'sent' => 'bg-primary-container text-on-primary-container',
        'accepted' => 'bg-secondary-container text-on-secondary-container',
        'rejected' => 'bg-error-container text-on-error-container',
        'expired' => 'bg-surface-container-high text-outline',
        'converted' => 'bg-tertiary-container text-on-tertiary-container',
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Quotations</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Quotations</h2>
        </div>
        @can('quotations.create')
            <a href="{{ route('admin.quotations.create') }}"
                class="px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">add</span> New quotation
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card title="Total" tone="primary" icon="request_quote" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Open" tone="tertiary" icon="drafts" :value="number_format($stats['open'])" />
        <x-admin.stat-card title="Accepted" tone="secondary" icon="thumb_up" :value="number_format($stats['accepted'])" />
        <x-admin.stat-card title="Converted" tone="primary" icon="receipt_long" :value="number_format($stats['converted'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search number or customer…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="status" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                @foreach ($statuses + ['converted' => 'converted'] as $s)
                    <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.quotations.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>@endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3">Quotation</th>
                        <th class="px-6 py-3">Customer</th>
                        <th class="px-6 py-3 text-center">Items</th>
                        <th class="px-6 py-3 text-right">Total</th>
                        <th class="px-6 py-3">Valid until</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($quotations as $quotation)
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.quotations.show', $quotation) }}" class="font-semibold text-primary hover:underline">{{ $quotation->quotation_number }}</a>
                                <p class="text-[11px] text-outline capitalize">{{ $quotation->price_tier }}</p>
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant">{{ $quotation->customer?->name ?? 'Walk-in / prospect' }}</td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ $quotation->items_count }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ format_money($quotation->grand_total) }}</td>
                            <td class="px-6 py-3 text-on-surface-variant">{{ $quotation->valid_until?->format('d M Y') ?? '—' }}</td>
                            <td class="px-6 py-3"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold capitalize {{ $statusStyles[$quotation->status] ?? '' }}">{{ $quotation->status }}</span></td>
                            <td class="px-6 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.quotations.print', $quotation) }}" target="_blank" title="Print" class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors"><span class="material-symbols-outlined text-[20px] leading-none">print</span></a>
                                    <a href="{{ route('admin.quotations.show', $quotation) }}" title="View" class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors"><span class="material-symbols-outlined text-[20px] leading-none">visibility</span></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">request_quote</span>
                                <p class="mt-3 font-semibold text-on-surface">No quotations yet</p>
                                <p class="text-sm text-on-surface-variant mt-1">@if (array_filter($filters)) Try clearing the filters. @else <a href="{{ route('admin.quotations.create') }}" class="text-primary font-semibold hover:underline">Create your first quotation</a>. @endif</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($quotations->hasPages())<div class="px-6 py-4 border-t border-outline-variant/60"><x-admin.pagination :paginator="$quotations" /></div>@endif
    </x-admin.panel>
@endsection
