<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Customers</title>
    @include('admin.exports.pdf._style')
</head>
<body>
    @include('admin.exports.pdf._watermark')
    <div class="doc-header">
        <h1>{{ config('app.name') }} — Customers</h1>
        <p class="meta">Generated {{ $generatedAt->format('d M Y, h:i A') }} · {{ $customers->count() }} customers</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Type</th>
                <th>Price tier</th>
                <th class="num">Opening balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($customers as $i => $c)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->email ?? '—' }}</td>
                    <td>{{ $c->phone ?? '—' }}</td>
                    <td>{{ ucfirst($c->type) }}</td>
                    <td>{{ ucfirst($c->price_tier) }}</td>
                    <td class="num">{{ format_money($c->opening_balance) }}</td>
                    <td><span class="badge {{ $c->is_active ? 'on' : 'off' }}">{{ $c->is_active ? 'Active' : 'Inactive' }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
