@extends('layouts.admin')

@section('title', 'Coupons')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Coupons</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Coupons</h2>
        </div>
        @can('coupons.create')
            <a href="{{ route('admin.coupons.create') }}"
                class="px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">add</span> Add coupon
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card title="Total" tone="primary" icon="confirmation_number" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Active" tone="secondary" icon="check_circle" :value="number_format($stats['active'])" />
        <x-admin.stat-card title="Expired" tone="tertiary" icon="schedule" :value="number_format($stats['expired'])" />
        <x-admin.stat-card title="Redemptions" tone="primary" icon="redeem" :value="number_format($stats['redemptions'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search code or description…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="status" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.coupons.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>@endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3">Code</th>
                        <th class="px-6 py-3">Discount</th>
                        <th class="px-6 py-3">Min spend</th>
                        <th class="px-6 py-3 text-center">Usage</th>
                        <th class="px-6 py-3">Validity</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($coupons as $coupon)
                        @php
                            if (! $coupon->is_active) {
                                [$label, $style] = ['Inactive', 'bg-surface-container-high text-on-surface-variant'];
                            } elseif ($coupon->starts_at && $coupon->starts_at->isFuture()) {
                                [$label, $style] = ['Scheduled', 'bg-primary-container text-on-primary-container'];
                            } elseif ($coupon->expires_at && $coupon->expires_at->isPast()) {
                                [$label, $style] = ['Expired', 'bg-error-container text-on-error-container'];
                            } elseif ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
                                [$label, $style] = ['Used up', 'bg-tertiary-container text-on-tertiary-container'];
                            } else {
                                [$label, $style] = ['Active', 'bg-secondary-container text-on-secondary-container'];
                            }
                        @endphp
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <p class="font-mono font-bold text-on-surface">{{ $coupon->code }}</p>
                                @if ($coupon->description)<p class="text-[11px] text-outline">{{ $coupon->description }}</p>@endif
                            </td>
                            <td class="px-6 py-3 font-semibold text-on-surface">
                                {{ $coupon->type === 'percent' ? rtrim(rtrim(number_format((float) $coupon->value, 2), '0'), '.') . '%' : format_money($coupon->value) }}
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant">{{ $coupon->min_subtotal ? format_money($coupon->min_subtotal) : '—' }}</td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ $coupon->used_count }}{{ $coupon->max_uses ? ' / ' . $coupon->max_uses : '' }}</td>
                            <td class="px-6 py-3 text-on-surface-variant text-xs">
                                {{ $coupon->starts_at?->format('d M Y') ?? 'Now' }} → {{ $coupon->expires_at?->format('d M Y') ?? 'Never' }}
                            </td>
                            <td class="px-6 py-3"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $style }}">{{ $label }}</span></td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @can('coupons.edit')
                                        <a href="{{ route('admin.coupons.edit', $coupon) }}" title="Edit" class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></a>
                                    @endcan
                                    @can('coupons.delete')
                                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Delete coupon “{{ $coupon->code }}”?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Delete" class="p-2 rounded-lg text-on-surface-variant hover:bg-error-container hover:text-error transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">confirmation_number</span>
                                <p class="mt-3 font-semibold text-on-surface">No coupons yet</p>
                                <p class="text-sm text-on-surface-variant mt-1">@if (array_filter($filters)) Try clearing the filters. @else <a href="{{ route('admin.coupons.create') }}" class="text-primary font-semibold hover:underline">Create your first coupon</a>. @endif</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($coupons->hasPages())<div class="px-6 py-4 border-t border-outline-variant/60"><x-admin.pagination :paginator="$coupons" /></div>@endif
    </x-admin.panel>
@endsection
