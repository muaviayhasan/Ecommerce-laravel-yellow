@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Orders</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Orders</h2>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <x-admin.stat-card title="Total orders" tone="primary" icon="receipt_long" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="To fulfil" tone="tertiary" icon="local_shipping" :value="number_format($stats['to_fulfil'])" />
        <x-admin.stat-card title="Revenue (paid)" tone="secondary" icon="payments" :value="format_money($stats['revenue'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search order # or customer…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="status"
                class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select name="payment"
                class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any payment</option>
                @foreach (['paid', 'partial', 'unpaid', 'refunded'] as $pay)
                    <option value="{{ $pay }}" @selected(($filters['payment'] ?? '') === $pay)>{{ ucfirst($pay) }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.orders.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3">Order</th>
                        <th class="px-6 py-3">Customer</th>
                        <th class="px-6 py-3 text-center">Items</th>
                        <th class="px-6 py-3 text-right">Total</th>
                        <th class="px-6 py-3">Payment</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-on-surface hover:text-primary transition-colors">#{{ $order->order_number }}</a>
                                <div class="text-[11px] text-outline">{{ format_date($order->placed_at ?? $order->created_at) }}</div>
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant">{{ $order->customer?->name ?? 'Guest' }}</td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ number_format($order->items_count) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ format_money($order->grand_total) }}</td>
                            <td class="px-6 py-3"><x-admin.order-badge :status="$order->payment_status" type="payment" /></td>
                            <td class="px-6 py-3"><x-admin.order-badge :status="$order->status" /></td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.orders.print', $order) }}" target="_blank" title="Print bill"
                                        class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[20px] leading-none">print</span>
                                    </a>
                                    <a href="{{ route('admin.orders.show', $order) }}" title="View order"
                                        class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[20px] leading-none">visibility</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">receipt_long</span>
                                <p class="mt-3 font-semibold text-on-surface">No orders found</p>
                                <p class="text-sm text-on-surface-variant mt-1">
                                    @if (array_filter($filters)) Try clearing the filters. @else Orders placed on the storefront or POS will appear here. @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/60">
                <x-admin.pagination :paginator="$orders" />
            </div>
        @endif
    </x-admin.panel>
@endsection
