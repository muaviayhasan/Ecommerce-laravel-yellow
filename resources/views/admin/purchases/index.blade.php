@extends('layouts.admin')

@section('title', 'Purchases')

@php
    $statusTone = [
        'draft' => 'bg-surface-container-high text-on-surface-variant',
        'received' => 'bg-secondary-container text-on-secondary-container',
        'cancelled' => 'bg-error-container text-on-error-container',
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Purchases</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Purchases</h2>
        </div>
        @can('purchases.create')
            <a href="{{ route('admin.purchases.create') }}"
                class="px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">add</span> New purchase
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card title="Total" tone="primary" icon="inventory" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Draft" tone="tertiary" icon="edit_note" :value="number_format($stats['draft'])" />
        <x-admin.stat-card title="Received" tone="secondary" icon="inventory_2" :value="number_format($stats['received'])" />
        <x-admin.stat-card title="Payable" tone="primary" icon="account_balance" :value="format_money($stats['payable'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search number, reference, supplier…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="supplier" data-no-select2 class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">All suppliers</option>
                @foreach ($suppliers as $id => $name)
                    <option value="{{ $id }}" @selected((string) ($filters['supplier'] ?? '') === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="status" data-no-select2 class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                @foreach (['draft', 'received', 'cancelled'] as $s)
                    <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.purchases.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3">Purchase</th>
                        <th class="px-6 py-3">Supplier</th>
                        <th class="px-6 py-3 text-center">Items</th>
                        <th class="px-6 py-3 text-right">Total</th>
                        <th class="px-6 py-3 text-right">Payable</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($purchases as $purchase)
                        @php $payable = $purchase->status === 'received' ? (float) $purchase->grand_total - (float) $purchase->paid_total : 0; @endphp
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.purchases.show', $purchase) }}" class="font-bold text-on-surface hover:text-primary transition-colors">{{ $purchase->purchase_number }}</a>
                                <div class="text-[11px] text-outline">{{ format_date($purchase->purchase_date) }}</div>
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant">{{ $purchase->supplier?->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ number_format($purchase->items_count) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ format_money($purchase->grand_total) }}</td>
                            <td class="px-6 py-3 text-right {{ $payable > 0 ? 'text-error font-semibold' : 'text-outline' }}">{{ $payable > 0 ? format_money($payable) : '—' }}</td>
                            <td class="px-6 py-3"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold capitalize {{ $statusTone[$purchase->status] ?? '' }}">{{ $purchase->status }}</span></td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end">
                                    <a href="{{ route('admin.purchases.show', $purchase) }}" title="View" class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">inventory</span>
                                <p class="mt-3 font-semibold text-on-surface">No purchases found</p>
                                <p class="text-sm text-on-surface-variant mt-1">
                                    @if (array_filter($filters)) Try clearing the filters. @else <a href="{{ route('admin.purchases.create') }}" class="text-primary font-semibold hover:underline">Create your first purchase</a>. @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($purchases->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/60">
                <x-admin.pagination :paginator="$purchases" />
            </div>
        @endif
    </x-admin.panel>
@endsection
