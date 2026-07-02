@props([
    'method' => null,   // pickup | own_rider | courier | other
    'agent' => null,    // handled-by name
    'contact' => null,  // phone / tracking number
    'charge' => null,   // delivery charge amount
    'title' => 'Delivery',
])
@php $label = delivery_method_label($method); @endphp
@if ($label || filled($agent) || filled($contact) || (float) $charge > 0)
    <x-admin.panel :title="$title">
        <dl class="space-y-2.5 text-sm">
            @if ($label)
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Method</dt>
                    <dd class="text-on-surface font-medium text-right">{{ $label }}</dd>
                </div>
            @endif
            @if (filled($agent))
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Handled by</dt>
                    <dd class="text-on-surface font-medium text-right">{{ $agent }}</dd>
                </div>
            @endif
            @if (filled($contact))
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Contact / tracking</dt>
                    <dd class="text-on-surface font-medium text-right">{{ $contact }}</dd>
                </div>
            @endif
            @if ((float) $charge > 0)
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Delivery charge</dt>
                    <dd class="text-on-surface font-medium text-right">{{ format_money($charge) }}</dd>
                </div>
            @endif
        </dl>
    </x-admin.panel>
@endif
