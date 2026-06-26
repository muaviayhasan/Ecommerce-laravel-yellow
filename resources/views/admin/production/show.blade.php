@extends('layouts.admin')

@section('title', $order->production_number)

@php
    $statusTone = ['draft' => 'bg-surface-container-high text-on-surface-variant', 'completed' => 'bg-secondary-container text-on-secondary-container', 'cancelled' => 'bg-error-container text-on-error-container'];
    $num = fn ($q) => rtrim(rtrim(number_format((float) $q, 3), '0'), '.');
    $scale = $order->bom ? (float) $order->quantity / max((float) $order->bom->output_quantity, 0.001) : 0;
    $estTotal = ($bomUnitCost ?? 0) * (float) $order->quantity;
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.production.index') }}" class="text-primary font-semibold hover:underline">Production</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">{{ $order->production_number }}</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-2xl font-bold text-on-surface">{{ $order->production_number }}</h2>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold capitalize {{ $statusTone[$order->status] ?? '' }}">{{ $order->status }}</span>
            </div>
            <p class="text-sm text-on-surface-variant mt-1">{{ $num($order->quantity) }} × {{ $order->variant?->product?->name ?? '—' }}@if ($order->produced_at) · produced {{ format_date($order->produced_at) }}@endif</p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            @if ($order->status === 'draft')
                @can('production.edit')
                    <a href="{{ route('admin.production.edit', $order) }}" class="px-4 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors flex items-center gap-2"><span class="material-symbols-outlined text-[20px]">edit</span> Edit</a>
                @endcan
                @can('production.complete')
                    <form method="POST" action="{{ route('admin.production.complete', $order) }}" onsubmit="return confirm('Complete this run? Components will be consumed and finished stock produced.');">
                        @csrf
                        <button type="submit" class="px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2"><span class="material-symbols-outlined text-[20px]">task_alt</span> Complete</button>
                    </form>
                @endcan
                @can('production.delete')
                    <form method="POST" action="{{ route('admin.production.destroy', $order) }}" onsubmit="return confirm('Delete this draft run?');">
                        @csrf @method('DELETE')
                        <button type="submit" title="Delete" class="p-2.5 rounded-lg text-on-surface-variant hover:bg-error-container hover:text-error transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                    </form>
                @endcan
            @elseif ($order->status === 'completed')
                @can('production.complete')
                    <form method="POST" action="{{ route('admin.production.cancel', $order) }}" onsubmit="return confirm('Cancel this run? Finished stock is removed and components are returned.');">
                        @csrf
                        <button type="submit" class="px-4 py-2.5 border border-error text-error font-semibold text-sm rounded-lg hover:bg-error-container transition-colors flex items-center gap-2"><span class="material-symbols-outlined text-[20px]">undo</span> Cancel run</button>
                    </form>
                @endcan
            @endif
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6 items-start">
        <div class="col-span-12 lg:col-span-8">
            <x-admin.panel class="!p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-outline-variant/60">
                    <h3 class="text-lg font-bold text-on-surface">{{ $order->status === 'completed' ? 'Components consumed' : 'Components required' }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                            <tr><th class="px-6 py-3">Component</th><th class="px-6 py-3 text-right">Quantity</th><th class="px-6 py-3 text-right">Unit cost</th><th class="px-6 py-3 text-right">Line cost</th></tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @if ($order->status === 'completed')
                                @foreach ($order->consumptions as $c)
                                    <tr>
                                        <td class="px-6 py-3"><p class="font-medium text-on-surface">{{ $c->component?->product?->name ?? '—' }}</p><p class="text-[11px] text-outline font-mono">{{ $c->component?->sku }}</p></td>
                                        <td class="px-6 py-3 text-right text-on-surface-variant">{{ $num($c->quantity) }}</td>
                                        <td class="px-6 py-3 text-right text-on-surface-variant">{{ format_money($c->unit_cost) }}</td>
                                        <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ format_money($c->line_cost) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                @foreach (($order->bom?->items ?? []) as $item)
                                    @php $need = (float) $item->quantity * (1 + (float) $item->waste_percent / 100) * $scale; $uc = (float) ($item->component?->cost ?? 0); @endphp
                                    <tr>
                                        <td class="px-6 py-3"><p class="font-medium text-on-surface">{{ $item->component?->product?->name ?? '—' }}</p><p class="text-[11px] text-outline font-mono">{{ $item->component?->sku }}</p></td>
                                        <td class="px-6 py-3 text-right {{ $need > (float) ($item->component?->stock_quantity ?? 0) ? 'text-error font-semibold' : 'text-on-surface-variant' }}">{{ $num($need) }}</td>
                                        <td class="px-6 py-3 text-right text-on-surface-variant">{{ format_money($uc) }}</td>
                                        <td class="px-6 py-3 text-right font-semibold text-on-surface">{{ format_money($need * $uc) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-5 border-t border-outline-variant/60 bg-surface-container-low/40">
                    <div class="ml-auto max-w-xs space-y-2 text-sm">
                        @if ($order->status === 'completed')
                            <div class="flex justify-between text-on-surface-variant"><span>Components</span><span>{{ format_money($order->total_component_cost) }}</span></div>
                            <div class="flex justify-between text-on-surface-variant"><span>Labor</span><span>{{ format_money($order->labor_cost) }}</span></div>
                            <div class="flex justify-between text-on-surface-variant"><span>Overhead</span><span>{{ format_money($order->overhead_cost) }}</span></div>
                            <div class="flex justify-between font-bold text-on-surface text-base pt-2 border-t border-outline-variant/60"><span>Finished unit cost</span><span>{{ format_money($order->unit_cost) }}</span></div>
                        @else
                            <div class="flex justify-between text-on-surface-variant"><span>Est. cost / unit</span><span>{{ format_money($bomUnitCost ?? 0) }}</span></div>
                            <div class="flex justify-between font-bold text-on-surface text-base pt-2 border-t border-outline-variant/60"><span>Est. total</span><span>{{ format_money($estTotal) }}</span></div>
                        @endif
                    </div>
                </div>
            </x-admin.panel>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <x-admin.panel title="Summary">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Finished product</dt><dd class="text-on-surface font-medium text-right">{{ $order->variant?->product?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">BOM</dt><dd class="text-on-surface font-medium text-right">{{ $order->bom?->name ?: 'Standard' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Quantity</dt><dd class="text-on-surface font-medium text-right">{{ $num($order->quantity) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-on-surface-variant">Created by</dt><dd class="text-on-surface font-medium text-right">{{ $order->creator?->name ?? '—' }}</dd></div>
                </dl>
                @if ($order->notes)<p class="mt-4 pt-4 border-t border-outline-variant/60 text-sm text-on-surface-variant whitespace-pre-line">{{ $order->notes }}</p>@endif
            </x-admin.panel>
        </div>
    </div>
@endsection
