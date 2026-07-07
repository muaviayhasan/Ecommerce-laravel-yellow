@extends('layouts.admin')

@section('title', $quotation->quotation_number)

@php
    $statusStyles = [
        'draft' => 'bg-surface-container-high text-on-surface-variant',
        'sent' => 'bg-primary-container text-on-primary-container',
        'accepted' => 'bg-secondary-container text-on-secondary-container',
        'rejected' => 'bg-error-container text-on-error-container',
        'expired' => 'bg-surface-container-high text-outline',
        'converted' => 'bg-tertiary-container text-on-tertiary-container',
    ];
    $badge = $statusStyles[$quotation->status] ?? 'bg-surface-container-high text-on-surface-variant';
    $locked = $quotation->status === 'converted';
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.quotations.index') }}" class="text-primary font-semibold hover:underline">Quotations</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">{{ $quotation->quotation_number }}</span>
            </div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold text-on-surface">{{ $quotation->quotation_number }}</h2>
                <span class="px-3 py-1 rounded-full text-xs font-bold capitalize {{ $badge }}">{{ $quotation->status }}</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-admin.print-menu :base="route('admin.quotations.print', $quotation)" label="Print" />
            @if (! $locked)
                @can('quotations.edit')
                    <a href="{{ route('admin.quotations.edit', $quotation) }}" class="px-4 py-2 text-sm font-semibold text-on-surface-variant border border-outline-variant rounded-lg hover:bg-surface-container-high flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">edit</span> Edit
                    </a>
                @endcan
                @can('quotations.edit')
                    @foreach ($quotation->allowedTransitions() as $to)
                        <form method="POST" action="{{ route('admin.quotations.status', $quotation) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $to }}">
                            <button type="submit" class="px-3 py-2 text-sm font-semibold text-on-surface-variant border border-outline-variant rounded-lg hover:bg-surface-container-high capitalize">
                                {{ $to === 'sent' && in_array($quotation->status, ['rejected', 'expired'], true) ? 'Re-send' : 'Mark ' . $to }}
                            </button>
                        </form>
                    @endforeach
                @endcan
            @endif

            @if ($quotation->status === 'accepted')
                @can('quotations.convert')
                    <form method="POST" action="{{ route('admin.quotations.convert', $quotation) }}" onsubmit="return confirm('Convert this quotation into an order? Stock will be reserved and the sale recorded.');">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-primary text-on-primary text-sm font-bold rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">point_of_sale</span> Convert to order
                        </button>
                    </form>
                @endcan
            @endif

            @if ($quotation->convertedOrder)
                <a href="{{ route('admin.orders.show', $quotation->convertedOrder) }}" class="px-4 py-2 bg-tertiary-container text-on-tertiary-container text-sm font-bold rounded-lg hover:brightness-110 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span> {{ $quotation->convertedOrder->order_number }}
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6 items-start">
        <div class="col-span-12 lg:col-span-8">
            <x-admin.panel class="!p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-outline-variant/60"><h3 class="font-bold text-on-surface">Line items</h3></div>
                <div class="divide-y divide-outline-variant/40">
                    @foreach ($quotation->items as $item)
                        <div class="flex items-center gap-4 px-6 py-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-on-surface truncate">{{ $item->name_snapshot }}</p>
                                @if ($item->description)<p class="text-xs text-on-surface-variant">{{ $item->description }}</p>@endif
                                <p class="text-[11px] text-outline">{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }} × {{ format_money($item->unit_price) }}</p>
                            </div>
                            <div class="w-28 text-right font-bold text-on-surface shrink-0">{{ format_money($item->line_total) }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="px-6 py-4 border-t border-outline-variant/60 flex justify-end">
                    <div class="w-full max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between text-on-surface-variant"><span>Subtotal</span><span>{{ format_money($quotation->subtotal) }}</span></div>
                        @if ((float) $quotation->discount_total > 0)<div class="flex justify-between text-on-surface-variant"><span>Discount{{ $quotation->discount_type === 'percent' ? ' (' . rtrim(rtrim(number_format((float) $quotation->discount_value, 2), '0'), '.') . '%)' : '' }}</span><span>− {{ format_money($quotation->discount_total) }}</span></div>@endif
                        @if ((float) $quotation->tax_total > 0)<div class="flex justify-between text-on-surface-variant"><span>Tax</span><span>{{ format_money($quotation->tax_total) }}</span></div>@endif
                        <div class="flex justify-between font-bold text-on-surface text-base pt-2 border-t border-outline-variant/60"><span>Grand total</span><span>{{ format_money($quotation->grand_total) }}</span></div>
                    </div>
                </div>
            </x-admin.panel>

            @if ($quotation->notes)
                <x-admin.panel title="Notes" class="mt-6">
                    <p class="text-sm text-on-surface-variant whitespace-pre-line">{{ $quotation->notes }}</p>
                </x-admin.panel>
            @endif
        </div>

        <div class="col-span-12 lg:col-span-4 space-y-6">
            <x-admin.panel title="Details">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Customer</dt><dd class="text-on-surface font-medium text-right">{{ $quotation->customer?->name ?? 'Walk-in / prospect' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Price tier</dt><dd class="text-on-surface font-medium capitalize">{{ $quotation->price_tier }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Valid until</dt><dd class="text-on-surface font-medium">{{ $quotation->valid_until?->format('d M Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Created</dt><dd class="text-on-surface font-medium">{{ $quotation->created_at?->format('d M Y') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">By</dt><dd class="text-on-surface font-medium">{{ $quotation->creator?->name ?? '—' }}</dd></div>
                </dl>

                @if (! $locked)
                    @can('quotations.delete')
                        <form method="POST" action="{{ route('admin.quotations.destroy', $quotation) }}" onsubmit="return confirm('Delete this quotation?');" class="mt-5 pt-5 border-t border-outline-variant/60">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm font-semibold text-error hover:underline flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">delete</span> Delete quotation</button>
                        </form>
                    @endcan
                @endif
            </x-admin.panel>
        </div>
    </div>
@endsection
