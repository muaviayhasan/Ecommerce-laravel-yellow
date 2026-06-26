@extends('layouts.admin')

@section('title', 'Ledger')

@php
    $acc = fn ($a) => ucfirst(str_replace('_', ' ', $a));
    $balanced = abs($trialTotals['debit'] - $trialTotals['credit']) < 0.01;
@endphp

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Ledger</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Ledger</h2>
            <p class="text-sm text-on-surface-variant mt-1">The source of truth — every purchase, production run and stock adjustment posts here.</p>
        </div>
    </div>

    {{-- Position --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card title="Cash & bank" tone="primary" icon="account_balance_wallet" :value="format_money($summary['cash'])" />
        <x-admin.stat-card title="Inventory value" tone="secondary" icon="inventory_2" :value="format_money($summary['inventory'])" />
        <x-admin.stat-card title="Accounts payable" tone="tertiary" icon="call_made" :value="format_money($summary['payable'])" />
        <x-admin.stat-card title="Accounts receivable" tone="primary" icon="call_received" :value="format_money($summary['receivable'])" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- P&L --}}
        <x-admin.panel title="Profit &amp; loss">
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-on-surface-variant">Revenue</dt><dd class="font-semibold text-on-surface">{{ format_money($summary['revenue']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-on-surface-variant">Cost of goods sold</dt><dd class="text-on-surface">{{ format_money($summary['cogs']) }}</dd></div>
                <div class="flex justify-between font-bold pt-2 border-t border-outline-variant/60"><dt class="text-on-surface">Gross profit</dt><dd class="{{ $summary['gross_profit'] >= 0 ? 'text-secondary' : 'text-error' }}">{{ format_money($summary['gross_profit']) }}</dd></div>
                <div class="flex justify-between pt-1"><dt class="text-on-surface-variant">Tax collected</dt><dd class="text-on-surface">{{ format_money($summary['tax']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-on-surface-variant">Refunds</dt><dd class="text-on-surface">{{ format_money($summary['refunds']) }}</dd></div>
            </dl>
            @if ($summary['revenue'] == 0.0)
                <p class="mt-4 text-xs text-outline">Revenue &amp; COGS populate once the sales/checkout flow is built — purchasing, production and adjustments are already posting.</p>
            @endif
        </x-admin.panel>

        {{-- Trial balance --}}
        <x-admin.panel class="!p-0 overflow-hidden">
            <div class="px-6 py-4 border-b border-outline-variant/60 flex items-center justify-between">
                <h3 class="text-lg font-bold text-on-surface">Trial balance</h3>
                @if ($trial->isNotEmpty())
                    <span class="flex items-center gap-1 text-[11px] font-bold {{ $balanced ? 'text-secondary' : 'text-error' }}">
                        <span class="material-symbols-outlined text-[16px]">{{ $balanced ? 'check_circle' : 'error' }}</span>{{ $balanced ? 'Balanced' : 'Out of balance' }}
                    </span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                        <tr><th class="px-6 py-3">Account</th><th class="px-6 py-3 text-right">Debit</th><th class="px-6 py-3 text-right">Credit</th></tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @forelse ($trial as $row)
                            <tr>
                                <td class="px-6 py-2.5 text-on-surface">{{ $acc($row['account']) }}</td>
                                <td class="px-6 py-2.5 text-right text-on-surface-variant">{{ $row['debit'] ? format_money($row['debit']) : '—' }}</td>
                                <td class="px-6 py-2.5 text-right text-on-surface-variant">{{ $row['credit'] ? format_money($row['credit']) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-10 text-center text-on-surface-variant">No ledger entries yet.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($trial->isNotEmpty())
                        <tfoot class="border-t border-outline-variant/60 font-bold text-on-surface">
                            <tr><td class="px-6 py-3">Total</td><td class="px-6 py-3 text-right">{{ format_money($trialTotals['debit']) }}</td><td class="px-6 py-3 text-right">{{ format_money($trialTotals['credit']) }}</td></tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-admin.panel>
    </div>

    {{-- Entries --}}
    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-44">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search memo…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary outline-none">
            </div>
            <select name="account" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">All accounts</option>
                @foreach ($accounts as $a)
                    <option value="{{ $a }}" @selected(($filters['account'] ?? '') === $a)>{{ $acc($a) }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" title="From" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" title="To" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none">
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.ledger.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>@endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Account</th>
                        <th class="px-6 py-3 text-right">Debit</th>
                        <th class="px-6 py-3 text-right">Credit</th>
                        <th class="px-6 py-3">Memo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($entries as $entry)
                        @php
                            $ref = $entry->reference;
                            [$refLabel, $refUrl] = match (true) {
                                $ref instanceof \App\Models\Purchase => [$ref->purchase_number, route('admin.purchases.show', $ref)],
                                $ref instanceof \App\Models\ProductionOrder => [$ref->production_number, route('admin.production.show', $ref)],
                                default => [null, null],
                            };
                        @endphp
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3 text-on-surface-variant whitespace-nowrap">{{ format_date($entry->entry_date) }}</td>
                            <td class="px-6 py-3"><span class="px-2 py-0.5 rounded bg-surface-container-high text-on-surface-variant text-[11px] font-semibold">{{ $acc($entry->account) }}</span></td>
                            <td class="px-6 py-3 text-right font-semibold {{ $entry->debit > 0 ? 'text-on-surface' : 'text-outline' }}">{{ $entry->debit > 0 ? format_money($entry->debit) : '—' }}</td>
                            <td class="px-6 py-3 text-right font-semibold {{ $entry->credit > 0 ? 'text-on-surface' : 'text-outline' }}">{{ $entry->credit > 0 ? format_money($entry->credit) : '—' }}</td>
                            <td class="px-6 py-3 text-on-surface-variant">
                                {{ $entry->memo ?: '—' }}
                                @if ($refUrl)<a href="{{ $refUrl }}" class="text-primary font-semibold hover:underline ml-1">{{ $refLabel }}</a>@endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">account_balance</span>
                                <p class="mt-3 font-semibold text-on-surface">No ledger entries</p>
                                <p class="text-sm text-on-surface-variant mt-1">@if (array_filter($filters)) Try clearing the filters. @else Receive a purchase or complete a production run to post entries. @endif</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($entries->hasPages())<div class="px-6 py-4 border-t border-outline-variant/60"><x-admin.pagination :paginator="$entries" /></div>@endif
    </x-admin.panel>
@endsection
