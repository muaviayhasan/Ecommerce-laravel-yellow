@extends('layouts.admin')

@section('title', 'Reports')

@php
    $card = 'bg-surface-container-lowest dark:bg-surface-container rounded-xl border border-outline-variant shadow-sm';
    $tones = [
        'primary' => ['chip' => 'bg-primary/10 text-primary', 'bar' => 'bg-primary'],
        'tertiary' => ['chip' => 'bg-tertiary/10 text-tertiary', 'bar' => 'bg-tertiary'],
        'secondary' => ['chip' => 'bg-secondary/10 text-secondary', 'bar' => 'bg-secondary'],
    ];
@endphp

@section('content')
    {{-- Header --}}
    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Reports</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Reports</h2>
            <p class="text-sm text-on-surface-variant mt-1">Last 12 months · live from your orders &amp; customers.</p>
        </div>
        @can('reports.export')
            <a href="{{ route('admin.reports.export') }}"
                class="flex items-center gap-2 px-4 py-2.5 border border-outline-variant text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[18px]">download</span> Export CSV
            </a>
        @endcan
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach ($kpis as $kpi)
            @php
                $tone = $tones[$kpi['tone']];
                $sparkMax = max(1, max($kpi['spark']));
                $tr = $kpi['trend'];
                [$trColor, $trIcon] = $tr > 0 ? ['text-secondary bg-secondary/10', 'trending_up'] : ($tr < 0 ? ['text-error bg-error/10', 'trending_down'] : ['text-on-surface-variant bg-on-surface-variant/10', 'horizontal_rule']);
            @endphp
            <div class="{{ $card }} p-6 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg {{ $tone['chip'] }}">
                            <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">{{ $kpi['icon'] }}</span>
                        </div>
                        <div>
                            <p class="text-label-md text-on-surface-variant">{{ $kpi['label'] }}</p>
                            <h3 class="text-2xl font-bold text-on-surface">{{ $kpi['money'] ? format_money($kpi['value']) : number_format($kpi['value']) }}</h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 text-label-md font-semibold px-2 py-0.5 rounded-full {{ $trColor }}">
                        <span class="material-symbols-outlined text-[14px]">{{ $trIcon }}</span>
                        <span>{{ number_format(abs($tr), 1) }}%</span>
                    </div>
                </div>
                <div class="flex items-end gap-[3px] h-12 mt-5">
                    @foreach ($kpi['spark'] as $v)
                        <div class="flex-1 {{ $tone['bar'] }} rounded-t-sm opacity-70" style="height: {{ max(4, round(($v / $sparkMax) * 100)) }}%"></div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Earnings + Monthly sales --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Earnings (revenue vs profit) --}}
        <div class="{{ $card }} p-6">
            <div class="flex justify-between items-start mb-6">
                <h4 class="text-lg font-bold text-on-surface">Revenue &amp; profit</h4>
                <div class="text-right text-label-sm">
                    <p class="text-on-surface-variant">Avg order <span class="font-bold text-on-surface">{{ format_money($summary['avg_order']) }}</span></p>
                </div>
            </div>
            <div class="flex gap-6 mb-5 text-sm">
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-primary"></span><span class="text-on-surface-variant">Revenue</span><span class="font-bold text-on-surface">{{ format_money($earnings['revenue_total']) }}</span></div>
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-surface-dim dark:bg-on-surface-variant"></span><span class="text-on-surface-variant">Profit</span><span class="font-bold text-on-surface">{{ format_money($earnings['profit_total']) }}</span></div>
            </div>
            <div class="h-[220px] flex items-end justify-between gap-2 border-b border-outline-variant pt-6">
                @foreach ($labels as $i => $label)
                    @php
                        $revH = max(2, round(($earnings['revenue'][$i] / $earnings['max']) * 100));
                        $profH = max(2, round((max($earnings['profit'][$i], 0) / $earnings['max']) * 100));
                    @endphp
                    <div class="flex-1 flex flex-col items-center gap-1.5 group">
                        <div class="w-full flex items-end justify-center gap-1 h-[180px]">
                            <div class="w-2.5 bg-primary rounded-t-sm group-hover:opacity-80 transition-opacity" style="height: {{ $revH }}%" title="Revenue: {{ format_money($earnings['revenue'][$i]) }}"></div>
                            <div class="w-2.5 bg-surface-dim dark:bg-on-surface-variant rounded-t-sm group-hover:opacity-80 transition-opacity" style="height: {{ $profH }}%" title="Profit: {{ format_money($earnings['profit'][$i]) }}"></div>
                        </div>
                        <span class="text-label-sm text-on-surface-variant">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Monthly sales --}}
        <div class="{{ $card }} p-6">
            <div class="flex justify-between items-start mb-6">
                <h4 class="text-lg font-bold text-on-surface">Monthly sales</h4>
                <p class="text-label-sm text-on-surface-variant">Total <span class="font-bold text-on-surface">{{ format_money($sales['total']) }}</span></p>
            </div>
            <div class="h-[220px] flex items-end justify-between gap-2 border-b border-outline-variant pt-6 mt-[44px]">
                @foreach ($labels as $i => $label)
                    @php $h = max(2, round(($sales['data'][$i] / $sales['max']) * 100)); @endphp
                    <div class="flex-1 flex flex-col items-center gap-1.5 group">
                        <div class="w-full flex items-end justify-center h-[180px]">
                            <div class="w-5 bg-primary-fixed-dim dark:bg-primary-container rounded-t-sm group-hover:bg-primary transition-colors" style="height: {{ $h }}%" title="{{ format_money($sales['data'][$i]) }}"></div>
                        </div>
                        <span class="text-label-sm text-on-surface-variant">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Sales vs returns line chart --}}
    <div class="{{ $card }} p-6">
        <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
            <div>
                <h4 class="text-lg font-bold text-on-surface">Sales vs returns</h4>
                <p class="text-2xl font-bold text-on-surface mt-1">{{ format_money($line['sales_total']) }}
                    <span class="text-sm font-medium {{ $line['return_rate'] > 0 ? 'text-error' : 'text-secondary' }} inline-flex items-center align-middle">
                        <span class="material-symbols-outlined text-[16px]">{{ $line['return_rate'] > 0 ? 'undo' : 'check' }}</span> {{ $line['return_rate'] }}% returns
                    </span>
                </p>
            </div>
            <div class="flex items-center gap-4 text-label-sm">
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-primary"></span><span class="text-on-surface-variant">Sales {{ format_money($line['sales_total']) }}</span></div>
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-tertiary"></span><span class="text-on-surface-variant">Returns {{ format_money($line['returns_total']) }}</span></div>
            </div>
        </div>
        <div class="h-[240px]">
            <svg class="w-full h-full overflow-visible" viewBox="0 0 1000 180" preserveAspectRatio="none">
                @foreach ([0, 45, 90, 135, 180] as $y)
                    <line class="stroke-outline-variant/40" stroke-dasharray="4" stroke-width="1" x1="0" x2="1000" y1="{{ $y }}" y2="{{ $y }}" />
                @endforeach
                @if ($line['salesArea'])<path class="fill-primary/10" d="{{ $line['salesArea'] }}" />@endif
                @if ($line['salesPath'])<path class="fill-none stroke-primary" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="{{ $line['salesPath'] }}" />@endif
                @if ($line['returnsPath'])<path class="fill-none stroke-tertiary" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="2 6" d="{{ $line['returnsPath'] }}" />@endif
            </svg>
        </div>
        <div class="flex justify-between mt-3 text-label-sm text-outline">
            @foreach ($labels as $label)<span>{{ $label }}</span>@endforeach
        </div>
    </div>

    {{-- Recent orders + top products --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Recent orders --}}
        <div class="{{ $card }} lg:col-span-2 overflow-hidden">
            <div class="p-6 border-b border-outline-variant flex justify-between items-center">
                <h4 class="text-lg font-bold text-on-surface">Recent orders</h4>
                <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-primary hover:underline">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60 bg-surface-container-low/40">
                        <tr>
                            <th class="px-6 py-3">Order</th>
                            <th class="px-6 py-3">Customer</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @forelse ($recentOrders as $order)
                            <tr class="hover:bg-surface-container-high/50 transition-colors">
                                <td class="px-6 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-on-surface hover:text-primary">#{{ $order->order_number }}</a></td>
                                <td class="px-6 py-3 text-on-surface-variant">{{ $order->customer?->name ?? 'Guest' }}</td>
                                <td class="px-6 py-3 text-on-surface-variant">{{ format_date($order->placed_at ?? $order->created_at) }}</td>
                                <td class="px-6 py-3"><x-admin.order-badge :status="$order->status" /></td>
                                <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ format_money($order->grand_total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-on-surface-variant">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top products --}}
        <div class="{{ $card }} p-6">
            <h4 class="text-lg font-bold text-on-surface mb-4">Top products</h4>
            <div class="space-y-3">
                @forelse ($topProducts as $i => $p)
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 shrink-0 grid place-items-center rounded-md bg-primary/10 text-primary text-xs font-bold">{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-on-surface line-clamp-1">{{ $p->name }}</p>
                            <p class="text-[11px] text-outline">{{ rtrim(rtrim(number_format((float) $p->units, 3), '0'), '.') }} sold</p>
                        </div>
                        <span class="text-sm font-semibold text-on-surface">{{ format_money($p->revenue) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-on-surface-variant">No sales yet.</p>
                @endforelse
            </div>
            <div class="mt-6 pt-4 border-t border-outline-variant/60 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-on-surface-variant">Gross profit (12 mo)</span><span class="font-semibold text-on-surface">{{ format_money($summary['profit_total']) }}</span></div>
                <div class="flex justify-between"><span class="text-on-surface-variant">Pending fulfilment</span><span class="font-semibold text-on-surface">{{ number_format($summary['pending']) }}</span></div>
            </div>
        </div>
    </div>
@endsection
