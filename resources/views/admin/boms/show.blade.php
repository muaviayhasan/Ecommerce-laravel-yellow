@extends('layouts.admin')

@section('title', 'BOM · ' . ($bom->product?->name ?? ''))

@php
    $statusTone = ['draft' => 'bg-surface-container-high text-on-surface-variant', 'completed' => 'bg-secondary-container text-on-secondary-container', 'cancelled' => 'bg-error-container text-on-error-container'];
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.boms.index') }}" class="text-primary font-semibold hover:underline">BOMs</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">{{ $bom->product?->name }}</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-2xl font-bold text-on-surface">{{ $bom->product?->name ?? 'BOM' }}</h2>
                @if (! $bom->is_active)<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-surface-container-high text-on-surface-variant">Inactive</span>@endif
            </div>
            <p class="text-sm text-on-surface-variant mt-1">{{ $bom->name ?: 'Standard build' }} · makes {{ rtrim(rtrim(number_format((float) $bom->output_quantity, 3), '0'), '.') }} per run</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @can('boms.edit')
                <a href="{{ route('admin.boms.edit', $bom) }}" class="px-4 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">edit</span> Edit
                </a>
            @endcan
            @can('production.create')
                @if ($bom->is_active)
                    <a href="{{ route('admin.production.create', ['bom' => $bom->id]) }}" class="px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">precision_manufacturing</span> Start production
                    </a>
                @endif
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6 items-start">
        <div class="col-span-12 lg:col-span-8 space-y-6">
            <x-admin.panel class="!p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-outline-variant/60"><h3 class="text-lg font-bold text-on-surface">Components</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                            <tr>
                                <th class="px-6 py-3">Component</th>
                                <th class="px-6 py-3 text-right">Qty</th>
                                <th class="px-6 py-3 text-right">Waste</th>
                                <th class="px-6 py-3 text-right">Unit cost</th>
                                <th class="px-6 py-3 text-right">Line cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @foreach ($bom->items as $item)
                                @php $line = (float) ($item->component?->cost ?? 0) * (float) $item->quantity * (1 + (float) $item->waste_percent / 100); @endphp
                                <tr>
                                    <td class="px-6 py-3">
                                        <p class="font-medium text-on-surface">{{ $item->component?->product?->name ?? '—' }}</p>
                                        <p class="text-[11px] text-outline font-mono">{{ $item->component?->sku }}</p>
                                    </td>
                                    <td class="px-6 py-3 text-right text-on-surface-variant">{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</td>
                                    <td class="px-6 py-3 text-right text-on-surface-variant">{{ rtrim(rtrim(number_format((float) $item->waste_percent, 2), '0'), '.') }}%</td>
                                    <td class="px-6 py-3 text-right text-on-surface-variant">{{ format_money($item->component?->cost ?? 0) }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ format_money($line) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-5 border-t border-outline-variant/60 bg-surface-container-low/40">
                    <div class="ml-auto max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between text-on-surface-variant"><span>Labor</span><span>{{ format_money($bom->labor_cost) }}</span></div>
                        <div class="flex justify-between text-on-surface-variant"><span>Overhead</span><span>{{ format_money($bom->overhead_cost) }}</span></div>
                        <div class="flex justify-between font-bold text-on-surface text-base pt-2 border-t border-outline-variant/60"><span>Cost / unit</span><span>{{ format_money($unitCost) }}</span></div>
                    </div>
                </div>
            </x-admin.panel>

            @if ($bom->productionOrders->isNotEmpty())
                <x-admin.panel class="!p-0 overflow-hidden">
                    <div class="px-6 py-4 border-b border-outline-variant/60"><h3 class="text-lg font-bold text-on-surface">Recent production</h3></div>
                    <div class="divide-y divide-outline-variant/40">
                        @foreach ($bom->productionOrders as $po)
                            <a href="{{ route('admin.production.show', $po) }}" class="flex items-center justify-between px-6 py-3 text-sm hover:bg-surface-container-high/50 transition-colors">
                                <span class="font-semibold text-on-surface">{{ $po->production_number }}</span>
                                <span class="text-on-surface-variant">{{ rtrim(rtrim(number_format((float) $po->quantity, 3), '0'), '.') }} units</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold capitalize {{ $statusTone[$po->status] ?? '' }}">{{ $po->status }}</span>
                            </a>
                        @endforeach
                    </div>
                </x-admin.panel>
            @endif
        </div>

        <div class="col-span-12 lg:col-span-4">
            <x-admin.panel title="Summary">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Product</dt><dd class="text-on-surface font-medium text-right">{{ $bom->product?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Output / run</dt><dd class="text-on-surface font-medium text-right">{{ rtrim(rtrim(number_format((float) $bom->output_quantity, 3), '0'), '.') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Components</dt><dd class="text-on-surface font-medium text-right">{{ $bom->items->count() }}</dd></div>
                    <div class="flex justify-between gap-4 pt-2 border-t border-outline-variant/60"><dt class="text-on-surface-variant">Cost / unit</dt><dd class="text-on-surface font-bold text-right">{{ format_money($unitCost) }}</dd></div>
                </dl>
            </x-admin.panel>
        </div>
    </div>
@endsection
