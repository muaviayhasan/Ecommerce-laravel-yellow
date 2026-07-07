@extends('layouts.admin')

@section('title', 'Abandoned carts')

@section('content')
    <div class="mb-2">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">Abandoned carts</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Abandoned carts</h2>
        <p class="text-sm text-on-surface-variant mt-1">Carts that reached checkout but weren’t paid for. Reminders are controlled in
            <a href="{{ route('admin.settings.show', 'emails') }}" class="text-primary font-semibold hover:underline">Settings → Email Notifications → Cart recovery</a>.
        </p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card title="Open carts" tone="primary" icon="shopping_cart" :value="number_format($stats['open'])" />
        <x-admin.stat-card title="Potential value" tone="tertiary" icon="savings" :value="format_money($stats['open_value'])" />
        <x-admin.stat-card title="Recovered" tone="secondary" icon="task_alt" :value="number_format($stats['recovered'])" />
        <x-admin.stat-card title="Recovery rate" tone="primary" icon="trending_up" :value="$stats['rate'] . '%'" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search email or name…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="status" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                <option value="open" @selected(($filters['status'] ?? '') === 'open')>Open</option>
                <option value="reminded" @selected(($filters['status'] ?? '') === 'reminded')>Reminded</option>
                <option value="recovered" @selected(($filters['status'] ?? '') === 'recovered')>Recovered</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.abandoned-carts.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>@endif
            <x-admin.per-page :per-page="$perPage" />
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-on-surface-variant border-b border-outline-variant/60">
                        <th class="px-5 py-3 font-semibold"><x-admin.sort-header column="email" label="Customer" /></th>
                        <th class="px-5 py-3 font-semibold"><x-admin.sort-header column="items" label="Items" /></th>
                        <th class="px-5 py-3 font-semibold"><x-admin.sort-header column="value" label="Value" /></th>
                        <th class="px-5 py-3 font-semibold"><x-admin.sort-header column="reminders" label="Reminders" /></th>
                        <th class="px-5 py-3 font-semibold"><x-admin.sort-header column="reminded" label="Last reminded" /></th>
                        <th class="px-5 py-3 font-semibold"><x-admin.sort-header column="status" label="Status" /></th>
                        <th class="px-5 py-3 font-semibold"><x-admin.sort-header column="created" label="Abandoned" /></th>
                        <th class="px-5 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse ($carts as $cart)
                        <tr class="hover:bg-surface-container-low/50">
                            <td class="px-5 py-3">
                                <div class="font-medium text-on-surface">{{ $cart->email }}</div>
                                @if ($cart->name)<div class="text-xs text-on-surface-variant">{{ $cart->name }}</div>@endif
                            </td>
                            <td class="px-5 py-3 text-on-surface-variant">{{ number_format($cart->item_count) }}</td>
                            <td class="px-5 py-3 font-semibold text-on-surface">{{ format_money($cart->subtotal) }}</td>
                            <td class="px-5 py-3 text-on-surface-variant">{{ $cart->reminders_sent }}</td>
                            <td class="px-5 py-3 text-on-surface-variant">{{ $cart->last_reminded_at ? format_datetime($cart->last_reminded_at) : '—' }}</td>
                            <td class="px-5 py-3">
                                @if ($cart->recovered_at)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">Recovered</span>
                                @elseif ($cart->reminders_sent > 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-tertiary-container text-on-tertiary-container">Reminded</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-surface-container-high text-on-surface-variant">Open</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-on-surface-variant">{{ format_date($cart->created_at) }}</td>
                            <td class="px-5 py-3 text-right">
                                @can('abandoned-carts.delete')
                                    <form method="POST" action="{{ route('admin.abandoned-carts.destroy', $cart) }}" onsubmit="return confirm('Remove this abandoned cart?');" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-on-surface-variant hover:text-error rounded-lg inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">delete</span> Remove</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">remove_shopping_cart</span>
                                <p class="mt-3 font-semibold text-on-surface">No abandoned carts</p>
                                <p class="text-sm text-on-surface-variant mt-1">@if (array_filter($filters)) Try clearing the filters. @else Unfinished checkouts will appear here once cart recovery is enabled. @endif</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($carts->hasPages())<div class="px-6 py-4 border-t border-outline-variant/60"><x-admin.pagination :paginator="$carts" /></div>@endif
    </x-admin.panel>
@endsection
