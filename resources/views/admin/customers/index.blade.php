@extends('layouts.admin')

@section('title', 'Customers')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Customers</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Customers</h2>
        </div>
        <a href="{{ route('admin.customers.create') }}"
            class="bg-primary text-on-primary px-5 py-2.5 rounded-lg font-semibold text-sm flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-sm shadow-primary/20">
            <span class="material-symbols-outlined">add</span> Add customer
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <x-admin.stat-card title="Total customers" tone="primary" icon="groups" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Active" tone="secondary" icon="check_circle" :value="number_format($stats['active'])" />
        <x-admin.stat-card title="Wholesale" tone="tertiary" icon="storefront" :value="number_format($stats['wholesale'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, email or phone…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="type" data-no-select2
                class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">All types</option>
                <option value="retail" @selected(($filters['type'] ?? '') === 'retail')>Retail</option>
                <option value="wholesale" @selected(($filters['type'] ?? '') === 'wholesale')>Wholesale</option>
            </select>
            <select name="status" data-no-select2
                class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.customers.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3">Customer</th>
                        <th class="px-6 py-3">Phone</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3 text-center">Orders</th>
                        <th class="px-6 py-3 text-right">Balance</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-full bg-primary-container text-white grid place-items-center font-bold text-xs shrink-0">
                                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.customers.edit', $customer) }}" class="font-bold text-on-surface hover:text-primary transition-colors block truncate">{{ $customer->name }}</a>
                                        <div class="text-[11px] text-outline truncate">{{ $customer->email ?: '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant">{{ $customer->phone ?: '—' }}</td>
                            <td class="px-6 py-3">
                                <span @class([
                                    'px-2 py-0.5 text-[10px] font-bold rounded-full capitalize',
                                    'bg-tertiary-fixed text-tertiary' => $customer->type === 'wholesale',
                                    'bg-surface-container-high text-on-surface-variant' => $customer->type !== 'wholesale',
                                ])>{{ $customer->type }}</span>
                            </td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ number_format($customer->orders_count) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ format_money($customer->opening_balance) }}</td>
                            <td class="px-6 py-3">
                                @if ($customer->is_active)
                                    <span class="px-2 py-0.5 bg-secondary-container text-on-secondary-container text-[10px] font-bold rounded-full">Active</span>
                                @else
                                    <span class="px-2 py-0.5 bg-surface-container-high text-on-surface-variant text-[10px] font-bold rounded-full">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.customers.edit', $customer) }}" title="Edit"
                                        class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}"
                                        onsubmit="return confirm('Delete “{{ $customer->name }}”? This cannot be undone.')">
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
                            <td colspan="7" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">groups</span>
                                <p class="mt-3 font-semibold text-on-surface">No customers found</p>
                                <p class="text-sm text-on-surface-variant mt-1">
                                    @if (array_filter($filters)) Try clearing the filters, or @endif
                                    <a href="{{ route('admin.customers.create') }}" class="text-primary font-semibold hover:underline">add your first customer</a>.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/60">
                <x-admin.pagination :paginator="$customers" />
            </div>
        @endif
    </x-admin.panel>
@endsection
