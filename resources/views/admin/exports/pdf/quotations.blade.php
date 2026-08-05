<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quotations</title>
    @include('admin.exports.pdf._style')
</head>
<body>
    @include('admin.exports.pdf._watermark')
    <div class="doc-header">
        <h1>{{ config('app.name') }} — Quotations</h1>
        <p class="meta">Generated {{ $generatedAt->format('d M Y, h:i A') }} · {{ $quotations->count() }} quotations</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Quotation #</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Status</th>
                <th class="num">Items</th>
                <th class="num">Grand total</th>
                <th>Valid until</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotations as $q)
                <tr>
                    <td>{{ $q->quotation_number }}</td>
                    <td>{{ format_date($q->created_at) }}</td>
                    <td>{{ $q->customer?->name ?? '—' }}</td>
                    <td>{{ ucfirst($q->status) }}</td>
                    <td class="num">{{ $q->items_count }}</td>
                    <td class="num">{{ format_money($q->grand_total) }}</td>
                    <td>{{ $q->valid_until ? format_date($q->valid_until) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
