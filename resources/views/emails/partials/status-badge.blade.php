{{-- Coloured status pill. Param: $status (order status string). --}}
@php
    $map = [
        'pending' => ['#fff4d6', '#7a5b00', 'Pending'],
        'processing' => ['#e2edff', '#0b4aa2', 'Processing'],
        'shipped' => ['#e9e4ff', '#4a34a8', 'Shipped'],
        'delivered' => ['#dcf5e4', '#1c7a45', 'Delivered'],
        'completed' => ['#dcf5e4', '#1c7a45', 'Completed'],
        'cancelled' => ['#fde3e1', '#a52620', 'Cancelled'],
    ];
    [$bg, $fg, $text] = $map[$status] ?? ['#eceadd', '#55524a', ucfirst((string) $status)];
@endphp
<span style="display:inline-block; padding:5px 12px; border-radius:999px; background:{{ $bg }}; color:{{ $fg }}; font-size:12px; font-weight:700; letter-spacing:0.02em;">{{ $text }}</span>
