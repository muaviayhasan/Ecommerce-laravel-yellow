<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Orders report</title>
    @include('admin.exports.pdf._style')
</head>
<body>
    @include('admin.exports.pdf._watermark')
    <div class="doc-header">
        <h1>{{ config('app.name') }} — Orders report</h1>
        <p class="meta">Generated {{ $generatedAt->format('d M Y, h:i A') }} · {{ $orders->count() }} orders</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Payment</th>
                <th class="num">Total</th>
                <th class="num">Paid</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $o)
                <tr>
                    <td>{{ $o->order_number }}</td>
                    <td>{{ format_date($o->placed_at ?? $o->created_at) }}</td>
                    <td>{{ $o->customer?->name ?? 'Guest' }}</td>
                    <td>{{ ucfirst($o->status) }}</td>
                    <td>{{ ucfirst($o->payment_status) }}</td>
                    <td class="num">{{ format_money($o->grand_total) }}</td>
                    <td class="num">{{ format_money($o->paid_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
