@extends('layouts.admin')

@section('title', 'Deals')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Deals</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Deals</h2>
            <p class="text-sm text-on-surface-variant mt-1">Bundle or discount multiple products under one promotion.</p>
        </div>
        @can('deals.create')
            <a href="{{ route('admin.deals.create') }}"
                class="px-5 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2 shrink-0">
                <span class="material-symbols-outlined text-[20px]">add</span> New deal
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <x-admin.stat-card title="Total deals" :value="$stats['total']" icon="sell" />
        <x-admin.stat-card title="Live now" :value="$stats['live']" icon="bolt" tone="secondary" />
        <x-admin.stat-card title="Scheduled" :value="$stats['scheduled']" icon="schedule" tone="tertiary" />
        <x-admin.stat-card title="Expired" :value="$stats['expired']" icon="history_toggle_off" tone="neutral" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="px-6 py-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="255" placeholder="Search deals…"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <button type="submit" class="px-4 py-2 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 transition-all">Filter</button>
        </form>

        @if ($deals->isEmpty())
            <div class="px-6 py-20 text-center text-on-surface-variant">
                <span class="material-symbols-outlined text-outline" style="font-size:56px;">sell</span>
                <p class="mt-3 font-semibold text-on-surface">No deals yet</p>
                <p class="text-sm mt-1">Create a deal to bundle or discount multiple products together.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                        <tr>
                            <th class="px-6 py-3">Deal</th>
                            <th class="px-6 py-3">Items</th>
                            <th class="px-6 py-3">Discount</th>
                            <th class="px-6 py-3">Deal total</th>
                            <th class="px-6 py-3">Window</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40 text-sm">
                        @foreach ($deals as $deal)
                            @php
                                $status = $deal->status();
                                $statusChip = match ($status) {
                                    'live' => 'bg-secondary-container text-on-secondary-container',
                                    'scheduled' => 'bg-tertiary-fixed text-tertiary',
                                    'expired' => 'bg-surface-container-high text-on-surface-variant',
                                    default => 'bg-error-container text-on-error-container',
                                };
                            @endphp
                            <tr class="hover:bg-surface-container-low/40 transition-colors">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-lg bg-surface-container-low border border-outline-variant/40 overflow-hidden grid place-items-center shrink-0">
                                            @if ($deal->image)
                                                <img src="{{ $deal->image->url }}" alt="" loading="lazy" class="w-full h-full object-cover">
                                            @else
                                                <span class="material-symbols-outlined text-outline text-[20px]">sell</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-on-surface truncate">{{ $deal->name }}</p>
                                            <p class="text-[11px] text-outline truncate">/{{ $deal->slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-on-surface-variant">{{ $deal->items_count }}</td>
                                <td class="px-6 py-3 text-on-surface-variant">
                                    @if ((float) $deal->discount_value > 0)
                                        {{ $deal->discount_type === 'percent' ? rtrim(rtrim(number_format((float) $deal->discount_value, 2), '0'), '.') . '%' : format_money($deal->discount_value) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    <span class="font-semibold text-on-surface">{{ format_money($deal->dealTotal()) }}</span>
                                    @if ($deal->discountAmount() > 0)
                                        <span class="text-[11px] text-outline line-through ml-1">{{ format_money($deal->retailTotal()) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-on-surface-variant text-xs">
                                    @if ($deal->starts_at || $deal->ends_at)
                                        {{ $deal->starts_at ? format_date($deal->starts_at) : '…' }} → {{ $deal->ends_at ? format_date($deal->ends_at) : '…' }}
                                    @else
                                        Always on
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold capitalize {{ $statusChip }}">{{ $status }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @can('deals.edit')
                                            <a href="{{ route('admin.deals.edit', $deal) }}" title="Edit" class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                                <span class="material-symbols-outlined text-[20px]">edit</span>
                                            </a>
                                        @endcan
                                        @can('deals.delete')
                                            <button type="button" title="Delete" x-data
                                                @click="$store.pageConfirm.ask(@js('Delete this deal?'), @js('“' . $deal->name . '” will be removed. Products themselves are not affected.'), () => window.__postForm(@js(route('admin.deals.destroy', $deal)), {}, 'DELETE'), 'delete_forever')"
                                                class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-error-container hover:text-error transition-colors">
                                                <span class="material-symbols-outlined text-[20px]">delete</span>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-outline-variant/60">
                <x-admin.pagination :paginator="$deals" />
            </div>
        @endif
    </x-admin.panel>

    <x-admin.confirm-modal />
@endsection
