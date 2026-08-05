<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Categories</title>
    @include('admin.exports.pdf._style')
</head>
<body>
    @include('admin.exports.pdf._watermark')
    <div class="doc-header">
        <h1>{{ config('app.name') }} — Categories</h1>
        <p class="meta">Generated {{ $generatedAt->format('d M Y, h:i A') }} · {{ $categories->count() }} categories</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Parent</th>
                <th class="num">Sort</th>
                <th class="num">Markup %</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $i => $c)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $c->name }}</td>
                    <td class="muted">{{ $c->slug }}</td>
                    <td>{{ $c->parent?->name ?? '—' }}</td>
                    <td class="num">{{ $c->sort_order }}</td>
                    <td class="num">{{ $c->markup_percent !== null ? number_format((float) $c->markup_percent, 2) : '—' }}</td>
                    <td><span class="badge {{ $c->is_active ? 'on' : 'off' }}">{{ $c->is_active ? 'Active' : 'Inactive' }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
