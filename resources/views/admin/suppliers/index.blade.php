@extends('layouts.admin')

@section('title', 'Suppliers')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Suppliers</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Suppliers</h2>
        </div>
        @can('suppliers.create')
            <a href="{{ route('admin.suppliers.create') }}"
                class="px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">add</span> Add supplier
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <x-admin.stat-card title="Total suppliers" tone="primary" icon="local_shipping" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Active" tone="secondary" icon="check_circle" :value="number_format($stats['active'])" />
        <x-admin.stat-card title="Payable" tone="tertiary" icon="account_balance" :value="format_money($stats['payable'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, company, phone…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="status" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.suppliers.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>
            @endif
            <x-admin.per-page :per-page="$perPage" />
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3"><x-admin.sort-header column="name" label="Supplier" /></th>
                        <th class="px-6 py-3">Contact</th>
                        <th class="px-6 py-3 text-center"><x-admin.sort-header column="purchases" label="Purchases" /></th>
                        <th class="px-6 py-3 text-right">Balance</th>
                        <th class="px-6 py-3"><x-admin.sort-header column="status" label="Status" /></th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($suppliers as $supplier)
                        @php $balance = (float) $supplier->opening_balance + (float) ($supplier->received_grand ?? 0) - (float) ($supplier->received_paid ?? 0); @endphp
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <p class="font-semibold text-on-surface">{{ $supplier->name }}</p>
                                @if ($supplier->company)<p class="text-[11px] text-outline">{{ $supplier->company }}</p>@endif
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant">
                                @if ($supplier->phone)<div>{{ $supplier->phone }}</div>@endif
                                @if ($supplier->email)<div class="text-[11px] text-outline">{{ $supplier->email }}</div>@endif
                                @if (! $supplier->phone && ! $supplier->email)<span class="text-outline">—</span>@endif
                            </td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ number_format($supplier->purchases_count) }}</td>
                            <td class="px-6 py-3 text-right font-semibold {{ $balance > 0 ? 'text-error' : 'text-on-surface' }}">{{ format_money($balance) }}</td>
                            <td class="px-6 py-3">
                                @if ($supplier->is_active)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">Active</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-surface-container-high text-on-surface-variant">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @can('suppliers.edit')
                                        <a href="{{ route('admin.suppliers.edit', $supplier) }}" title="Edit" class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </a>
                                    @endcan
                                    @can('suppliers.delete')
                                        <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete “{{ $supplier->name }}”?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Delete" class="p-2 rounded-lg text-on-surface-variant hover:bg-error-container hover:text-error transition-colors">
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
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">local_shipping</span>
                                <p class="mt-3 font-semibold text-on-surface">No suppliers found</p>
                                <p class="text-sm text-on-surface-variant mt-1">
                                    @if (array_filter($filters)) Try clearing the filters. @else <a href="{{ route('admin.suppliers.create') }}" class="text-primary font-semibold hover:underline">Add your first supplier</a>. @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($suppliers->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/60">
                <x-admin.pagination :paginator="$suppliers" />
            </div>
        @endif
    </x-admin.panel>
@endsection
