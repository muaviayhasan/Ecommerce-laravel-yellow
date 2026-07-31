@props(['status'])
@php
    /*
     | Four states, four hues — the colour carries real information here, so it
     | stays varied. Blue and indigo used to split "confirmed/processing" from
     | "packed/shipped", but both mean the same thing to a customer (in flight)
     | and the label already says which; one `info` hue does that job.
     */
    $waiting = 'bg-warning-container text-on-warning-container';
    $inFlight = 'bg-info-container text-on-info-container';
    $done = 'bg-success-container text-on-success-container';
    $stopped = 'bg-error-container text-on-error-container';
    $neutral = 'bg-surface-container text-on-surface-variant';

    $map = [
        'pending' => ['Pending', $waiting],
        'confirmed' => ['Confirmed', $inFlight],
        'processing' => ['Processing', $inFlight],
        'packed' => ['Packed', $inFlight],
        'shipped' => ['Shipped', $inFlight],
        'out_for_delivery' => ['Out for delivery', $inFlight],
        'delivered' => ['Delivered', $done],
        'completed' => ['Completed', $done],
        'cancelled' => ['Cancelled', $stopped],
        'refunded' => ['Refunded', $neutral],
    ];
    [$label, $classes] = $map[$status] ?? [ucfirst(str_replace('_', ' ', (string) $status)), $neutral];
@endphp
<span {{ $attributes->class(['inline-flex items-center px-2.5 py-0.5 rounded-full text-label-sm font-semibold whitespace-nowrap', $classes]) }}>{{ $label }}</span>
